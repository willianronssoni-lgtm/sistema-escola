<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_aluno();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('catalogo.php'));
}

if (!csfr_verify($_POST['csrf_token'] ?? '')) {
    flash('erro', 'Token inválido');
    redirect(url('livro.php') . '?id=' . (int)($_POST['obra_id'] ?? 0));
}

$obra_id = (int)($_POST['obra_id'] ?? 0);
if (!$obra_id) {
    flash('erro', 'Livro inválido');
    redirect(url('catalogo.php'));
}

try {
    $resultado = servico_reservar_obra($obra_id, (int)usuario_id());

    flash('sucesso', 'Reserva confirmada! Retire o livro na biblioteca até ' . format_datetime($resultado['expiracao']));
} catch (Throwable $e) {
    error_log('Reserva: ' . $e->getMessage());
    flash('erro', $e instanceof PDOException
        ? 'Não foi possível concluir a reserva. Tente novamente.'
        : $e->getMessage());
}

redirect(url('livro.php') . '?id=' . $obra_id);
