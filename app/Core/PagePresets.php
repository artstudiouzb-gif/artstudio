<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Готовые сборки страниц: набор блоков с уже расставленными отступами, фонами
 * и текстами-рыбой, который редактор применяет одной кнопкой и дальше просто
 * заменяет содержимое.
 *
 * Формат совпадает со снимком BlockSnippet::captureFromPage(), поэтому
 * применение идёт через BlockSnippet::applyToPage() — своего кода вставки нет.
 *
 * ПРАВИЛА РИТМА (почему у блоков именно такие _bg/_spacing):
 *  - Фоны чередуются: none → light/tint → none. Две подряд подложки читаются
 *    как одна большая секция и ломают ритм страницы.
 *  - Тёмная секция (navy) на странице одна и стоит последней — это призыв к
 *    действию, а не рядовой блок.
 *  - _fullwidth ставится только вместе с фоном: без подложки растягивать
 *    нечего, а контент всё равно остаётся в контейнере.
 *  - Отступы: hero — max, смысловые секции — premium, вспомогательные
 *    (контакты, документы под текстом) — small.
 *  - Анимация появления — только у карточных секций ниже первого экрана;
 *    hero анимировать нельзя, он виден сразу и мигал бы при загрузке.
 */
final class PagePresets
{
    /** Оформление секции: фон + отступы + анимация одной строкой. */
    private static function look(string $bg = 'none', string $spacing = 'premium', string $reveal = ''): array
    {
        return [
            '_bg' => $bg,
            '_fullwidth' => $bg !== 'none',
            '_spacing' => $spacing,
            '_reveal' => $reveal !== ''
                ? ['enabled' => true, 'type' => $reveal]
                : ['enabled' => false, 'type' => 'fade'],
        ];
    }

    /**
     * @return array<string, array{name:string, description:string, outline:list<string>, blocks:list<array<string,mixed>>}>
     */
    public static function all(): array
    {
        return [
            'department' => self::department(),
            'service' => self::service(),
            'about' => self::about(),
            'press' => self::press(),
            'contacts' => self::contacts(),
            'project' => self::project(),
        ];
    }

