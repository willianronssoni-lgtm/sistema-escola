<?= partial('breadcrumb', ['itens' => [['titulo' => 'Início', 'url' => url('index.php')], ['titulo' => 'Minha fila']]]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><i class="bi bi-people me-2"></i>Minha fila de espera</h1>
    <p class="text-muted mb-0">Acompanhe a sua posição nas obras sem exemplar disponível.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('minhas-reservas.php') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-bookmark-check me-1"></i>Minhas reservas
    </a>
    <a href="<?= url('catalogo.php') ?>" class="btn btn-outline-primary">
      <i class="bi bi-plus me-1"></i>Buscar livros
    </a>
  </div>
</div>

<?php if (empty($fila)): ?>

  <div class="card shadow-sm">
    <div class="card-body empty-state">
      <i class="bi bi-check2-circle fs-1 text-success"></i>
      <h4 class="mt-3">Você não está em nenhuma fila</h4>
      <p class="text-muted">Quando uma obra estiver esgotada, entre na fila de espera pela página do livro.</p>
      <a href="<?= url('catalogo.php') ?>" class="btn btn-primary">Explorar catálogo</a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($fila as $item): ?>
      <?php $posicao = (int)$item['posicao_fila'] + (int)$item['pessoas_a_frente']; ?>
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="row align-items-center g-3">
              <div class="col-auto">
                <div class="text-center">
                  <div class="display-6 fw-bold text-primary">#<?= $posicao ?></div>
                  <small class="text-muted">posição</small>
                </div>
              </div>
              <div class="col">
                <h6 class="mb-1">
                  <a href="<?= url('livro.php') ?>?id=<?= (int)$item['obra_id'] ?>" class="text-decoration-none text-dark">
                    <?= e($item['titulo']) ?>
                  </a>
                </h6>
                <small class="text-muted"><?= e($item['autor']) ?></small>
                <div class="small text-muted mt-1">
                  <i class="bi bi-clock me-1"></i>Na fila desde <?= e(format_datetime($item['data_reserva'])) ?>
                  <?php if ((int)$item['pessoas_a_frente'] > 0): ?>
                    · <?= (int)$item['pessoas_a_frente'] ?> pessoa(s) na frente
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-auto text-end">
                <?= status_badge('na_fila') ?>
                <form method="post" action="<?= url('cancelar-reserva.php') ?>" class="mt-2" onsubmit="return confirm('Sair da fila de espera?')">
                  <?= csfr_input() ?>
                  <input type="hidden" name="reserva_id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i>Sair da fila
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="alert alert-info mt-4">
    <i class="bi bi-info-circle me-2"></i>
    Quando um exemplar for devolvido, a biblioteca confirma a reserva do primeiro da fila e você
    recebe uma notificação com o prazo de <?= (int)PRAZO_RESERVA_HORAS ?> horas para retirar o livro.
  </div>
<?php endif; ?>
