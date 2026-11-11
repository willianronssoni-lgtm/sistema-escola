<?php
/**
 * Serviços de empréstimo e devolução.
 *
 * Concentram as regras de negócio para que as páginas web e a API REST
 * compartilhem exatamente a mesma lógica.
 */

declare(strict_types=1);

/**
 * Registra a retirada de um exemplar por um aluno.
 *
 * @return array{sucesso: bool, erro?: string, id?: int, data_prevista?: string}
 */
function servico_registrar_emprestimo(int $aluno_id, int $exemplar_id, int $bibliotecario_id): array {
    $db = db();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND tipo = 'aluno' AND ativo = 1 FOR UPDATE");
        $stmt->execute([$aluno_id]);
        $aluno = $stmt->fetch();
        if (!$aluno) {
            throw new RuntimeException('Aluno não encontrado ou inativo.');
        }

        $permissao = usuario_pode_emprestar($aluno_id);
        if (!$permissao['pode']) {
            throw new RuntimeException($permissao['motivo']);
        }

        $stmt = $db->prepare("SELECT id, tombo FROM exemplares WHERE id = ? AND status = 'disponivel' FOR UPDATE");
        $stmt->execute([$exemplar_id]);
        $exemplar = $stmt->fetch();
        if (!$exemplar) {
            throw new RuntimeException('Exemplar indisponível. Atualize a página.');
        }

        $hoje = date('Y-m-d');
        $prevista = date('Y-m-d', strtotime('+' . PRAZO_EMPRESTIMO_DIAS . ' days'));

        $stmt = $db->prepare("
            INSERT INTO emprestimos (exemplar_id, usuario_id, bibliotecario_id, data_retirada, data_prevista_devolucao)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$exemplar_id, $aluno_id, $bibliotecario_id, $hoje, $prevista]);
        $emprestimo_id = (int)$db->lastInsertId();

        $stmt = $db->prepare("UPDATE exemplares SET status = 'emprestado' WHERE id = ?");
        $stmt->execute([$exemplar_id]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    registrar_log('emprestimo_criado', 'emprestimos', $emprestimo_id, [
        'usuario_id' => $aluno_id,
        'exemplar_id' => $exemplar_id,
    ]);

    criar_notificacao($aluno_id, 'devolucao_proxima', 'Empréstimo registrado',
        'A devolução está prevista para ' . format_date($prevista) . '.',
        url('meus-emprestimos.php'));

    return ['sucesso' => true, 'id' => $emprestimo_id, 'data_prevista' => $prevista];
}

/**
 * Registra a devolução de um empréstimo ativo.
 *
 * @return array{sucesso: bool, erro?: string, dias_atraso?: int, suspensao_ate?: ?string}
 */
function servico_registrar_devolucao(int $emprestimo_id): array {
    $db = db();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            SELECT emp.*, e.obra_id, e.id AS exemplar_id
            FROM emprestimos emp
            JOIN exemplares e ON e.id = emp.exemplar_id
            WHERE emp.id = ? AND emp.data_devolucao IS NULL
            FOR UPDATE
        ");
        $stmt->execute([$emprestimo_id]);
        $emprestimo = $stmt->fetch();
        if (!$emprestimo) {
            throw new RuntimeException('Empréstimo ativo não encontrado.');
        }

        $hoje = date('Y-m-d');
        $atraso = max(0, (int)((strtotime($hoje) - strtotime($emprestimo['data_prevista_devolucao'])) / 86400));

        $stmt = $db->prepare("UPDATE emprestimos SET data_devolucao = ?, dias_atraso = ? WHERE id = ?");
        $stmt->execute([$hoje, $atraso, $emprestimo_id]);

        $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
        $stmt->execute([(int)$emprestimo['exemplar_id']]);

        // Havendo fila de espera, o exemplar fica reservado para o próximo.
        $fila = proximo_da_fila((int)$emprestimo['obra_id']);
        if ($fila) {
            $expira = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));

            $stmt = $db->prepare("
                UPDATE reservas
                SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?, posicao_fila = NULL
                WHERE id = ?
            ");
            $stmt->execute([(int)$emprestimo['exemplar_id'], $expira, $fila['id']]);

            $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
            $stmt->execute([(int)$emprestimo['exemplar_id']]);

            criar_notificacao((int)$fila['usuario_id'], 'livro_disponivel', 'Livro disponível para retirada',
                'Sua vez chegou. Retire o exemplar até ' . format_datetime($expira) . '.',
                url('minhas-reservas.php'));
        }

        // O atraso gera suspensão automática proporcional.
        $suspensao_ate = null;
        if ($atraso > 0) {
            $suspensao_ate = date('Y-m-d', strtotime('+' . $atraso . ' days'));

            $stmt = $db->prepare("UPDATE usuarios SET suspenso_ate = ? WHERE id = ?");
            $stmt->execute([$suspensao_ate, (int)$emprestimo['usuario_id']]);

            $stmt = $db->prepare("UPDATE emprestimos SET suspensao_aplicada = 1 WHERE id = ?");
            $stmt->execute([$emprestimo_id]);

            criar_notificacao((int)$emprestimo['usuario_id'], 'suspensao', 'Suspensão aplicada',
                'O seu atraso de ' . $atraso . ' dia(s) resultou em suspensão até ' . format_date($suspensao_ate) . '.',
                url('meus-emprestimos.php'));
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    registrar_log('emprestimo_devolvido', 'emprestimos', $emprestimo_id, ['dias_atraso' => $atraso]);

    return ['sucesso' => true, 'dias_atraso' => $atraso, 'suspensao_ate' => $suspensao_ate];
}

