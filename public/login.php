<?php
require_once __DIR__ . '/../app/config/config.php';

// Se já logado, redirecionar
if (usuario_logado()) {
    redirect(is_bibliotecario() ? '/biblioteca/public/painel.php' : '/biblioteca/public/index.php');
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        $erro = 'Token inválido. Tente novamente.';
    } else {
        $matricula = trim($_POST['matricula'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (!$matricula || !$senha) {
            $erro = 'Preencha todos os campos';
        } else {
            $resultado = login($matricula, $senha);
            if ($resultado['sucesso']) {
                redirect(is_bibliotecario() ? '/biblioteca/public/painel.php' : '/biblioteca/public/index.php');
            } else {
                $erro = $resultado['erro'];
            }
        }
    }
}

view('login', [
    'titulo' => 'Login',
    'erro' => $erro,
]);