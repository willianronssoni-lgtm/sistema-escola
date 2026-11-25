<?php
$titulo = $titulo ?? 'Entrar';
$erro = $erro ?? '';
$identificador = $identificador ?? '';
?>

<link rel="stylesheet" href="<?= url('assets/css/login-screen.css') ?>?v=4">

<section class="login-screen login-screen-photo" aria-label="Acesso à Biblioteca Escolar">
    <div class="login-backdrop" aria-hidden="true"></div>
    <div class="login-card">
        <div class="login-brand">
            <span class="login-brand-icon" aria-hidden="true"><i class="bi bi-book"></i></span>
            <h1>Biblioteca Escolar</h1>
            <p>Conhecimento ao seu alcance</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger login-error" role="alert"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" class="login-form" novalidate>
            <?= csfr_input() ?>
            <div class="login-field">
                <label class="visually-hidden" for="identificador">Usuário</label>
                <i class="bi bi-person" aria-hidden="true"></i>
                <input type="text" id="identificador" name="identificador"
                    value="<?= e($identificador) ?>" placeholder="Usuário" required
                    autofocus autocomplete="username" inputmode="email">
            </div>
            <div class="login-field">
                <label class="visually-hidden" for="senha">Senha</label>
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="senha" name="senha" placeholder="Senha"
                    required autocomplete="current-password">
                <button class="password-toggle" type="button" data-password-toggle="senha"
                    aria-label="Mostrar senha"><i class="bi bi-eye-slash"></i></button>
            </div>
            <button type="submit" class="login-submit">Entrar</button>
        </form>

        <p class="login-footnote"><span></span>Acesse sua conta para explorar nossa biblioteca<span></span></p>
    </div>
</section>
