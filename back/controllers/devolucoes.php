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
            $resultado = servico_registrar_devolucao((int)($_POST['emprestimo_id'] ?? 0));

            flash('sucesso', $resultado['dias_atraso'] > 0
                ? "Devolução registrada com {$resultado['dias_atraso']} dia(s) de atraso. Aluno suspenso até " . format_date($resultado['suspensao_ate']) . '.'
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
