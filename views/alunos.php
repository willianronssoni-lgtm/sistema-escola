<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Alunos</h1>
    <p class="text-muted mb-0">Cadastro, situação e movimentações dos alunos.</p>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Buscar</label>
        <input type="search" name="q" class="form-control" value="<?= e($busca) ?>" placeholder="Nome, matrícula ou turma">
      </div>
      <div class="col-md-4">
        <label class="form-label">Situação</label>
        <select name="status" class="form-select" data-auto-submit>
          <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'suspensos' => 'Suspensos', 'todos' => 'Todos'] as $valor => $rotulo): ?>
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

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Cadastrar aluno</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?= csfr_input() ?>
      <input type="hidden" name="acao" value="criar">
      <div class="col-md-3">
        <label class="form-label">Matrícula *</label>
        <input class="form-control" name="matricula" maxlength="20" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nome completo *</label>
        <input class="form-control" name="nome" maxlength="120" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Turma</label>
        <input class="form-control" name="turma" maxlength="20">
      </div>
      <div class="col-md-2">
        <label class="form-label">Senha inicial *</label>
        <input class="form-control" type="password" name="senha" minlength="6" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">E-mail</label>
        <input class="form-control" type="email" name="email" maxlength="150">
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Cadastrar aluno</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Alunos encontrados (<?= count($alunos) ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Aluno</th>
            <th>Turma</th>
            <th>Empréstimos</th>
            <th>Reservas</th>
            <th>Situação</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$alunos): ?>
          <tr><td colspan="6" class="empty-state">Nenhum aluno encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($alunos as $aluno): ?>
          <?php $suspenso = $aluno['suspenso_ate'] && $aluno['suspenso_ate'] >= date('Y-m-d'); ?>
          <tr>
            <td>
              <strong><?= e($aluno['nome']) ?></strong><br>
              <small class="text-muted"><?= e($aluno['matricula']) ?><?= $aluno['email'] ? ' · ' . e($aluno['email']) : '' ?></small>
            </td>
            <td><?= e($aluno['turma'] ?: '-') ?></td>
            <td>
              <?= (int)$aluno['emprestimos_ativos'] ?> ativos
              <?php if ((int)$aluno['emprestimos_atrasados'] > 0): ?>
                <span class="badge bg-danger"><?= (int)$aluno['emprestimos_atrasados'] ?> em atraso</span>
              <?php endif; ?>
            </td>
            <td><?= (int)$aluno['reservas_ativas'] ?></td>
            <td>
              <?php if (!$aluno['ativo']): ?>
                <span class="badge bg-secondary">Inativo</span>
              <?php elseif ($suspenso): ?>
                <span class="badge bg-warning text-dark">Suspenso até <?= e(format_date($aluno['suspenso_ate'])) ?></span>
              <?php else: ?>
                <span class="badge bg-success">Ativo</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editarAluno<?= (int)$aluno['id'] ?>">Editar</button>
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#senhaAluno<?= (int)$aluno['id'] ?>">Senha</button>
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#suspenderAluno<?= (int)$aluno['id'] ?>">Suspender</button>
              </div>
            </td>
          </tr>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

          <?php foreach ($alunos as $aluno): ?>
<div class="modal fade" id="editarAluno<?= (int)$aluno['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <?= csfr_input() ?>
                  <input type="hidden" name="acao" value="atualizar">
                  <input type="hidden" name="aluno_id" value="<?= (int)$aluno['id'] ?>">
                  <div class="modal-header">
                    <h5 class="modal-title">Editar <?= e($aluno['nome']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-2">
                      <label class="form-label">Nome</label>
                      <input class="form-control" name="nome" maxlength="120" value="<?= e($aluno['nome']) ?>">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">E-mail</label>
                      <input class="form-control" type="email" name="email" maxlength="150" value="<?= e($aluno['email'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Turma</label>
                      <input class="form-control" name="turma" maxlength="20" value="<?= e($aluno['turma'] ?? '') ?>">
                    </div>
                    <div class="mb-2 form-check">
                      <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo<?= (int)$aluno['id'] ?>" <?= $aluno['ativo'] ? 'checked' : '' ?>>
                      <label class="form-check-label" for="ativo<?= (int)$aluno['id'] ?>">Aluno ativo</label>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Salvar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="modal fade" id="senhaAluno<?= (int)$aluno['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <?= csfr_input() ?>
                  <input type="hidden" name="acao" value="redefinir_senha">
                  <input type="hidden" name="aluno_id" value="<?= (int)$aluno['id'] ?>">
                  <div class="modal-header">
                    <h5 class="modal-title">Redefinir senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <label class="form-label">Nova senha (mínimo 6 caracteres)</label>
                    <input class="form-control" type="password" name="nova_senha" minlength="6" required>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Redefinir</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="modal fade" id="suspenderAluno<?= (int)$aluno['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <?= csfr_input() ?>
                  <input type="hidden" name="acao" value="suspender">
                  <input type="hidden" name="aluno_id" value="<?= (int)$aluno['id'] ?>">
                  <div class="modal-header">
                    <h5 class="modal-title">Suspender <?= e($aluno['nome']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <label class="form-label">Dias de suspensão (0 remove a suspensão)</label>
                    <input class="form-control" type="number" name="dias" min="0" max="365" value="0">
                    <?php if ($suspenso): ?>
                      <p class="form-text">Suspensão atual: até <?= e(format_date($aluno['suspenso_ate'])) ?>.</p>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-warning">Aplicar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
<?php endforeach; ?>
