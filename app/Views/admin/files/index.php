<?php

use App\Core\Csrf;
use App\Core\Format;
use App\Models\FileEntry;

$pageTitle = 'Файлы';
$activeNav = 'files';
require __DIR__ . '/../layout/header.php';

/** @var array $items */
?>
<style>
/* Двухколоночный грид панелей загрузки */
.files-upload-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.files-upload-grid .form-card {
    margin-bottom: 0 !important;
}

/* Переключение режимов отображения */
.files-view-list .files-grid { display: none !important; }
.files-view-grid .files-table { display: none !important; }

/* Тулбар выбора режима (активные кнопки) */
#view_mode_list.is-active, #view_mode_grid.is-active {
    background: var(--admin-accent, #173a63) !important;
    color: #fff !important;
    border-color: var(--admin-accent, #173a63) !important;
}

/* Сетка карточек файлов */
.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.file-card {
    background: #fff;
    border: 1px solid var(--admin-border, #e6e8ec);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--admin-shadow, 0 1px 3px rgba(0,0,0,.05));
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.08);
}

.file-card__preview {
    position: relative;
    aspect-ratio: 16/10;
    background: #f4f5f8;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid var(--admin-border, #e6e8ec);
    overflow: hidden;
}

.file-card__preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-card__icon {
    color: var(--admin-muted, #727b8e);
}

.file-card__badge {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    z-index: 2;
}

.file-card__info {
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.file-card__name {
    font-weight: 600;
    font-size: 14px;
    color: var(--admin-ink, #1f2937);
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}

.file-card__meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--admin-muted, #727b8e);
}

.file-card__actions {
    display: flex;
    gap: 6px;
    margin-top: auto;
    padding-top: 8px;
    border-top: 1px solid #f2f4f7;
}

.file-card__actions .btn {
    padding: 5px 10px;
    font-size: 12px;
    flex: 1;
    justify-content: center;
}

.btn--success {
    background: #10b981 !important;
    color: #fff !important;
    border-color: #10b981 !important;
}
</style>

<div class="files-upload-grid">
    <div class="form-card">
        <form method="post" action="/admin/files/upload" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="file">Файл</label>
                <div class="dropzone" id="dropzone_standard">
                    <input type="file" id="file" name="file" required>
                    <div class="dropzone__text">Перетащите файл сюда или <strong>выберите на диске</strong><br><small id="standard_filename" style="display:block;margin-top:6px;font-weight:600;color:var(--admin-accent);"></small></div>
                </div>
            </div>
            <div class="form-field">
                <label for="access_type">Доступ</label>
                <select id="access_type" name="access_type">
                    <option value="public">Открытый (прямая ссылка)</option>
                    <option value="protected">Защищённый (только по сессии или токену)</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Загрузить</button>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h2 style="margin-top:0;">Загрузка больших файлов (по частям)</h2>
        <p class="form-hint">Для больших файлов (видео, PDF-презентации) — загрузка чанками в обход ограничений хостинга. До 200 МБ.</p>
        <div class="form-grid">
            <div class="form-field">
                <label for="chunk_file">Файл</label>
                <div class="dropzone" id="dropzone_chunk">
                    <input type="file" id="chunk_file">
                    <div class="dropzone__text">Перетащите большой файл сюда или <strong>выберите на диске</strong><br><small id="chunk_filename" style="display:block;margin-top:6px;font-weight:600;color:var(--admin-accent);"></small></div>
                </div>
            </div>
            <div class="form-field">
                <label for="chunk_access">Доступ</label>
                <select id="chunk_access">
                    <option value="public">Открытый (прямая ссылка)</option>
                    <option value="protected">Защищённый (только по сессии или токену)</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" id="chunk_upload_btn" class="btn"
                    data-csrf="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES) ?>">Загрузить по частям</button>
            </div>
            <div id="chunk_progress" class="form-hint"></div>
        </div>
    </div>
</div>

<form method="get" action="/admin/files" class="list-filters list-filters--panel" style="margin-bottom: 20px;">
    <div class="list-filter list-filter--search">
        <label for="filter_q">Поиск</label>
        <input type="search" id="filter_q" name="q" value="<?= htmlspecialchars((string) ($_GET['q'] ?? ''), ENT_QUOTES) ?>" placeholder="Поиск по имени…">
    </div>
    <div class="list-filter">
        <label for="filter_type">Тип файла</label>
        <select id="filter_type" name="type">
            <option value="">Все типы</option>
            <option value="image" <?= ($_GET['type'] ?? '') === 'image' ? 'selected' : '' ?>>Изображения</option>
            <option value="document" <?= ($_GET['type'] ?? '') === 'document' ? 'selected' : '' ?>>Документы</option>
            <option value="video" <?= ($_GET['type'] ?? '') === 'video' ? 'selected' : '' ?>>Видео</option>
        </select>
    </div>
    <div class="list-filter">
        <label for="filter_sort">Сортировка</label>
        <select id="filter_sort" name="sort">
            <option value="date_desc" <?= ($_GET['sort'] ?? '') === 'date_desc' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="date_asc" <?= ($_GET['sort'] ?? '') === 'date_asc' ? 'selected' : '' ?>>Сначала старые</option>
            <option value="size_desc" <?= ($_GET['sort'] ?? '') === 'size_desc' ? 'selected' : '' ?>>Сначала крупные</option>
            <option value="size_asc" <?= ($_GET['sort'] ?? '') === 'size_asc' ? 'selected' : '' ?>>Сначала небольшие</option>
            <option value="name_asc" <?= ($_GET['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>По имени (А-Я)</option>
            <option value="name_desc" <?= ($_GET['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>По имени (Я-А)</option>
        </select>
    </div>
    <div class="list-filters__actions">
        <button type="submit" class="btn btn--primary">Применить</button>
        <?php if (!empty($_GET['q']) || !empty($_GET['type']) || !empty($_GET['sort'])): ?>
            <a href="/admin/files" class="btn" style="text-decoration:none;">Сбросить</a>
        <?php endif; ?>
    </div>

    <!-- Toggle View Mode buttons aligned to the right! -->
    <div class="list-filters__actions" style="margin-left: auto;">
        <button type="button" class="btn btn--small" id="view_mode_list" title="Список" style="min-width:36px;padding:6px 10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="display:block;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
        <button type="button" class="btn btn--small" id="view_mode_grid" title="Сетка" style="min-width:36px;padding:6px 10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="display:block;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
    </div>
</form>

<div id="files_container" class="files-view-list">
    <!-- Табличное отображение (Список) -->
    <table class="data-table files-table">
        <thead>
            <tr>
                <th>Имя файла</th>
                <th>Тип</th>
                <th>Размер</th>
                <th>Доступ</th>
                <th>Ссылка</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="data-table__empty">Файлов пока нет.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="file-cell">
                            <?php if (str_starts_with((string) $item['mime_type'], 'image/')): ?>
                                <?php 
                                $thumbUrl = $item['access_type'] === 'public' 
                                    ? FileEntry::publicUrl($item) 
                                    : '/download.php?file_id=' . (int) $item['id'] . '&token=' . htmlspecialchars((string) $item['access_token'], ENT_QUOTES); 
                                ?>
                                <div class="file-thumbnail">
                                    <img src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES) ?>" alt="" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="file-thumbnail file-thumbnail--icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                            <?php endif; ?>
                            <span class="file-name"><?= htmlspecialchars($item['original_name'], ENT_QUOTES) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($item['mime_type'], ENT_QUOTES) ?></td>
                    <td><?= Format::fileSize((int) $item['size']) ?></td>
                    <td>
                        <span class="badge badge--<?= $item['access_type'] === 'public' ? 'published' : 'draft' ?>">
                            <?= $item['access_type'] === 'public' ? 'Открытый' : 'Защищённый' ?>
                        </span>
                    </td>
                    <td style="max-width:260px; word-break:break-all;">
                        <?php if ($item['access_type'] === 'public'): ?>
                            <code><?= htmlspecialchars(FileEntry::publicUrl($item), ENT_QUOTES) ?></code>
                        <?php else: ?>
                            <code>/download.php?file_id=<?= (int) $item['id'] ?>&amp;token=<?= htmlspecialchars($item['access_token'], ENT_QUOTES) ?></code>
                        <?php endif; ?>
                    </td>
                    <td class="data-table__actions">
                        <button type="button" class="btn btn--small" data-copy-link="<?= htmlspecialchars($item['access_type'] === 'public' ? FileEntry::publicUrl($item) : '/download.php?file_id=' . (int) $item['id'] . '&token=' . $item['access_token'], ENT_QUOTES) ?>">Ссылка</button>
                        <?php if ($item['access_type'] === 'protected'): ?>
                            <form method="post" action="/admin/files/<?= (int) $item['id'] ?>/regenerate-token" style="margin:0;">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn--small">Токен</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/files/<?= (int) $item['id'] ?>/delete" data-confirm="Удалить файл «<?= htmlspecialchars($item['original_name'], ENT_QUOTES) ?>»?" style="margin:0;">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--small btn--danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Сеточное отображение (Сетка) -->
    <div class="files-grid">
        <?php if (empty($items)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--admin-muted); font-size: 15px;">Файлов пока нет.</div>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php 
            $isImg = str_starts_with((string) $item['mime_type'], 'image/');
            $url = FileEntry::publicUrl($item);
            if ($item['access_type'] !== 'public') {
                $url = '/download.php?file_id=' . (int) $item['id'] . '&token=' . htmlspecialchars((string) $item['access_token'], ENT_QUOTES);
            }
            ?>
            <div class="file-card">
                <div class="file-card__preview">
                    <?php if ($isImg): ?>
                        <img src="<?= htmlspecialchars($url, ENT_QUOTES) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="file-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                    <?php endif; ?>
                    <span class="badge badge--<?= $item['access_type'] === 'public' ? 'published' : 'draft' ?> file-card__badge">
                        <?= $item['access_type'] === 'public' ? 'Открытый' : 'Защищённый' ?>
                    </span>
                </div>
                <div class="file-card__info">
                    <div class="file-card__name" title="<?= htmlspecialchars($item['original_name'], ENT_QUOTES) ?>"><?= htmlspecialchars($item['original_name'], ENT_QUOTES) ?></div>
                    <div class="file-card__meta">
                        <span><?= htmlspecialchars(explode('/', $item['mime_type'])[1] ?? $item['mime_type'], ENT_QUOTES) ?></span>
                        <span><?= Format::fileSize((int) $item['size']) ?></span>
                    </div>
                    <div class="file-card__actions">
                        <button type="button" class="btn btn--small" data-copy-link="<?= htmlspecialchars($url, ENT_QUOTES) ?>">Ссылка</button>
                        <?php if ($item['access_type'] === 'protected'): ?>
                            <form method="post" action="/admin/files/<?= (int) $item['id'] ?>/regenerate-token" style="margin:0;">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn--small">Токен</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/files/<?= (int) $item['id'] ?>/delete" data-confirm="Удалить файл «<?= htmlspecialchars($item['original_name'], ENT_QUOTES) ?>»?" style="margin:0;">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--small btn--danger">Удалить</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
(function() {
    'use strict';
    
    function initDropzone(dropzoneId, inputId, labelId) {
        var dz = document.getElementById(dropzoneId);
        var input = document.getElementById(inputId);
        var label = document.getElementById(labelId);
        if (!dz || !input) return;
        
        ['dragenter', 'dragover'].forEach(function(eventName) {
            dz.addEventListener(eventName, function(e) {
                e.preventDefault();
                dz.classList.add('is-dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(function(eventName) {
            dz.addEventListener(eventName, function(e) {
                e.preventDefault();
                dz.classList.remove('is-dragover');
            }, false);
        });
        
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                label.textContent = 'Выбран: ' + input.files[0].name + ' (' + (input.files[0].size / 1024 / 1024).toFixed(2) + ' МБ)';
            } else {
                label.textContent = '';
            }
        });
    }
    
    initDropzone('dropzone_standard', 'file', 'standard_filename');
    initDropzone('dropzone_chunk', 'chunk_file', 'chunk_filename');

    // Переключение режимов Список/Сетка
    var container = document.getElementById('files_container');
    var btnList = document.getElementById('view_mode_list');
    var btnGrid = document.getElementById('view_mode_grid');
    
    if (container && btnList && btnGrid) {
        var savedMode = localStorage.getItem('artstudio:files-view-mode') || 'list';
        setMode(savedMode);
        
        btnList.addEventListener('click', function() { setMode('list'); });
        btnGrid.addEventListener('click', function() { setMode('grid'); });
        
        function setMode(mode) {
            if (mode === 'grid') {
                container.className = 'files-view-grid';
                btnGrid.classList.add('is-active');
                btnList.classList.remove('is-active');
            } else {
                container.className = 'files-view-list';
                btnList.classList.add('is-active');
                btnGrid.classList.remove('is-active');
            }
            try { localStorage.setItem('artstudio:files-view-mode', mode); } catch(e) {}
        }
    }

    // Копирование ссылок в буфер обмена
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-copy-link]');
        if (btn) {
            var val = btn.getAttribute('data-copy-link');
            var url = val.startsWith('/') ? window.location.origin + val : val;
            navigator.clipboard.writeText(url).then(function() {
                var oldText = btn.textContent;
                btn.textContent = 'ОК!';
                btn.classList.add('btn--success');
                setTimeout(function() {
                    btn.textContent = oldText;
                    btn.classList.remove('btn--success');
                }, 1500);
            }).catch(function() {
                window.prompt('Скопируйте ссылку вручную:', url);
            });
        }
    });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
