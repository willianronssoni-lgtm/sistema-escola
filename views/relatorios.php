<?= partial('breadcrumb', ['itens' => [['titulo' => 'Início', 'url' => url('index.php')], ['titulo' => 'Biblioteca', 'url' => url('painel.php')], ['titulo' => 'Relatórios']]]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Relatórios</h1>
    <p class="text-muted mb-0">Indicadores de uso, atraso e movimentação do acervo.</p>
  </div>
  <button class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
</div>

<div class="row g-3 mb-4">
<?php
$cards = [
    'alunos' => 'Alunos cadastrados',
    'obras' => 'Títulos no acervo',
    'exemplares' => 'Exemplares',
    'disponiveis' => 'Exemplares disponíveis',
    'emprestados' => 'Exemplares emprestados',
    'reservados' => 'Exemplares reservados',
    'manutencao' => 'Em manutenção',
    'emprestimos_ativos' => 'Empréstimos ativos',
    'atrasados' => 'Empréstimos em atraso',
    'fila_espera' => 'Pessoas na fila',
    'suspensos' => 'Alunos suspensos',
];
foreach ($cards as $chave => $rotulo): ?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small"><?= e($rotulo) ?></div>
        <div class="display-6 fw-semibold"><?= (int)$totais[$chave] ?></div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Acervo por categoria</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Categoria</th><th>Obras</th><th class="text-end">Exemplares</th></tr></thead>
          <tbody>
          <?php foreach ($porCategoria as $linha): ?>
            <tr>
              <td><?= e($linha['categoria']) ?></td>
              <td><?= (int)$linha['obras'] ?></td>
              <td class="text-end"><?= (int)$linha['exemplares'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Obras mais emprestadas</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Obra</th><th class="text-end">Empréstimos</th></tr></thead>
          <tbody>
          <?php foreach ($maisEmprestados as $linha): ?>
            <tr>
              <td><?= e($linha['titulo']) ?><br><small class="text-muted"><?= e($linha['autor']) ?></small></td>
              <td class="text-end"><?= (int)$linha['total'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Movimentação nos últimos 6 meses</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Mês</th><th class="text-end">Retiradas</th><th class="text-end">Devoluções</th></tr></thead>
          <tbody>
          <?php
          $devolucoesPorMes = [];
          foreach ($devolucoes as $linha) {
              $devolucoesPorMes[$linha['mes']] = $linha;
          }
          foreach ($porMes as $linha):
              $dev = $devolucoesPorMes[$linha['mes']] ?? null;
          ?>
            <tr>
              <td><?= e($linha['mes']) ?></td>
              <td class="text-end"><?= (int)$linha['retiradas'] ?></td>
              <td class="text-end"><?= $dev ? (int)$dev['devolucoes'] : 0 ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header fw-semibold">Empréstimos em atraso</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Aluno</th><th>Obra</th><th class="text-end">Dias</th></tr></thead>
          <tbody>
          <?php if (!$atrasados): ?>
            <tr><td colspan="3" class="empty-state">Nenhum empréstimo em atraso.</td></tr>
          <?php endif; ?>
          <?php foreach ($atrasados as $linha): ?>
            <tr>
              <td><?= e($linha['aluno']) ?><br><small class="text-muted"><?= e($linha['matricula']) ?></small></td>
              <td><?= e($linha['obra_titulo']) ?></td>
              <td class="text-end"><span class="badge bg-danger"><?= (int)$linha['dias_atraso'] ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
