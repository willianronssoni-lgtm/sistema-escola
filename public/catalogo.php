<?php
require_once __DIR__ . '/../app/config/config.php';

$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 12;
$offset = ($pagina - 1) * $por_pagina;

$filtros = [
    'busca' => trim($_GET['q'] ?? ''),
    'categoria' => $_GET['categoria'] ?? '',
    'so_disponiveis' => isset($_GET['disponiveis']),
];

$obras = buscar_obras($filtros, $por_pagina, $offset);
$total = contar_obras($filtros);
$categorias = buscar_categorias();

view('catalogo', [
    'titulo' => 'Catálogo',
    'obras' => $obras,
    'total' => $total,
    'pagina' => $pagina,
    'por_pagina' => $por_pagina,
    'filtros' => $filtros,
    'categorias' => $categorias,
]);