<?php
require_once __DIR__ . '/../app/config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect('/biblioteca/public/catalogo.php');
}

$obra = buscar_obra_por_id($id);
if (!$obra) {
    flash('erro', 'Livro não encontrado');
    redirect('/biblioteca/public/catalogo.php');
}

// Verificar se aluno já tem reserva ativa para esta obra
$reserva_ativa = null;
$na_fila = false;
$posicao_fila = null;
if (is_aluno()) {
    $db = db();
    $stmt = $db->prepare("
        SELECT * FROM reservas 
        WHERE usuario_id = ? AND obra_id = ? AND status IN ('pendente', 'confirmada', 'na_fila')
        ORDER BY 
            CASE status WHEN 'confirmada' THEN 1 WHEN 'pendente' THEN 2 WHEN 'na_fila' THEN 3 END
        LIMIT 1
    ");
    $stmt->execute([usuario_id(), $id]);
    $reserva_ativa = $stmt->fetch();
    
    if ($reserva_ativa && $reserva_ativa['status'] === 'na_fila') {
        $na_fila = true;
        $stmt = $db->prepare("
            SELECT COUNT(*) + 1 as posicao FROM reservas 
            WHERE obra_id = ? AND status = 'na_fila' AND data_reserva < ?
        ");
        $stmt->execute([$id, $reserva_ativa['data_reserva']]);
        $posicao_fila = (int)$stmt->fetchColumn();
    }
}

// Verificar empréstimo ativo deste aluno para esta obra
$emprestimo_ativo = null;
if (is_aluno()) {
    $db = db();
    $stmt = $db->prepare("
        SELECT e.*, ex.tombo 
        FROM emprestimos e
        JOIN exemplares ex ON ex.id = e.exemplar_id
        WHERE e.usuario_id = ? AND ex.obra_id = ? AND e.data_devolucao IS NULL
    ");
    $stmt->execute([usuario_id(), $id]);
    $emprestimo_ativo = $stmt->fetch();
}

view('livro', [
    'titulo' => $obra['titulo'],
    'obra' => $obra,
    'reserva_ativa' => $reserva_ativa,
    'na_fila' => $na_fila,
    'posicao_fila' => $posicao_fila,
    'emprestimo_ativo' => $emprestimo_ativo,
]);