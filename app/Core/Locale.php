<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;

/**
 * Хранит активную локаль текущего запроса. Устанавливается роутером
 * (из префикса URL) и используется моделями контента и шаблонами сайта.
 */
final class Locale
{
    private static ?string $current = null;
    private static string $path = '/';

    public static function set(string $code): void
    {
        self::$current = $code;
    }

    /**
     * Путь текущего запроса БЕЗ языкового префикса (например, '/news/foo').
     * Используется переключателем языков для построения ссылок.
     */
    public static function setPath(string $path): void
    {
        self::$path = $path === '' ? '/' : $path;
    }

    public static function path(): string
    {
        return self::$path;
    }

    public static function current(): string
    {
        if (self::$current === null) {
            self::$current = Language::defaultCode();
        }

        return self::$current;
    }

    public static function isDefault(): bool
    {
        return self::current() === Language::defaultCode();
    }

    /**
     * Префикс для ссылок текущего (или указанного) языка: '' для языка
     * по умолчанию, иначе '/{code}'.
     */
    public static function prefix(?string $code = null): string
    {
        $code = $code ?? self::current();

        return $code === Language::defaultCode() ? '' : '/' . $code;
    }

    /**
     * Строит URL сайта с учётом языкового префикса.
     */
    public static function url(string $path = '', ?string $code = null): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            $path = '';
        }

        $url = self::prefix($code) . $path;

        return $url === '' ? '/' : $url;
    }
}
