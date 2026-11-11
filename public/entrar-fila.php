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
    $resultado = servico_entrar_na_fila($obra_id, (int)usuario_id());

    flash('sucesso', 'Você entrou na fila de espera! Sua posição: #' . $resultado['posicao']);
} catch (Throwable $e) {
    error_log('Fila de espera: ' . $e->getMessage());
    flash('erro', $e instanceof PDOException
        ? 'Não foi possível entrar na fila. Tente novamente.'
        : $e->getMessage());
}

redirect(url('livro.php') . '?id=' . $obra_id);
