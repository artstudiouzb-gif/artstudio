<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];
?>
<div class="block-mediagallery">
    <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
        <div class="section-head">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
            <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-mediagallery__empty">Материалы ещё не добавлены.</p>
    <?php else: ?>
        <div class="mediagallery-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $url = trim((string) ($item['url'] ?? ''));
                $img = trim((string) ($item['image'] ?? ''));
                $tag = $url !== '' ? 'a' : 'div';
                $duration = trim((string) ($item['meta'] ?? ''));
                ?>
                <<?= $tag ?> class="mediacard"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                    <span class="mediacard__media"<?= $img !== '' ? ' style="background-image:url(\'' . htmlspecialchars($img, ENT_QUOTES) . '\')"' : '' ?>>
                        <span class="mediacard__play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                        <?php if ($duration !== ''): ?><span class="mediacard__duration"><?= htmlspecialchars($duration, ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                    <span class="mediacard__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                    <?php if (!empty($item['text'])): ?><span class="mediacard__date"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
