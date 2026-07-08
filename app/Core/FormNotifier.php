<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Telegram-уведомления о новых заявках форм через собственного бота
 * (TelegramBot, тот же токен, что и для кодов входа). Получатели — список
 * chat_id в настройке telegram_notify_chat_ids (через запятую; отрицательные
 * id — групповые чаты). Не путать с TelegramNotifier (алерты логов из config).
 */
final class FormNotifier
{
    /** Telegram ограничивает сообщение 4096 символами — режем с запасом. */
    private const MAX_TEXT = 3800;

    /** Длинные значения полей укорачиваются до этого размера. */
    private const MAX_FIELD = 500;

    public static function isEnabled(): bool
    {
        return TelegramBot::isConfigured() && self::chatIds() !== [];
    }

    /** @return array<int, int> */
    public static function chatIds(): array
    {
        return self::parseChatIds(Setting::get('telegram_notify_chat_ids', ''));
    }

    /**
     * Разбирает список chat_id из настройки: разделители — запятая, точка с
     * запятой, пробелы, переводы строк; мусор пропускается (чистая функция).
     *
     * @return array<int, int>
     */
    public static function parseChatIds(string $raw): array
    {
        $ids = [];
        foreach (preg_split('/[\s,;]+/', trim($raw)) ?: [] as $part) {
            if ($part !== '' && preg_match('/^-?\d{1,20}$/', $part) === 1) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Текст уведомления о заявке (чистая функция): название формы + поля.
     *
     * @param array<string, string> $fields
     */
    public static function formatSubmission(string $formName, array $fields): string
    {
        $lines = ["\u{1F4E9} Новая заявка: " . $formName];
        foreach ($fields as $key => $value) {
            $value = trim((string) $value);
            if (mb_strlen($value) > self::MAX_FIELD) {
                $value = mb_substr($value, 0, self::MAX_FIELD) . '…';
            }
            $lines[] = $key . ': ' . $value;
        }

        $text = implode("\n", $lines);
        if (mb_strlen($text) > self::MAX_TEXT) {
            $text = mb_substr($text, 0, self::MAX_TEXT) . '…';
        }

        return $text;
    }

    /**
     * Шлёт уведомление о новой заявке всем получателям.
     * Возвращает число успешных доставок (0 — если канал не настроен).
     *
     * @param array<string, string> $fields
     */
    public static function notifySubmission(string $formName, array $fields): int
    {
        if (!self::isEnabled()) {
            return 0;
        }

        $text = self::formatSubmission($formName, $fields);
        $sent = 0;
        foreach (self::chatIds() as $chatId) {
            if (TelegramBot::sendMessage($chatId, $text)) {
                $sent++;
            }
        }

        return $sent;
    }
}
