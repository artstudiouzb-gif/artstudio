<?php

use App\Core\Csrf;

$pageTitle = 'Меню';
$activeNav = 'menu';
require __DIR__ . '/../layout/header.php';

/** @var array $tree */
/** @var array $items */
/** @var array $pages */
/** @var array $languages */

$urlTypeLabels = ['page' => 'Страница', 'news_index' => 'Раздел новостей', 'custom' => 'Произвольный URL'];

/**
 * Встроенная форма редактирования пункта (раскрывается по клику).
 */
$renderEditForm = function (array $item) use ($languages): string {
    $id = (int) $item['id'];
    $isDivider = !empty($item['is_divider']);
    $out = '<form method="post" action="/admin/menu/' . $id . '/edit" class="menu-node__edit form-grid">' . Csrf::field();

    $out .= '<div class="form-field"><label>Название</label>'
        . '<input type="text" name="title" value="' . htmlspecialchars((string) $item['title'], ENT_QUOTES) . '"></div>';

    $out .= '<div class="form-field"><label>Тип ссылки</label><select name="url_type">';
    foreach (['page' => 'Страница сайта', 'news_index' => 'Раздел новостей', 'custom' => 'Произвольный URL'] as $v => $l) {
        $sel = ($item['url_type'] ?? 'custom') === $v ? ' selected' : '';
        $out .= '<option value="' . $v . '"' . $sel . '>' . $l . '</option>';
    }
    $out .= '</select></div>';

    $out .= '<div class="form-field"><label>Страница (slug) или URL</label>'
        . '<input type="text" name="url_value" value="' . htmlspecialchars((string) ($item['url_value'] ?? ''), ENT_QUOTES) . '" list="page-slugs"></div>';

    $out .= '<div class="form-field"><label>Язык</label><select name="lang">';
    $out .= '<option value=""' . ($item['lang'] === '' ? ' selected' : '') . '>Все языки</option>';
    foreach ($languages as $lang) {
        $sel = (string) $item['lang'] === (string) $lang['code'] ? ' selected' : '';
        $out .= '<option value="' . htmlspecialchars((string) $lang['code'], ENT_QUOTES) . '"' . $sel . '>'
            . htmlspecialchars((string) $lang['name'], ENT_QUOTES) . '</option>';
    }
    $out .= '</select></div>';

    $out .= '<div class="form-field"><label>SVG-иконка (необязательно)</label>'
        . '<textarea name="icon_svg" rows="3" placeholder="<svg viewBox=&quot;0 0 24 24&quot;>…</svg>">'
        . htmlspecialchars((string) ($item['icon_svg'] ?? ''), ENT_QUOTES) . '</textarea>'
        . '<span class="form-hint">Вставьте код SVG. Скрипты и внешние ссылки вырезаются автоматически.</span></div>';

    $out .= '<div class="form-field form-field--checkbox"><input type="checkbox" name="is_divider" value="1" id="div_' . $id . '"'
        . ($isDivider ? ' checked' : '') . '><label for="div_' . $id . '">Разделитель (черта без ссылки)</label></div>';

    $out .= '<div class="form-field form-field--checkbox"><input type="checkbox" name="is_active" value="1" id="act_' . $id . '"'
        . (!empty($item['is_active']) ? ' checked' : '') . '><label for="act_' . $id . '">Активен</label></div>';

    $out .= '<div class="form-actions"><button type="submit" class="btn btn--small btn--primary">Сохранить</button></div>';
    $out .= '</form>';

    return $out;
};

/**
 * Рендер одной строки пункта меню (переиспользуется для верхнего уровня и детей).
 */
