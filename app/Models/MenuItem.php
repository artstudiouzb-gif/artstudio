<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MenuItem
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM menu_items ORDER BY sort_order ASC, id ASC');

        return $stmt->fetchAll();
    }

    /**
     * Активные пункты меню для языка: пункты этого языка + пункты, помеченные
     * как «для всех языков» (lang = ''). Если для языка нет ни одного
     * специфичного пункта, показываются пункты языка по умолчанию.
     */
    public static function activeForLang(string $lang): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM menu_items WHERE is_active = 1 AND (lang = :lang OR lang = '')
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([':lang' => $lang]);
        $rows = $stmt->fetchAll();

        $hasLangSpecific = false;
        foreach ($rows as $row) {
            if ((string) $row['lang'] === $lang && $lang !== '') {
                $hasLangSpecific = true;
                break;
            }
        }

        // Если для запрошенного языка нет собственных пунктов и это не язык
        // по умолчанию — откатываемся на пункты языка по умолчанию + общие.
        if (!$hasLangSpecific && $lang !== Language::defaultCode()) {
            $default = Language::defaultCode();
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM menu_items WHERE is_active = 1 AND (lang = :lang OR lang = '')
                 ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute([':lang' => $default]);

            return $stmt->fetchAll();
        }

        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM menu_items WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menu_items WHERE lang = :lang'
        );
        $stmt->execute([':lang' => $data['lang']]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = Database::pdo()->prepare(
            'INSERT INTO menu_items (lang, title, url_type, url_value, sort_order, is_active, created_at)
             VALUES (:lang, :title, :url_type, :url_value, :sort_order, :is_active, NOW())'
        );
        $stmt->execute([
            ':lang' => $data['lang'],
            ':title' => $data['title'],
            ':url_type' => $data['url_type'],
            ':url_value' => $data['url_value'],
            ':sort_order' => $nextOrder,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE menu_items SET lang = :lang, title = :title, url_type = :url_type,
             url_value = :url_value, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute([
            ':lang' => $data['lang'],
            ':title' => $data['title'],
            ':url_type' => $data['url_type'],
            ':url_value' => $data['url_value'],
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function move(int $id, string $direction): void
    {
        $item = self::findById($id);
        if (!$item) {
            return;
        }
        $siblings = array_values(array_filter(
            self::all(),
            static fn (array $r) => (string) $r['lang'] === (string) $item['lang']
        ));

        $index = null;
        foreach ($siblings as $i => $s) {
            if ((int) $s['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($siblings)) {
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE menu_items SET sort_order = :order WHERE id = :id');
            $stmt->execute([':order' => $siblings[$swap]['sort_order'], ':id' => $siblings[$index]['id']]);
            $stmt->execute([':order' => $siblings[$index]['sort_order'], ':id' => $siblings[$swap]['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Разрешает конечный URL пункта меню с учётом языкового префикса.
     */
    public static function resolveUrl(array $item, string $lang): string
    {
        $prefix = $lang === Language::defaultCode() ? '' : '/' . $lang;

        return match ($item['url_type']) {
            'news_index' => $prefix . '/news',
            'page' => $prefix . '/' . ltrim((string) $item['url_value'], '/'),
            default => (string) $item['url_value'],
        };
    }
}
