<?= partial('breadcrumb', ['itens' => [['titulo' => 'Início', 'url' => url('index.php')], ['titulo' => 'Meu perfil']]]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Meu perfil</h1>
    <p class="text-muted mb-0">Dados de acesso e movimentações da sua conta.</p>
  </div>
  <a href="<?= url('alterar-senha.php') ?>" class="btn btn-outline-primary"><i class="bi bi-key me-1"></i>Alterar senha</a>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Dados cadastrais</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Matrícula</dt>
          <dd class="col-sm-8"><?= e($dados['matricula'] ?? '-') ?></dd>

          <dt class="col-sm-4">Nome</dt>
          <dd class="col-sm-8"><?= e($dados['nome'] ?? '-') ?></dd>

          <dt class="col-sm-4">E-mail</dt>
          <dd class="col-sm-8"><?= e($dados['email'] ?? '-') ?></dd>

          <dt class="col-sm-4">Turma</dt>
          <dd class="col-sm-8"><?= e($dados['turma'] ?? '-') ?></dd>

          <dt class="col-sm-4">Perfil</dt>
          <dd class="col-sm-8"><?= ($dados['tipo'] ?? '') === 'bibliotecario' ? 'Bibliotecário' : 'Aluno' ?></dd>

          <dt class="col-sm-4">Conta desde</dt>
          <dd class="col-sm-8"><?= e(format_date($dados['criado_em'] ?? null)) ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Situação atual</div>
      <div class="card-body">
        <div class="row g-3 text-center">
          <div class="col-4">
            <div class="display-6 fw-semibold"><?= (int)$emprestimos_ativos ?></div>
            <div class="text-muted small">Empréstimos ativos</div>
          </div>
          <div class="col-4">
            <div class="display-6 fw-semibold"><?= (int)$reservas_ativas ?></div>
            <div class="text-muted small">Reservas ativas</div>
          </div>
          <div class="col-4">
            <div class="display-6 fw-semibold"><?= (int)MAX_EMPRESTIMOS_SIMULTANEOS ?></div>
            <div class="text-muted small">Limite de empréstimos</div>
          </div>
        </div>

        <?php if (!empty($dados['suspenso_ate']) && $dados['suspenso_ate'] >= date('Y-m-d')): ?>
          <div class="alert alert-warning mt-3 mb-0">
            Sua conta está suspensa até <strong><?= e(format_date($dados['suspenso_ate'])) ?></strong>.
          </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
          <a href="<?= url('meus-emprestimos.php') ?>" class="btn btn-outline-secondary">Meus empréstimos</a>
          <a href="<?= url('minhas-reservas.php') ?>" class="btn btn-outline-secondary">Minhas reservas</a>
        </div>
      </div>
    </div>
  </div>
</div>
