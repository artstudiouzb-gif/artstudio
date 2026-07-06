<?php

use App\Core\Csrf;

$pageTitle = $type['name'] ?? 'Записи';
$activeNav = 'content:' . ($type['slug'] ?? '');
require __DIR__ . '/../layout/header.php';

/** @var array $type */
/** @var array $items */
?>
<div class="page-toolbar">
    <a href="/admin/content/<?= htmlspecialchars((string) $type['slug'], ENT_QUOTES) ?>/create" class="btn btn--primary">+ Добавить запись</a>
</div>

<table class="data-table">
    <thead><tr><th>Заголовок</th><th>Статус</th><th>Обновлено</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($items)): ?><tr><td colspan="4" class="data-table__empty">Записей пока нет.</td></tr><?php endif; ?>
        <?php foreach ($items as $e): ?>
            <tr>
                <td><?= htmlspecialchars((string) $e['title'], ENT_QUOTES) ?></td>
                <td><span class="badge badge--<?= $e['status'] ?>"><?= $e['status'] === 'published' ? 'Опубликовано' : 'Черновик' ?></span></td>
                <td><?= htmlspecialchars((string) $e['updated_at'], ENT_QUOTES) ?></td>
                <td class="data-table__actions">
                    <a class="btn btn--small" href="/admin/content/<?= htmlspecialchars((string) $type['slug'], ENT_QUOTES) ?>/<?= (int) $e['id'] ?>/edit">Редактировать</a>
                    <form method="post" action="/admin/content/<?= htmlspecialchars((string) $type['slug'], ENT_QUOTES) ?>/<?= (int) $e['id'] ?>/delete" data-confirm="Удалить запись?">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--small btn--danger">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
