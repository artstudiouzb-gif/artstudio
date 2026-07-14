<?php

$pageTitle = 'Дашборд';
$activeNav = 'dashboard';
require __DIR__ . '/layout/header.php';

/** @var array $user */
/** @var array $counts */
?>
<section class="admin-welcome" aria-labelledby="admin-welcome-title">
    <div>
        <h2 id="admin-welcome-title">Добро пожаловать, <?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?></h2>
        <p>Управляйте содержимым сайта и быстро переходите к основным действиям.</p>
    </div>
    <div class="admin-welcome__actions">
        <a href="/admin/news/create" class="btn btn--primary">Добавить новость</a>
        <a href="/admin/pages/create" class="btn">Добавить страницу</a>
        <a href="/" target="_blank" rel="noopener" class="btn">Открыть сайт</a>
    </div>
</section>

<div class="stat-grid">
    <a href="/admin/news" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['news'] ?></span>
        <span class="stat-card__label">Новостей</span>
    </a>
    <a href="/admin/pages" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['pages'] ?></span>
        <span class="stat-card__label">Страниц</span>
    </a>
    <a href="/admin/projects" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['projects'] ?></span>
        <span class="stat-card__label">Проектов</span>
    </a>
    <a href="/admin/team" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['team'] ?></span>
        <span class="stat-card__label">Сотрудников</span>
    </a>
    <a href="/admin/forms" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['forms'] ?></span>
        <span class="stat-card__label">Форм</span>
    </a>
    <a href="/admin/forms" class="stat-card<?= $counts['submissions_unread'] > 0 ? ' stat-card--highlight' : '' ?>">
        <span class="stat-card__value"><?= (int) $counts['submissions_unread'] ?></span>
        <span class="stat-card__label">Непрочитанных заявок</span>
    </a>
    <a href="/admin/files" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['files'] ?></span>
        <span class="stat-card__label">Файлов</span>
    </a>
</div>

<?php if (\App\Core\Auth::isSuperAdmin()): ?>
<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Демо-контент</h2>
    <p class="form-hint">Наполнить сайт примерами: оформленная главная (hero, счётчики, направления, проекты, новости, медиа) с демо-изображениями, новости, документы, вакансии, тендеры, руководство, типовые страницы и меню. Существующие записи не дублируются, отредактированную главную не трогает — нажимать повторно безопасно.</p>
    <form method="post" action="/admin/demo-content" data-confirm="Загрузить демо-контент в разделы сайта?">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn--primary">Загрузить демо-контент</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
