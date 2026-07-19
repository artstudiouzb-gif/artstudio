<?php
/** @var array $data */
$title = $data['title'] ?? '';
$images = $data['images'] ?? [];
?>
<div class="block-gallery">
    <?php if ($title !== ''): ?><h2><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-gallery__grid">
        <?php foreach ($images as $image): ?>
            <a class="block-gallery__item" href="<?= htmlspecialchars($image['url'] ?? '#', ENT_QUOTES) ?>" target="_blank" rel="noopener">
                <?= \App\Core\Media::picture((string) ($image['url'] ?? ''), (string) ($image['caption'] ?? ''), null, null, '', true, '(max-width: 600px) 100vw, 25vw') ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
