<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Database;
use App\Core\PagePresets;
use App\Models\Block;
use App\Models\BlockSnippet;

test('Сборки страниц: описаны корректно и используют существующие типы блоков', function () {
    $presets = PagePresets::all();
    assert_true(count($presets) >= 5, 'сборок должно быть несколько');

    $known = array_keys(BlockRenderer::DEFAULTS);
    foreach ($presets as $id => $preset) {
        assert_true($preset['name'] !== '', "{$id}: нет названия");
        assert_true($preset['description'] !== '', "{$id}: нет описания");
        assert_true($preset['outline'] !== [], "{$id}: нет состава для карточки");
        assert_true($preset['blocks'] !== [], "{$id}: нет блоков");

        foreach ($preset['blocks'] as $block) {
            assert_true(
                in_array($block['type'], $known, true),
                "{$id}: неизвестный тип блока «{$block['type']}» — на сайте вместо секции будет пустой комментарий"
            );
            assert_true(is_array($block['data'] ?? null), "{$id}: у блока {$block['type']} нет данных");
        }
    }
});

test('Сборки страниц: ритм фонов и отступов выдержан', function () {
    foreach (PagePresets::all() as $id => $preset) {
        $backgrounds = [];
        foreach ($preset['blocks'] as $block) {
            $bg = (string) ($block['data']['_bg'] ?? 'none');
            assert_true(in_array($bg, ['none', 'light', 'tint', 'navy'], true), "{$id}: недопустимый фон «{$bg}»");
            // Подложка растягивается только вместе с фоном.
            if ($bg === 'none') {
                assert_false(!empty($block['data']['_fullwidth']), "{$id}: полная ширина без фона бессмысленна");
            }
            $backgrounds[] = $bg;
        }

        // Две подряд одинаковые подложки сливаются в одну секцию.
        for ($i = 1, $n = count($backgrounds); $i < $n; $i++) {
            assert_false(
                $backgrounds[$i] !== 'none' && $backgrounds[$i] === $backgrounds[$i - 1],
                "{$id}: два одинаковых фона подряд ({$backgrounds[$i]})"
            );
        }

        // Тёмная секция — акцент, её не должно быть больше одной.
        $navy = count(array_filter($backgrounds, static fn (string $b): bool => $b === 'navy'));
        assert_true($navy <= 1, "{$id}: тёмных секций больше одной");

        // Первый блок — обложка с максимальным «воздухом» и без анимации:
        // он виден сразу, анимировать его — мигание при загрузке.
        $first = $preset['blocks'][0];
        assert_same('hero', $first['type'], "{$id}: сборка должна начинаться с обложки");
        assert_same('max', (string) ($first['data']['_spacing'] ?? ''), "{$id}: у обложки максимальные отступы");
        assert_false(!empty($first['data']['_reveal']['enabled']), "{$id}: обложку не анимируем");
    }
});

test('Сборки страниц: применяются к странице и рендерятся без ошибок (БД)', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $pdo->exec("INSERT INTO pages (title, slug, status, created_at) VALUES ('Preset', 'preset-" . bin2hex(random_bytes(3)) . "', 'published', NOW())");
    $pageId = (int) $pdo->lastInsertId();

    foreach (PagePresets::all() as $id => $preset) {
        $count = BlockSnippet::applyToPage($preset['blocks'], $pageId, 'ru', true);
        assert_same(count($preset['blocks']), $count, "{$id}: создано не столько блоков, сколько в сборке");

        $rendered = BlockRenderer::renderPage(Block::forPageLocalized($pageId, 'ru'));
        assert_not_contains('Неизвестный тип блока', $rendered['html'], "{$id}: в HTML попал неизвестный блок");
        assert_true(strlen($rendered['html']) > 500, "{$id}: подозрительно пустой результат");
    }

    // Режим «добавить» не стирает то, что уже было на странице.
    $before = count(Block::forPage($pageId, 'ru'));
    BlockSnippet::applyToPage(PagePresets::find('contacts')['blocks'], $pageId, 'ru', false);
    assert_true(count(Block::forPage($pageId, 'ru')) > $before, 'append обязан добавлять, а не заменять');

    $pdo->exec("DELETE FROM pages WHERE id = {$pageId}");
});

test('Сборки страниц: неизвестный идентификатор не находится', function () {
    assert_same(null, PagePresets::find('нет-такой'));
    assert_same(null, PagePresets::find(''));
    assert_true(PagePresets::find('contacts') !== null);
});
