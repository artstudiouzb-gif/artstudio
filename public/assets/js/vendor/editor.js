/*
 * ArtEditor — автономный WYSIWYG-редактор (без npm/Composer/зависимостей).
 * Оборачивает <textarea data-wysiwyg> в панель форматирования + contenteditable.
 * Значение синхронизируется обратно в textarea; сервер прогоняет его через
 * TextProcessor/HtmlSanitizer (разрешены p, заголовки, списки, таблицы, img,
 * blockquote, pre/code, hr — редактор вставляет только их).
 *
 * Полный набор: отмена/повтор, H2–H4/абзац/цитата/код, жирный/курсив/
 * подчёркнутый/зачёркнутый, списки, отступы, выравнивание, ссылка,
 * изображение из медиабиблиотеки, таблица, линия, очистка, HTML-режим,
 * полноэкранный режим.
 */
(function () {
    'use strict';

    var SEP = { sep: true };
    var BUTTONS = [
        { cmd: 'undo', label: '↶', title: 'Отменить (Ctrl+Z)' },
        { cmd: 'redo', label: '↷', title: 'Повторить' },
        SEP,
        { cmd: 'formatBlock', value: 'P', label: '¶', title: 'Абзац' },
        { cmd: 'formatBlock', value: 'H2', label: 'H2', title: 'Заголовок 2' },
        { cmd: 'formatBlock', value: 'H3', label: 'H3', title: 'Заголовок 3' },
        { cmd: 'formatBlock', value: 'H4', label: 'H4', title: 'Заголовок 4' },
        { cmd: 'formatBlock', value: 'BLOCKQUOTE', label: '❝', title: 'Цитата' },
        { cmd: 'formatBlock', value: 'PRE', label: '</>', title: 'Код' },
        SEP,
        { cmd: 'bold', label: 'Ж', title: 'Жирный (Ctrl+B)', style: 'font-weight:700' },
        { cmd: 'italic', label: 'К', title: 'Курсив (Ctrl+I)', style: 'font-style:italic' },
        { cmd: 'underline', label: 'П', title: 'Подчёркнутый (Ctrl+U)', style: 'text-decoration:underline' },
        { cmd: 'strikeThrough', label: 'S', title: 'Зачёркнутый', style: 'text-decoration:line-through' },
        SEP,
        { cmd: 'insertUnorderedList', label: '•—', title: 'Маркированный список' },
        { cmd: 'insertOrderedList', label: '1.', title: 'Нумерованный список' },
        { cmd: 'outdent', label: '⇤', title: 'Уменьшить отступ' },
        { cmd: 'indent', label: '⇥', title: 'Увеличить отступ' },
        SEP,
        { cmd: 'justifyLeft', label: '⯇', title: 'По левому краю' },
        { cmd: 'justifyCenter', label: '≡', title: 'По центру' },
        { cmd: 'justifyRight', label: '⯈', title: 'По правому краю' },
        SEP,
        { cmd: 'createLink', label: '🔗', title: 'Ссылка' },
        { cmd: 'unlink', label: '⛓', title: 'Убрать ссылку' },
        { cmd: 'image', label: '🖼', title: 'Изображение из медиабиблиотеки' },
        { cmd: 'table', label: '⊞', title: 'Таблица' },
        { cmd: 'insertHorizontalRule', label: '―', title: 'Горизонтальная линия' },
        SEP,
        { cmd: 'removeFormat', label: '✕', title: 'Очистить форматирование' },
        { cmd: 'source', label: 'HTML', title: 'Режим HTML-кода' },
        { cmd: 'fullscreen', label: '⛶', title: 'На весь экран' }
    ];

    function exec(cmd, value) {
        try { document.execCommand(cmd, false, value || null); } catch (e) {}
    }

    function attach(textarea) {
        if (textarea.dataset.wysiwygReady === '1') { return; }
        textarea.dataset.wysiwygReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'art-editor';

        var toolbar = document.createElement('div');
        toolbar.className = 'art-editor__toolbar';

        var area = document.createElement('div');
        area.className = 'art-editor__area';
        area.contentEditable = 'true';
        area.innerHTML = textarea.value || '';

        var sourceMode = false;
        var savedRange = null;

        function sync() { if (!sourceMode) { textarea.value = area.innerHTML; } }

        // Сохранение/восстановление выделения — модалка медиабиблиотеки
        // уводит фокус, без этого вставка попала бы не туда.
        function saveSelection() {
            var sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && area.contains(sel.anchorNode)) {
                savedRange = sel.getRangeAt(0).cloneRange();
            }
        }
        function restoreSelection() {
            area.focus();
            if (savedRange) {
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(savedRange);
            }
        }

        function insertImage() {
            saveSelection();
            var doInsert = function (url) {
                restoreSelection();
                exec('insertHTML', '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" loading="lazy">');
                sync();
            };
            if (window.MediaPicker) {
                window.MediaPicker.pick(doInsert);
            } else {
                var url = window.prompt('Адрес изображения:', '/uploads/public/');
                if (url) { doInsert(url); }
            }
        }

        function insertTable() {
            var spec = window.prompt('Размер таблицы (столбцы x строки):', '3x3');
            if (!spec) { return; }
            var m = spec.toLowerCase().replace(/\s+/g, '').match(/^(\d{1,2})[x×](\d{1,2})$/);
            if (!m) { window.alert('Формат: 3x3'); return; }
            var cols = Math.min(parseInt(m[1], 10), 10);
            var rows = Math.min(parseInt(m[2], 10), 30);
            var html = '<table><thead><tr>';
            for (var c = 0; c < cols; c++) { html += '<th>Заголовок</th>'; }
            html += '</tr></thead><tbody>';
            for (var r = 0; r < rows; r++) {
                html += '<tr>';
                for (var c2 = 0; c2 < cols; c2++) { html += '<td>&nbsp;</td>'; }
                html += '</tr>';
            }
            html += '</tbody></table><p></p>';
            exec('insertHTML', html);
            sync();
        }

        function toggleSource(btn) {
            sourceMode = !sourceMode;
            if (sourceMode) {
                textarea.value = area.innerHTML;
                area.style.display = 'none';
                textarea.style.display = 'block';
                textarea.classList.add('art-editor__source');
            } else {
                area.innerHTML = textarea.value;
                textarea.style.display = 'none';
                area.style.display = '';
            }
            btn.classList.toggle('is-active', sourceMode);
            // В HTML-режиме команды форматирования не работают — гасим их.
            toolbar.querySelectorAll('.art-editor__btn').forEach(function (b) {
                if (b !== btn && b.dataset.cmd !== 'fullscreen') {
                    b.disabled = sourceMode;
                }
            });
        }

        BUTTONS.forEach(function (b) {
            if (b.sep) {
                var sep = document.createElement('span');
                sep.className = 'art-editor__sep';
                toolbar.appendChild(sep);
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'art-editor__btn';
            btn.title = b.title;
            btn.textContent = b.label;
            btn.dataset.cmd = b.cmd;
            if (b.style) { btn.setAttribute('style', b.style); }
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function () {
                if (b.cmd === 'source') { toggleSource(btn); return; }
                if (b.cmd === 'fullscreen') {
                    wrap.classList.toggle('art-editor--full');
                    btn.classList.toggle('is-active', wrap.classList.contains('art-editor--full'));
                    return;
                }
                if (sourceMode) { return; }
                area.focus();
                if (b.cmd === 'createLink') {
                    var url = window.prompt('Адрес ссылки (https://…):', 'https://');
                    if (url) {
                        if (/^(https?:|mailto:|tel:|\/)/i.test(url)) { exec('createLink', url); }
                        else { window.alert('Недопустимый адрес ссылки.'); }
                    }
                } else if (b.cmd === 'image') {
                    insertImage(); return;
                } else if (b.cmd === 'table') {
                    insertTable(); return;
                } else if (b.cmd === 'formatBlock') {
                    exec('formatBlock', b.value);
                } else {
                    exec(b.cmd);
                }
                sync();
            });
            toolbar.appendChild(btn);
        });

        area.addEventListener('input', sync);
        area.addEventListener('blur', function () { saveSelection(); sync(); });
        area.addEventListener('keyup', saveSelection);
        area.addEventListener('mouseup', saveSelection);
        textarea.addEventListener('arteditor:restore', function () {
            area.innerHTML = textarea.value || '';
            if (sourceMode) { textarea.style.display = 'block'; }
        });

        // Вставка из Word/буфера: чистим на сервере (TextProcessor), здесь
        // только гарантируем text/plain для явно «грязных» источников не надо —
        // оставляем rich paste, сервер отфильтрует лишнее.

        // Синхронизация при отправке формы (и выход из HTML-режима).
        if (textarea.form) {
            textarea.form.addEventListener('submit', function () {
                if (!sourceMode) { textarea.value = area.innerHTML; }
            });
        }

        // Esc выходит из полноэкранного режима.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && wrap.classList.contains('art-editor--full')) {
                wrap.classList.remove('art-editor--full');
                var fbtn = toolbar.querySelector('[data-cmd="fullscreen"]');
                if (fbtn) { fbtn.classList.remove('is-active'); }
            }
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrap, textarea);
        wrap.appendChild(toolbar);
        wrap.appendChild(area);
        wrap.appendChild(textarea);
    }

    window.ArtEditor = { attach: attach };
})();
