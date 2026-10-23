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
    
    $obra = buscar_obra_por_id($obra_id);
    if (!$obra) {
        throw new Exception('Livro não encontrado');
    }
    
    // Verificar se já tem reserva ativa
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
    
    // Calcular posição na fila
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE obra_id = ? AND status = 'na_fila'");
    $stmt->execute([$obra_id]);
    $posicao = (int)$stmt->fetchColumn() + 1;
    
    // Criar reserva na fila
    $stmt = $db->prepare("
        INSERT INTO reservas (usuario_id, obra_id, status, posicao_fila)
        VALUES (?, ?, 'na_fila', ?)
    ");
    $stmt->execute([usuario_id(), $obra_id, $posicao]);
    $reserva_id = $db->lastInsertId();
    
    // Notificação
    criar_notificacao(usuario_id(), 'livro_disponivel',
        'Você entrou na fila: ' . $obra['titulo'],
        'Você está na posição #' . $posicao . ' da fila de espera para "' . $obra['titulo'] . '". Será notificado quando um exemplar ficar disponível.',
        '/biblioteca/public/minhas-reservas.php'
    );
    
    registrar_log('fila_entrada', 'reservas', $reserva_id, ['obra_id' => $obra_id, 'posicao' => $posicao]);
    
    $db->commit();
    flash('sucesso', 'Você entrou na fila de espera! Sua posição: #' . $posicao);
    
} catch (Exception $e) {
    $db->rollBack();
    flash('erro', $e->getMessage());
}

redirect('/biblioteca/public/livro.php?id=' . $obra_id);