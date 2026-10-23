<?php
$titulo = $titulo ?? 'Início';
$estatisticas = $estatisticas ?? [];
$destaques = $destaques ?? [];
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="display-5 fw-bold text-primary"><i class="bi bi-house-door-fill me-2"></i>Bem-vindo à Biblioteca Escolar</h1>
        <p class="lead text-muted">Consulte o acervo, reserve livros e acompanhe seus empréstimos</p>
    </div>
</div>

<!-- Estatísticas -->
<div class="row g-3 mb-5">
    <div class="col-6 col-md-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-book fs-1 text-primary"></i>
                <h3 class="mt-2 mb-0"><?= number_format($estatisticas['obras']) ?></h3>
                <small class="text-muted">Títulos no acervo</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-journals fs-1 text-success"></i>
                <h3 class="mt-2 mb-0"><?= number_format($estatisticas['exemplares']) ?></h3>
                <small class="text-muted">Exemplares totais</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                <h3 class="mt-2 mb-0"><?= number_format($estatisticas['disponiveis']) ?></h3>
                <small class="text-muted">Disponíveis agora</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people fs-1 text-info"></i>
                <h3 class="mt-2 mb-0"><?= number_format($estatisticas['alunos']) ?></h3>
                <small class="text-muted">Alunos cadastrados</small>
            </div>
        </div>
    </div>
</div>

<?php if (usuario_logado() && is_aluno()): ?>
    <!-- Ações rápidas para aluno -->
    <div class="row g-3 mb-5">
        <div class="col-12">
            <h3 class="h5 mb-3"><i class="bi bi-lightning-fill me-2"></i>Ações Rápidas</h3>
        </div>
        <div class="col-6 col-md-3">
            <a href="/biblioteca/public/catalogo.php" class="card action-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-search fs-1 text-primary"></i>
                    <h5 class="mt-2 mb-0">Buscar Livros</h5>
                    <small class="text-muted">Consultar o catálogo</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="/biblioteca/public/minhas-reservas.php" class="card action-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-bookmark-check fs-1 text-warning"></i>
                    <h5 class="mt-2 mb-0">Minhas Reservas</h5>
                    <small class="text-muted">Ver fila e status</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="/biblioteca/public/meus-emprestimos.php" class="card action-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-journal-bookmark fs-1 text-success"></i>
                    <h5 class="mt-2 mb-0">Meus Empréstimos</h5>
                    <small class="text-muted">Prazos e renovações</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="/biblioteca/public/notificacoes.php" class="card action-card h-100 text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-bell fs-1 text-danger"></i>
                    <h5 class="mt-2 mb-0">Notificações</h5>
                    <small class="text-muted">Avisos e lembretes</small>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Destaques do acervo -->
<?php if (!empty($destaques)): ?>
    <div class="row mb-5">
        <div class="col-12 d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0"><i class="bi bi-star-fill me-2 text-warning"></i>Disponíveis no Momento</h3>
            <a href="/biblioteca/public/catalogo.php?disponiveis=1" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
        
        <div class="row g-3">
            <?php foreach ($destaques as $obra): ?>
                <div class="col-6 col-md-3">
                    <div class="card book-card h-100">
                        <div class="card-img-top book-cover">
                            <?php if ($obra['capa']): ?>
                                <img src="/biblioteca/uploads/capas/<?= e($obra['capa']) ?>" alt="<?= e($obra['titulo']) ?>" class="img-fluid">
                            <?php else: ?>
                                <i class="bi bi-book fs-1 text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-truncate" title="<?= e($obra['titulo']) ?>"><?= e($obra['titulo']) ?></h6>
                            <p class="card-text text-muted small mb-1"><?= e($obra['autor']) ?></p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="badge bg-success"><?= (int)$obra['disp'] ?> disp.</span>
                                <a href="/biblioteca/public/livro.php?id=<?= $obra['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Como funciona -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-search fs-2"></i>
                </div>
                <h5>1. Busque</h5>
                <p class="text-muted small">Procure por título, autor, categoria ou ISBN no catálogo</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-bookmark-plus fs-2"></i>
                </div>
                <h5>2. Reserve</h5>
                <p class="text-muted small">Solicite a reserva; o bibliotecário confirma e avisa você</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-check-circle fs-2"></i>
                </div>
                <h5>3. Retire</h5>
                <p class="text-muted small">Pegue o livro na biblioteca e devolva no prazo</p>
            </div>
        </div>
    </div>
</div>