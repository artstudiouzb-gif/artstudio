<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\DemoSeeder;
use App\Core\Flash;
use App\Core\View;

final class DashboardController
{
    /** Загрузка демо-контента (супер-админ). Идемпотентно. */
    public function seedDemo(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        try {
            $c = DemoSeeder::run(Database::pdo());
            Cache::forgetPrefix('page:');
            $added = array_sum($c);
            Flash::success($added > 0
                ? sprintf('Демо-контент загружен: новости +%d, документы +%d, вакансии +%d, тендеры +%d, руководство +%d, страницы +%d, меню +%d.', $c['news'], $c['documenty'], $c['vakansii'], $c['tendery'], $c['team'], $c['pages'], $c['menu'])
                : 'Демо-контент уже загружен — новых записей не добавлено.');
        } catch (\Throwable $e) {
            Flash::error('Не удалось загрузить демо-контент: ' . $e->getMessage());
        }
        header('Location: /admin');
        exit;
    }

    public function index(): void
    {
        Auth::requireLogin();

        $counts = [
            'news' => (int) Database::pdo()->query('SELECT COUNT(*) FROM news')->fetchColumn(),
            'pages' => (int) Database::pdo()->query('SELECT COUNT(*) FROM pages')->fetchColumn(),
            'projects' => (int) Database::pdo()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
            'team' => (int) Database::pdo()->query('SELECT COUNT(*) FROM team_members')->fetchColumn(),
            'forms' => (int) Database::pdo()->query('SELECT COUNT(*) FROM forms')->fetchColumn(),
            'submissions_unread' => (int) Database::pdo()->query('SELECT COUNT(*) FROM form_submissions WHERE is_read = 0')->fetchColumn(),
            'files' => (int) Database::pdo()->query('SELECT COUNT(*) FROM files')->fetchColumn(),
        ];

        View::render('admin/dashboard', ['user' => Auth::user(), 'counts' => $counts]);
    }
}
