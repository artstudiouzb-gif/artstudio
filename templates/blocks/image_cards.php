<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];
?>
<div class="block-imgcards">
    <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
        <div class="section-head">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
            <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-imgcards__empty">Карточки ещё не добавлены.</p>
    <?php else: ?>
        <div class="imgcards-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $url = trim((string) ($item['url'] ?? ''));
                $img = trim((string) ($item['image'] ?? ''));
                $tag = $url !== '' ? 'a' : 'div';
                ?>
                <<?= $tag ?> class="imgcard"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                    <span class="imgcard__media"<?= $img !== '' ? ' style="background-image:url(\'' . htmlspecialchars($img, ENT_QUOTES) . '\')"' : '' ?>></span>
                    <span class="imgcard__overlay"></span>
                    <span class="imgcard__body">
                        <span class="imgcard__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                        <?php if ($url !== ''): ?><span class="imgcard__more">Подробнее →</span><?php endif; ?>
                    </span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
