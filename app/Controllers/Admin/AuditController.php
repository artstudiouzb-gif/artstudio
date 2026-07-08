<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

/**
 * Журнал действий администраторов: просмотр с фильтрами по пользователю,
 * пути, датам; пагинация. Только супер-админ.
 */
final class AuditController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        Auth::requireSuperAdmin();

        $filters = [
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'from' => trim((string) ($_GET['from'] ?? '')),
            'to' => trim((string) ($_GET['to'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = AuditLog::search($filters, $page, self::PER_PAGE);

        View::render('admin/audit/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => (int) ceil($result['total'] / self::PER_PAGE),
            'filters' => $filters,
            'actors' => AuditLog::actors(),
        ]);
    }
}
