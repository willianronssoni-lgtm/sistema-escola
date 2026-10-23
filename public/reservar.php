<?php
require_once __DIR__ . '/../app/config/config.php';
require_aluno();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/biblioteca/public/catalogo.php');
}

if (!csfr_verify($_POST['csrf_token'] ?? '')) {
    flash('erro', 'Token inválido');
    redirect('/biblioteca/public/livro.php?id=' . ($_POST['obra_id'] ?? 0));
}

$obra_id = (int)($_POST['obra_id'] ?? 0);
if (!$obra_id) {
    flash('erro', 'Livro inválido');
    redirect('/biblioteca/public/catalogo.php');
}

$db = db();

try {
    $db->beginTransaction();
    
    // Verificar se obra existe e tem exemplar disponível
    $obra = buscar_obra_por_id($obra_id);
    if (!$obra) {
        throw new Exception('Livro não encontrado');
    }
    
    if ($obra['disponiveis'] <= 0) {
        throw new Exception('Nenhum exemplar disponível para reserva direta');
    }
    
    // Verificar se aluno já tem reserva ativa
    $stmt = $db->prepare("
        SELECT id FROM reservas 
        WHERE usuario_id = ? AND obra_id = ? AND status IN ('pendente', 'confirmada', 'na_fila')
    ");
    $stmt->execute([usuario_id(), $obra_id]);
    if ($stmt->fetch()) {
        throw new Exception('Você já tem uma reserva ativa para este livro');
    }
    
    // Verificar se já tem o livro emprestado
    $stmt = $db->prepare("
        SELECT e.id FROM emprestimos e
        JOIN exemplares ex ON ex.id = e.exemplar_id
        WHERE e.usuario_id = ? AND ex.obra_id = ? AND e.data_devolucao IS NULL
    ");
    $stmt->execute([usuario_id(), $obra_id]);
    if ($stmt->fetch()) {
        throw new Exception('Você já tem este livro emprestado');
    }
    
    // Verificar limite de empréstimos
    $pode = usuario_pode_emprestar(usuario_id());
    if (!$pode['pode']) {
        throw new Exception($pode['motivo']);
    }
    
    // Buscar exemplar disponível
    $exemplar = buscar_exemplar_disponivel($obra_id);
    if (!$exemplar) {
        throw new Exception('Nenhum exemplar disponível no momento');
    }
    
    // Criar reserva confirmada com exemplar atribuído
    $data_expiracao = date('Y-m-d H:i:s', strtotime('+48 hours'));
    $stmt = $db->prepare("
        INSERT INTO reservas (usuario_id, obra_id, exemplar_id, status, data_confirmacao, data_expiracao)
        VALUES (?, ?, ?, 'confirmada', NOW(), ?)
    ");
    $stmt->execute([usuario_id(), $obra_id, $exemplar['id'], $data_expiracao]);
    $reserva_id = $db->lastInsertId();
    
    // Atualizar status do exemplar
    $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
    $stmt->execute([$exemplar['id']]);
    
    // Notificação
    criar_notificacao(usuario_id(), 'reserva_confirmada', 
        'Reserva confirmada: ' . $obra['titulo'],
        'Sua reserva foi confirmada. Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') na biblioteca até ' . format_datetime($data_expiracao) . '.',
        '/biblioteca/public/minhas-reservas.php'
    );
    
    registrar_log('reserva_criada', 'reservas', $reserva_id, ['obra_id' => $obra_id, 'exemplar_id' => $exemplar['id']]);
    
    $db->commit();
    flash('sucesso', 'Reserva confirmada! Retire o livro na biblioteca até ' . format_datetime($data_expiracao));
    
} catch (Exception $e) {
    $db->rollBack();
    flash('erro', $e->getMessage());
}

redirect('/biblioteca/public/livro.php?id=' . $obra_id);