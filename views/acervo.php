<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Acervo</h1>
    <p class="text-muted mb-0">Obras, exemplares e situação de cada item.</p>
  </div>
  <a href="<?= url('painel.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Painel</a>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Buscar</label>
        <input type="search" name="q" class="form-control" value="<?= e($busca) ?>" placeholder="Título, autor ou ISBN">
      </div>
      <div class="col-md-4">
        <label class="form-label">Categoria</label>
        <select name="categoria" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Nova obra</div>
  <div class="card-body">
    <form method="post">
      <?= csfr_input() ?>
      <input type="hidden" name="acao" value="obra">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Título *</label>
          <input class="form-control" name="titulo" maxlength="200" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Autor *</label>
          <input class="form-control" name="autor" maxlength="150" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Categoria</label>
          <input class="form-control" name="categoria" maxlength="80">
        </div>
        <div class="col-md-4">
          <label class="form-label">Editora</label>
          <input class="form-control" name="editora" maxlength="100">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ano</label>
          <input class="form-control" type="number" name="ano" min="1000" max="<?= (int)date('Y') + 1 ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">ISBN</label>
          <input class="form-control" name="isbn" maxlength="20">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tombo do primeiro exemplar</label>
          <input class="form-control" name="tombo" maxlength="30">
        </div>
        <div class="col-md-4">
          <label class="form-label">Localização</label>
          <input class="form-control" name="localizacao" maxlength="80" placeholder="Ex.: Estante A1">
        </div>
        <div class="col-12">
          <label class="form-label">Sinopse</label>
          <textarea class="form-control" name="sinopse" rows="2"></textarea>
        </div>
      </div>
      <button class="btn btn-primary mt-3">Cadastrar obra</button>
    </form>
  </div>
</div>

<?php if (!$obras): ?>
  <div class="card shadow-sm"><div class="card-body empty-state">Nenhuma obra encontrada com os filtros informados.</div></div>
<?php else: ?>
  <div class="card shadow-sm">
    <div class="card-header fw-semibold">Obras cadastradas (<?= count($obras) ?>)</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Obra</th>
              <th>Categoria</th>
              <th>Exemplares</th>
              <th>Disponíveis</th>
              <th>Situação</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($obras as $obra): ?>
            <?php $exemplaresDaObra = $exemplares[$obra['id']] ?? []; ?>
            <tr>
              <td>
                <strong><?= e($obra['titulo']) ?></strong><br>
                <small class="text-muted"><?= e($obra['autor']) ?></small>
              </td>
              <td><?= e($obra['categoria'] ?: '-') ?></td>
              <td>
                <?php if (!$exemplaresDaObra): ?>
                  <span class="text-muted">sem exemplares</span>
                <?php else: ?>
                  <div class="d-flex flex-column gap-1">
                    <?php foreach ($exemplaresDaObra as $ex): ?>
                      <form method="post" class="row g-1 align-items-center">
                        <?= csfr_input() ?>
                        <input type="hidden" name="acao" value="exemplar">
                        <input type="hidden" name="exemplar_id" value="<?= (int)$ex['id'] ?>">
                        <div class="col-auto"><code><?= e($ex['tombo']) ?></code></div>
                        <div class="col-auto">
                          <select name="status" class="form-select form-select-sm" style="width: auto">
                            <?php foreach (['disponivel' => 'Disponível', 'emprestado' => 'Emprestado', 'reservado' => 'Reservado', 'manutencao' => 'Manutenção'] as $valor => $rotulo): ?>
                              <option value="<?= $valor ?>" <?= $ex['status'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-auto">
                          <input type="text" name="localizacao" class="form-control form-control-sm" style="width: 9rem"
                                 value="<?= e($ex['localizacao'] ?? '') ?>" placeholder="Localização">
                        </div>
                        <div class="col-auto">
                          <button class="btn btn-sm btn-outline-primary">Salvar</button>
                        </div>
                      </form>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= (int)($obra['disponiveis'] ?? 0) ?></td>
              <td>
                <a href="<?= url('livro.php') ?>?id=<?= (int)$obra['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
