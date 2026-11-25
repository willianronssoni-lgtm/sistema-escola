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
        if ($acao === 'cancelar') {
            $resultado = servico_cancelar_reserva($reservaId, (int)usuario_id(), true);
            flash('sucesso', 'Reserva cancelada.');
        } elseif ($acao === 'confirmar') {
            $resultado = servico_confirmar_reserva($reservaId);

            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }

            flash('sucesso', 'Reserva confirmada. Retirada até ' . format_datetime($resultado['expiracao']) . '.');
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
