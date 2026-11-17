<?php
$titulo = $titulo ?? 'Entrar';
$erro = $erro ?? '';
?>

<section class="login-screen">
    <div class="login-visual" aria-label="Livro aberto sobre uma mesa em uma biblioteca">
        <div class="login-library-glow"></div>
        <div class="login-lamp" aria-hidden="true">
            <span class="lamp-shade"></span><span class="lamp-arm"></span><span class="lamp-base"></span><span class="lamp-light"></span>
        </div>
        <div class="login-book-wrap" aria-hidden="true">
            <svg class="login-open-book" viewBox="0 0 720 360" role="img">
                <defs>
                    <linearGradient id="pageLeft" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fffdf5"/><stop offset="1" stop-color="#e9dfc9"/></linearGradient>
                    <linearGradient id="pageRight" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fffdf7"/><stop offset="1" stop-color="#e5d8bf"/></linearGradient>
                    <linearGradient id="bookCover" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--book-color)"/><stop offset="1" stop-color="var(--book-color-deep)"/></linearGradient>
                    <filter id="bookShadow" x="-20%" y="-20%" width="140%" height="160%"><feGaussianBlur stdDeviation="12"/></filter>
                </defs>
                <ellipse cx="360" cy="305" rx="310" ry="28" fill="#000" opacity=".38" filter="url(#bookShadow)"/>
                <path d="M48 258 Q190 220 350 276 Q510 220 672 258 L660 310 Q510 276 360 326 Q205 276 60 310Z" fill="url(#bookCover)" stroke="#261b35" stroke-width="8"/>
                <path d="M62 245 Q202 198 354 265 L354 304 Q202 244 62 287Z" fill="url(#pageLeft)" stroke="#b8a98e" stroke-width="3"/>
                <path d="M354 265 Q510 198 658 245 L658 287 Q510 244 354 304Z" fill="url(#pageRight)" stroke="#b8a98e" stroke-width="3"/>
                <path d="M354 265 Q350 288 354 304" fill="none" stroke="#9a8768" stroke-width="4"/>
                <g stroke="#b9ad96" stroke-width="3" opacity=".8" fill="none">
                    <path d="M105 250 Q215 220 315 267 M105 263 Q215 233 315 280 M105 276 Q215 246 315 293"/>
                    <path d="M392 267 Q500 220 615 250 M392 280 Q500 233 615 263 M392 293 Q500 246 615 276"/>
                </g>
            </svg>
        </div>
        <div class="login-visual-caption"><span class="login-mark"><i class="bi bi-book-half"></i></span><span>Conhecimento que inspira</span></div>
    </div>
    <div class="login-panel">
        <div class="login-panel-inner">
            <div class="login-brand">
                <span class="login-brand-icon"><i class="bi bi-book-half"></i></span>
                <h1>Biblioteca Escolar</h1><p>Conhecimento que inspira</p>
            </div>
            <h2>Faça seu login</h2>
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            <form method="post" class="login-form" novalidate>
                <?= csfr_input() ?>
                <div class="mb-3">
                    <label for="matricula" class="form-label">Matrícula</label>
                    <div class="login-input-wrap"><i class="bi bi-person" aria-hidden="true"></i>
                        <input type="text" class="form-control" id="matricula" name="matricula" placeholder="Digite sua matrícula" required autofocus autocomplete="username">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="login-input-wrap"><i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
                        <button class="password-toggle" type="button" data-password-toggle="senha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 login-submit"><i class="bi bi-box-arrow-in-right me-2"></i>Entrar</button>
            </form>
            <a class="login-back-link" href="<?= url('index.php') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar ao início</a>
            <p class="login-footnote">Acesso protegido • Biblioteca Escolar</p>
        </div>
    </div>
</section>
