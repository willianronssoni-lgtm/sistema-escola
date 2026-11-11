<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

$totais = [
    'obras' => (int)$db->query('SELECT COUNT(*) FROM obras')->fetchColumn(),
    'exemplares' => (int)$db->query('SELECT COUNT(*) FROM exemplares')->fetchColumn(),
    'disponiveis' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'disponivel'")->fetchColumn(),
    'emprestados' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'emprestado'")->fetchColumn(),
    'reservados' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'reservado'")->fetchColumn(),
    'manutencao' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'manutencao'")->fetchColumn(),
    'alunos' => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno'")->fetchColumn(),
    'emprestimos_ativos' => (int)$db->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn(),
    'atrasados' => (int)$db->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL AND data_prevista_devolucao < CURDATE()')->fetchColumn(),
    'fila_espera' => (int)$db->query("SELECT COUNT(*) FROM reservas WHERE status = 'na_fila'")->fetchColumn(),
    'suspensos' => (int)$db->query('SELECT COUNT(*) FROM usuarios WHERE suspenso_ate IS NOT NULL AND suspenso_ate >= CURDATE()')->fetchColumn(),
];

$maisEmprestados = $db->query("
    SELECT o.titulo, o.autor, COUNT(emp.id) AS total
    FROM emprestimos emp
    JOIN exemplares x ON x.id = emp.exemplar_id
    JOIN obras o ON o.id = x.obra_id
    GROUP BY o.id
    ORDER BY total DESC, o.titulo
    LIMIT 10
")->fetchAll();

$porCategoria = $db->query("
    SELECT COALESCE(NULLIF(categoria, ''), 'Sem categoria') AS categoria,
           COUNT(*) AS obras,
           SUM((SELECT COUNT(*) FROM exemplares e WHERE e.obra_id = o.id)) AS exemplares
    FROM obras o
    GROUP BY COALESCE(NULLIF(categoria, ''), 'Sem categoria')
    ORDER BY exemplares DESC
")->fetchAll();

$porMes = $db->query("
    SELECT DATE_FORMAT(data_retirada, '%Y-%m') AS mes, COUNT(*) AS retiradas
    FROM emprestimos
    WHERE data_retirada >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes
")->fetchAll();

$devolucoes = $db->query("
    SELECT DATE_FORMAT(data_devolucao, '%Y-%m') AS mes, COUNT(*) AS devolucoes, SUM(dias_atraso) AS dias_atraso
    FROM emprestimos
    WHERE data_devolucao IS NOT NULL AND data_devolucao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes
")->fetchAll();

$atrasados = $db->query("
    SELECT emp.id, DATEDIFF(CURDATE(), emp.data_prevista_devolucao) AS dias_atraso,
           u.nome AS aluno, u.matricula, o.titulo AS obra_titulo, x.tombo,
           emp.data_prevista_devolucao
    FROM emprestimos emp
    JOIN usuarios u ON u.id = emp.usuario_id
    JOIN exemplares x ON x.id = emp.exemplar_id
    JOIN obras o ON o.id = x.obra_id
    WHERE emp.data_devolucao IS NULL AND emp.data_prevista_devolucao < CURDATE()
    ORDER BY dias_atraso DESC
")->fetchAll();

view('relatorios', [
    'titulo' => 'Relatórios',
    'totais' => $totais,
    'maisEmprestados' => $maisEmprestados,
    'porCategoria' => $porCategoria,
    'porMes' => $porMes,
    'devolucoes' => $devolucoes,
    'atrasados' => $atrasados,
]);
