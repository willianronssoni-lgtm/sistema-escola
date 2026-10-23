<?php
/**
 * Renderizador de views simples
 */

declare(strict_types=1);

function view(string $arquivo, array $dados = []): void {
    extract($dados);
    
    ob_start();
    require VIEWS_PATH . "/{$arquivo}.php";
    $content = ob_get_clean();
    
    require VIEWS_PATH . '/layout.php';
}

function partial(string $arquivo, array $dados = []): string {
    extract($dados);
    ob_start();
    require VIEWS_PATH . "/partials/{$arquivo}.php";
    return ob_get_clean();
}