/**
 * Renova um empréstimo ativo, respeitando prazo, atraso e fila de espera.
 *
 * @return array{sucesso: bool, erro?: string, data_prevista?: string}
 */
function servico_renovar_emprestimo(int $emprestimo_id, int $usuario_id, bool $bibliotecario = false): array {
    $db = db();

    $stmt = $db->prepare("
        SELECT emp.*, ex.tombo, ex.obra_id, o.titulo
        FROM emprestimos emp
        JOIN exemplares ex ON ex.id = emp.exemplar_id
        JOIN obras o ON o.id = ex.obra_id
        WHERE emp.id = ?
    ");
    $stmt->execute([$emprestimo_id]);
    $emprestimo = $stmt->fetch();

    if (!$emprestimo) {
        return ['sucesso' => false, 'erro' => 'Empréstimo não encontrado.'];
    }

    if (!$bibliotecario && (int)$emprestimo['usuario_id'] !== $usuario_id) {
        return ['sucesso' => false, 'erro' => 'Empréstimo não encontrado.'];
    }

    if ($emprestimo['data_devolucao'] !== null) {
        return ['sucesso' => false, 'erro' => 'Este empréstimo já foi devolvido.'];
    }

    $hoje = date('Y-m-d');

    if ($emprestimo['data_prevista_devolucao'] < $hoje) {
        return ['sucesso' => false, 'erro' => 'Não é possível renovar um empréstimo em atraso.'];
    }

    if ((int)$emprestimo['renovado'] >= MAX_RENOVACOES) {
        return ['sucesso' => false, 'erro' => 'Limite de ' . MAX_RENOVACOES . ' renovação(ões) por empréstimo atingido.'];
    }

    // Quem está na fila tem prioridade: se outro aluno espera pela mesma
    // obra, a renovação é bloqueada.
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM reservas
        WHERE obra_id = ? AND usuario_id != ? AND status IN ('pendente','confirmada','na_fila')
    ");
    $stmt->execute([(int)$emprestimo['obra_id'], (int)$emprestimo['usuario_id']]);
    if ((int)$stmt->fetchColumn() > 0) {
        return ['sucesso' => false, 'erro' => 'Outro usuário está na fila deste livro. Faça a devolução para liberar o exemplar.'];
    }

    $nova_data = date('Y-m-d', strtotime($emprestimo['data_prevista_devolucao'] . ' +' . PRAZO_RENOVACAO_DIAS . ' days'));
    if ($nova_data < $hoje) {
        $nova_data = date('Y-m-d', strtotime($hoje . ' +' . PRAZO_RENOVACAO_DIAS . ' days'));
    }

    $stmt = $db->prepare("UPDATE emprestimos SET data_prevista_devolucao = ?, renovado = 1 WHERE id = ?");
    $stmt->execute([$nova_data, $emprestimo_id]);

    registrar_log('emprestimo_renovado', 'emprestimos', $emprestimo_id, ['nova_data_prevista' => $nova_data]);

    criar_notificacao((int)$emprestimo['usuario_id'], 'renovacao', 'Empréstimo renovado',
        'Sua reserva de "' . $emprestimo['titulo'] . '" foi renovada. Nova devolução prevista para ' . format_date($nova_data) . '.',
        url('meus-emprestimos.php'));

    return ['sucesso' => true, 'data_prevista' => $nova_data];
}
