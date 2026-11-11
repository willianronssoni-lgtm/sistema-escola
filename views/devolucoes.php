<?= partial('breadcrumb', ['itens' => [['titulo' => 'Início', 'url' => url('index.php')], ['titulo' => 'Biblioteca', 'url' => url('painel.php')], ['titulo' => 'Devoluções']]]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Devoluções</h1>
    <p class="text-muted mb-0">Registrar devoluções e acompanhar atrasos.</p>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Empréstimos em aberto (<?= count($ativos) ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Aluno</th>
            <th>Obra / tombo</th>
            <th>Previsto</th>
            <th>Situação</th>
            <th class="text-end">Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$ativos): ?>
          <tr><td colspan="5" class="empty-state">Nenhum empréstimo em aberto.</td></tr>
        <?php endif; ?>
        <?php foreach ($ativos as $emprestimo): ?>
          <?php $atrasado = (int)$emprestimo['dias_atraso'] > 0; ?>
          <tr>
            <td>
              <strong><?= e($emprestimo['aluno']) ?></strong><br>
              <small class="text-muted"><?= e($emprestimo['matricula']) ?><?= $emprestimo['turma'] ? ' · ' . e($emprestimo['turma']) : '' ?></small>
            </td>
            <td>
              <?= e($emprestimo['obra_titulo']) ?><br>
              <small class="text-muted">Tombo: <code><?= e($emprestimo['tombo']) ?></code></small>
            </td>
            <td><?= e(format_date($emprestimo['data_prevista_devolucao'])) ?></td>
            <td>
              <?php if ($atrasado): ?>
                <span class="badge bg-danger"><?= (int)$emprestimo['dias_atraso'] ?> dia(s) de atraso</span>
              <?php else: ?>
                <?= status_badge('emprestado') ?>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <form method="post">
                <?= csfr_input() ?>
                <input type="hidden" name="acao" value="devolver">
                <input type="hidden" name="emprestimo_id" value="<?= (int)$emprestimo['id'] ?>">
                <button class="btn btn-sm btn-outline-success">Registrar devolução</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Últimas devoluções</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Aluno</th>
            <th>Obra</th>
            <th>Devolvido em</th>
            <th>Atraso</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$historico): ?>
          <tr><td colspan="4" class="empty-state">Nenhuma devolução registrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($historico as $devolucao): ?>
          <tr>
            <td><?= e($devolucao['aluno']) ?></td>
            <td><?= e($devolucao['obra_titulo']) ?> <small class="text-muted">(<code><?= e($devolucao['tombo']) ?></code>)</small></td>
            <td><?= e(format_date($devolucao['data_devolucao'])) ?></td>
            <td>
              <?php if ((int)$devolucao['dias_atraso'] > 0): ?>
                <span class="badge bg-danger"><?= (int)$devolucao['dias_atraso'] ?> dia(s)</span>
              <?php else: ?>
                <span class="badge bg-success">no prazo</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
