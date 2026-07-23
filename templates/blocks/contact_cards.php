<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
?>
<div class="block-contact-cards">
    <?php if ($title !== ''): ?>
        <h2 class="block-contact-cards__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-contact-cards__empty">Контактные карточки ещё не добавлены.</p>
    <?php else: ?>
        <div class="contact-cards">
            <?php foreach ($items as $item): ?>
                <div class="contact-card">
                    <?php if (!empty($item['icon_svg'])): ?>
                        <span class="contact-card__icon" aria-hidden="true"><?= $item['icon_svg'] ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['title'])): ?>
                        <div class="contact-card__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></div>
                    <?php endif; ?>
                    <?php foreach (preg_split('/\R/', (string) ($item['lines'] ?? '')) ?: [] as $line): ?>
                        <?php $line = trim($line); if ($line === '') { continue; } ?>
                        <p class="contact-card__line"><?= htmlspecialchars($line, ENT_QUOTES) ?></p>
                    <?php endforeach; ?>
                    <?php $linkUrl = trim((string) ($item['link_url'] ?? '')); ?>
                    <?php if ($linkUrl !== '' && \App\Core\UrlGuard::isSafeLink($linkUrl) && !empty($item['link_text'])): ?>
                        <a class="contact-card__link" href="<?= htmlspecialchars($linkUrl, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $item['link_text'], ENT_QUOTES) ?> →</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
