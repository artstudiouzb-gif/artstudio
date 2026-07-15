<?php

declare(strict_types=1);

test('design and settings pages share the same card and save toolbar', function (): void {
    $design = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/design/index.php');
    $settings = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/settings/index.php');
    $performance = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/performance/index.php');
    $social = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/settings/social.php');
    $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/admin.css');

    assert_true(is_string($design));
    assert_true(is_string($settings));
    assert_true(is_string($performance));
    assert_true(is_string($social));
    assert_true(is_string($css));
    assert_contains('form-card admin-settings-card', $design);
    assert_contains('form-card admin-settings-card', $settings);
    assert_contains('form-actions form-actions--sticky', $design);
    assert_true(!str_contains($design, 'class="design-actions"'));
    assert_contains('.admin-settings-card, .admin-builder-workspace { width: 100%; max-width: 1080px; }', $css);
    assert_true(!str_contains($css, '.design-actions'));
    assert_contains('form-actions form-actions--sticky', $performance);
    assert_contains('form-actions form-actions--sticky', $social);
    assert_contains('--admin-radius: 6px', $css);
});
