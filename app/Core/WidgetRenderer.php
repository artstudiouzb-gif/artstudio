<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\News;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TeamMember;

/**
 * Модульный рендер виджетов сайдбара, по аналогии с BlockRenderer.
 * Шаблон каждого типа лежит в templates/widgets/{type}.php и подключается
 * изолированно (собственная область видимости переменных). Каждый виджет
 * оборачивается в <aside id="widget-{id}"> для изоляции.
 */
final class WidgetRenderer
{
    private const DEFAULTS = [
        'latest_news' => ['count' => 5],
        'contacts' => ['show_socials' => true],
        'custom_html' => ['html' => ''],
        'projects_list' => ['count' => 5],
        'team_list' => ['count' => 5],
    ];

    public static function render(array $widget, string $lang): string
    {
        $type = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $widget['type'])) ?? '';
        $widgetId = (int) $widget['id'];

        $data = json_decode((string) ($widget['data'] ?? '{}'), true);
        if (!is_array($data)) {
            $data = [];
        }
        // Смердживание с дефолтами: старые/неполные JSON не ломают шаблон.
        $data = array_merge(self::DEFAULTS[$type] ?? [], $data);

        $view = self::buildViewData($type, $data, $lang);

        $templateFile = dirname(__DIR__, 2) . '/templates/widgets/' . $type . '.php';
        if (!is_file($templateFile)) {
            return '';
        }

        $inner = self::renderTemplate($templateFile, $view, $lang);
        $title = trim((string) ($widget['title'] ?? ''));

        return sprintf(
            '<aside id="widget-%d" class="widget widget--%s">%s%s</aside>',
            $widgetId,
            htmlspecialchars($type, ENT_QUOTES),
            $title !== '' ? '<h3 class="widget__title">' . htmlspecialchars($title, ENT_QUOTES) . '</h3>' : '',
            $inner
        );
    }

    private static function buildViewData(string $type, array $data, string $lang): array
    {
        switch ($type) {
            case 'latest_news':
                $data['items'] = News::published((int) ($data['count'] ?? 5), 0, $lang);
                break;
            case 'projects_list':
                $data['items'] = array_slice(Project::published(), 0, (int) ($data['count'] ?? 5));
                break;
            case 'team_list':
                $data['items'] = array_slice(TeamMember::published(), 0, (int) ($data['count'] ?? 5));
                break;
            case 'contacts':
                $data['phone'] = Setting::get('contact_phone');
                $data['email'] = Setting::get('contact_email');
                $data['address'] = Setting::get('contact_address');
                $data['social'] = HeaderConfig::get()['social_buttons'];
                break;
        }

        return $data;
    }

    private static function renderTemplate(string $file, array $data, string $lang): string
    {
        $render = static function (string $__file, array $data, string $lang): void {
            extract(['data' => $data, 'lang' => $lang], EXTR_SKIP);
            require $__file;
        };

        ob_start();
        $render($file, $data, $lang);

        return (string) ob_get_clean();
    }
}
