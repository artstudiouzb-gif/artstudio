<?php
/** @var array $data */
$items = $data['items'] ?? [];
?>
<ul class="widget-projects">
    <?php foreach ($items as $item): ?>
        <li>
            <?php if (!empty($item['cover_image'])): ?>
                <img src="<?= htmlspecialchars($item['cover_image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>" loading="lazy">
            <?php endif; ?>
            <span><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></span>
        </li>
    <?php endforeach; ?>
    <?php if (empty($items)): ?><li class="widget-empty">Нет проектов.</li><?php endif; ?>
</ul>
