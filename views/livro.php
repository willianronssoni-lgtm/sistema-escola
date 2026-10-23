<?php
$titulo = $titulo ?? 'Livro';
$obra = $obra ?? [];
$reserva_ativa = $reserva_ativa ?? null;
$na_fila = $na_fila ?? false;
$posicao_fila = $posicao_fila ?? null;
$emprestimo_ativo = $emprestimo_ativo ?? null;
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card book-card h-100">
            <div class="book-cover-large">
                <?php if ($obra['capa']): ?>
                    <img src="/biblioteca/uploads/capas/<?= e($obra['capa']) ?>" alt="<?= e($obra['titulo']) ?>" class="img-fluid">
                <?php else: ?>
                    <i class="bi bi-book fs-1 text-muted"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/biblioteca/public/index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="/biblioteca/public/catalogo.php">Catálogo</a></li>
                <li class="breadcrumb-item active"><?= e($obra['titulo']) ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1"><?= e($obra['titulo']) ?></h1>
                <p class="text-muted mb-0">por <strong><?= e($obra['autor']) ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($obra['disponiveis'] > 0): ?>
                    <span class="badge bg-success fs-6 px-3 py-2"><?= (int)$obra['disponiveis'] ?> disponível(is)</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6 px-3 py-2">Indisponível</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mb-4">
            <?php if ($obra['isbn']): ?>
                <div class="col-md-3"><small class="text-muted">ISBN</small><br><?= e($obra['isbn']) ?></div>
            <?php endif; ?>
            <?php if ($obra['editora']): ?>
                <div class="col-md-3"><small class="text-muted">Editora</small><br><?= e($obra['editora']) ?></div>
            <?php endif; ?>
            <?php if ($obra['ano']): ?>
                <div class="col-md-2"><small class="text-muted">Ano</small><br><?= e($obra['ano']) ?></div>
            <?php endif; ?>
            <?php if ($obra['categoria']): ?>
                <div class="col-md-4"><small class="text-muted">Categoria</small><br>
                    <a href="/biblioteca/public/catalogo.php?categoria=<?= urlencode($obra['categoria']) ?>" class="text-decoration-none">
                        <span class="badge bg-secondary"><?= e($obra['categoria']) ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($obra['sinopse']): ?>
            <div class="card mb-4">
                <div class="card-header"><strong>Sinopse</strong></div>
                <div class="card-body"><?= nl2br(e($obra['sinopse'])) ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Exemplares -->
        <?php if (!empty($obra['exemplares'])): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Exemplares (<?= count($obra['exemplares']) ?>)</strong>
                    <?php if (is_bibliotecario()): ?>
                        <a href="/biblioteca/public/exemplar-novo.php?obra_id=<?= $obra['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus me-1"></i>Novo exemplar
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tombo</th>
                                    <th>Status</th>
                                    <th>Localização</th>
                                    <?php if (is_bibliotecario()): ?><th>Ações</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($obra['exemplares'] as $ex): ?>
                                    <tr>
                                        <td><code><?= e($ex['tombo']) ?></code></td>
                                        <td><?= status_badge($ex['status']) ?></td>
                                        <td><?= e($ex['localizacao'] ?? '-') ?></td>
                                        <?php if (is_bibliotecario()): ?>
                                            <td>
                                                <a href="/biblioteca/public/exemplar-editar.php?id=<?= $ex['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ações do aluno -->
        <?php if (is_aluno()): ?>
            <div class="card">
                <div class="card-header"><strong>Disponibilidade para você</strong></div>
                <div class="card-body">
                    <?php if ($emprestimo_ativo): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-journal-check me-2"></i>
                            <strong>Você já tem este livro emprestado!</strong><br>
                            Tombo: <code><?= e($emprestimo_ativo['tombo']) ?></code> |
                            Devolução prevista: <strong><?= format_date($emprestimo_ativo['data_prevista_devolucao']) ?></strong>
                            <?php if ($emprestimo_ativo['dias_atraso'] > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $emprestimo_ativo['dias_atraso'] ?> dia(s) de atraso</span>
                            <?php endif; ?>
                            <?php if (!$emprestimo_ativo['renovado']): ?>
                                <a href="/biblioteca/public/renovar.php?id=<?= $emprestimo_ativo['id'] ?>" class="btn btn-sm btn-outline-primary ms-2">Renovar</a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($reserva_ativa): ?>
                        <?php if ($reserva_ativa['status'] === 'confirmada'): ?>
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Reserva confirmada!</strong> Retire o livro na biblioteca até 
                                <strong><?= $reserva_ativa['data_expiracao'] ? format_datetime($reserva_ativa['data_expiracao']) : 'em breve' ?></strong>.
                                <?php if ($reserva_ativa['exemplar_id']): ?>
                                    Tombo reservado: <code><?= e($reserva_ativa['exemplar_id']) ?></code>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($reserva_ativa['status'] === 'pendente'): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-hourglass me-2"></i>
                                <strong>Reserva pendente</strong> - Aguardando confirmação do bibliotecário.
                            </div>
                        <?php elseif ($na_fila): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-people me-2"></i>
                                <strong>Você está na fila de espera</strong> - Posição: <strong>#<?= $posicao_fila ?></strong>.
                                Será notificado quando um exemplar ficar disponível.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($obra['disponiveis'] > 0): ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <form method="post" action="/biblioteca/public/reservar.php">
                                    <?= csfr_input() ?>
                                    <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-bookmark-plus me-1"></i>Reservar exemplar disponível
                                    </button>
                                </form>
                                <a href="/biblioteca/public/catalogo.php" class="btn btn-outline-secondary">Voltar ao catálogo</a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <form method="post" action="/biblioteca/public/entrar-fila.php">
                                    <?= csfr_input() ?>
                                    <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-person-lines-fill me-1"></i>Entrar na fila de espera
                                    </button>
                                </form>
                                <a href="/biblioteca/public/catalogo.php" class="btn btn-outline-secondary">Voltar ao catálogo</a>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Todos os exemplares estão emprestados. Entre na fila para ser avisado quando um ficar disponível.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>