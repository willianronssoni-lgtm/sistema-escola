<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div><h1 class="h3 mb-1">Painel do bibliotecário</h1><p class="text-muted mb-0">Visão geral e operações da biblioteca escolar.</p></div>
  <span class="badge text-bg-success">Área administrativa</span>
</div>
<div class="row g-3 mb-4">
<?php foreach (['alunos'=>'Alunos ativos','obras'=>'Títulos no acervo','disponiveis'=>'Exemplares disponíveis','ativos'=>'Empréstimos ativos'] as $key=>$label): ?>
  <div class="col-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small"><?= e($label) ?></div><div class="display-6 fw-semibold"><?= (int)$stats[$key] ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-4">
 <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Cadastrar aluno</div><div class="card-body">
  <form method="post"><?= csfr_input() ?><input type="hidden" name="acao" value="aluno">
   <div class="mb-2"><label class="form-label">Matrícula</label><input class="form-control" name="matricula" maxlength="20" required></div>
   <div class="mb-2"><label class="form-label">Nome completo</label><input class="form-control" name="nome" maxlength="120" required></div>
   <div class="mb-2"><label class="form-label">Turma</label><input class="form-control" name="turma" maxlength="20"></div>
   <div class="mb-2"><label class="form-label">E-mail (opcional)</label><input class="form-control" type="email" name="email" maxlength="150"></div>
   <div class="mb-3"><label class="form-label">Senha inicial</label><input class="form-control" type="password" name="senha" minlength="6" required></div>
   <button class="btn btn-primary">Cadastrar aluno</button>
  </form>
 </div></div></div>
 <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Adicionar livro e exemplar</div><div class="card-body">
  <form method="post"><?= csfr_input() ?><input type="hidden" name="acao" value="livro">
   <div class="mb-2"><label class="form-label">Título</label><input class="form-control" name="titulo" maxlength="200" required></div>
   <div class="mb-2"><label class="form-label">Autor</label><input class="form-control" name="autor" maxlength="150" required></div>
   <div class="mb-2"><label class="form-label">Categoria</label><input class="form-control" name="categoria" maxlength="80"></div>
   <div class="row"><div class="col-md-6 mb-2"><label class="form-label">ISBN</label><input class="form-control" name="isbn" maxlength="20"></div><div class="col-md-6 mb-2"><label class="form-label">Ano</label><input class="form-control" type="number" name="ano" min="1000" max="2155"></div></div>
   <div class="mb-2"><label class="form-label">Editora</label><input class="form-control" name="editora" maxlength="100"></div>
   <div class="mb-3"><label class="form-label">Localização</label><input class="form-control" name="localizacao" maxlength="80" placeholder="Ex.: Estante A1"></div>
   <div class="mb-3"><label class="form-label">Tombo do exemplar</label><input class="form-control" name="tombo" maxlength="30" required></div>
   <button class="btn btn-primary">Adicionar ao acervo</button>
  </form>
 </div></div></div>
 <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Registrar empréstimo</div><div class="card-body">
  <form method="post"><?= csfr_input() ?><input type="hidden" name="acao" value="emprestimo">
   <div class="mb-3"><label class="form-label">Aluno</label><select class="form-select" name="aluno_id" required><option value="">Selecione...</option><?php foreach($alunos as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome'].' · '.$a['matricula']) ?></option><?php endforeach; ?></select></div>
   <div class="mb-3"><label class="form-label">Exemplar disponível</label><select class="form-select" name="exemplar_id" required><option value="">Selecione...</option><?php foreach($exemplares as $x): ?><option value="<?= (int)$x['id'] ?>"><?= e($x['titulo'].' · '.$x['tombo']) ?></option><?php endforeach; ?></select></div>
   <button class="btn btn-primary" <?= (!$alunos || !$exemplares) ? 'disabled' : '' ?>>Confirmar empréstimo</button>
   <small class="d-block text-muted mt-2">Prazo padrão: <?= (int)PRAZO_EMPRESTIMO_DIAS ?> dias.</small>
  </form>
 </div></div></div>
 <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-semibold">Registrar devolução</div><div class="card-body">
  <?php if (!$ativos): ?><p class="text-muted mb-0">Não há empréstimos ativos.</p><?php else: ?>
  <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Aluno / livro</th><th>Devolução</th><th></th></tr></thead><tbody>
  <?php foreach($ativos as $emp): ?><tr><td><strong><?= e($emp['aluno']) ?></strong><br><small><?= e($emp['titulo'].' · '.$emp['tombo']) ?></small></td><td><?= e(format_date($emp['data_prevista_devolucao'])) ?></td><td><form method="post"><?= csfr_input() ?><input type="hidden" name="acao" value="devolucao"><input type="hidden" name="emprestimo_id" value="<?= (int)$emp['id'] ?>"><button class="btn btn-sm btn-outline-success">Devolver</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
 </div></div></div>
</div>
<div class="card shadow-sm mt-4"><div class="card-header fw-semibold">Livros adicionados recentemente</div><div class="card-body"><div class="row g-3"><?php foreach($ultimos as $livro): ?><div class="col-md-6 col-xl-3"><div class="border rounded p-3 h-100"><strong><?= e($livro['titulo']) ?></strong><div class="small text-muted"><?= e($livro['autor']) ?></div></div></div><?php endforeach; ?></div></div></div>
