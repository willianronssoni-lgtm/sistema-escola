<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_login();

$db = db();
$usuario = usuario_logado();

$stmt = $db->prepare("SELECT matricula, nome, email, turma, tipo, ativo, suspenso_ate, criado_em FROM usuarios WHERE id = ?");
$stmt->execute([usuario_id()]);
$dados = $stmt->fetch() ?: [];

$stmt = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ? AND data_devolucao IS NULL");
$stmt->execute([usuario_id()]);
$emprestimos_ativos = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND status IN ('pendente','confirmada','na_fila')");
$stmt->execute([usuario_id()]);
$reservas_ativas = (int)$stmt->fetchColumn();

view('perfil', [
    'titulo' => 'Meu perfil',
    'dados' => $dados,
    'usuario' => $usuario,
    'emprestimos_ativos' => $emprestimos_ativos,
    'reservas_ativas' => $reservas_ativas,
]);
