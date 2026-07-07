<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Демо-наполнение сайта: новости, документы, вакансии, тендеры, руководство,
 * типовые госстраницы (О организации, Руководство, Структура, Антикоррупция) и
 * пункты меню. Идемпотентно — записи создаются только если их ещё нет (по
 * slug/url). Вызывается и из CLI (database/seed_demo.php), и из админки.
 */
final class DemoSeeder
{
    /** @return array<string,int> счётчики добавленного по разделам */
    public static function run(PDO $pdo): array
    {
        $c = ['news' => 0, 'documenty' => 0, 'vakansii' => 0, 'tendery' => 0, 'team' => 0, 'pages' => 0, 'menu' => 0];

        self::seedNews($pdo, $c);
        self::seedEntries($pdo, $c);
        self::seedTeam($pdo, $c);
        self::seedPages($pdo, $c);
        self::seedMenu($pdo, $c);

        return $c;
    }

    private static function seedNews(PDO $pdo, array &$c): void
    {
        $news = [
            ['Запуск обновлённого официального портала', 'zapusk-portala', 'Представлен новый сайт организации с современным дизайном, удобной навигацией и версией для слабовидящих.'],
            ['График приёма граждан на квартал', 'grafik-priema', 'Опубликовано расписание личного приёма граждан руководством организации.'],
            ['Итоги деятельности за год', 'itogi-goda', 'Подведены основные итоги работы и ключевые показатели за отчётный период.'],
            ['Расширен перечень электронных услуг', 'elektronnye-uslugi', 'Теперь больше документов можно получить онлайн без личного визита.'],
            ['Объявлен новый набор специалистов', 'nabor-specialistov', 'Открыты вакансии в нескольких подразделениях. Подробности — в разделе «Вакансии».'],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO news (title, slug, excerpt, content, status, published_at, created_at)
             SELECT :t, :s, :e, :co, 'published', NOW() - INTERVAL :d DAY, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news WHERE slug = :s2)"
        );
        foreach ($news as $i => $n) {
            $ins->execute([':t' => $n[0], ':s' => $n[1], ':e' => $n[2], ':co' => '<p>' . $n[2] . '</p><p>Полный текст материала.</p>', ':d' => $i * 2, ':s2' => $n[1]]);
            $c['news'] += $ins->rowCount();
        }
    }

    private static function seedEntries(PDO $pdo, array &$c): void
    {
        $ins = $pdo->prepare(
            "INSERT INTO content_entries (type_id, title, slug, status, data, created_at)
             SELECT :tid, :t, :s, 'published', :d, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM content_entries WHERE type_id = :tid2 AND slug = :s2)"
        );
        $byType = [
            'documenty' => [
                ['Приказ №112 об утверждении регламента', 'prikaz-112', ['doc_number' => '112', 'doc_date' => '2026-05-14', 'category' => 'Приказы', 'summary' => 'Об утверждении регламента предоставления государственных услуг.']],
                ['Постановление №34 о мерах поддержки', 'postanovlenie-34', ['doc_number' => '34', 'doc_date' => '2026-04-02', 'category' => 'Постановления', 'summary' => 'О мерах по улучшению качества обслуживания граждан.']],
                ['Приказ №118 о структуре организации', 'prikaz-118', ['doc_number' => '118', 'doc_date' => '2026-05-28', 'category' => 'Приказы', 'summary' => 'Об утверждении организационной структуры.']],
                ['Регламент рассмотрения обращений', 'reglament-obrashcheniy', ['doc_number' => '7-Р', 'doc_date' => '2026-03-10', 'category' => 'Регламенты', 'summary' => 'Порядок и сроки рассмотрения обращений граждан.']],
                ['Отчёт о деятельности за год', 'otchet-god', ['doc_number' => 'ОТ-2026', 'doc_date' => '2026-01-20', 'category' => 'Отчёты', 'summary' => 'Годовой отчёт о результатах деятельности.']],
                ['Положение об антикоррупционной политике', 'polozhenie-antikorrupciya', ['doc_number' => '5-П', 'doc_date' => '2026-02-15', 'category' => 'Положения', 'summary' => 'Основные принципы противодействия коррупции.']],
            ],
            'vakansii' => [
                ['Ведущий специалист отдела ИТ', 'vedushchiy-it', ['department' => 'Отдел информационных технологий', 'salary' => 'по договорённости', 'deadline' => '2026-08-31', 'requirements' => 'Высшее образование, опыт от 3 лет, знание PHP/MySQL.', 'duties' => 'Сопровождение и развитие информационных систем.']],
                ['Юрисконсульт', 'yuriskonsult', ['department' => 'Юридический отдел', 'salary' => 'от 8 000 000 сум', 'deadline' => '2026-08-20', 'requirements' => 'Высшее юридическое образование, опыт от 2 лет.', 'duties' => 'Правовое сопровождение деятельности организации.']],
                ['Специалист по кадрам', 'specialist-kadry', ['department' => 'Отдел кадров', 'salary' => 'от 6 000 000 сум', 'deadline' => '2026-09-10', 'requirements' => 'Опыт кадрового делопроизводства.', 'duties' => 'Ведение кадрового учёта и документации.']],
                ['Пресс-секретарь', 'press-sekretar', ['department' => 'Пресс-служба', 'salary' => 'по итогам собеседования', 'deadline' => '2026-08-05', 'requirements' => 'Опыт в СМИ или PR, грамотная речь.', 'duties' => 'Взаимодействие со СМИ, ведение новостей сайта.']],
            ],
            'tendery' => [
                ['Поставка компьютерной техники', 'postavka-tekhniki', ['tender_number' => 'T-2026-014', 'budget' => '350 000 000 сум', 'start_date' => '2026-06-01', 'deadline' => '2026-07-15', 'summary' => 'Закупка рабочих станций и периферии.']],
                ['Ремонт административного здания', 'remont-zdaniya', ['tender_number' => 'T-2026-019', 'budget' => '1 200 000 000 сум', 'start_date' => '2026-06-10', 'deadline' => '2026-08-01', 'summary' => 'Капитальный ремонт помещений.']],
                ['Услуги охраны объектов', 'uslugi-ohrany', ['tender_number' => 'T-2026-021', 'budget' => '480 000 000 сум', 'start_date' => '2026-06-20', 'deadline' => '2026-07-30', 'summary' => 'Физическая охрана административных объектов.']],
                ['Разработка мобильного приложения', 'razrabotka-prilozheniya', ['tender_number' => 'T-2026-025', 'budget' => '600 000 000 сум', 'start_date' => '2026-07-01', 'deadline' => '2026-08-20', 'summary' => 'Создание мобильного приложения для граждан.']],
            ],
        ];
        foreach ($byType as $slug => $rows) {
            $tid = self::typeId($pdo, $slug);
            if ($tid === null) {
                continue;
            }
            foreach ($rows as $r) {
                $ins->execute([':tid' => $tid, ':t' => $r[0], ':s' => $r[1], ':d' => json_encode($r[2], JSON_UNESCAPED_UNICODE), ':tid2' => $tid, ':s2' => $r[1]]);
                $c[$slug] += $ins->rowCount();
            }
        }
    }

    private static function seedTeam(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'team_members'")->fetchColumn()) {
            return;
        }
        $team = [
            ['Ахмедов Рустам Каримович', 'Директор'],
            ['Юлдашева Нилуфар Азизовна', 'Заместитель директора'],
            ['Каримов Бехзод Шухратович', 'Начальник юридического отдела'],
            ['Исмоилова Дилноза Фарходовна', 'Руководитель пресс-службы'],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO team_members (name, position, status, sort_order, created_at)
             SELECT :n, :p, 'published', :o, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM team_members WHERE name = :n2)"
        );
        foreach ($team as $i => $t) {
            $ins->execute([':n' => $t[0], ':p' => $t[1], ':o' => $i, ':n2' => $t[0]]);
            $c['team'] += $ins->rowCount();
        }
    }

    private static function seedPages(PDO $pdo, array &$c): void
    {
        // [slug, title, [blocks: [type, title, data]]]
        $pages = [
            ['o-nas', 'Об организации', [
                ['text', 'О нас', ['title' => 'Об организации', 'content' => '<p>Официальный сайт организации. Здесь публикуется актуальная информация о деятельности, документы, новости, вакансии и тендеры.</p><p>Раздел можно отредактировать в конструкторе страниц.</p>']],
            ]],
            ['rukovodstvo', 'Руководство', [
                ['text', 'Введение', ['title' => 'Руководство', 'content' => '<p>Руководящий состав организации.</p>']],
                ['team_list', 'Команда', ['title' => 'Руководящий состав', 'limit' => 0]],
            ]],
            ['struktura', 'Структура', [
                ['text', 'Структура', ['title' => 'Организационная структура', 'content' => '<p>Организация включает профильные подразделения: юридический отдел, отдел информационных технологий, отдел кадров, пресс-службу и другие структурные единицы.</p>']],
            ]],
            ['antikorrupciya', 'Противодействие коррупции', [
                ['text', 'Антикоррупция', ['title' => 'Противодействие коррупции', 'content' => '<p>Организация проводит последовательную антикоррупционную политику. Ознакомиться с нормативными документами можно в разделе «Документы».</p><p>Сообщить о фактах коррупции можно через форму обратной связи.</p>']],
            ]],
        ];
        $pageIns = $pdo->prepare(
            "INSERT INTO pages (title, slug, status, is_home, layout_type, created_at)
             SELECT :t, :s, 'published', 0, 'no_sidebar', NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = :s2)"
        );
        $blockIns = $pdo->prepare(
            'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
             VALUES (:pid, :lang, :ty, :ti, :d, :so, 1, NOW())'
        );
        $lang = self::defaultLang($pdo);
        foreach ($pages as [$slug, $title, $blocks]) {
            $pageIns->execute([':t' => $title, ':s' => $slug, ':s2' => $slug]);
            $c['pages'] += $pageIns->rowCount();
            $pid = self::pageId($pdo, $slug);
            if ($pid === null) {
                continue;
            }
            // Блоки добавляем только если страница пустая (не дублируем).
            $hasBlocks = (int) $pdo->query('SELECT COUNT(*) FROM blocks WHERE page_id = ' . $pid)->fetchColumn() > 0;
            if ($hasBlocks) {
                continue;
            }
            $order = 1;
            foreach ($blocks as [$type, $btitle, $data]) {
                $blockIns->execute([':pid' => $pid, ':lang' => $lang, ':ty' => $type, ':ti' => $btitle, ':d' => json_encode($data, JSON_UNESCAPED_UNICODE), ':so' => $order++]);
            }
        }
    }

    private static function seedMenu(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'menu_items'")->fetchColumn()) {
            return;
        }
        $items = [
            ['Об организации', '/o-nas'],
            ['Руководство', '/rukovodstvo'],
            ['Структура', '/struktura'],
            ['Новости', '/news'],
            ['Документы', '/catalog/documenty'],
            ['Вакансии', '/catalog/vakansii'],
            ['Тендеры', '/catalog/tendery'],
            ['Противодействие коррупции', '/antikorrupciya'],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO menu_items (lang, title, url_type, url_value, sort_order, is_active, created_at)
             SELECT '', :t, 'custom', :u, :o, 1, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url_value = :u2)"
        );
        foreach ($items as $i => $it) {
            $ins->execute([':t' => $it[0], ':u' => $it[1], ':o' => $i, ':u2' => $it[1]]);
            $c['menu'] += $ins->rowCount();
        }
    }

    private static function typeId(PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM content_types WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private static function pageId(PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private static function defaultLang(PDO $pdo): string
    {
        try {
            $code = $pdo->query('SELECT code FROM languages WHERE is_default = 1 LIMIT 1')->fetchColumn();
            if ($code !== false && $code !== '') {
                return (string) $code;
            }
        } catch (\Throwable $e) {
            // таблица языков может отсутствовать в минимальной установке
        }

        return 'ru';
    }
}
