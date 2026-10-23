<?php
/**
 * Sistema de autenticação
 */

declare(strict_types=1);

function login(string $matricula, string $senha): array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE matricula = ? AND ativo = 1");
    $stmt->execute([$matricula]);
    $usuario = $stmt->fetch();
    
    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        registrar_log('login_falhou', 'usuarios', null, ['matricula' => $matricula]);
        return ['sucesso' => false, 'erro' => 'Matrícula ou senha inválidos'];
    }
    
    // Verificar se aluno está suspenso
    if ($usuario['tipo'] === 'aluno' && $usuario['suspenso_ate'] && $usuario['suspenso_ate'] >= date('Y-m-d')) {
        return ['sucesso' => false, 'erro' => 'Sua conta está suspensa até ' . format_date($usuario['suspenso_ate']) . ' devido a atrasos na devolução'];
    }
    
    // Regenerar ID da sessão para segurança
    session_regenerate_id(true);
    
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'matricula' => $usuario['matricula'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'turma' => $usuario['turma'],
        'tipo' => $usuario['tipo'],
    ];
    
    registrar_log('login', 'usuarios', $usuario['id']);
    
    return ['sucesso' => true, 'usuario' => $_SESSION['usuario']];
}

function logout(): void {
    if (usuario_id()) {
        registrar_log('logout', 'usuarios', usuario_id());
    }
    session_destroy();
    session_start();
}

function alterar_senha(int $usuario_id, string $senha_atual, string $nova_senha): array {
    $db = db();
    $stmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $hash = $stmt->fetchColumn();
    
    if (!$hash || !password_verify($senha_atual, $hash)) {
        return ['sucesso' => false, 'erro' => 'Senha atual incorreta'];
    }
    
    if (strlen($nova_senha) < 6) {
        return ['sucesso' => false, 'erro' => 'A nova senha deve ter pelo menos 6 caracteres'];
    }
    
    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
    $stmt->execute([$novo_hash, $usuario_id]);
    
    registrar_log('senha_alterada', 'usuarios', $usuario_id);
    
    return ['sucesso' => true];
}

function redefinir_senha_bibliotecario(int $usuario_id, string $nova_senha): array {
    if (!is_bibliotecario()) {
        return ['sucesso' => false, 'erro' => 'Permissão negada'];
    }
    
    if (strlen($nova_senha) < 6) {
        return ['sucesso' => false, 'erro' => 'A senha deve ter pelo menos 6 caracteres'];
    }
    
    $db = db();
    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $usuario_id]);
    
    registrar_log('senha_redefinida_bibliotecario', 'usuarios', $usuario_id, ['alvo_id' => $usuario_id]);
    
    return ['sucesso' => true];
}

function criar_usuario(array $dados): array {
    if (!is_bibliotecario()) {
        return ['sucesso' => false, 'erro' => 'Permissão negada'];
    }
    
    $required = ['matricula', 'nome', 'senha', 'tipo'];
    foreach ($required as $campo) {
        if (empty($dados[$campo])) {
            return ['sucesso' => false, 'erro' => "Campo '$campo' é obrigatório"];
        }
    }
    
    if (!in_array($dados['tipo'], ['aluno', 'bibliotecario'])) {
        return ['sucesso' => false, 'erro' => 'Tipo inválido'];
    }
    
    if (strlen($dados['senha']) < 6) {
        return ['sucesso' => false, 'erro' => 'A senha deve ter pelo menos 6 caracteres'];
    }
    
    $db = db();
    
    // Verificar matrícula única
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE matricula = ?");
    $stmt->execute([$dados['matricula']]);
    if ($stmt->fetch()) {
        return ['sucesso' => false, 'erro' => 'Matrícula já cadastrada'];
    }
    
    // Verificar email único (se fornecido)
    if (!empty($dados['email'])) {
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            return ['sucesso' => false, 'erro' => 'Email já cadastrado'];
        }
    }
    
    $hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $dados['matricula'],
        $dados['nome'],
        $dados['email'] ?: null,
        $dados['turma'] ?: null,
        $hash,
        $dados['tipo']
    ]);
    
    $id = (int)$db->lastInsertId();
    registrar_log('usuario_criado', 'usuarios', $id, ['matricula' => $dados['matricula'], 'tipo' => $dados['tipo']]);
    
    return ['sucesso' => true, 'id' => $id];
}

function atualizar_usuario(int $id, array $dados): array {
    if (!is_bibliotecario()) {
        return ['sucesso' => false, 'erro' => 'Permissão negada'];
    }
    
    $db = db();
    
    // Verificar se usuário existe
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        return ['sucesso' => false, 'erro' => 'Usuário não encontrado'];
    }
    
    $campos = [];
    $params = [];
    
    if (!empty($dados['nome'])) {
        $campos[] = 'nome = ?';
        $params[] = $dados['nome'];
    }
    
    if (isset($dados['email'])) {
        if ($dados['email'] !== $usuario['email']) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$dados['email'], $id]);
            if ($stmt->fetch()) {
                return ['sucesso' => false, 'erro' => 'Email já cadastrado'];
            }
        }
        $campos[] = 'email = ?';
        $params[] = $dados['email'] ?: null;
    }
    
    if (isset($dados['turma'])) {
        $campos[] = 'turma = ?';
        $params[] = $dados['turma'] ?: null;
    }
    
    if (isset($dados['ativo'])) {
        $campos[] = 'ativo = ?';
        $params[] = (int)$dados['ativo'];
    }
    
    if (isset($dados['suspenso_ate'])) {
        $campos[] = 'suspenso_ate = ?';
        $params[] = $dados['suspenso_ate'] ?: null;
    }
    
    if (empty($campos)) {
        return ['sucesso' => false, 'erro' => 'Nenhum dado para atualizar'];
    }
    
    $params[] = $id;
    $stmt = $db->prepare("UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?");
    $stmt->execute($params);
    
    registrar_log('usuario_atualizado', 'usuarios', $id, $dados);
    
    return ['sucesso' => true];
}

function excluir_usuario(int $id): array {
    if (!is_bibliotecario()) {
        return ['sucesso' => false, 'erro' => 'Permissão negada'];
    }
    
    if ($id === usuario_id()) {
        return ['sucesso' => false, 'erro' => 'Não pode excluir a si mesmo'];
    }
    
    $db = db();
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    registrar_log('usuario_excluido', 'usuarios', $id);
    
    return ['sucesso' => true];
}