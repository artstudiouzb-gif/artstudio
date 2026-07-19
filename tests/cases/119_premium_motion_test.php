<?php

declare(strict_types=1);

test('Главные CTA и преимущества используют доступные premium-анимации', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/frontend.css');

    assert_contains('@keyframes hero-button-sheen', $css);
    assert_contains('.block-hero__button:not(.block-hero__button--ghost)::after', $css);
    assert_contains('@keyframes advantages-icon-float', $css);
    assert_contains('.block-advantages__item:focus-within .block-advantages__icon', $css);
    assert_contains('@media (prefers-reduced-motion: reduce)', $css);
    assert_contains('.block-hero__button::after { content: none; animation: none; }', $css);
});

test('Карусель проектов не отключает анимации своих карточек', function (): void {
    $govCss = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    $frontendCss = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/frontend.css');

    assert_true(!str_contains($govCss, '[data-carousel-track] *'), 'Вложенные переходы карусели должны оставаться активными');
    assert_contains('transition: transform .7s cubic-bezier(.22, 1, .36, 1)', $govCss);
    assert_contains('transform: translateY(18px) scale(.99)', $frontendCss);
});
