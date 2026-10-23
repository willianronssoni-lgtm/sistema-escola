<?php
$titulo = $titulo ?? 'Minhas Reservas';
$reservas = $reservas ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-bookmark-check me-2"></i>Minhas Reservas</h1>
    <a href="/biblioteca/public/catalogo.php" class="btn btn-outline-primary">
        <i class="bi bi-plus me-1"></i>Buscar mais livros
    </a>
</div>

<?php if (empty($reservas)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bookmark-x fs-1 text-muted"></i>
        <h4 class="mt-3 text-muted">Nenhuma reserva</h4>
        <p class="text-muted">Você não tem reservas ativas ou no histórico</p>
        <a href="/biblioteca/public/catalogo.php" class="btn btn-primary">Explorar catálogo</a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($reservas as $r): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <?php if ($r['capa']): ?>
                                    <img src="/biblioteca/uploads/capas/<?= e($r['capa']) ?>" alt="" class="book-cover-sm">
                                <?php else: ?>
                                    <div class="book-cover-sm d-flex align-items-center justify-content-center bg-light">
                                        <i class="bi bi-book fs-3 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col">
                                <h6 class="mb-1">
                                    <a href="/biblioteca/public/livro.php?id=<?= $r['obra_id'] ?>" class="text-decoration-none text-dark">
                                        <?= e($r['titulo']) ?>
                                    </a>
                                </h6>
                                <small class="text-muted"><?= e($r['autor']) ?></small>
                            </div>
                            <div class="col-auto">
                                <?= status_badge($r['status']) ?>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <?php if ($r['status'] === 'confirmada'): ?>
                                        <div class="text-danger small fw-semibold">
                                            <i class="bi bi-clock me-1"></i>Expira: <?= $r['data_expiracao'] ? format_datetime($r['data_expiracao']) : '-' ?>
                                        </div>
                                        <?php if ($r['tombo']): ?>
                                            <small class="text-muted">Tombo: <code><?= e($r['tombo']) ?></code></small>
                                        <?php endif; ?>
                                    <?php elseif ($r['status'] === 'na_fila'): ?>
                                        <div class="text-info small fw-semibold">
                                            <i class="bi bi-people me-1"></i>Posição: #<?= $r['posicao_fila'] ?>
                                        </div>
                                    <?php elseif ($r['status'] === 'pendente'): ?>
                                        <small class="text-muted">Aguardando confirmação</small>
                                    <?php elseif ($r['status'] === 'expirada'): ?>
                                        <small class="text-muted">Reserva expirada em <?= $r['data_expiracao'] ? format_datetime($r['data_expiracao']) : '-' ?></small>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">Reservado em <?= format_datetime($r['data_reserva']) ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($r['status'] === 'confirmada' || $r['status'] === 'na_fila' || $r['status'] === 'pendente'): ?>
                            <hr class="my-2">
                            <form method="post" action="/biblioteca/public/cancelar-reserva.php" class="d-inline" onsubmit="return confirm('Cancelar esta reserva?')">
                                <?= csfr_input() ?>
                                <input type="hidden" name="reserva_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle me-1"></i>Cancelar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>