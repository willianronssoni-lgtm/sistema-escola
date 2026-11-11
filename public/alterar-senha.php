<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('alterar-senha.php'));
    }

    $atual = $_POST['senha_atual'] ?? '';
    $nova = $_POST['senha_nova'] ?? '';
    $confirmacao = $_POST['senha_confirmacao'] ?? '';

    if ($nova !== $confirmacao) {
        flash('erro', 'A confirmação não coincide com a nova senha.');
    } elseif (strlen($nova) < 6) {
        flash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
    } else {
        $resultado = alterar_senha((int)usuario_id(), $atual, $nova);
        if ($resultado['sucesso']) {
            flash('sucesso', 'Senha alterada com sucesso.');
            redirect(url('perfil.php'));
        }
        flash('erro', $resultado['erro']);
    }
}

view('alterar-senha', ['titulo' => 'Alterar senha']);
