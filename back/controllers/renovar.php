<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_aluno();

$emprestimoId = (int)($_GET['id'] ?? $_POST['emprestimo_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('renovar.php') . '?id=' . $emprestimoId);
    }

    $resultado = servico_renovar_emprestimo($emprestimoId, (int)usuario_id());

    if ($resultado['sucesso']) {
        flash('sucesso', 'Empréstimo renovado. Nova devolução prevista para ' . format_date($resultado['data_prevista']) . '.');
        redirect(url('meus-emprestimos.php'));
    }

    flash('erro', $resultado['erro']);
}

// Carrega os dados para exibição.
$db = db();
$stmt = $db->prepare("
    SELECT emp.*, ex.tombo, ex.obra_id, o.titulo
    FROM emprestimos emp
    JOIN exemplares ex ON ex.id = emp.exemplar_id
    JOIN obras o ON o.id = ex.obra_id
    WHERE emp.id = ? AND emp.usuario_id = ?
");
$stmt->execute([$emprestimoId, usuario_id()]);
$emprestimo = $stmt->fetch();

if (!$emprestimo) {
    flash('erro', 'Empréstimo não encontrado.');
    redirect(url('meus-emprestimos.php'));
}

if ($emprestimo['data_devolucao'] !== null) {
    flash('erro', 'Este empréstimo já foi devolvido.');
    redirect(url('meus-emprestimos.php'));
}

view('renovar', ['titulo' => 'Renovar empréstimo', 'emprestimo' => $emprestimo]);
