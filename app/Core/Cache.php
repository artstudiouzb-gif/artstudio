<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Простое файловое кеширование в storage/cache/. Ключи вида "page:5:ru"
 * отображаются в путь storage/cache/page/5/ru.cache, что позволяет
 * инвалидировать целые группы (например, все языки одной страницы) удалением
 * поддиректории.
 */
final class Cache
{
    private static function dir(): string
    {
        return APP_ROOT . '/storage/cache';
    }

    private static function pathFor(string $key): string
    {
        $segments = array_map(
            static fn ($s) => preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $s) ?? '_',
            explode(':', $key)
        );
        $file = array_pop($segments);
        $sub = $segments === [] ? '' : '/' . implode('/', $segments);

        return self::dir() . $sub . '/' . $file . '.cache';
    }

    public static function get(string $key): mixed
    {
        $path = self::pathFor($key);
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = @unserialize($raw, ['allowed_classes' => false]);

        return $data === false && $raw !== serialize(false) ? null : $data;
    }

    public static function put(string $key, mixed $value): void
    {
        $path = self::pathFor($key);
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        @file_put_contents($path, serialize($value), LOCK_EX);
    }

    /**
     * @param callable():mixed $callback
     */
    public static function remember(string $key, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $callback();
        self::put($key, $value);

        return $value;
    }

    public static function forget(string $key): void
    {
        $path = self::pathFor($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Инвалидация группы: удаляет поддиректорию, соответствующую префиксу
     * (например, "page:5" -> storage/cache/page/5).
     */
    public static function forgetPrefix(string $prefix): void
    {
        $segments = array_map(
            static fn ($s) => preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $s) ?? '_',
            explode(':', $prefix)
        );
        $target = self::dir() . '/' . implode('/', $segments);
        self::removeRecursive($target);
    }

    public static function flush(): void
    {
        self::removeRecursive(self::dir());
    }

    private static function removeRecursive(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::removeRecursive($path . '/' . $item);
        }
        @rmdir($path);
    }
}
