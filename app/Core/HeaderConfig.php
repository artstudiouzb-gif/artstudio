<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Чтение и запись конфигурации шапки сайта (mini-app конструктор).
 * Хранится JSON-строкой в settings['header_config']. Любые прочитанные
 * данные сливаются с дефолтной структурой, поэтому старые/неполные JSON
 * никогда не приводят к ошибкам обращения к отсутствующим ключам.
 */
final class HeaderConfig
{
    public const DEFAULTS = [
        'logo_position' => 'left',            // left | center
        'menu_position' => 'right',           // left | center | right
        'language_switcher' => [
            'enabled' => true,
            'format' => 'code',               // code | name | flag
        ],
        'social_buttons' => [],               // [{network, url}]
        'cta' => [
            'enabled' => false,
            'text' => '',
            'url' => '',
            'style' => 'filled',              // filled | outline
        ],
    ];

    public static function get(): array
    {
        $raw = Setting::get('header_config', '');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return self::mergeDefaults($decoded);
    }

    public static function save(array $config): void
    {
        $clean = self::mergeDefaults($config);
        Setting::set('header_config', json_encode($clean, JSON_UNESCAPED_UNICODE));
    }

    private static function mergeDefaults(array $config): array
    {
        $result = self::DEFAULTS;

        $result['logo_position'] = in_array($config['logo_position'] ?? '', ['left', 'center'], true)
            ? $config['logo_position'] : self::DEFAULTS['logo_position'];

        $result['menu_position'] = in_array($config['menu_position'] ?? '', ['left', 'center', 'right'], true)
            ? $config['menu_position'] : self::DEFAULTS['menu_position'];

        $ls = (array) ($config['language_switcher'] ?? []);
        $result['language_switcher'] = [
            'enabled' => !empty($ls['enabled']),
            'format' => in_array($ls['format'] ?? '', ['code', 'name', 'flag'], true) ? $ls['format'] : 'code',
        ];

        $result['social_buttons'] = [];
        foreach ((array) ($config['social_buttons'] ?? []) as $btn) {
            $network = trim((string) ($btn['network'] ?? ''));
            $url = trim((string) ($btn['url'] ?? ''));
            if ($network !== '' && $url !== '') {
                $result['social_buttons'][] = ['network' => $network, 'url' => $url];
            }
        }

        $cta = (array) ($config['cta'] ?? []);
        $result['cta'] = [
            'enabled' => !empty($cta['enabled']),
            'text' => trim((string) ($cta['text'] ?? '')),
            'url' => trim((string) ($cta['url'] ?? '')),
            'style' => in_array($cta['style'] ?? '', ['filled', 'outline'], true) ? $cta['style'] : 'filled',
        ];

        return $result;
    }
}
