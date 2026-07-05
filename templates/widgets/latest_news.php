<?php

use App\Core\Locale;

/** @var array $data */
/** @var string $lang */
$items = $data['items'] ?? [];
?>
<ul class="widget-latest-news">
    <?php foreach ($items as $item): ?>
        <li>
            <a href="<?= htmlspecialchars(Locale::url('news/' . $item['slug'], $lang), ENT_QUOTES) ?>">
                <?= htmlspecialchars($item['title'], ENT_QUOTES) ?>
            </a>
            <time><?= htmlspecialchars(substr((string) $item['published_at'], 0, 10), ENT_QUOTES) ?></time>
        </li>
    <?php endforeach; ?>
    <?php if (empty($items)): ?><li class="widget-empty">Нет новостей.</li><?php endif; ?>
</ul>
