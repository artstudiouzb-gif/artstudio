<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\ImageField;
use App\Core\Slug;
use App\Core\View;
use App\Models\Language;
use App\Models\News;
use App\Models\NewsTranslation;

final class NewsController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('admin/news/index', ['items' => News::all()]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        View::render('admin/news/form', ['news' => null, 'translations' => [], 'error' => null]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        [$data, $error] = $this->collectInput(null);

        if ($error !== null) {
            View::render('admin/news/form', ['news' => $data, 'translations' => [], 'error' => $error]);
            return;
        }

        $id = News::create($data);
        $this->saveTranslations($id);

        Flash::success('Новость создана.');
        header('Location: /admin/news');
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $news = News::findById((int) $params['id']);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('admin/news/form', [
            'news' => $news,
            'translations' => NewsTranslation::forNews((int) $news['id']),
            'error' => null,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $id = (int) $params['id'];
        $news = News::findById($id);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        [$data, $error] = $this->collectInput($id, $news);

        if ($error !== null) {
            View::render('admin/news/form', [
                'news' => array_merge($news, $data),
                'translations' => NewsTranslation::forNews($id),
                'error' => $error,
            ]);
            return;
        }

        News::update($id, $data);
        $this->saveTranslations($id);

        Flash::success('Новость обновлена.');
        header('Location: /admin/news');
        exit;
    }

    /**
     * Сохраняет переводы для всех НЕ-дефолтных активных языков из полей
     * translations[<lang>][...].
     */
    private function saveTranslations(int $newsId): void
    {
        $defaultCode = Language::defaultCode();
        $input = (array) ($_POST['translations'] ?? []);

        foreach (Language::active() as $lang) {
            $code = (string) $lang['code'];
            if ($code === $defaultCode) {
                continue;
            }
            $t = (array) ($input[$code] ?? []);
            NewsTranslation::upsert($newsId, $code, [
                'title' => trim((string) ($t['title'] ?? '')),
                'excerpt' => trim((string) ($t['excerpt'] ?? '')),
                'content' => (string) ($t['content'] ?? ''),
                'meta_title' => trim((string) ($t['meta_title'] ?? '')),
                'meta_description' => trim((string) ($t['meta_description'] ?? '')),
            ]);
        }
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        News::delete((int) $params['id']);
        Flash::success('Новость удалена.');
        header('Location: /admin/news');
        exit;
    }

    /**
     * @return array{0: array, 1: string|null}
     */
    private function collectInput(?int $id, ?array $existing = null): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
        $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));

        if ($title === '') {
            return [['title' => $title, 'slug' => $slugInput, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status], 'Укажите заголовок новости.'];
        }

        $slug = $slugInput !== '' ? Slug::make($slugInput) : Slug::make($title);
        if (News::slugExists($slug, $id)) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $publishedAt = $publishedAtInput !== '' ? str_replace('T', ' ', $publishedAtInput) . ':00' : date('Y-m-d H:i:s');

        $image = ImageField::resolve('image_file', 'image_url', $existing['image'] ?? null, Auth::id());

        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'content' => $content,
            'image' => $image,
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'status' => $status,
            'published_at' => $publishedAt,
            'author_id' => Auth::id(),
        ];

        return [$data, null];
    }
}
