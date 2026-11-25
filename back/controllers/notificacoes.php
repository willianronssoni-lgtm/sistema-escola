<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('notificacoes.php'));
    }

    if (($_POST['acao'] ?? '') === 'marcar_todas') {
        $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([usuario_id()]);
        flash('sucesso', 'Todas as notificações foram marcadas como lidas.');
    } elseif (($_POST['acao'] ?? '') === 'marcar') {
        $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND id = ?");
        $stmt->execute([usuario_id(), (int)($_POST['id'] ?? 0)]);
    }

    redirect(url('notificacoes.php'));
}

$stmt = $db->prepare("SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY criada_em DESC, id DESC LIMIT 100");
$stmt->execute([usuario_id()]);
$notificacoes = $stmt->fetchAll();

view('notificacoes', ['titulo' => 'Notificações', 'notificacoes' => $notificacoes]);
