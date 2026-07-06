<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Database;
use App\Core\Logger;

/**
 * Health-check для мониторинга (задача 59). Проверяет БД и доступность записи
 * в storage. Отдаёт JSON и корректный HTTP-код; при неуспехе шлёт алерт.
 */
final class HealthController
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        $checks = ['db' => false, 'storage' => false];

        try {
            Database::pdo()->query('SELECT 1');
            $checks['db'] = true;
        } catch (\Throwable $e) {
            Logger::critical('Health-check: БД недоступна — ' . $e->getMessage());
        }

        $logDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3)) . '/storage/logs';
        $checks['storage'] = is_dir($logDir) && is_writable($logDir);
        if (!$checks['storage']) {
            Logger::warning('Health-check: каталог storage/logs недоступен для записи.');
        }

        $ok = $checks['db'] && $checks['storage'];
        http_response_code($ok ? 200 : 503);
        echo json_encode([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ], JSON_UNESCAPED_UNICODE);
    }
}
