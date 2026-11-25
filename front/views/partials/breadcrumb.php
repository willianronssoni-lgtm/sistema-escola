<?php
/**
 * Trilha de navegação. Use com:
 *   partial('breadcrumb', ['itens' => [['titulo' => 'Painel', 'url' => url('painel.php')]]]);
 */
$itens = $itens ?? [];
?>
<nav aria-label="trilha de navegação">
    <ol class="breadcrumb small">
        <?php foreach ($itens as $item): ?>
            <?php if (!empty($item['url'])): ?>
                <li class="breadcrumb-item"><a href="<?= e($item['url']) ?>"><?= e($item['titulo']) ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($item['titulo']) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
