<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Uploader;

test('Reveal: тип анимации попадает в data-reveal-type, обратная совместимость с bool (группа 4.2)', function () {
    // Новый формат {enabled, type}.
    $out = BlockRenderer::render([
        'id' => 1, 'type' => 'text', 'custom_css' => null,
        'data' => json_encode(['content' => 'x', '_reveal' => ['enabled' => true, 'type' => 'slide-up']]),
    ])['html'];
    assert_contains('data-reveal', $out);
    assert_contains('data-reveal-type="slide-up"', $out);

    // Старое булево true → трактуется как fade.
    $legacy = BlockRenderer::render([
        'id' => 2, 'type' => 'text', 'custom_css' => null,
        'data' => json_encode(['content' => 'x', '_reveal' => true]),
    ])['html'];
    assert_contains('data-reveal-type="fade"', $legacy);

    // Выключено → атрибута нет.
    $off = BlockRenderer::render([
        'id' => 3, 'type' => 'text', 'custom_css' => null,
        'data' => json_encode(['content' => 'x', '_reveal' => ['enabled' => false, 'type' => 'fade']]),
    ])['html'];
    assert_true(!str_contains($off, 'data-reveal'), 'выключенная анимация не даёт data-reveal');
});

test('SVG-иконка: sanitizeSvgString вырезает <script> (группа 4.3)', function () {
    $dirty = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">'
        . '<script>alert(1)</script><circle cx="12" cy="12" r="10"/></svg>';
    $clean = Uploader::sanitizeSvgString($dirty);

    assert_true(!str_contains($clean, '<script'), 'скрипт должен быть вырезан');
    assert_contains('<circle', $clean, 'безопасное содержимое сохраняется');
});

test('Блок advantages рендерит инлайновый SVG-икон приоритетнее текстовой иконки (группа 4.3)', function () {
    $svg = Uploader::sanitizeSvgString('<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>');
    $html = render_block('advantages', [
        'title' => 'Плюсы',
        'items' => [['icon' => '★', 'icon_svg' => $svg, 'title' => 'Скорость', 'text' => 'быстро']],
    ]);
    assert_contains('block-advantages__icon--svg', $html);
    assert_contains('<rect', $html, 'инлайновый SVG отрисован');
    // Текстовая иконка не выводится, когда есть SVG.
    assert_true(!str_contains($html, '>★<'), 'при наличии SVG текстовая иконка не показывается');
});
