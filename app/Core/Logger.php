<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Лёгкий файловый логгер с ротацией по размеру. Перед записью проверяет
 * размер активного лог-файла; при достижении лимита выполняет циклическую
 * ротацию (.log -> .log.1 -> .log.2 ...) с жёстким ограничением числа
 * архивов, чтобы логи физически не могли занять лишнее место.
 */
final class Logger
{
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5 МБ на файл
    private const MAX_ARCHIVES = 5;

    public static function log(string $channel, string $message, string $level = 'INFO'): void
    {
        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $channel = preg_replace('/[^a-z0-9_\-]/i', '', $channel) ?: 'app';
        $file = $dir . '/' . $channel . '.log';

        self::rotateIfNeeded($file);

        $line = sprintf(
            "[%s] %s: %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            str_replace(["\r", "\n"], [' ', ' '], $message),
            PHP_EOL
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /** Файл-канал по уровню важности. */
    private const LEVEL_CHANNEL = [
        'CRITICAL' => 'error',
        'ERROR' => 'error',
        'WARNING' => 'warning',
        'SECURITY' => 'security',
        'INFO' => 'app',
    ];

    /**
     * Единая точка логирования с уровнем важности (задача 59): пишет в файл и
     * дублирует в Telegram по правилам TelegramNotifier (уровень/min_level/
     * троттлинг). Все модули могут вызывать её напрямую.
     *
     * @param array<string, mixed> $context
     */
    public static function event(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        $channel = self::LEVEL_CHANNEL[$level] ?? 'app';

        // Компактная запись контекста в файл.
        $suffix = '';
        if ($context !== []) {
            $pairs = [];
            foreach ($context as $k => $v) {
                if ($k === 'throttle') {
                    continue;
                }
                $pairs[] = $k . '=' . (is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE));
            }
            if ($pairs !== []) {
                $suffix = ' {' . implode(', ', $pairs) . '}';
            }
        }

        self::log($channel, $message . $suffix, $level);

        try {
            TelegramNotifier::send($level, $message, $context);
        } catch (\Throwable $e) {
            error_log('Logger telegram dispatch failed: ' . $e->getMessage());
        }
    }

    public static function critical(string $message, array $context = []): void
    {
        self::event('CRITICAL', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::event('WARNING', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::event('SECURITY', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::event('INFO', $message, $context);
    }

    /**
     * Ошибка: пишет в файл и (если настроено) шлёт ERROR-алерт в Telegram.
     * Сохранена сигнатура (message, channel) ради обратной совместимости.
     *
     * @param array<string, mixed> $context
     */
    public static function error(string $message, string $channel = 'error', array $context = []): void
    {
        self::log($channel, $message, 'ERROR');
        try {
            TelegramNotifier::send('ERROR', $message, $context);
        } catch (\Throwable $e) {
            error_log('Logger telegram dispatch failed: ' . $e->getMessage());
        }
    }

    private static function rotateIfNeeded(string $file): void
    {
        if (!is_file($file) || filesize($file) < self::MAX_SIZE_BYTES) {
            return;
        }

        // Удаляем самый старый архив.
        $oldest = $file . '.' . self::MAX_ARCHIVES;
        if (is_file($oldest)) {
            @unlink($oldest);
        }

        // Сдвигаем архивы: .log.(n-1) -> .log.n
        for ($i = self::MAX_ARCHIVES - 1; $i >= 1; $i--) {
            $src = $file . '.' . $i;
            if (is_file($src)) {
                @rename($src, $file . '.' . ($i + 1));
            }
        }

        // Активный лог -> .log.1
        @rename($file, $file . '.1');
    }

    /**
     * Ротация всех активных логов в каталоге (вызывается GC-механизмом).
     */
    public static function rotateAll(): void
    {
        $dir = self::dir();
        foreach (glob($dir . '/*.log') ?: [] as $file) {
            self::rotateIfNeeded($file);
        }
    }

    private static function dir(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }
}
