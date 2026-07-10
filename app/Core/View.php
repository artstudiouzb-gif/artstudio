<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $file = __DIR__ . '/../Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        // Извлечение переменных изолировано в статическом замыкании: его
        // локали (__file/__vars) не совпадают с именами переменных вьюхи,
        // поэтому extract() корректно создаёт даже переменную $data. Если бы
        // extract вызывался прямо здесь, ключ 'data' конфликтовал бы с
        // параметром $data и (при EXTR_SKIP) не попадал во вьюху — из-за этого
        // редакторы блоков не показывали сохранённое содержимое.
        (static function (string $__file, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            require $__file;
        })($file, $data);
    }

    public static function renderPartial(string $template, array $data = []): string
    {
        $file = __DIR__ . '/../Views/' . $template . '.php';
        if (!is_file($file)) {
            return '';
        }

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            require $__file;

            return (string) ob_get_clean();
        })($file, $data);
    }
}
