<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class News
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM news ORDER BY created_at DESC');

        return $stmt->fetchAll();
    }

    /**
     * Опубликованные новости, локализованные под указанный язык.
     */
    public static function published(int $limit = 20, int $offset = 0, ?string $lang = null): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM news WHERE status = 'published' AND published_at <= NOW()
             ORDER BY published_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        if ($lang === null || $lang === Language::defaultCode()) {
            return $rows;
        }

        return array_map(static fn (array $row) => self::localize($row, $lang), $rows);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Ищет опубликованную новость по слагу и локализует под язык.
     */
    public static function findPublishedBySlug(string $slug, ?string $lang = null): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM news WHERE slug = :slug AND status = 'published' AND published_at <= NOW() LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if ($lang === null || $lang === Language::defaultCode()) {
            return $row;
        }

        return self::localize($row, $lang);
    }

    /**
     * Накладывает перевод указанного языка на базовую строку. Пустые поля
     * перевода откатываются к значению языка по умолчанию (graceful fallback).
     */
    public static function localize(array $row, string $lang): array
    {
        $translation = NewsTranslation::find((int) $row['id'], $lang);
        if ($translation === null) {
            return $row;
        }

        foreach (['title', 'excerpt', 'content'] as $field) {
            if (isset($translation[$field]) && trim((string) $translation[$field]) !== '') {
                $row[$field] = $translation[$field];
            }
        }
        $row['meta_title'] = $translation['meta_title'] ?? null;
        $row['meta_description'] = $translation['meta_description'] ?? null;

        return $row;
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM news WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO news (title, slug, excerpt, content, image, meta_title, meta_description, status, published_at, author_id, created_at)
             VALUES (:title, :slug, :excerpt, :content, :image, :meta_title, :meta_description, :status, :published_at, :author_id, NOW())'
        );
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':excerpt' => $data['excerpt'],
            ':content' => $data['content'],
            ':image' => $data['image'],
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_description' => $data['meta_description'] ?? null,
            ':status' => $data['status'],
            ':published_at' => $data['published_at'],
            ':author_id' => $data['author_id'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE news SET title = :title, slug = :slug, excerpt = :excerpt, content = :content,
             image = :image, meta_title = :meta_title, meta_description = :meta_description,
             status = :status, published_at = :published_at WHERE id = :id'
        );
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':excerpt' => $data['excerpt'],
            ':content' => $data['content'],
            ':image' => $data['image'],
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_description' => $data['meta_description'] ?? null,
            ':status' => $data['status'],
            ':published_at' => $data['published_at'],
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM news WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
