<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Версионирование статических ассетов для инвалидации кэша CDN (задача 127).
 * К URL добавляется отпечаток содержимого (?v=hash), поэтому после деплоя новой
 * версии файла CDN отдаёт его как новый ресурс без ручной очистки кэша.
 * Отпечаток берётся из mtime+size файла (быстро, без чтения содержимого).
 */
final class Asset
{
    /** @var array<string,string> */
    private static array $cache = [];

    public static function url(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/')) {
            return $path;
        }
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
        $file = $root . '/public' . $path;

        $out = $path;
        if (is_file($file)) {
            $stat = @stat($file);
            if ($stat !== false) {
                $v = substr(hash('crc32b', $stat['mtime'] . '-' . $stat['size']), 0, 8);
                $out = $path . (str_contains($path, '?') ? '&' : '?') . 'v=' . $v;
            }
        }

        return self::$cache[$path] = $out;
    }
}
