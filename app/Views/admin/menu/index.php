<?php

use App\Core\Csrf;

$pageTitle = 'Меню';
$activeNav = 'menu';
require __DIR__ . '/../layout/header.php';

/** @var array $items */
/** @var array $pages */
/** @var array $languages */

$urlTypeLabels = ['page' => 'Страница', 'news_index' => 'Раздел новостей', 'custom' => 'Произвольный URL'];
?>
<table class="data-table" style="margin-bottom:30px;">
    <thead>
        <tr><th>Пункт</th><th>Тип</th><th>Назначение</th><th>Язык</th><th>Активен</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="6" class="data-table__empty">Пунктов меню пока нет.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $index => $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($urlTypeLabels[$item['url_type']] ?? $item['url_type'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string) ($item['url_value'] ?? '—'), ENT_QUOTES) ?></td>
                <td><?= $item['lang'] === '' ? 'все' : htmlspecialchars($item['lang'], ENT_QUOTES) ?></td>
                <td style="text-align:center;"><?= $item['is_active'] ? '✓' : '—' ?></td>
                <td class="data-table__actions">
                    <form method="post" action="/admin/menu/<?= (int) $item['id'] ?>/move">
                        <?= Csrf::field() ?><input type="hidden" name="direction" value="up">
                        <button class="btn btn--small" <?= $index === 0 ? 'disabled' : '' ?>>&uarr;</button>
                    </form>
                    <form method="post" action="/admin/menu/<?= (int) $item['id'] ?>/move">
                        <?= Csrf::field() ?><input type="hidden" name="direction" value="down">
                        <button class="btn btn--small" <?= $index === count($items) - 1 ? 'disabled' : '' ?>>&darr;</button>
                    </form>
                    <form method="post" action="/admin/menu/<?= (int) $item['id'] ?>/delete" data-confirm="Удалить пункт меню?">
                        <?= Csrf::field() ?>
                        <button class="btn btn--small btn--danger">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="form-card">
    <h2 style="margin-top:0;">Добавить пункт меню</h2>
    <form method="post" action="/admin/menu/create" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="title">Название</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-field">
            <label for="url_type">Тип ссылки</label>
            <select id="url_type" name="url_type">
                <option value="page">Страница сайта</option>
                <option value="news_index">Раздел новостей</option>
                <option value="custom">Произвольный URL</option>
            </select>
        </div>
        <div class="form-field">
            <label for="url_value">Страница (для типа «Страница») или URL (для «Произвольный»)</label>
            <input type="text" id="url_value" name="url_value" list="page-slugs" placeholder="slug страницы или https://...">
            <datalist id="page-slugs">
                <?php foreach ($pages as $p): ?>
                    <option value="<?= htmlspecialchars($p['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </datalist>
            <span class="form-hint">Для «Страница» укажите её slug. Для «Раздел новостей» поле можно оставить пустым.</span>
        </div>
        <div class="form-field">
            <label for="lang">Язык</label>
            <select id="lang" name="lang">
                <option value="">Все языки</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?= htmlspecialchars($lang['code'], ENT_QUOTES) ?>"><?= htmlspecialchars($lang['name'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
            <label for="is_active">Активен</label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Добавить</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
