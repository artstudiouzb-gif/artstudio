<?php

use App\Core\Csrf;
use App\Models\Language;

$isEdit = !empty($news['id']);
$pageTitle = $isEdit ? 'Редактирование новости' : 'Новая новость';
$activeNav = 'news';
require __DIR__ . '/../layout/header.php';

/** @var array|null $news */
/** @var array $translations */
/** @var string|null $error */

$action = $isEdit ? '/admin/news/' . (int) $news['id'] . '/edit' : '/admin/news/create';
$publishedAtValue = '';
if (!empty($news['published_at'])) {
    $publishedAtValue = str_replace(' ', 'T', substr((string) $news['published_at'], 0, 16));
}
$defaultCode = Language::defaultCode();
$languages = Language::active();
?>
<div class="form-card">
    <?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="form-grid">
        <?= Csrf::field() ?>

        <div data-lang-tabs>
            <div class="lang-tabs">
                <?php foreach ($languages as $i => $lang): ?>
                    <button type="button" class="lang-tab-btn <?= $i === 0 ? 'is-active' : '' ?>" data-lang-target="<?= htmlspecialchars($lang['code'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars($lang['name'], ENT_QUOTES) ?>
                        <?php if ($lang['code'] === $defaultCode): ?><span class="lang-tab-btn__badge">(основной)</span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($languages as $i => $lang): ?>
                <?php $code = (string) $lang['code']; $isDefault = $code === $defaultCode; ?>
                <div class="lang-tab-panel <?= $i === 0 ? 'is-active' : '' ?>" data-lang-panel="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                    <?php if ($isDefault): ?>
                        <div class="form-field">
                            <label>Заголовок</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($news['title'] ?? '', ENT_QUOTES) ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Краткое описание</label>
                            <textarea name="excerpt"><?= htmlspecialchars($news['excerpt'] ?? '', ENT_QUOTES) ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Текст новости (допускается HTML)</label>
                            <textarea name="content" style="min-height:220px;"><?= htmlspecialchars($news['content'] ?? '', ENT_QUOTES) ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>SEO: meta title</label>
                            <input type="text" name="meta_title" value="<?= htmlspecialchars($news['meta_title'] ?? '', ENT_QUOTES) ?>">
                        </div>
                        <div class="form-field">
                            <label>SEO: meta description</label>
                            <input type="text" name="meta_description" value="<?= htmlspecialchars($news['meta_description'] ?? '', ENT_QUOTES) ?>">
                        </div>
                    <?php else: ?>
                        <?php $t = $translations[$code] ?? []; ?>
                        <p class="form-hint">Перевод для языка «<?= htmlspecialchars($lang['name'], ENT_QUOTES) ?>». Пустые поля на сайте заменяются версией основного языка.</p>
                        <div class="form-field">
                            <label>Заголовок</label>
                            <input type="text" name="translations[<?= $code ?>][title]" value="<?= htmlspecialchars($t['title'] ?? '', ENT_QUOTES) ?>">
                        </div>
                        <div class="form-field">
                            <label>Краткое описание</label>
                            <textarea name="translations[<?= $code ?>][excerpt]"><?= htmlspecialchars($t['excerpt'] ?? '', ENT_QUOTES) ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Текст новости (допускается HTML)</label>
                            <textarea name="translations[<?= $code ?>][content]" style="min-height:220px;"><?= htmlspecialchars($t['content'] ?? '', ENT_QUOTES) ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>SEO: meta title</label>
                            <input type="text" name="translations[<?= $code ?>][meta_title]" value="<?= htmlspecialchars($t['meta_title'] ?? '', ENT_QUOTES) ?>">
                        </div>
                        <div class="form-field">
                            <label>SEO: meta description</label>
                            <input type="text" name="translations[<?= $code ?>][meta_description]" value="<?= htmlspecialchars($t['meta_description'] ?? '', ENT_QUOTES) ?>">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <hr style="border:none;border-top:1px solid var(--admin-border);margin:6px 0;">

        <div class="form-field">
            <label for="slug">ЧПУ (slug) — общий для всех языков</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($news['slug'] ?? '', ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
        </div>

        <div class="form-field">
            <label for="image_file">Изображение (файл)</label>
            <input type="file" id="image_file" name="image_file" accept="image/*">
        </div>
        <div class="form-field">
            <label for="image_url">...либо ссылка на изображение</label>
            <input type="text" id="image_url" name="image_url" value="<?= htmlspecialchars($news['image'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-field">
            <label for="status">Статус</label>
            <select id="status" name="status">
                <option value="draft" <?= ($news['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                <option value="published" <?= ($news['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
            </select>
        </div>

        <div class="form-field">
            <label for="published_at">Дата публикации</label>
            <input type="datetime-local" id="published_at" name="published_at" value="<?= htmlspecialchars($publishedAtValue, ENT_QUOTES) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Сохранить</button>
            <a href="/admin/news" class="btn">Отмена</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
