<?php
require_once __DIR__ . '/../app/config/config.php';

// Se já logado, redirecionar
if (usuario_logado()) {
    redirect(is_bibliotecario() ? url('painel.php') : url('index.php'));
}

$erro = '';
$identificador = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        $erro = 'Token inválido. Tente novamente.';
    } else {
        $identificador = trim($_POST['identificador'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (!$identificador || !$senha) {
            $erro = 'Preencha todos os campos';
        } else {
            $resultado = login($identificador, $senha);
            if ($resultado['sucesso']) {
                redirect(is_bibliotecario() ? url('painel.php') : url('index.php'));
            } else {
                $erro = $resultado['erro'];
            }
        }
    }
}

view('login', [
    'titulo' => 'Login',
    'erro' => $erro,
    'identificador' => $identificador,
]);