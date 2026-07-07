<?php

declare(strict_types=1);

/*
 * Демо-наполнение разделов сайта (новости, документы, вакансии, тендеры,
 * руководство, типовые госстраницы и меню). Идемпотентно: записи создаются
 * только если их ещё нет.
 *
 *   php database/seed_demo.php
 *
 * Логика — в App\Core\DemoSeeder (используется и кнопкой в админке).
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../app/Core/bootstrap.php';

$created = \App\Core\DemoSeeder::run(\App\Core\Database::pdo());

fwrite(STDOUT, "Демо-контент добавлен:\n");
foreach ($created as $section => $n) {
    fwrite(STDOUT, sprintf("  %-12s +%d\n", $section, $n));
}
fwrite(STDOUT, "Готово. Записи можно редактировать/удалять в админке.\n");
