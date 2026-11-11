<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_aluno();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('minhas-reservas.php'));
}

if (!csfr_verify($_POST['csrf_token'] ?? '')) {
    flash('erro', 'Token inválido');
    redirect(url('minhas-reservas.php'));
}

$reserva_id = (int)($_POST['reserva_id'] ?? 0);
if (!$reserva_id) {
    flash('erro', 'Reserva inválida');
    redirect(url('minhas-reservas.php'));
}

try {
    $resultado = servico_cancelar_reserva($reserva_id, (int)usuario_id());

    flash('sucesso', 'Reserva cancelada com sucesso');
} catch (Throwable $e) {
    error_log('Cancelamento de reserva: ' . $e->getMessage());
    flash('erro', $e instanceof PDOException
        ? 'Não foi possível cancelar a reserva. Tente novamente.'
        : $e->getMessage());
}

redirect(url('minhas-reservas.php'));
