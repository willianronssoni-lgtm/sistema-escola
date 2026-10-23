<?php
require_once __DIR__ . '/../app/config/config.php';
require_aluno();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/biblioteca/public/minhas-reservas.php');
}

if (!csfr_verify($_POST['csrf_token'] ?? '')) {
    flash('erro', 'Token inválido');
    redirect('/biblioteca/public/minhas-reservas.php');
}

$reserva_id = (int)($_POST['reserva_id'] ?? 0);
if (!$reserva_id) {
    flash('erro', 'Reserva inválida');
    redirect('/biblioteca/public/minhas-reservas.php');
}

$db = db();

try {
    $db->beginTransaction();
    
    // Verificar se a reserva pertence ao usuário
    $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$reserva_id, usuario_id()]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        throw new Exception('Reserva não encontrada');
    }
    
    if (!in_array($reserva['status'], ['pendente', 'confirmada', 'na_fila'])) {
        throw new Exception('Esta reserva não pode ser cancelada');
    }
    
    // Se confirmada, liberar o exemplar
    if ($reserva['status'] === 'confirmada' && $reserva['exemplar_id']) {
        $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
        $stmt->execute([$reserva['exemplar_id']]);
        
        // Verificar se há fila de espera
        $proximo = proximo_da_fila($reserva['obra_id']);
        if ($proximo) {
            // Confirmar para o próximo da fila
            $exemplar = buscar_exemplar_disponivel($reserva['obra_id']);
            if ($exemplar) {
                $data_expiracao = date('Y-m-d H:i:s', strtotime('+48 hours'));
                $stmt = $db->prepare("
                    UPDATE reservas SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?
                    WHERE id = ?
                ");
                $stmt->execute([$exemplar['id'], $data_expiracao, $proximo['reserva_id']]);
                
                $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
                $stmt->execute([$exemplar['id']]);
                
                criar_notificacao($proximo['usuario_id'], 'reserva_confirmada',
                    'Reserva confirmada (fila): ' . $proximo['obra_titulo'],
                    'Sua reserva na fila foi confirmada! Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') até ' . format_datetime($data_expiracao) . '.',
                    '/biblioteca/public/minhas-reservas.php'
                );
            }
        }
    }
    
    // Cancelar a reserva
    $stmt = $db->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
    $stmt->execute([$reserva_id]);
    
    registrar_log('reserva_cancelada', 'reservas', $reserva_id);
    
    $db->commit();
    flash('sucesso', 'Reserva cancelada com sucesso');
    
} catch (Exception $e) {
    $db->rollBack();
    flash('erro', $e->getMessage());
}

redirect('/biblioteca/public/minhas-reservas.php');