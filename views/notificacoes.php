<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Notificações</h1>
    <p class="text-muted mb-0">Avisos sobre reservas, empréstimos e suspensões.</p>
  </div>
  <?php if ($notificacoes): ?>
    <form method="post">
      <?= csfr_input() ?>
      <input type="hidden" name="acao" value="marcar_todas">
      <button class="btn btn-outline-secondary">Marcar todas como lidas</button>
    </form>
  <?php endif; ?>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php if (!$notificacoes): ?>
        <div class="empty-state">Nenhuma notificação por aqui.</div>
      <?php endif; ?>
      <?php foreach ($notificacoes as $notificacao): ?>
        <div class="list-group-item <?= $notificacao['lida'] ? '' : 'bg-light' ?>">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <strong><?= e($notificacao['titulo']) ?></strong>
              <p class="mb-1"><?= e($notificacao['mensagem']) ?></p>
              <small class="text-muted"><?= e(format_datetime($notificacao['criada_em'])) ?></small>
              <?php if ($notificacao['link']): ?>
                <a href="<?= e($notificacao['link']) ?>" class="small ms-2">Abrir</a>
              <?php endif; ?>
            </div>
            <?php if (!$notificacao['lida']): ?>
              <form method="post">
                <?= csfr_input() ?>
                <input type="hidden" name="acao" value="marcar">
                <input type="hidden" name="id" value="<?= (int)$notificacao['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary">Marcar como lida</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
