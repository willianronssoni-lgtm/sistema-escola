<?php
/**
 * Serviços de reserva e fila de espera.
 *
 * As mesmas regras são aplicadas pelas páginas do aluno, pelo painel do
 * bibliotecário e pela API REST.
 */

declare(strict_types=1);

/**
 * Reserva um exemplar disponível para o aluno.
 *
 * @return array{sucesso: bool, erro?: string, id?: int, expiracao?: string}
 */
function servico_reservar_obra(int $obra_id, int $usuario_id): array {
    $db = db();

    try {
        $db->beginTransaction();

        $obra = buscar_obra_por_id($obra_id);
        if (!$obra) {
            throw new RuntimeException('Livro não encontrado.');
        }

        if ((int)$obra['disponiveis'] <= 0) {
            throw new RuntimeException('Nenhum exemplar disponível para reserva direta.');
        }

        $stmt = $db->prepare("
            SELECT id FROM reservas
            WHERE usuario_id = ? AND obra_id = ? AND status IN ('pendente','confirmada','na_fila')
        ");
        $stmt->execute([$usuario_id, $obra_id]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Você já tem uma reserva ativa para este livro.');
        }

        $stmt = $db->prepare("
            SELECT e.id FROM emprestimos e
            JOIN exemplares ex ON ex.id = e.exemplar_id
            WHERE e.usuario_id = ? AND ex.obra_id = ? AND e.data_devolucao IS NULL
        ");
        $stmt->execute([$usuario_id, $obra_id]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Você já tem este livro emprestado.');
        }

        $pode = usuario_pode_emprestar($usuario_id);
        if (!$pode['pode']) {
            throw new RuntimeException($pode['motivo']);
        }

        $exemplar = buscar_exemplar_disponivel($obra_id);
        if (!$exemplar) {
            throw new RuntimeException('Nenhum exemplar disponível no momento.');
        }

        $data_expiracao = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));

        $stmt = $db->prepare("
            INSERT INTO reservas (usuario_id, obra_id, exemplar_id, status, data_confirmacao, data_expiracao)
            VALUES (?, ?, ?, 'confirmada', NOW(), ?)
        ");
        $stmt->execute([$usuario_id, $obra_id, (int)$exemplar['id'], $data_expiracao]);
        $reserva_id = (int)$db->lastInsertId();

        $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
        $stmt->execute([(int)$exemplar['id']]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    criar_notificacao($usuario_id, 'reserva_confirmada',
        'Reserva confirmada: ' . $obra['titulo'],
        'Sua reserva foi confirmada. Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') na biblioteca até ' . format_datetime($data_expiracao) . '.',
        url('minhas-reservas.php'));

    registrar_log('reserva_criada', 'reservas', $reserva_id, [
        'obra_id' => $obra_id,
        'exemplar_id' => (int)$exemplar['id'],
    ]);

    return ['sucesso' => true, 'id' => $reserva_id, 'expiracao' => $data_expiracao];
}

/**
 * Coloca o aluno na fila de espera da obra.
 *
 * @return array{sucesso: bool, erro?: string, id?: int, posicao?: int}
 */
function servico_entrar_na_fila(int $obra_id, int $usuario_id): array {
    $db = db();

    try {
        $db->beginTransaction();

        $obra = buscar_obra_por_id($obra_id);
        if (!$obra) {
            throw new RuntimeException('Livro não encontrado.');
        }

        $stmt = $db->prepare("
            SELECT id FROM reservas
            WHERE usuario_id = ? AND obra_id = ? AND status IN ('pendente','confirmada','na_fila')
        ");
        $stmt->execute([$usuario_id, $obra_id]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Você já tem uma reserva ativa para este livro.');
        }

        $stmt = $db->prepare("
            SELECT e.id FROM emprestimos e
            JOIN exemplares ex ON ex.id = e.exemplar_id
            WHERE e.usuario_id = ? AND ex.obra_id = ? AND e.data_devolucao IS NULL
        ");
        $stmt->execute([$usuario_id, $obra_id]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Você já tem este livro emprestado.');
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE obra_id = ? AND status = 'na_fila'");
        $stmt->execute([$obra_id]);
        $posicao = (int)$stmt->fetchColumn() + 1;

        $stmt = $db->prepare("
            INSERT INTO reservas (usuario_id, obra_id, status, posicao_fila)
            VALUES (?, ?, 'na_fila', ?)
        ");
        $stmt->execute([$usuario_id, $obra_id, $posicao]);
        $reserva_id = (int)$db->lastInsertId();

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    criar_notificacao($usuario_id, 'livro_disponivel',
        'Você entrou na fila: ' . $obra['titulo'],
        'Você está na posição #' . $posicao . ' da fila de espera para "' . $obra['titulo'] . '". Será notificado quando um exemplar ficar disponível.',
        url('minhas-reservas.php'));

    registrar_log('fila_entrada', 'reservas', $reserva_id, ['obra_id' => $obra_id, 'posicao' => $posicao]);

    return ['sucesso' => true, 'id' => $reserva_id, 'posicao' => $posicao];
}

/**
 * Cancela uma reserva. Quando o exemplar é liberado, o próximo da fila é
 * promovido automaticamente.
 *
 * @return array{sucesso: bool, erro?: string, promovido?: ?int}
 */
function servico_cancelar_reserva(int $reserva_id, int $usuario_id, bool $bibliotecario = false): array {
    $db = db();

    try {
        $db->beginTransaction();

        if ($bibliotecario) {
            $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
            $stmt->execute([$reserva_id]);
        } else {
            $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$reserva_id, $usuario_id]);
        }
        $reserva = $stmt->fetch();

        if (!$reserva) {
            throw new RuntimeException('Reserva não encontrada.');
        }

        if (!in_array($reserva['status'], ['pendente', 'confirmada', 'na_fila'], true)) {
            throw new RuntimeException('Esta reserva não pode ser cancelada.');
        }

        $promovido = null;

        // Libera o exemplar e promove o próximo da fila.
        if ($reserva['status'] === 'confirmada' && $reserva['exemplar_id']) {
            $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
            $stmt->execute([(int)$reserva['exemplar_id']]);

            $proximo = proximo_da_fila((int)$reserva['obra_id']);
            if ($proximo) {
                $exemplar = buscar_exemplar_disponivel((int)$reserva['obra_id']);
                if ($exemplar) {
                    $data_expiracao = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));

                    $stmt = $db->prepare("
                        UPDATE reservas
                        SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([(int)$exemplar['id'], $data_expiracao, (int)$proximo['id']]);

                    $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
                    $stmt->execute([(int)$exemplar['id']]);

                    criar_notificacao((int)$proximo['usuario_id'], 'reserva_confirmada',
                        'Reserva confirmada (fila): ' . $proximo['obra_titulo'],
                        'Sua reserva na fila foi confirmada! Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') até ' . format_datetime($data_expiracao) . '.',
                        url('minhas-reservas.php'));

                    $promovido = (int)$proximo['id'];
                }
            }
        }

        $stmt = $db->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([$reserva_id]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    registrar_log('reserva_cancelada', 'reservas', $reserva_id, ['promovido' => $promovido]);

    return ['sucesso' => true, 'promovido' => $promovido];
}

/**
 * Confirma manualmente uma reserva que estava na fila de espera.
 *
 * @return array{sucesso: bool, erro?: string, id?: int, expiracao?: string}
 */
function servico_confirmar_reserva(int $reserva_id): array {
    $db = db();

    $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->execute([$reserva_id]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        return ['sucesso' => false, 'erro' => 'Reserva não encontrada.'];
    }

    if ($reserva['status'] !== 'na_fila') {
        return ['sucesso' => false, 'erro' => 'Somente reservas na fila podem ser confirmadas.'];
    }

    $exemplar = buscar_exemplar_disponivel((int)$reserva['obra_id']);
    if (!$exemplar) {
        return ['sucesso' => false, 'erro' => 'Não há exemplar disponível desta obra no momento.'];
    }

    try {
        $db->beginTransaction();

        $expira = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));

        $stmt = $db->prepare("
            UPDATE reservas
            SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?, posicao_fila = NULL
            WHERE id = ?
        ");
        $stmt->execute([(int)$exemplar['id'], $expira, $reserva_id]);

        $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
        $stmt->execute([(int)$exemplar['id']]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    criar_notificacao((int)$reserva['usuario_id'], 'reserva_confirmada', 'Reserva confirmada',
        'Sua reserva foi confirmada. Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') até ' . format_datetime($expira) . '.',
        url('minhas-reservas.php'));

    registrar_log('reserva_confirmada_bibliotecario', 'reservas', $reserva_id);

    return ['sucesso' => true, 'id' => $reserva_id, 'expiracao' => $expira];
}
