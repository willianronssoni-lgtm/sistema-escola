<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/biblioteca/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/biblioteca/public/index.php">
                <i class="bi bi-book-fill me-2"></i><?= APP_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/biblioteca/public/catalogo.php"><i class="bi bi-search me-1"></i>Catálogo</a>
                    </li>
                    
                    <?php if (is_aluno()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/biblioteca/public/minhas-reservas.php"><i class="bi bi-bookmark-check me-1"></i>Minhas Reservas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/biblioteca/public/meus-emprestimos.php"><i class="bi bi-journal-bookmark me-1"></i>Meus Empréstimos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/biblioteca/public/notificacoes.php">
                                <i class="bi bi-bell me-1"></i>Notificações
                                <?php $nao_lidas = notificacoes_nao_lidas(usuario_id()); ?>
                                <?php if ($nao_lidas > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $nao_lidas ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (is_bibliotecario()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-1"></i>Painel
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/biblioteca/public/painel.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/acervo.php"><i class="bi bi-bookshelf me-2"></i>Acervo</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/alunos.php"><i class="bi bi-people me-2"></i>Alunos</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/reservas.php"><i class="bi bi-clock-history me-2"></i>Reservas</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/emprestimos.php"><i class="bi bi-journal-plus me-2"></i>Empréstimos</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/devolucoes.php"><i class="bi bi-arrow-return-left me-2"></i>Devoluções</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/relatorios.php"><i class="bi bi-graph-up me-2"></i>Relatórios</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (usuario_logado()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle fs-5 me-2"></i>
                                <span class="d-none d-md-inline"><?= e(usuario_logado()['nome']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?= e(usuario_logado()['matricula']) ?></h6></li>
                                <li><span class="dropdown-item-text text-muted"><?= usuario_tipo() === 'aluno' ? 'Aluno' : 'Bibliotecário' ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/perfil.php"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="/biblioteca/public/alterar-senha.php"><i class="bi bi-key me-2"></i>Alterar Senha</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="/biblioteca/public/logout.php" method="post">
                                        <?= csfr_input() ?>
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/biblioteca/public/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?= flash_html() ?>
        {{content}}
    </main>

    <footer class="bg-light border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            <?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/biblioteca/assets/js/main.js"></script>
    {{scripts}}
</body>
</html>