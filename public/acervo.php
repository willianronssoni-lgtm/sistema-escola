<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('acervo.php'));
    }

    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'obra') {
            $titulo = trim($_POST['titulo'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $isbn = trim($_POST['isbn'] ?? '');
            $editora = trim($_POST['editora'] ?? '');
            $ano = trim($_POST['ano'] ?? '');
            $sinopse = trim($_POST['sinopse'] ?? '');
            $tombo = trim($_POST['tombo'] ?? '');
            $localizacao = trim($_POST['localizacao'] ?? '');

            if ($titulo === '' || $autor === '') {
                throw new InvalidArgumentException('Título e autor são obrigatórios.');
            }

            $anoSql = $ano !== '' ? (int)$ano : null;
            if ($anoSql !== null && ($anoSql < 1000 || $anoSql > (int)date('Y') + 1)) {
                throw new InvalidArgumentException('Ano inválido.');
            }

            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO obras (titulo, autor, categoria, isbn, editora, ano, sinopse)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titulo,
                $autor,
                $categoria !== '' ? $categoria : null,
                $isbn !== '' ? $isbn : null,
                $editora !== '' ? $editora : null,
                $anoSql,
                $sinopse !== '' ? $sinopse : null,
            ]);
            $obraId = (int)$db->lastInsertId();

            if ($tombo !== '') {
                $stmt = $db->prepare("INSERT INTO exemplares (obra_id, tombo, status, localizacao) VALUES (?, ?, 'disponivel', ?)");
                $stmt->execute([$obraId, $tombo, $localizacao !== '' ? $localizacao : null]);
            }

            $db->commit();
            registrar_log('cadastro_obra', 'obras', $obraId, ['titulo' => $titulo]);
            flash('sucesso', 'Obra cadastrada com sucesso.');
        } elseif ($acao === 'exemplar') {
            $exemplarId = (int)($_POST['exemplar_id'] ?? 0);
            $status = $_POST['status'] ?? 'disponivel';
            $localizacao = trim($_POST['localizacao'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');

            $statusPermitidos = ['disponivel', 'emprestado', 'reservado', 'manutencao'];
            if (!in_array($status, $statusPermitidos, true)) {
                throw new InvalidArgumentException('Status inválido.');
            }

            $stmt = $db->prepare("SELECT id, status FROM exemplares WHERE id = ?");
            $stmt->execute([$exemplarId]);
            $exemplar = $stmt->fetch();
            if (!$exemplar) {
                throw new RuntimeException('Exemplar não encontrado.');
            }

            // Um exemplar emprestado ou reservado só volta a ficar disponível
            // quando o empréstimo/reserva correspondente é encerrado.
            if ($status === 'disponivel' && in_array($exemplar['status'], ['emprestado', 'reservado'], true)) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE exemplar_id = ? AND data_devolucao IS NULL");
                $stmt->execute([$exemplarId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Há empréstimo ativo para este exemplar. Registre a devolução antes.');
                }
                $stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE exemplar_id = ? AND status = 'confirmada'");
                $stmt->execute([$exemplarId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new RuntimeException('Há reserva confirmada para este exemplar. Cancele a reserva antes.');
                }
            }

            $stmt = $db->prepare("UPDATE exemplares SET status = ?, localizacao = ?, observacoes = ? WHERE id = ?");
            $stmt->execute([
                $status,
                $localizacao !== '' ? $localizacao : null,
                $observacoes !== '' ? $observacoes : null,
                $exemplarId,
            ]);

            registrar_log('exemplar_atualizado', 'exemplares', $exemplarId, ['status' => $status]);
            flash('sucesso', 'Exemplar atualizado.');
        } elseif ($acao === 'excluir_exemplar') {
            $exemplarId = (int)($_POST['exemplar_id'] ?? 0);
            $stmt = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE exemplar_id = ?");
            $stmt->execute([$exemplarId]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Não é possível excluir um exemplar que já possui empréstimo registrado.');
            }
            $stmt = $db->prepare("DELETE FROM exemplares WHERE id = ?");
            $stmt->execute([$exemplarId]);
            registrar_log('exemplar_excluido', 'exemplares', $exemplarId);
            flash('sucesso', 'Exemplar excluído.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Acervo: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique os dados informados.'
            : $e->getMessage());
    }

    redirect(url('acervo.php'));
}

$busca = trim($_GET['q'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$obras = buscar_obras(['busca' => $busca, 'categoria' => $categoria], 100);
$categorias = buscar_categorias();

$stmt = $db->query("SELECT id, obra_id, tombo, status, localizacao, observacoes FROM exemplares ORDER BY tombo");
$exemplares = [];
foreach ($stmt->fetchAll() as $ex) {
    $exemplares[$ex['obra_id']][] = $ex;
}

view('acervo', [
    'titulo' => 'Acervo',
    'obras' => $obras,
    'categorias' => $categorias,
    'exemplares' => $exemplares,
    'busca' => $busca,
    'categoria' => $categoria,
]);
