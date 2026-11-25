<?= partial('breadcrumb', ['itens' => [['titulo' => 'Início', 'url' => url('index.php')], ['titulo' => 'Alterar senha']]]) ?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Alterar senha</div>
      <div class="card-body">
        <form method="post">
          <?= csfr_input() ?>
          <div class="mb-3">
            <label class="form-label">Senha atual</label>
            <input class="form-control" type="password" name="senha_atual" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Nova senha</label>
            <input class="form-control" type="password" name="senha_nova" minlength="6" required autocomplete="new-password">
            <div class="form-text">Use pelo menos 6 caracteres.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmar nova senha</label>
            <input class="form-control" type="password" name="senha_confirmacao" minlength="6" required autocomplete="new-password">
          </div>
          <button class="btn btn-primary">Salvar nova senha</button>
        </form>
      </div>
    </div>
  </div>
</div>
