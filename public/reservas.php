<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('reservas.php'));
    }

    $acao = $_POST['acao'] ?? '';
    $reservaId = (int)($_POST['reserva_id'] ?? 0);

    try {
        $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        if (!$reserva) {
            throw new RuntimeException('Reserva não encontrada.');
        }

        if ($acao === 'cancelar') {
            if (!in_array($reserva['status'], ['pendente', 'confirmada', 'na_fila'], true)) {
                throw new RuntimeException('Esta reserva não pode mais ser cancelada.');
            }

            $db->beginTransaction();

            if ($reserva['status'] === 'confirmada' && $reserva['exemplar_id']) {
                $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
                $stmt->execute([(int)$reserva['exemplar_id']]);
            }

            $stmt = $db->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
            $stmt->execute([$reservaId]);

            $db->commit();
            registrar_log('reserva_cancelada_bibliotecario', 'reservas', $reservaId);
            flash('sucesso', 'Reserva cancelada.');
        } elseif ($acao === 'confirmar') {
            if ($reserva['status'] !== 'na_fila') {
                throw new RuntimeException('Somente reservas na fila podem ser confirmadas.');
            }

            $exemplar = buscar_exemplar_disponivel((int)$reserva['obra_id']);
            if (!$exemplar) {
                throw new RuntimeException('Não há exemplar disponível desta obra no momento.');
            }

            $db->beginTransaction();

            $expira = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));
            $stmt = $db->prepare("
                UPDATE reservas
                SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?, posicao_fila = NULL
                WHERE id = ?
            ");
            $stmt->execute([(int)$exemplar['id'], $expira, $reservaId]);

            $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
            $stmt->execute([(int)$exemplar['id']]);

            $db->commit();

            criar_notificacao((int)$reserva['usuario_id'], 'reserva_confirmada', 'Reserva confirmada',
                'Sua reserva foi confirmada. Retire o exemplar (Tombo: ' . $exemplar['tombo'] . ') até ' . format_datetime($expira) . '.',
                url('minhas-reservas.php'));

            registrar_log('reserva_confirmada_bibliotecario', 'reservas', $reservaId);
            flash('sucesso', 'Reserva confirmada para ' . format_date($expira, 'd/m/Y H:i') . '.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Reservas: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique os dados informados.'
            : $e->getMessage());
    }

    redirect(url('reservas.php'));
}

$status = $_GET['status'] ?? 'ativas';
$busca = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($status === 'ativas') {
    $where[] = "r.status IN ('pendente','confirmada','na_fila')";
} elseif ($status !== 'todas' && $status !== '') {
    $where[] = 'r.status = ?';
    $params[] = $status;
}

if ($busca !== '') {
    $where[] = '(u.nome LIKE ? OR u.matricula LIKE ? OR o.titulo LIKE ?)';
    $termo = "%{$busca}%";
    array_push($params, $termo, $termo, $termo);
}

$sql = "
    SELECT r.*, u.nome AS aluno, u.matricula, u.turma, o.titulo AS obra_titulo,
           x.tombo
    FROM reservas r
    JOIN usuarios u ON u.id = r.usuario_id
    JOIN obras o ON o.id = r.obra_id
    LEFT JOIN exemplares x ON x.id = r.exemplar_id
";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY r.data_reserva DESC, r.id DESC LIMIT 200';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll();

$contagens = [];
foreach ($db->query("SELECT status, COUNT(*) AS total FROM reservas GROUP BY status") as $linha) {
    $contagens[$linha['status']] = (int)$linha['total'];
}

view('reservas', [
    'titulo' => 'Reservas',
    'reservas' => $reservas,
    'contagens' => $contagens,
    'status' => $status,
    'busca' => $busca,
]);
