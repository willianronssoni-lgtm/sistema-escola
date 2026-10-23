<?php
require_once __DIR__ . '/../app/config/config.php';
require_aluno();

$db = db();
$stmt = $db->prepare("
    SELECT e.*, ex.tombo, ex.obra_id, o.titulo, o.autor, o.capa,
           DATEDIFF(CURDATE(), e.data_prevista_devolucao) as dias_atraso_calc
    FROM emprestimos e
    JOIN exemplares ex ON ex.id = e.exemplar_id
    JOIN obras o ON o.id = ex.obra_id
    WHERE e.usuario_id = ?
    ORDER BY e.data_retirada DESC
");
$stmt->execute([usuario_id()]);
$emprestimos = $stmt->fetchAll();

// Separar ativos e histórico
$ativos = [];
$historico = [];
foreach ($emprestimos as $emp) {
    if ($emp['data_devolucao'] === null) {
        // Recalcular dias de atraso
        $atraso = max(0, (int)$emp['dias_atraso_calc']);
        if ($atraso != $emp['dias_atraso']) {
            $db->prepare("UPDATE emprestimos SET dias_atraso = ? WHERE id = ?")->execute([$atraso, $emp['id']]);
        }
        $emp['dias_atraso'] = $atraso;
        $ativos[] = $emp;
    } else {
        $historico[] = $emp;
    }
}

// Verificar suspensão
$stmt = $db->prepare("SELECT suspenso_ate FROM usuarios WHERE id = ?");
$stmt->execute([usuario_id()]);
$suspenso_ate = $stmt->fetchColumn();

view('meus-emprestimos', [
    'titulo' => 'Meus Empréstimos',
    'ativos' => $ativos,
    'historico' => $historico,
    'suspenso_ate' => $suspenso_ate,
]);