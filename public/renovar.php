<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_aluno();

$db = db();

$emprestimoId = (int)($_GET['id'] ?? $_POST['emprestimo_id'] ?? 0);

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

$motivo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('renovar.php') . '?id=' . $emprestimoId);
    }

    // Não é permitido renovar com atraso.
    $hoje = date('Y-m-d');
    if ($emprestimo['data_prevista_devolucao'] < $hoje) {
        $motivo = 'Não é possível renovar um empréstimo em atraso.';
    }

    // Limite de renovações.
    if ($motivo === '' && (int)$emprestimo['renovado'] >= MAX_RENOVACOES) {
        $motivo = 'Limite de ' . MAX_RENOVACOES . ' renovação(ões) por empréstimo atingido.';
    }

    // Quem está na fila tem prioridade: se houver reserva confirmada de outro
    // aluno para a mesma obra, a renovação é bloqueada.
    if ($motivo === '') {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM reservas
            WHERE obra_id = ? AND usuario_id != ? AND status IN ('pendente','confirmada','na_fila')
        ");
        $stmt->execute([(int)$emprestimo['obra_id'], usuario_id()]);
        if ((int)$stmt->fetchColumn() > 0) {
            $motivo = 'Outro usuário está na fila deste livro. Faça a devolução para liberar o exemplar.';
        }
    }

    if ($motivo === '') {
        $nova_data = date('Y-m-d', strtotime($emprestimo['data_prevista_devolucao'] . ' +' . PRAZO_RENOVACAO_DIAS . ' days'));
        if ($nova_data < $hoje) {
            $nova_data = date('Y-m-d', strtotime($hoje . ' +' . PRAZO_RENOVACAO_DIAS . ' days'));
        }

        $stmt = $db->prepare("UPDATE emprestimos SET data_prevista_devolucao = ?, renovado = 1 WHERE id = ?");
        $stmt->execute([$nova_data, $emprestimoId]);

        registrar_log('emprestimo_renovado', 'emprestimos', $emprestimoId, [
            'nova_data_prevista' => $nova_data,
        ]);

        criar_notificacao(usuario_id(), 'renovacao', 'Empréstimo renovado',
            'Sua reserva de "' . $emprestimo['titulo'] . '" foi renovada. Nova devolução prevista para ' . format_date($nova_data) . '.',
            url('meus-emprestimos.php'));

        flash('sucesso', 'Empréstimo renovado. Nova devolução prevista para ' . format_date($nova_data) . '.');
        redirect(url('meus-emprestimos.php'));
    }

    flash('erro', $motivo);
}

view('renovar', ['titulo' => 'Renovar empréstimo', 'emprestimo' => $emprestimo]);
