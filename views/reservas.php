<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Reservas</h1>
    <p class="text-muted mb-0">Reservas confirmadas, fila de espera e histórico.</p>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Buscar</label>
        <input type="search" name="q" class="form-control" value="<?= e($busca) ?>" placeholder="Aluno, matrícula ou obra">
      </div>
      <div class="col-md-4">
        <label class="form-label">Situação</label>
        <select name="status" class="form-select" data-auto-submit>
          <?php foreach (['ativas' => 'Ativas', 'confirmada' => 'Confirmadas', 'na_fila' => 'Fila de espera', 'pendente' => 'Pendentes', 'expirada' => 'Expiradas', 'cancelada' => 'Canceladas', 'atendida' => 'Atendidas', 'todas' => 'Todas'] as $valor => $rotulo): ?>
            <option value="<?= $valor ?>" <?= $status === $valor ? 'selected' : '' ?>>
              <?= $rotulo ?><?= isset($contagens[$valor]) ? ' (' . (int)$contagens[$valor] . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Reservas (<?= count($reservas) ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Aluno</th>
            <th>Obra</th>
            <th>Situação</th>
            <th>Retirada</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$reservas): ?>
          <tr><td colspan="5" class="empty-state">Nenhuma reserva encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($reservas as $reserva): ?>
          <tr>
            <td>
              <strong><?= e($reserva['aluno']) ?></strong><br>
              <small class="text-muted"><?= e($reserva['matricula']) ?><?= $reserva['turma'] ? ' · ' . e($reserva['turma']) : '' ?></small>
            </td>
            <td>
              <a href="<?= url('livro.php') ?>?id=<?= (int)$reserva['obra_id'] ?>"><?= e($reserva['obra_titulo']) ?></a>
              <?php if ($reserva['tombo']): ?>
                <br><small class="text-muted">Tombo: <code><?= e($reserva['tombo']) ?></code></small>
              <?php endif; ?>
            </td>
            <td>
              <?= status_badge($reserva['status']) ?>
              <?php if ($reserva['status'] === 'na_fila' && $reserva['posicao_fila']): ?>
                <span class="badge bg-light text-dark">posição <?= (int)$reserva['posicao_fila'] ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($reserva['data_expiracao']): ?>
                até <?= e(format_datetime($reserva['data_expiracao'])) ?>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if ($reserva['status'] === 'na_fila'): ?>
                  <form method="post">
                    <?= csfr_input() ?>
                    <input type="hidden" name="acao" value="confirmar">
                    <input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>">
                    <button class="btn btn-outline-primary">Confirmar</button>
                  </form>
                <?php endif; ?>
                <?php if (in_array($reserva['status'], ['pendente', 'confirmada', 'na_fila'], true)): ?>
                  <form method="post">
                    <?= csfr_input() ?>
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>">
                    <button class="btn btn-outline-danger">Cancelar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
