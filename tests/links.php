<?php
/**
 * Verificador de links internos.
 *
 * Percorre as views e os controllers procurando links gerados por url()
 * e confirma que cada página de destino existe em public/. Isso impede
 * que um item de menu aponte para uma página inexistente (erro 404).
 *
 * Execução: php tests/links.php
 */
declare(strict_types=1);

$repo = dirname(__DIR__);
$public = $repo . '/public';

$arquivos = array_merge(
    glob($repo . '/views/*.php') ?: [],
    glob($repo . '/views/partials/*.php') ?: [],
    glob($repo . '/public/*.php') ?: []
);

$alvos = [];
$porArquivo = [];

foreach ($arquivos as $arquivo) {
    $conteudo = file_get_contents($arquivo);

    // url('pagina.php') e url("pagina.php")
    if (preg_match_all("/url\(\s*['\"]([^'\"]+)['\"]\s*\)/", $conteudo, $matches)) {
        foreach ($matches[1] as $destino) {
            $destino = explode('?', $destino)[0];
            if ($destino === '') {
                continue;
            }
            $alvos[$destino] = true;
            $porArquivo[$arquivo][] = $destino;
        }
    }
}

$falhas = [];
foreach (array_keys($alvos) as $destino) {
    if (str_contains($destino, '/')) {
        // Caminhos com barra (por exemplo assets/css/style.css) são estáticos.
        $caminho = $public . '/' . ltrim($destino, '/');
        if (!file_exists($caminho)) {
            $falhas[] = $destino;
        }
        continue;
    }

    if (!file_exists($public . '/' . $destino)) {
        $falhas[] = $destino;
    }
}

// As páginas de navegação precisam existir para que o menu não quebre.
$paginasDeNavegacao = [
    'index.php', 'login.php', 'logout.php', 'catalogo.php', 'livro.php',
    'minhas-reservas.php', 'minha-fila.php', 'meus-emprestimos.php',
    'notificacoes.php', 'perfil.php', 'alterar-senha.php', 'renovar.php',
    'reservar.php', 'entrar-fila.php', 'cancelar-reserva.php', 'capa.php',
    'painel.php', 'acervo.php', 'alunos.php', 'reservas.php',
    'emprestimos.php', 'devolucoes.php', 'relatorios.php',
];

foreach ($paginasDeNavegacao as $pagina) {
    if (!file_exists($public . '/' . $pagina)) {
        $falhas[] = $pagina . ' (página de navegação)';
    }
}

if ($falhas) {
    echo "FAIL: links internos apontando para páginas inexistentes:\n";
    foreach (array_unique($falhas) as $falha) {
        echo "  - {$falha}\n";
    }
    echo "\nResultado: 0 aprovados; " . count(array_unique($falhas)) . " falharam.\n";
    exit(1);
}

echo "PASS: os " . count($alvos) . " destinos de link referenciados por url() existem em public/\n";
echo "PASS: as " . count($paginasDeNavegacao) . " páginas de navegação existem\n";
echo "\nResultado: 2 aprovados; 0 falharam.\n";
exit(0);
