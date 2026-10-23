<?php
$titulo = $titulo ?? 'Meus Empréstimos';
$ativos = $ativos ?? [];
$historico = $historico ?? [];
$suspenso_ate = $suspenso_ate ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-journal-bookmark me-2"></i>Meus Empréstimos</h1>
</div>

<?php if ($suspenso_ate && $suspenso_ate >= date('Y-m-d')): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Conta suspensa</strong> até <strong><?= format_date($suspenso_ate) ?></strong> devido a atrasos na devolução.
        Você não pode fazer novas reservas ou empréstimos até esta data.
    </div>
<?php endif; ?>

<!-- Empréstimos Ativos -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-book me-1"></i>Empréstimos Ativos (<?= count($ativos) ?>)</strong>
        <span class="badge bg-info"><?= count($ativos) ?>/<?= MAX_EMPRESTIMOS_SIMULTANEOS ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($ativos)): ?>
            <p class="text-muted mb-0">Nenhum empréstimo ativo no momento.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Retirada</th>
                            <th>Prevista</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ativos as $emp): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($emp['capa']): ?>
                                            <img src="/biblioteca/uploads/capas/<?= e($emp['capa']) ?>" alt="" class="book-cover-xs me-2">
                                        <?php else: ?>
                                            <div class="book-cover-xs bg-light d-flex align-items-center justify-content-center me-2">
                                                <i class="bi bi-book text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="/biblioteca/public/livro.php?id=<?= $emp['obra_id'] ?>" class="text-decoration-none fw-semibold"><?= e($emp['titulo']) ?></a>
                                            <br><small class="text-muted">Tombo: <code><?= e($emp['tombo']) ?></code></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= format_date($emp['data_retirada']) ?></td>
                                <td class="<?= $emp['dias_atraso'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                    <?= format_date($emp['data_prevista_devolucao']) ?>
                                    <?php if ($emp['dias_atraso'] > 0): ?>
                                        <br><span class="badge bg-danger"><?= $emp['dias_atraso'] ?> dia(s) atraso</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($emp['dias_atraso'] > 0): ?>
                                        <span class="badge bg-danger">Atrasado</span>
                                    <?php elseif ($emp['renovado']): ?>
                                        <span class="badge bg-warning text-dark">Renovado</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Em dia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($emp['dias_atraso'] === 0 && !$emp['renovado']): ?>
                                            <a href="/biblioteca/public/renovar.php?id=<?= $emp['id'] ?>" class="btn btn-outline-primary" title="Renovar por mais 15 dias">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/biblioteca/public/livro.php?id=<?= $emp['obra_id'] ?>" class="btn btn-outline-secondary" title="Ver livro">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Histórico -->
<div class="card">
    <div class="card-header">
        <strong><i class="bi bi-clock-history me-1"></i>Histórico de Empréstimos</strong>
    </div>
    <div class="card-body">
        <?php if (empty($historico)): ?>
            <p class="text-muted mb-0">Nenhum empréstimo anterior.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Retirada</th>
                            <th>Devolução</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $emp): ?>
                            <tr>
                                <td>
                                    <a href="/biblioteca/public/livro.php?id=<?= $emp['obra_id'] ?>" class="text-decoration-none fw-semibold"><?= e($emp['titulo']) ?></a>
                                    <br><small class="text-muted">Tombo: <code><?= e($emp['tombo']) ?></code></small>
                                </td>
                                <td><?= format_date($emp['data_retirada']) ?></td>
                                <td><?= format_date($emp['data_devolucao']) ?></td>
                                <td>
                                    <?php if ($emp['dias_atraso'] > 0): ?>
                                        <span class="badge bg-danger">Devolvido com <?= $emp['dias_atraso'] ?> dia(s) de atraso</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Devolvido no prazo</span>
                                    <?php endif; ?>
                                    <?php if ($emp['renovado']): ?>
                                        <span class="badge bg-warning text-dark ms-1">Foi renovado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>