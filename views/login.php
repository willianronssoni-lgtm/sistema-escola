<?php
$titulo = $titulo ?? 'Login';
$erro = $erro ?? '';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-box-arrow-in-right me-2"></i>Entrar</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= e($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" novalidate>
                    <?= csfr_input() ?>
                    
                    <div class="mb-3">
                        <label for="matricula" class="form-label fw-semibold">Matrícula</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" 
                               placeholder="Ex: 2024001 ou BIB001" required autofocus autocomplete="username">
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label fw-semibold">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               placeholder="Sua senha" required autocomplete="current-password">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center text-muted small">
                    <p class="mb-1"><strong>Credenciais de teste:</strong></p>
                    <p class="mb-1"><code>BIB001</code> / <code>123456</code> (Bibliotecário)</p>
                    <p class="mb-0"><code>2024001</code> a <code>2024015</code> / <code>123456</code> (Alunos)</p>
                </div>
            </div>
        </div>
    </div>
</div>