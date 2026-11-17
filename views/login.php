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
            <svg class="login-open-book" viewBox="0 0 760 420" role="img" aria-label="Livro aberto com páginas iluminadas">
                <defs>
                    <linearGradient id="pageLeft" x1="0" y1="0" x2="0.8" y2="1"><stop offset="0" stop-color="#fffef9"/><stop offset=".72" stop-color="#f1e8d7"/><stop offset="1" stop-color="#d8c9ad"/></linearGradient>
                    <linearGradient id="pageRight" x1=".2" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fffef9"/><stop offset=".72" stop-color="#f3ead9"/><stop offset="1" stop-color="#d5c4a5"/></linearGradient>
                    <linearGradient id="cover" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--book-color)"/><stop offset="1" stop-color="var(--book-color-deep)"/></linearGradient>
                    <linearGradient id="spine" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#241b32"/><stop offset=".5" stop-color="var(--book-color-deep)"/><stop offset="1" stop-color="#241b32"/></linearGradient>
                    <filter id="shadow" x="-20%" y="-20%" width="140%" height="160%"><feGaussianBlur stdDeviation="13"/></filter>
                </defs>
                <ellipse cx="380" cy="360" rx="300" ry="25" fill="#000" opacity=".42" filter="url(#shadow)"/>
                <path d="M66 270 Q208 220 374 286 Q548 220 694 270 L678 344 Q530 300 380 370 Q225 300 82 344Z" fill="url(#cover)" stroke="#171321" stroke-width="8" stroke-linejoin="round"/>
                <path d="M78 252 Q220 188 376 267 L376 326 Q222 258 78 310Z" fill="#c9b99b" opacity=".65"/>
                <path d="M384 267 Q540 188 682 252 L682 310 Q538 258 384 326Z" fill="#b9a887" opacity=".65"/>
                <path d="M78 240 Q218 176 376 254 Q536 176 682 240 L682 300 Q536 244 380 326 Q222 244 78 300Z" fill="url(#pageLeft)" stroke="#b7a88d" stroke-width="4"/>
                <path d="M380 254 Q536 176 682 240 L682 300 Q536 244 380 326Z" fill="url(#pageRight)" stroke="#b7a88d" stroke-width="4"/>
                <path d="M380 254 Q373 288 380 326" fill="none" stroke="url(#spine)" stroke-width="10"/>
                <path d="M84 300 Q225 244 376 326 M384 326 Q536 244 676 300" fill="none" stroke="#8f7d60" stroke-width="3" opacity=".7"/>
                <g fill="none" stroke="#b7aa91" stroke-width="3.5" stroke-linecap="round" opacity=".82">
                    <path d="M128 238 Q238 196 340 254 M128 254 Q238 212 340 270 M128 270 Q238 228 340 286 M128 286 Q238 244 340 302"/>
                    <path d="M420 254 Q522 196 632 238 M420 270 Q522 212 632 254 M420 286 Q522 228 632 270 M420 302 Q522 244 632 286"/>
                </g>
                <path d="M80 240 Q220 177 376 254 Q536 177 680 240" fill="none" stroke="#fff9e9" stroke-width="5" opacity=".8"/>
            </svg>       </div>
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
