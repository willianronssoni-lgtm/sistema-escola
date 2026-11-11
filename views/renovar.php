<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Renovar empréstimo</div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-sm-4">Obra</dt>
          <dd class="col-sm-8"><?= e($emprestimo['titulo']) ?></dd>

          <dt class="col-sm-4">Tombo</dt>
          <dd class="col-sm-8"><code><?= e($emprestimo['tombo']) ?></code></dd>

          <dt class="col-sm-4">Retirada</dt>
          <dd class="col-sm-8"><?= e(format_date($emprestimo['data_retirada'])) ?></dd>

          <dt class="col-sm-4">Devolução atual</dt>
          <dd class="col-sm-8"><?= e(format_date($emprestimo['data_prevista_devolucao'])) ?></dd>
        </dl>

        <div class="alert alert-info">
          A renovação estende a devolução em <?= (int)PRAZO_RENOVACAO_DIAS ?> dias.
          O limite é de <?= (int)MAX_RENOVACOES ?> renovação por empréstimo.
        </div>

        <form method="post" data-confirm="Confirmar a renovação deste empréstimo?">
          <?= csfr_input() ?>
          <input type="hidden" name="emprestimo_id" value="<?= (int)$emprestimo['id'] ?>">
          <div class="d-flex gap-2">
            <button class="btn btn-primary">Renovar</button>
            <a href="<?= url('meus-emprestimos.php') ?>" class="btn btn-outline-secondary">Voltar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
