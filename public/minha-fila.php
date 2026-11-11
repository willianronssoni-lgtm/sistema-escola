<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_aluno();

$db = db();

$stmt = $db->prepare("
    SELECT r.*, o.titulo, o.autor, o.capa,
           (SELECT COUNT(*) FROM reservas r2
            WHERE r2.obra_id = r.obra_id AND r2.status = 'na_fila' AND r2.data_reserva < r.data_reserva) AS pessoas_a_frente
    FROM reservas r
    JOIN obras o ON o.id = r.obra_id
    WHERE r.usuario_id = ? AND r.status = 'na_fila'
    ORDER BY r.data_reserva
");
$stmt->execute([usuario_id()]);
$fila = $stmt->fetchAll();

view('minha-fila', ['titulo' => 'Minha fila de espera', 'fila' => $fila]);
