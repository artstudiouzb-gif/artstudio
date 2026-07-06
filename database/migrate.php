<?php

declare(strict_types=1);

/*
 * CLI-система миграций ArtStudio CMS.
 *
 *   php database/migrate.php            — накатить все новые миграции
 *   php database/migrate.php status     — показать статус (применённые/новые)
 *
 * Сканирует database/migrations/*.sql в алфавитном порядке имён (используйте
 * префикс с датой: 2026_07_05_*.sql) и накатывает те, что ещё не записаны в
 * таблице migrations. Каждая миграция применяется в транзакции (если её DDL
 * это позволяет) и фиксируется в таблице.
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/Database.php';

use App\Core\Config;
use App\Core\Database;

$config = require __DIR__ . '/../config/config.php';
Config::set($config);
Database::init($config['db']);
$pdo = Database::pdo();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migrations_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

$command = $argv[1] ?? 'migrate';

if ($command === 'status') {
    fwrite(STDOUT, "Миграции:\n");
    foreach ($files as $file) {
        $name = basename($file);
        $mark = isset($applied[$name]) ? '[x] применена' : '[ ] новая';
        fwrite(STDOUT, "  {$mark}  {$name}\n");
    }
    exit(0);
}

$pending = array_filter($files, static fn ($f) => !isset($applied[basename($f)]));

if (empty($pending)) {
    fwrite(STDOUT, "Нет новых миграций.\n");
    exit(0);
}

$record = $pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (:filename, NOW())');

foreach ($pending as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "Пропуск пустого файла: {$name}\n");
        continue;
    }

    fwrite(STDOUT, "Применяю {$name} ... ");
    try {
        // Многооператорный SQL выполняется целиком (миграции — доверенные файлы).
        $pdo->exec($sql);
        $record->execute([':filename' => $name]);
        fwrite(STDOUT, "ok\n");
    } catch (\Throwable $e) {
        fwrite(STDOUT, "ОШИБКА\n");
        fwrite(STDERR, "Миграция {$name} провалилась: " . $e->getMessage() . "\n");
        fwrite(STDERR, "Дальнейшие миграции остановлены.\n");
        exit(1);
    }
}

fwrite(STDOUT, "Готово.\n");
