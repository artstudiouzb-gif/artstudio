<?php

declare(strict_types=1);

test('new installations default to Uzbek and Asia Tashkent', function (): void {
    $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/InstallController.php');
    $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/install/step3.php');
    $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
    $language = file_get_contents(dirname(__DIR__, 2) . '/app/Models/Language.php');

    assert_true(is_string($controller));
    assert_true(is_string($view));
    assert_true(is_string($schema));
    assert_true(is_string($language));
    assert_contains("DEFAULT_TIMEZONE = 'Asia/Tashkent'", $controller);
    assert_contains("DEFAULT_LANGUAGE = 'uz'", $controller);
    assert_contains('$tz === $selectedTimezone', $view);
    assert_contains("(string) \$lang['code'] === \$selectedLanguage", $view);
    assert_contains("('uz', 'Oʻzbekcha', 1, 1, 0)", $schema);
    assert_contains("'code' => 'uz'", $language);
});
