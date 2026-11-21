<?php
$titulo = $titulo ?? 'Entrar';
$erro = $erro ?? '';
$identificador = $identificador ?? '';
?>

<section class="login-screen">
    <div class="login-visual" aria-label="Livro aberto sobre uma mesa em uma biblioteca">
        <div class="login-library-glow"></div>
        <div class="login-lamp" aria-hidden="true">
            <span class="lamp-shade"></span><span class="lamp-arm"></span><span class="lamp-base"></span><span class="lamp-light"></span>
        </div>
        <div class="login-book-wrap" aria-hidden="true">
            <svg class="login-open-book" viewBox="0 0 760 420" role="img" aria-label="Livro aberto moderno com páginas douradas e capa elegante">
                <defs>
                    <linearGradient id="newBookCover" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--book-color)"/><stop offset="1" stop-color="var(--book-color-deep)"/></linearGradient>
                    <linearGradient id="newBookPages" x1="0" y1="0" x2="0.2" y2="1"><stop offset="0" stop-color="#fffef8"/><stop offset=".72" stop-color="#f5ead2"/><stop offset="1" stop-color="#d8c49d"/></linearGradient>
                    <linearGradient id="newBookGold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff0bd"/><stop offset="1" stop-color="#c99b4b"/></linearGradient>
                    <filter id="newBookShadow" x="-30%" y="-30%" width="160%" height="180%"><feGaussianBlur stdDeviation="12"/></filter>
                </defs>
                <ellipse cx="380" cy="350" rx="270" ry="28" fill="#000" opacity=".38" filter="url(#newBookShadow)"/>
                <g opacity=".9" fill="none" stroke="url(#newBookGold)" stroke-width="4" stroke-linecap="round">
                    <path d="M145 112l8-17 8 17 17 8-17 8-8 17-8-17-17-8z"/>
                    <path d="M600 92l5-11 5 11 11 5-11 5-5 11-5-11-11-5z"/>
                    <path d="M535 150l4-9 4 9 9 4-9 4-4 9-4-9-9-4z"/>
                </g>
                <path d="M105 260 Q232 205 375 274 Q520 205 655 260 L638 335 Q510 292 380 362 Q245 292 122 335Z" fill="url(#newBookCover)" stroke="#171321" stroke-width="8" stroke-linejoin="round"/>
                <path d="M122 245 Q248 186 375 254 L375 322 Q245 267 122 310Z" fill="#b99b65" opacity=".75"/>
                <path d="M385 254 Q520 186 638 245 L638 310 Q510 267 385 322Z" fill="#a98b58" opacity=".75"/>
                <path d="M112 225 Q245 160 375 238 Q515 160 648 225 L638 294 Q510 240 380 322 Q245 240 122 294Z" fill="url(#newBookPages)" stroke="#d4c19a" stroke-width="4"/>
                <path d="M380 238 Q515 160 648 225 L638 294 Q510 240 380 322Z" fill="url(#newBookPages)" stroke="#d4c19a" stroke-width="4"/>
                <path d="M380 238 Q372 280 380 322" fill="none" stroke="#8d6b3c" stroke-width="9"/>
                <g fill="none" stroke="#c4b28f" stroke-width="3" stroke-linecap="round" opacity=".9">
                    <path d="M160 226 Q250 187 342 244 M154 244 Q250 205 342 262 M150 262 Q250 223 342 280"/>
                    <path d="M418 244 Q510 187 600 226 M418 262 Q510 205 606 244 M418 280 Q510 223 610 262"/>
                </g>
                <path d="M112 225 Q245 160 375 238 Q515 160 648 225" fill="none" stroke="#fffdf4" stroke-width="6" opacity=".9"/>
                <path d="M380 322 Q510 240 638 294" fill="none" stroke="#8b7049" stroke-width="3" opacity=".65"/>
                <path d="M122 294 Q245 240 375 322" fill="none" stroke="#8b7049" stroke-width="3" opacity=".65"/>
                <path d="M380 330v24" stroke="url(#newBookGold)" stroke-width="5" stroke-linecap="round"/>
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
                    <label for="identificador" class="form-label">E-mail escolar</label>
                    <div class="login-input-wrap"><i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="text" class="form-control" id="identificador" name="identificador" value="<?= e($identificador) ?>" placeholder="seunome@escola" required autofocus autocomplete="username" inputmode="email">
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
            <a class="login-back-link" href="<?= url() ?>"><i class="bi bi-arrow-left me-1"></i>Voltar ao início</a>
            <p class="login-footnote">Acesso protegido • Biblioteca Escolar</p>
        </div>
    </div>
</section>
