<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class NewsTranslation
{
    /**
     * @return array<string, array<string, mixed>> переводы по коду языка
     */
    public static function forNews(int $newsId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM news_translations WHERE news_id = :id');
        $stmt->execute([':id' => $newsId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['lang']] = $row;
        }

        return $result;
    }

    public static function find(int $newsId, string $lang): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM news_translations WHERE news_id = :id AND lang = :lang LIMIT 1'
        );
        $stmt->execute([':id' => $newsId, ':lang' => $lang]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function upsert(int $newsId, string $lang, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO news_translations (news_id, lang, title, excerpt, content, meta_title, meta_description)
             VALUES (:news_id, :lang, :title, :excerpt, :content, :meta_title, :meta_description)
             ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt),
                content = VALUES(content), meta_title = VALUES(meta_title), meta_description = VALUES(meta_description)'
        );
        $stmt->execute([
            ':news_id' => $newsId,
            ':lang' => $lang,
            ':title' => $data['title'] ?? null,
            ':excerpt' => $data['excerpt'] ?? null,
            ':content' => $data['content'] ?? null,
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_description' => $data['meta_description'] ?? null,
        ]);
    }
}
