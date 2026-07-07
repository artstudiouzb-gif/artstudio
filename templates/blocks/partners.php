<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
?>
<div class="block-partners">
    <?php if ($title !== ''): ?><h2 class="block-partners__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if (!empty($items)): ?>
        <div class="block-partners__grid">
            <?php foreach ($items as $p): ?>
                <?php
                $logo = htmlspecialchars((string) ($p['logo'] ?? ''), ENT_QUOTES);
                $name = htmlspecialchars((string) ($p['name'] ?? ''), ENT_QUOTES);
                $url = (string) ($p['url'] ?? '');
                if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                    $url = '';
                }
                $img = '<img class="block-partners__logo" src="' . $logo . '" alt="' . $name . '" loading="lazy">';
                ?>
                <?php if ($url !== ''): ?>
                    <a class="block-partners__item" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" rel="noopener" title="<?= $name ?>"><?= $img ?></a>
                <?php else: ?>
                    <span class="block-partners__item" title="<?= $name ?>"><?= $img ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
