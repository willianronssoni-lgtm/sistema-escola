<?php
require_once __DIR__ . '/../app/config/config.php';

$estatisticas = [];
try {
    $db = db();
    $estatisticas['obras'] = $db->query("SELECT COUNT(*) FROM obras")->fetchColumn();
    $estatisticas['exemplares'] = $db->query("SELECT COUNT(*) FROM exemplares")->fetchColumn();
    $estatisticas['disponiveis'] = $db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'disponivel'")->fetchColumn();
    $estatisticas['emprestados'] = $db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'emprestado'")->fetchColumn();
    $estatisticas['alunos'] = $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetchColumn();
} catch (Exception) {
    $estatisticas = ['obras' => 0, 'exemplares' => 0, 'disponiveis' => 0, 'emprestados' => 0, 'alunos' => 0];
}

$destaques = [];
try {
    $db = db();
    $stmt = $db->query("
        SELECT o.*, COUNT(e.id) as total, SUM(e.status = 'disponivel') as disp
        FROM obras o
        LEFT JOIN exemplares e ON e.obra_id = o.id
        GROUP BY o.id
        HAVING disp > 0
        ORDER BY RAND()
        LIMIT 4
    ");
    $destaques = $stmt->fetchAll();
} catch (Exception) {
    $destaques = [];
}

view('index', [
    'titulo' => 'Início',
    'estatisticas' => $estatisticas,
    'destaques' => $destaques,
]);