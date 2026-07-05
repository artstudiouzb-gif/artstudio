<?php

use App\Core\Locale;

/** @var array $news */

$metaTitle = $news['meta_title'] ?: $news['title'];
$metaDescription = $news['meta_description'] ?: ($news['excerpt'] ?? '');
require __DIR__ . '/_header.php';
?>
<article class="news-single">
    <h1><?= htmlspecialchars($news['title'], ENT_QUOTES) ?></h1>
    <time><?= htmlspecialchars(substr((string) $news['published_at'], 0, 10), ENT_QUOTES) ?></time>
    <?php if (!empty($news['image'])): ?>
        <img src="<?= htmlspecialchars($news['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($news['title'], ENT_QUOTES) ?>">
    <?php endif; ?>
    <div class="news-single__content"><?= $news['content'] ?></div>
</article>
<p><a href="<?= htmlspecialchars(Locale::url('news'), ENT_QUOTES) ?>">&larr; Все новости</a></p>
<?php require __DIR__ . '/_footer.php'; ?>