    /** @return array{name:string, description:string, outline:list<string>, blocks:list<array<string,mixed>>}|null */
    public static function find(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    /** Страница структурного подразделения. */
    private static function department(): array
    {
        return [
            'name' => 'Страница подразделения',
            'description' => 'Управление, департамент или отдел: чем занимается, кто руководит, документы и контакты.',
            'outline' => ['Заголовок раздела', 'О подразделении с фото', 'Руководство', 'Документы', 'Контакты', 'Призыв обратиться'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок раздела',
                    'data' => array_merge([
                        'eyebrow' => 'Структура агентства',
                        'title' => 'Название подразделения',
                        'subtitle' => 'Одно предложение о том, чем занимается подразделение и за что отвечает.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'text_image',
                    'title' => 'О подразделении',
                    'data' => array_merge([
                        'title' => 'Задачи и полномочия',
                        'text' => "<p>Опишите основные направления работы подразделения: какие задачи решает, "
                            . "с какими организациями взаимодействует, за какие услуги отвечает.</p>"
                            . "<p>Два-три абзаца достаточно — детали лучше вынести в документы ниже.</p>",
                        'items' => [
                            ['label' => 'Направление работы', 'icon_svg' => ''],
                            ['label' => 'Второе направление', 'icon_svg' => ''],
                            ['label' => 'Третье направление', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'person_cards',
                    'title' => 'Руководство',
                    'data' => array_merge([
                        'title' => 'Руководство подразделения',
                        'items' => [
                            ['name' => 'Фамилия Имя Отчество', 'role' => 'Начальник управления', 'photo' => '', 'url' => ''],
                            ['name' => 'Фамилия Имя Отчество', 'role' => 'Заместитель начальника', 'photo' => '', 'url' => ''],
                        ],
                    ], self::look('light', 'premium', 'slide-up')),
                ],
                [
                    'type' => 'docs_list',
                    'title' => 'Документы',
                    'data' => array_merge([
                        'title' => 'Документы подразделения',
                        'columns' => 3,
                        'items' => [
                            ['title' => 'Положение о подразделении', 'meta' => 'PDF', 'url' => ''],
                            ['title' => 'Регламент работы', 'meta' => 'PDF', 'url' => ''],
                            ['title' => 'План мероприятий', 'meta' => 'PDF', 'url' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'contact_cards',
                    'title' => 'Контакты',
                    'data' => array_merge([
                        'title' => 'Как связаться',
                        'items' => [
                            ['title' => 'Приёмная', 'lines' => "+998 (71) 000-00-00\nПн–Пт, 9:00–18:00", 'link_text' => '', 'link_url' => '', 'icon_svg' => ''],
                            ['title' => 'Электронная почта', 'lines' => 'info@example.uz', 'link_text' => 'Написать', 'link_url' => 'mailto:info@example.uz', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'small')),
                ],
                self::ctaBand('Не нашли нужную информацию?', 'Направьте обращение — ответим в установленный законом срок.'),
            ],
        ];
    }

    /** Страница услуги или направления деятельности. */
    private static function service(): array
    {
        return [
            'name' => 'Услуга или направление',
            'description' => 'Что это, кому положено, порядок получения, документы и частые вопросы.',
            'outline' => ['Заголовок услуги', 'Описание', 'Порядок получения', 'Необходимые документы', 'Частые вопросы', 'Призыв подать заявление'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок услуги',
                    'data' => array_merge([
                        'eyebrow' => 'Услуги',
                        'title' => 'Название услуги',
                        'subtitle' => 'Кратко: кому услуга адресована и какой результат получает заявитель.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                        'button_text' => 'Подать заявление',
                        'button_url' => '',
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'text',
                    'title' => 'Описание',
                    'data' => array_merge([
                        'title' => 'Кому и на каких условиях',
                        'content' => "<p>Опишите, кто имеет право на услугу, в какой срок она оказывается и "
                            . "сколько стоит (или укажите, что бесплатно).</p>"
                            . "<p>Сложные условия лучше вынести в список — так их проще читать.</p>",
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'stages',
                    'title' => 'Порядок получения',
                    'data' => array_merge([
                        'title' => 'Порядок получения',
                        'items' => [
                            ['stage' => 'Шаг 1', 'title' => 'Подготовьте документы', 'text' => 'Список — в разделе ниже.', 'year' => '', 'status' => '', 'status_text' => ''],
                            ['stage' => 'Шаг 2', 'title' => 'Подайте заявление', 'text' => 'Лично или через электронную форму.', 'year' => '', 'status' => '', 'status_text' => ''],
                            ['stage' => 'Шаг 3', 'title' => 'Получите результат', 'text' => 'Срок рассмотрения — до 15 рабочих дней.', 'year' => '', 'status' => '', 'status_text' => ''],
                        ],
                    ], self::look('light', 'premium', 'slide-up')),
                ],
                [
                    'type' => 'docs_list',
                    'title' => 'Необходимые документы',
                    'data' => array_merge([
                        'title' => 'Необходимые документы',
                        'columns' => 2,
                        'items' => [
                            ['title' => 'Заявление по форме', 'meta' => 'DOCX', 'url' => ''],
                            ['title' => 'Копия документа, удостоверяющего личность', 'meta' => '', 'url' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'faq',
                    'title' => 'Частые вопросы',
                    'data' => array_merge([
                        'title' => 'Частые вопросы',
                        'items' => [
                            ['question' => 'Сколько рассматривается заявление?', 'answer' => 'Укажите срок в рабочих днях и основание.'],
                            ['question' => 'Можно ли подать документы онлайн?', 'answer' => 'Опишите электронный способ подачи, если он есть.'],
                            ['question' => 'В каком случае откажут?', 'answer' => 'Перечислите основания для отказа со ссылкой на норму.'],
                        ],
                    ], self::look('tint', 'premium', 'fade')),
                ],
                self::ctaBand('Готовы подать заявление?', 'Заполните электронную форму — это займёт несколько минут.', 'Подать заявление'),
            ],
        ];
    }

    /** Страница «О ведомстве». */
    private static function about(): array
    {
        return [
            'name' => 'О ведомстве',
            'description' => 'Миссия, цифры, направления работы, история и руководство — представительская страница.',
            'outline' => ['Заголовок', 'Миссия', 'В цифрах', 'Направления работы', 'История', 'Руководство', 'Призыв к сотрудничеству'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок',
                    'data' => array_merge([
                        'eyebrow' => 'Об агентстве',
                        'title' => 'Кратко о ведомстве',
                        'subtitle' => 'Одно предложение о роли ведомства и его главной задаче.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'text',
                    'title' => 'Миссия',
                    'data' => array_merge([
                        'title' => 'Миссия и задачи',
                        'content' => "<p>Два-три абзаца о том, зачем создано ведомство, какие задачи решает "
                            . "и на основании каких документов действует.</p>",
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'counters',
                    'title' => 'В цифрах',
                    'data' => array_merge([
                        'title' => 'Деятельность в цифрах',
                        'items' => [
                            ['value' => '120', 'suffix' => '+', 'label' => 'реализованных проектов', 'icon_svg' => ''],
                            ['value' => '14', 'suffix' => '', 'label' => 'регионов охвата', 'icon_svg' => ''],
                            ['value' => '35', 'suffix' => ' млрд', 'label' => 'привлечённых инвестиций', 'icon_svg' => ''],
                        ],
                    ], self::look('light', 'premium', 'slide-up')),
                ],
                [
                    'type' => 'feature_band',
                    'title' => 'Направления работы',
                    'data' => array_merge([
                        'title' => 'Основные направления',
                        'items' => [
                            ['title' => 'Первое направление', 'text' => 'Короткое пояснение в одну-две строки.', 'icon_svg' => ''],
                            ['title' => 'Второе направление', 'text' => 'Короткое пояснение в одну-две строки.', 'icon_svg' => ''],
                            ['title' => 'Третье направление', 'text' => 'Короткое пояснение в одну-две строки.', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'timeline',
                    'title' => 'История',
                    'data' => array_merge([
                        'title' => 'Этапы развития',
                        'items' => [
                            ['year' => '2019', 'text' => 'Событие, с которого началась работа ведомства.'],
                            ['year' => '2022', 'text' => 'Значимое расширение полномочий или направлений.'],
                            ['year' => '2025', 'text' => 'Текущий этап и приоритеты.'],
                        ],
                    ], self::look('tint', 'premium', 'fade')),
                ],
                [
                    'type' => 'person_cards',
                    'title' => 'Руководство',
                    'data' => array_merge([
                        'title' => 'Руководство',
                        'items' => [
                            ['name' => 'Фамилия Имя Отчество', 'role' => 'Директор', 'photo' => '', 'url' => ''],
                            ['name' => 'Фамилия Имя Отчество', 'role' => 'Первый заместитель', 'photo' => '', 'url' => ''],
                            ['name' => 'Фамилия Имя Отчество', 'role' => 'Заместитель', 'photo' => '', 'url' => ''],
                        ],
                    ], self::look('none', 'premium', 'slide-up')),
                ],
                self::ctaBand('Открыты к сотрудничеству', 'Предложения о партнёрстве и инвестициях направляйте через форму обращения.'),
            ],
        ];
    }

    /** Пресс-центр. */
    private static function press(): array
    {
        return [
            'name' => 'Пресс-центр',
            'description' => 'Лента новостей, фото- и видеоматериалы, подписка на рассылку.',
            'outline' => ['Заголовок', 'Новости и аналитика', 'Медиатека', 'Подписка'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок',
                    'data' => array_merge([
                        'eyebrow' => 'Пресс-центр',
                        'title' => 'Новости и медиаматериалы',
                        'subtitle' => 'Официальные сообщения, фото- и видеохроника мероприятий.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'news_feature',
                    'title' => 'Новости',
                    'data' => array_merge([
                        'title' => 'Новости и аналитика',
                        'all_text' => 'Все новости',
                        'limit' => 6,
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'media_gallery',
                    'title' => 'Медиатека',
                    'data' => array_merge([
                        'title' => 'Фото и видео',
                        'source' => 'media',
                        'limit' => 8,
                    ], self::look('light', 'premium', 'slide-up')),
                ],
                [
                    'type' => 'subscribe',
                    'title' => 'Подписка',
                    'data' => array_merge([
                        'title' => 'Подписка на новости',
                        'text' => 'Получайте дайджест официальных сообщений на почту раз в неделю.',
                        'button_text' => 'Подписаться',
                    ], self::look('none', 'small')),
                ],
            ],
        ];
    }

    /** Контакты. */
    private static function contacts(): array
    {
        return [
            'name' => 'Контакты',
            'description' => 'Реквизиты и приёмная, карта проезда и форма обращения.',
            'outline' => ['Заголовок', 'Контактные данные', 'Карта проезда', 'Форма обращения'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок',
                    'data' => array_merge([
                        'eyebrow' => 'Контакты',
                        'title' => 'Как с нами связаться',
                        'subtitle' => 'Приёмная, электронная почта и адрес для корреспонденции.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'contact_cards',
                    'title' => 'Контактные данные',
                    'data' => array_merge([
                        'title' => 'Контактные данные',
                        'items' => [
                            ['title' => 'Приёмная', 'lines' => "+998 (71) 000-00-00\nПн–Пт, 9:00–18:00", 'link_text' => '', 'link_url' => '', 'icon_svg' => ''],
                            ['title' => 'Электронная почта', 'lines' => 'info@example.uz', 'link_text' => 'Написать', 'link_url' => 'mailto:info@example.uz', 'icon_svg' => ''],
                            ['title' => 'Адрес', 'lines' => "г. Ташкент, ул. Примерная, 1\nИндекс 100000", 'link_text' => '', 'link_url' => '', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'map_point',
                    'title' => 'Карта проезда',
                    'data' => array_merge([
                        'title' => 'Как добраться',
                        'card_title' => 'Главный офис',
                        'address' => 'г. Ташкент, ул. Примерная, 1',
                        'embed_url' => '',
                        'button_text' => 'Открыть в картах',
                        'button_url' => '',
                    ], self::look('light', 'premium', 'fade')),
                ],
                [
                    'type' => 'form',
                    'title' => 'Форма обращения',
                    'data' => array_merge([
                        'form_id' => null,
                    ], self::look('none', 'premium')),
                ],
            ],
        ];
    }

    /** Проект или программа. */
    private static function project(): array
    {
        return [
            'name' => 'Проект или программа',
            'description' => 'Суть проекта, этапы реализации, результаты и фотоотчёт.',
            'outline' => ['Заголовок проекта', 'О проекте с фото', 'Этапы реализации', 'Результаты', 'Фотоотчёт', 'Призыв к участию'],
            'blocks' => [
                [
                    'type' => 'hero',
                    'title' => 'Заголовок проекта',
                    'data' => array_merge([
                        'eyebrow' => 'Проекты',
                        'title' => 'Название проекта',
                        'subtitle' => 'Одно предложение о цели проекта и сроках реализации.',
                        'height' => 'regular',
                        'width' => 'full',
                        'text_position' => 'left',
                        'overlay_opacity' => 55,
                    ], self::look('none', 'max')),
                ],
                [
                    'type' => 'text_image',
                    'title' => 'О проекте',
                    'data' => array_merge([
                        'title' => 'О проекте',
                        'text' => "<p>Опишите задачу, которую решает проект, его масштаб и участников.</p>"
                            . "<p>Укажите источник финансирования и ожидаемый эффект.</p>",
                        'items' => [
                            ['label' => 'Срок реализации: 2025–2027', 'icon_svg' => ''],
                            ['label' => 'Охват: 14 регионов', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'stages',
                    'title' => 'Этапы реализации',
                    'data' => array_merge([
                        'title' => 'Этапы реализации',
                        'items' => [
                            ['stage' => 'Этап 1', 'title' => 'Подготовка', 'text' => 'Проектирование и согласования.', 'year' => '2025', 'status' => 'done', 'status_text' => 'Завершён'],
                            ['stage' => 'Этап 2', 'title' => 'Реализация', 'text' => 'Основные работы по проекту.', 'year' => '2026', 'status' => 'active', 'status_text' => 'В работе'],
                            ['stage' => 'Этап 3', 'title' => 'Ввод в эксплуатацию', 'text' => 'Запуск и оценка результатов.', 'year' => '2027', 'status' => '', 'status_text' => 'Запланирован'],
                        ],
                    ], self::look('light', 'premium', 'slide-up')),
                ],
                [
                    'type' => 'counters',
                    'title' => 'Результаты',
                    'data' => array_merge([
                        'title' => 'Результаты проекта',
                        'items' => [
                            ['value' => '12', 'suffix' => '', 'label' => 'построенных объектов', 'icon_svg' => ''],
                            ['value' => '4500', 'suffix' => '+', 'label' => 'человек охвачено', 'icon_svg' => ''],
                            ['value' => '98', 'suffix' => '%', 'label' => 'выполнение плана', 'icon_svg' => ''],
                        ],
                    ], self::look('none', 'premium', 'fade')),
                ],
                [
                    'type' => 'gallery',
                    'title' => 'Фотоотчёт',
                    'data' => array_merge([
                        'title' => 'Фотоотчёт',
                        'images' => [],
                    ], self::look('none', 'small', 'fade')),
                ],
                self::ctaBand('Хотите участвовать в проекте?', 'Направьте заявку — рассмотрим предложения о партнёрстве.', 'Направить заявку'),
            ],
        ];
    }

    /**
     * Завершающая тёмная полоса. Единственная navy-секция на странице —
     * поэтому вынесена в общий метод, чтобы не расползлась по пресетам.
     */
    private static function ctaBand(string $title, string $text, string $buttonText = 'Написать обращение'): array
    {
        return [
            'type' => 'cta_band',
            'title' => 'Призыв к действию',
            'data' => array_merge([
                'title' => $title,
                'text' => $text,
                'button_text' => $buttonText,
                'button_url' => '',
                'icon_svg' => '',
            ], self::look('navy', 'premium', 'fade')),
        ];
    }
}
