<?php
$titulo = $titulo ?? 'Catálogo';
$obras = $obras ?? [];
$total = $total ?? 0;
$pagina = $pagina ?? 1;
$por_pagina = $por_pagina ?? 12;
$filtros = $filtros ?? [];
$categorias = $categorias ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-search me-2"></i>Catálogo</h1>
    <span class="text-muted small"><?= number_format($total) ?> livro(s) encontrado(s)</span>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label fw-semibold">Buscar</label>
                <input type="text" class="form-control" id="q" name="q" 
                       placeholder="Título, autor, ISBN..." value="<?= e($filtros['busca']) ?>">
            </div>
            <div class="col-md-3">
                <label for="categoria" class="form-label fw-semibold">Categoria</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $filtros['categoria'] === $cat ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="disponiveis" name="disponiveis" value="1"
                           <?= $filtros['so_disponiveis'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="disponiveis">
                        Só disponíveis
                    </label>
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="/biblioteca/public/catalogo.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Resultados -->
<?php if (empty($obras)): ?>
    <div class="text-center py-5">
        <i class="bi bi-book fs-1 text-muted"></i>
        <h4 class="mt-3 text-muted">Nenhum livro encontrado</h4>
        <p class="text-muted">Tente alterar os filtros ou buscar por outro termo</p>
        <a href="/biblioteca/public/catalogo.php" class="btn btn-outline-primary">Ver todos</a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($obras as $obra): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card book-card h-100">
                    <div class="card-img-top book-cover">
                        <?php if ($obra['capa']): ?>
                            <img src="/biblioteca/uploads/capas/<?= e($obra['capa']) ?>" alt="<?= e($obra['titulo']) ?>" class="img-fluid">
                        <?php else: ?>
                            <i class="bi bi-book fs-1 text-muted"></i>
                        <?php endif; ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <?= status_badge($obra['disponiveis'] > 0 ? 'disponivel' : 'emprestado') ?>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate" title="<?= e($obra['titulo']) ?>">
                            <a href="/biblioteca/public/livro.php?id=<?= $obra['id'] ?>" class="text-decoration-none text-dark stretched-link">
                                <?= e($obra['titulo']) ?>
                            </a>
                        </h6>
                        <p class="card-text text-muted small mb-1"><?= e($obra['autor']) ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <small class="text-muted">
                                <?= (int)$obra['disponiveis'] ?> de <?= (int)$obra['total_exemplares'] ?> disp.
                            </small>
                            <a href="/biblioteca/public/livro.php?id=<?= $obra['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginação -->
    <?= paginacao($total, $por_pagina, $pagina, '/biblioteca/public/catalogo.php', [
        'q' => $filtros['busca'],
        'categoria' => $filtros['categoria'],
        'disponiveis' => $filtros['so_disponiveis'] ? '1' : '',
    ]) ?>
<?php endif; ?>