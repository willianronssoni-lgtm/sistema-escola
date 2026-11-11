<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('devolucoes.php'));
    }

    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'devolver') {
            $emprestimoId = (int)($_POST['emprestimo_id'] ?? 0);

            $db->beginTransaction();

            $stmt = $db->prepare("
                SELECT emp.*, e.obra_id, e.id AS exemplar_id
                FROM emprestimos emp
                JOIN exemplares e ON e.id = emp.exemplar_id
                WHERE emp.id = ? AND emp.data_devolucao IS NULL
                FOR UPDATE
            ");
            $stmt->execute([$emprestimoId]);
            $emprestimo = $stmt->fetch();
            if (!$emprestimo) {
                throw new RuntimeException('Empréstimo ativo não encontrado.');
            }

            $hoje = date('Y-m-d');
            $atraso = max(0, (int)((strtotime($hoje) - strtotime($emprestimo['data_prevista_devolucao'])) / 86400));

            $stmt = $db->prepare("UPDATE emprestimos SET data_devolucao = ?, dias_atraso = ? WHERE id = ?");
            $stmt->execute([$hoje, $atraso, $emprestimoId]);

            $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
            $stmt->execute([(int)$emprestimo['exemplar_id']]);

            // Se houver fila de espera, o exemplar fica reservado para o próximo.
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

            // Atraso gera suspensão automática proporcional.
            if ($atraso > 0) {
                $suspensaoAte = date('Y-m-d', strtotime('+' . $atraso . ' days'));
                $stmt = $db->prepare("UPDATE usuarios SET suspenso_ate = ? WHERE id = ?");
                $stmt->execute([$suspensaoAte, (int)$emprestimo['usuario_id']]);

                $stmt = $db->prepare("UPDATE emprestimos SET suspensao_aplicada = 1 WHERE id = ?");
                $stmt->execute([$emprestimoId]);

                criar_notificacao((int)$emprestimo['usuario_id'], 'suspensao', 'Suspensão aplicada',
                    'O seu atraso de ' . $atraso . ' dia(s) resultou em suspensão até ' . format_date($suspensaoAte) . '.',
                    url('meus-emprestimos.php'));
            }

            $db->commit();

            registrar_log('emprestimo_devolvido', 'emprestimos', $emprestimoId, ['dias_atraso' => $atraso]);

            flash('sucesso', $atraso > 0
                ? "Devolução registrada com {$atraso} dia(s) de atraso. Aluno suspenso até " . format_date($suspensaoAte) . '.'
                : 'Devolução registrada com sucesso.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Devoluções: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique os dados informados.'
            : $e->getMessage());
    }

    redirect(url('devolucoes.php'));
}

$stmt = $db->query("
    SELECT emp.*, u.nome AS aluno, u.matricula, u.turma, o.titulo AS obra_titulo, x.tombo,
           DATEDIFF(CURDATE(), emp.data_prevista_devolucao) AS dias_atraso
    FROM emprestimos emp
    JOIN usuarios u ON u.id = emp.usuario_id
    JOIN exemplares x ON x.id = emp.exemplar_id
    JOIN obras o ON o.id = x.obra_id
    WHERE emp.data_devolucao IS NULL
    ORDER BY emp.data_prevista_devolucao
");
$ativos = $stmt->fetchAll();

$stmt = $db->query("
    SELECT emp.id, emp.data_devolucao, emp.dias_atraso, u.nome AS aluno, o.titulo AS obra_titulo, x.tombo
    FROM emprestimos emp
    JOIN usuarios u ON u.id = emp.usuario_id
    JOIN exemplares x ON x.id = emp.exemplar_id
    JOIN obras o ON o.id = x.obra_id
    WHERE emp.data_devolucao IS NOT NULL
    ORDER BY emp.data_devolucao DESC
    LIMIT 30
");
$historico = $stmt->fetchAll();

view('devolucoes', ['titulo' => 'Devoluções', 'ativos' => $ativos, 'historico' => $historico]);
