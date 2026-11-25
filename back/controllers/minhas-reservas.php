<?php
require_once __DIR__ . '/../app/config/config.php';
require_aluno();

$db = db();
$stmt = $db->prepare("
    SELECT r.*, o.titulo, o.autor, o.capa, ex.tombo
    FROM reservas r
    JOIN obras o ON o.id = r.obra_id
    LEFT JOIN exemplares ex ON ex.id = r.exemplar_id
    WHERE r.usuario_id = ?
    ORDER BY 
        CASE r.status 
            WHEN 'confirmada' THEN 1 
            WHEN 'pendente' THEN 2 
            WHEN 'na_fila' THEN 3 
            WHEN 'atendida' THEN 4 
            WHEN 'expirada' THEN 5 
            WHEN 'cancelada' THEN 6 
        END,
        r.data_reserva DESC
");
$stmt->execute([usuario_id()]);
$reservas = $stmt->fetchAll();

view('minhas-reservas', [
    'titulo' => 'Minhas Reservas',
    'reservas' => $reservas,
]);