<?php

declare(strict_types=1);

/*
 * Скопируйте этот файл в config.php и заполните реальными данными,
 * либо задайте те же значения через переменные окружения хостинга
 * (APP_ENV, APP_DEBUG, APP_URL, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD и т.д.)
 */

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'url' => getenv('APP_URL') ?: 'https://example.com',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Moscow',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'artstudio_cms',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'asc_session',
        'lifetime' => 7200,
    ],
    'security' => [
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 15,
        'login_attempts_window_minutes' => 15,
    ],
    'paths' => [
        'protected_uploads' => __DIR__ . '/../storage/protected_uploads',
        'public_uploads' => __DIR__ . '/../public/uploads/public',
        'public_uploads_url' => '/uploads/public',
    ],
    'mail' => [
        // Если host пуст — письма не отправляются (уведомления просто пропускаются).
        'host' => getenv('SMTP_HOST') ?: '',
        'port' => (int) (getenv('SMTP_PORT') ?: 587),
        // 'tls' (STARTTLS, порт 587), 'ssl' (implicit TLS, порт 465) или 'none'.
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
        'username' => getenv('SMTP_USERNAME') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'ArtStudio CMS',
        'timeout' => 15,
    ],
    // Telegram-алертинг (задача 59). Читается строго из файла/окружения, не из
    // БД — чтобы падение БД не мешало отправке критических оповещений.
    'telegram' => [
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '',
        // Опциональный отдельный чат для событий безопасности (SECURITY).
        'chat_id_security' => getenv('TELEGRAM_CHAT_ID_SECURITY') ?: '',
        // Минимальный уровень для отправки в Telegram: INFO|WARNING|ERROR|CRITICAL.
        // Ниже этого уровня события пишутся только в файл. SECURITY отправляется
        // всегда (если Telegram настроен).
        'min_level' => getenv('TELEGRAM_MIN_LEVEL') ?: 'WARNING',
    ],
];
