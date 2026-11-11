<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Empréstimos</h1>
    <p class="text-muted mb-0">Retiradas registradas e acompanhamento dos prazos.</p>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Registrar empréstimo</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csfr_input() ?>
      <input type="hidden" name="acao" value="registrar">
      <div class="col-md-5">
        <label class="form-label">Aluno</label>
        <select class="form-select" name="aluno_id" required>
          <option value="">Selecione...</option>
          <?php foreach ($alunos as $aluno): ?>
            <option value="<?= (int)$aluno['id'] ?>"><?= e($aluno['nome'] . ' · ' . $aluno['matricula']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Exemplar disponível</label>
        <select class="form-select" name="exemplar_id" required>
          <option value="">Selecione...</option>
          <?php foreach ($exemplares as $exemplar): ?>
            <option value="<?= (int)$exemplar['id'] ?>"><?= e($exemplar['titulo'] . ' · ' . $exemplar['tombo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid align-self-end">
        <button class="btn btn-primary" <?= (!$alunos || !$exemplares) ? 'disabled' : '' ?>>Confirmar</button>
      </div>
      <div class="col-12">
        <small class="text-muted">Prazo padrão de <?= (int)PRAZO_EMPRESTIMO_DIAS ?> dias. Limite de <?= (int)MAX_EMPRESTIMOS_SIMULTANEOS ?> empréstimos simultâneos por aluno.</small>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Buscar</label>
        <input type="search" name="q" class="form-control" value="<?= e($busca) ?>" placeholder="Aluno, obra ou tombo">
      </div>
      <div class="col-md-4">
        <label class="form-label">Situação</label>
        <select name="status" class="form-select" data-auto-submit>
          <?php foreach (['ativos' => 'Ativos', 'atrasados' => 'Em atraso', 'devolvidos' => 'Devolvidos', 'todos' => 'Todos'] as $valor => $rotulo): ?>
            <option value="<?= $valor ?>" <?= $status === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
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
  <div class="card-header fw-semibold">Empréstimos (<?= count($emprestimos) ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Aluno</th>
            <th>Obra / tombo</th>
            <th>Retirada</th>
            <th>Devolução</th>
            <th>Situação</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$emprestimos): ?>
          <tr><td colspan="6" class="empty-state">Nenhum empréstimo encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($emprestimos as $emprestimo): ?>
          <?php
          $atrasado = $emprestimo['data_devolucao'] === null && $emprestimo['data_prevista_devolucao'] < date('Y-m-d');
          $diasAtraso = $atrasado ? (int)((strtotime(date('Y-m-d')) - strtotime($emprestimo['data_prevista_devolucao'])) / 86400) : 0;
          ?>
          <tr>
            <td>
              <strong><?= e($emprestimo['aluno']) ?></strong><br>
              <small class="text-muted"><?= e($emprestimo['matricula']) ?></small>
            </td>
            <td>
              <?= e($emprestimo['obra_titulo']) ?><br>
              <small class="text-muted">Tombo: <code><?= e($emprestimo['tombo']) ?></code></small>
            </td>
            <td><?= e(format_date($emprestimo['data_retirada'])) ?></td>
            <td>
              <?= e(format_date($emprestimo['data_prevista_devolucao'])) ?>
              <?php if ((int)$emprestimo['renovado'] > 0): ?>
                <br><small class="text-muted">renovado</small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($emprestimo['data_devolucao'] !== null): ?>
                <?= status_badge('atendida') ?>
                <small class="text-muted">em <?= e(format_date($emprestimo['data_devolucao'])) ?></small>
              <?php elseif ($atrasado): ?>
                <span class="badge bg-danger"><?= $diasAtraso ?> dia(s) de atraso</span>
              <?php else: ?>
                <?= status_badge('emprestado') ?>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($emprestimo['data_devolucao'] === null): ?>
                <div class="btn-group btn-group-sm">
                  <?php if ((int)$emprestimo['renovado'] < MAX_RENOVACOES): ?>
                    <form method="post">
                      <?= csfr_input() ?>
                      <input type="hidden" name="acao" value="renovar">
                      <input type="hidden" name="emprestimo_id" value="<?= (int)$emprestimo['id'] ?>">
                      <button class="btn btn-outline-primary">Renovar</button>
                    </form>
                  <?php endif; ?>
                  <a href="<?= url('devolucoes.php') ?>" class="btn btn-outline-success">Devolver</a>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