$renderNode = function (array $item) use ($urlTypeLabels, $renderEditForm): string {
    $isDivider = !empty($item['is_divider']);
    $hasIcon = trim((string) ($item['icon_svg'] ?? '')) !== '';
    $title = $isDivider ? '— разделитель —' : htmlspecialchars($item['title'], ENT_QUOTES);
    $type = htmlspecialchars($urlTypeLabels[$item['url_type']] ?? $item['url_type'], ENT_QUOTES);
    $dest = htmlspecialchars((string) ($item['url_value'] ?? '—'), ENT_QUOTES);
    $lang = $item['lang'] === '' ? 'все' : htmlspecialchars($item['lang'], ENT_QUOTES);
    $active = $item['is_active'] ? '✓' : '—';
    $hasChildren = !empty($item['children']);
    $confirm = $hasChildren
        ? 'Удалить пункт вместе с вложенными? Дочерние пункты будут удалены безвозвратно.'
        : 'Удалить пункт меню?';

    $meta = $isDivider ? 'разделитель · ' . $lang . ' · ' . $active
        : $type . ' · ' . $dest . ' · ' . $lang . ' · ' . $active;

    $html = '<div class="menu-node__row">';
    $html .= '<span class="menu-node__handle" title="Перетащите для сортировки/вложенности" aria-hidden="true">⠿</span>';
    if ($hasIcon) {
        $html .= '<span class="menu-node__icon" aria-hidden="true">' . $item['icon_svg'] . '</span>';
    }
    $html .= '<span class="menu-node__title">' . $title . '</span>';
    $html .= '<span class="menu-node__meta">' . $meta . '</span>';
    $html .= '<span class="menu-node__actions">';
    $html .= '<details class="menu-node__editwrap"><summary class="btn btn--small">Изменить</summary>'
        . $renderEditForm($item) . '</details>';
    $html .= '<form method="post" action="/admin/menu/' . (int) $item['id'] . '/delete" data-confirm="'
        . htmlspecialchars($confirm, ENT_QUOTES) . '">' . Csrf::field()
        . '<button class="btn btn--small btn--danger">Удалить</button></form>';
    $html .= '</span></div>';

    return $html;
};
?>
<p class="admin-hint">
    Перетаскивайте пункты для сортировки. Чтобы создать выпадающее подменю,
    перетащите пункт «внутрь» другого (в его область с отступом). Глубина
    ограничена одним уровнем. Кнопка «Изменить» открывает поля пункта: SVG-иконку
    и признак разделителя.
</p>

<datalist id="page-slugs">
    <?php foreach ($pages as $p): ?>
        <option value="<?= htmlspecialchars($p['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
</datalist>

<ul class="menu-tree" data-menu-sortable data-csrf="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>" style="margin-bottom:30px;">
    <?php if (empty($tree)): ?>
        <li class="menu-tree__empty">Пунктов меню пока нет.</li>
    <?php endif; ?>
    <?php foreach ($tree as $node): ?>
        <li class="menu-node<?= !empty($node['is_divider']) ? ' menu-node--divider' : '' ?>" data-menu-id="<?= (int) $node['id'] ?>" draggable="true">
            <?= $renderNode($node) ?>
            <ul class="menu-node__children" data-menu-children>
                <?php foreach ($node['children'] ?? [] as $child): ?>
                    <li class="menu-node menu-node--child" data-menu-id="<?= (int) $child['id'] ?>" draggable="true">
                        <?= $renderNode($child) ?>
                        <ul class="menu-node__children" data-menu-children></ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
</ul>

<div class="form-card">
    <h2 style="margin-top:0;">Добавить пункт меню</h2>
    <form method="post" action="/admin/menu/create" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="title">Название</label>
            <input type="text" id="title" name="title">
        </div>
        <div class="form-field">
            <label for="icon_svg">SVG-иконка (необязательно)</label>
            <textarea id="icon_svg" name="icon_svg" rows="3" placeholder="<svg viewBox=&quot;0 0 24 24&quot;>…</svg>"></textarea>
            <span class="form-hint">Вставьте код SVG-иконки. Скрипты, обработчики событий и внешние ссылки вырезаются автоматически.</span>
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
        <div class="form-field">
            <label for="parent_id">Родительский пункт (для подменю)</label>
            <select id="parent_id" name="parent_id">
                <option value="">— верхний уровень —</option>
                <?php foreach ($tree as $node): // родителями могут быть только пункты верхнего уровня ?>
                    <?php if (!empty($node['is_divider'])) { continue; } ?>
                    <option value="<?= (int) $node['id'] ?>" data-lang="<?= htmlspecialchars((string) $node['lang'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars($node['title'], ENT_QUOTES) ?><?= $node['lang'] !== '' ? ' (' . htmlspecialchars($node['lang'], ENT_QUOTES) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">Вложенность — один уровень. Родитель и пункт должны быть на одном языке.</span>
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="is_divider" name="is_divider" value="1">
            <label for="is_divider">Разделитель (черта между пунктами, без ссылки)</label>
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
