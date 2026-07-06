<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use ZipArchive;

/**
 * Создание резервной копии: дамп БД (портируемый, через PDO — без внешнего
 * mysqldump, чтобы работать на дешёвых хостингах) + папки загрузок, всё в
 * единый .zip. Архив кладётся в storage/backups (вне вебрута, доступ закрыт).
 */
final class Backup
{
    public static function backupDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/backups';
    }

    /**
     * Создаёт архив и возвращает абсолютный путь к нему.
     */
    public static function create(): string
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException('Расширение PHP zip не установлено.');
        }

        $dir = self::backupDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Не удалось создать каталог для бэкапов.');
        }

        $timestamp = date('Y-m-d_His');
        $zipPath = $dir . '/backup_' . $timestamp . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Не удалось создать zip-архив.');
        }

        // 1. Дамп базы данных.
        $zip->addFromString('database.sql', self::dumpDatabase());

        // 2. Папки загрузок.
        self::addDirectory($zip, Config::get('paths.public_uploads'), 'uploads/public');
        self::addDirectory($zip, Config::get('paths.protected_uploads'), 'uploads/protected');

        // 3. Манифест.
        $zip->addFromString('manifest.txt', sprintf(
            "ArtStudio CMS backup\nDate: %s\nApp URL: %s\n",
            date('c'),
            (string) Config::get('app.url', '')
        ));

        $zip->close();

        Logger::info('Резервная копия создана', ['file' => basename($zipPath)]);

        return $zipPath;
    }

    /**
     * Портируемый SQL-дамп всех таблиц через PDO.
     */
    public static function dumpDatabase(): string
    {
        $pdo = Database::pdo();
        $output = "-- ArtStudio CMS database dump\n-- " . date('c') . "\n\n";
        $output .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? '';

            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createSql . ";\n\n";

            $rows = $pdo->query('SELECT * FROM `' . $table . '`');
            $rowData = $rows->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rowData as $row) {
                $columns = array_map(static fn ($c) => '`' . $c . '`', array_keys($row));
                $values = array_map(static function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string) $value);
                }, array_values($row));

                $output .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
                    . implode(', ', $values) . ");\n";
            }

            if (!empty($rowData)) {
                $output .= "\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $output;
    }

    private static function addDirectory(ZipArchive $zip, ?string $path, string $zipPrefix): void
    {
        if ($path === null) {
            return;
        }
        $real = realpath($path);
        if ($real === false || !is_dir($real)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $zip->addEmptyDir($zipPrefix);

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $relative = substr($item->getPathname(), strlen($real) + 1);
            $relative = str_replace('\\', '/', $relative);
            // Не включаем .gitkeep-заглушки.
            if ($item->getFilename() === '.gitkeep') {
                continue;
            }
            $zipEntry = $zipPrefix . '/' . $relative;
            if ($item->isDir()) {
                $zip->addEmptyDir($zipEntry);
            } else {
                $zip->addFile($item->getPathname(), $zipEntry);
            }
        }
    }
}
