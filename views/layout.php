<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-appearance="roxo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? APP_NAME) ?></title>
    <script>
        (function () {
            // O site sempre abre no modo claro; o usuário pode ativar o escuro pelo botão.
            document.documentElement.setAttribute('data-theme', 'light');
            try {
                var aparencia = localStorage.getItem('biblioteca-escolar-appearance');
                if (['roxo','azul','branco','dourado','laranja','bordo','esmeralda'].indexOf(aparencia) !== -1) document.documentElement.setAttribute('data-appearance', aparencia);
                localStorage.setItem('biblioteca-escolar-theme', 'light');
            } catch (e) {}
        })();
    </script>
    <style>
        html:not([data-theme="dark"]), html:not([data-theme="dark"]) body,
        html:not([data-theme="dark"]) body main,
        html:not([data-theme="dark"]) body main.container {
            background-color: #F7F7F7 !important;
            color: #292929 !important;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>?v=13" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg shadow-sm floating-toolbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">
                <i class="bi bi-book-fill me-2"></i><?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('catalogo.php') ?>"><i class="bi bi-search me-1"></i>Catálogo</a>
                    </li>
                    <?php if (is_aluno()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('minhas-reservas.php') ?>"><i class="bi bi-bookmark-check me-1"></i>Minhas Reservas</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('minha-fila.php') ?>"><i class="bi bi-people me-1"></i>Minha Fila</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= url('meus-emprestimos.php') ?>"><i class="bi bi-journal-bookmark me-1"></i>Meus Empréstimos</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('notificacoes.php') ?>">
                                <i class="bi bi-bell me-1"></i>Notificações
                                <?php $nao_lidas = notificacoes_nao_lidas(usuario_id()); ?>
                                <?php if ($nao_lidas > 0): ?><span class="badge bg-danger ms-1"><?= $nao_lidas ?></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (is_bibliotecario()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-gear me-1"></i>Biblioteca</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= url('painel.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url('acervo.php') ?>"><i class="bi bi-bookshelf me-2"></i>Acervo</a></li>
                                <li><a class="dropdown-item" href="<?= url('alunos.php') ?>"><i class="bi bi-people me-2"></i>Alunos</a></li>
                                <li><a class="dropdown-item" href="<?= url('emprestimos.php') ?>"><i class="bi bi-journal-plus me-2"></i>Empréstimos</a></li>
                                <li><a class="dropdown-item" href="<?= url('devolucoes.php') ?>"><i class="bi bi-arrow-return-left me-2"></i>Devoluções</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url('reservas.php') ?>"><i class="bi bi-clock-history me-2"></i>Reservas e fila</a></li>
                                <li><a class="dropdown-item" href="<?= url('relatorios.php') ?>"><i class="bi bi-graph-up me-2"></i>Relatórios</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item me-lg-2">
                        <label class="visually-hidden" for="appearanceSelect">Aparência do sistema</label>
                        <select id="appearanceSelect" class="form-select form-select-sm appearance-select" data-appearance-select aria-label="Escolher aparência do sistema">
                            <option value="roxo">Roxo discreto</option>
                            <option value="azul">Azul</option>
                            <option value="branco">Branco</option>
                            <option value="dourado">Dourado</option>
                            <option value="laranja">Laranja</option>
                            <option value="bordo">Bordô</option>
                            <option value="esmeralda">Verde esmeralda</option>
                        </select>
                    </li>
                    <li class="nav-item me-lg-2">
                        <button type="button" class="btn btn-sm btn-outline-light theme-toggle" data-theme-toggle aria-label="Ativar modo escuro" title="Ativar modo escuro">
                            <i class="bi bi-moon-stars"></i><span data-theme-label>Modo escuro</span>
                        </button>
                    </li>
                    <?php if (usuario_logado()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle fs-5 me-2"></i><span class="d-none d-md-inline"><?= e(usuario_logado()['nome']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?= e(usuario_logado()['matricula']) ?></h6></li>
                                <li><span class="dropdown-item-text text-muted"><?= usuario_tipo() === 'aluno' ? 'Aluno' : 'Bibliotecário' ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if (is_bibliotecario()): ?><li><a class="dropdown-item" href="<?= url('painel.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Painel da biblioteca</a></li><?php endif; ?>
                                <li><a class="dropdown-item" href="<?= url('perfil.php') ?>"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="<?= url('alterar-senha.php') ?>"><i class="bi bi-key me-2"></i>Alterar Senha</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><form action="<?= url('logout.php') ?>" method="post"><?= csfr_input() ?><button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sair</button></form></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?= flash_html() ?>
        <?= $content ?>
    </main>

    <footer class="border-top py-4 mt-auto">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="fw-semibold mb-2"><i class="bi bi-book-fill me-1"></i><?= APP_NAME ?></div>
                    <p class="text-muted small mb-0">Organização da biblioteca escolar: catálogo, reservas, fila de espera e empréstimos.</p>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fw-semibold mb-2">Aluno</div>
                    <ul class="list-unstyled small mb-0">
                        <li><a class="link-secondary" href="<?= url('catalogo.php') ?>">Catálogo</a></li>
                        <li><a class="link-secondary" href="<?= url('minhas-reservas.php') ?>">Minhas reservas</a></li>
                        <li><a class="link-secondary" href="<?= url('minha-fila.php') ?>">Minha fila de espera</a></li>
                        <li><a class="link-secondary" href="<?= url('meus-emprestimos.php') ?>">Meus empréstimos</a></li>
                        <li><a class="link-secondary" href="<?= url('notificacoes.php') ?>">Notificações</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fw-semibold mb-2">Bibliotecário</div>
                    <ul class="list-unstyled small mb-0">
                        <li><a class="link-secondary" href="<?= url('painel.php') ?>">Painel</a></li>
                        <li><a class="link-secondary" href="<?= url('acervo.php') ?>">Acervo</a></li>
                        <li><a class="link-secondary" href="<?= url('alunos.php') ?>">Alunos</a></li>
                        <li><a class="link-secondary" href="<?= url('emprestimos.php') ?>">Empréstimos</a></li>
                        <li><a class="link-secondary" href="<?= url('devolucoes.php') ?>">Devoluções</a></li>
                        <li><a class="link-secondary" href="<?= url('reservas.php') ?>">Reservas e fila</a></li>
                        <li><a class="link-secondary" href="<?= url('relatorios.php') ?>">Relatórios</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <div class="fw-semibold mb-2">Conta</div>
                    <ul class="list-unstyled small mb-0">
                        <li><a class="link-secondary" href="<?= url('perfil.php') ?>">Meu perfil</a></li>
                        <li><a class="link-secondary" href="<?= url('alterar-senha.php') ?>">Alterar senha</a></li>
                        <li><a class="link-secondary" href="<?= url('login.php') ?>">Entrar</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center text-muted small mt-3 pt-3 border-top">
                <?= APP_NAME ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('assets/js/main.js') ?>?v=8"></script>
    <?= $scripts ?>
</body>
</html>