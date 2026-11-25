<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/ArticleController.php
 * 模块：后台文章管理
 * 职责：文章增删改查、分类、封面上传、发布、草稿和归档状态管理。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Article;
use App\Models\Category;
use App\Support\ImageUpload;
use App\Support\Slugger;
use Core\Auth;
use Core\Paginator;
use Core\Response;

final class ArticleController extends AdminController
{
    private const PER_PAGE = 15;

    /** @var array<int, string> */
    private const STATUSES = ['draft', 'published', 'archived'];

    public function index(): Response
    {
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, self::STATUSES, true) ? $status : '';
        $categoryId = (int) $this->request->query('category_id', 0);
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';

        $model = new Article();
        $total = $model->countAdmin($status, $categoryId, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/articles/index', '文章管理', 'articles', [
            'articles' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $status, $categoryId, $query),
            'categories' => (new Category())->adminAll(),
            'query' => $query,
            'status' => $status,
            'categoryId' => $categoryId,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '文章管理'],
        ]);
    }

    public function create(): Response
    {
        return $this->formPage(null);
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validateInput(null);
        $newCover = $this->storeCover($errors);
        if ($errors !== []) {
            if ($newCover !== null) {
                ImageUpload::removeManaged($newCover);
            }
            return $this->backWithErrors('/admin/articles/create', $errors, $data['old']);
        }

        $data['cover_path'] = $newCover;
        $data['author_id'] = Auth::id();
        $data['published_at'] = $data['status'] === 'published' ? date('Y-m-d H:i:s') : null;
        $articleId = (new Article())->createArticle($data);
        $this->logAction('articles', 'create', '创建文章：' . $data['title'], 'article', $articleId);
        $this->success('文章已创建。');

        return $this->redirect('/admin/articles');
    }

    public function edit(string $id): Response
    {
        $article = (new Article())->findAdmin((int) $id);
        if ($article === null) {
            abort(404, '文章不存在。');
        }

        return $this->formPage($article);
    }

    public function update(string $id): Response
    {
        $articleId = (int) $id;
        $model = new Article();
        $article = $model->findAdmin($articleId);
        if ($article === null) {
            abort(404, '文章不存在。');
        }

        [$data, $errors] = $this->validateInput($article);
        $newCover = $this->storeCover($errors);
        if ($errors !== []) {
            if ($newCover !== null) {
                ImageUpload::removeManaged($newCover);
            }
            return $this->backWithErrors('/admin/articles/' . $articleId . '/edit', $errors, $data['old']);
        }

        $coverPath = (string) ($article['cover_path'] ?? '');
        if ($newCover !== null) {
            $coverPath = $newCover;
        } elseif ($this->checkbox('remove_cover')) {
            $coverPath = '';
        }
        $oldCover = (string) ($article['cover_path'] ?? '');
        $data['cover_path'] = $coverPath === '' ? null : $coverPath;
        $data['author_id'] = (int) $article['author_id'];
        if ($data['status'] === 'published') {
            $data['published_at'] = $article['published_at'] ?? date('Y-m-d H:i:s');
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        } else {
            $data['published_at'] = $article['published_at'] ?? null;
        }

        $model->updateArticle($articleId, $data);
        if (($newCover !== null || $coverPath === '') && $oldCover !== $coverPath) {
            ImageUpload::removeManaged($oldCover);
        }

        $this->logAction('articles', 'update', '更新文章：' . $data['title'], 'article', $articleId);
        $this->success('文章已更新。');

        return $this->redirect('/admin/articles');
    }

    public function status(string $id): Response
    {
        $articleId = (int) $id;
        $model = new Article();
        $article = $model->findAdmin($articleId);
        if ($article === null) {
            abort(404, '文章不存在。');
        }

        $status = $this->text('status', 20);
        if (!in_array($status, self::STATUSES, true)) {
            $this->error('文章状态不合法。');
            return $this->redirect('/admin/articles');
        }

        $model->setStatus($articleId, $status);
        $this->logAction('articles', 'status', '文章状态改为 ' . $status . '：' . $article['title'], 'article', $articleId);
        $this->success('文章状态已更新。');

        return $this->redirect('/admin/articles');
    }

    public function destroy(string $id): Response
    {
        $articleId = (int) $id;
        $model = new Article();
        $article = $model->findAdmin($articleId);
        if ($article === null) {
            abort(404, '文章不存在。');
        }

        $model->deleteArticle($articleId);
        ImageUpload::removeManaged((string) ($article['cover_path'] ?? ''));
        $this->logAction('articles', 'delete', '删除文章：' . $article['title'], 'article', $articleId);
        $this->success('文章已删除。');

        return $this->redirect('/admin/articles');
    }

    /**
     * @param array<string, mixed>|null $article
     */
    private function formPage(?array $article): Response
    {
        $editing = $article !== null;
        $categories = (new Category())->adminAll();

        return $this->page('admin/articles/form', $editing ? '编辑文章' : '新增文章', 'articles', [
            'article' => $article,
            'editing' => $editing,
            'categories' => $categories,
            'statusOptions' => [
                'draft' => '草稿',
                'published' => '发布',
                'archived' => '归档',
            ],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '文章管理', 'url' => '/admin/articles'],
            ['label' => $editing ? '编辑文章' : '新增文章'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $article
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?array $article): array
    {
        $title = $this->text('title', 200);
        $slugInput = $this->text('slug', 220);
        $slug = Slugger::normalize($slugInput);
        if ($slug === '') {
            $slug = Slugger::fromTitle($title, 'article');
        }
        $summary = $this->text('summary', 500);
        $content = $this->text('content', 60000);
        $categoryId = $this->integer('category_id');
        $status = $this->text('status', 20);
        $isFeatured = $this->checkbox('is_featured');
        $errors = [];

        if (mb_strlen($title) < 2) {
            $errors['title'] = '文章标题至少 2 个字符。';
        }
        if (!Slugger::isValid($slug)) {
            $errors['slug'] = 'URL 别名只能使用小写字母、数字和连字符。';
        } elseif ((new Article())->slugExists($slug, $article === null ? null : (int) $article['id'])) {
            $errors['slug'] = 'URL 别名已被其他文章使用。';
        }
        if (mb_strlen($summary) < 5) {
            $errors['summary'] = '文章摘要至少 5 个字符。';
        }
        if (mb_strlen($content) < 10) {
            $errors['content'] = '文章正文至少 10 个字符。';
        }
        if ((new Category())->find($categoryId) === null) {
            $errors['category_id'] = '请选择有效分类。';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = '文章状态不合法。';
        }

        return [[
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'content' => $content,
            'category_id' => $categoryId,
            'status' => $status,
            'is_featured' => $isFeatured ? 1 : 0,
            'cover_path' => null,
            'published_at' => null,
            'old' => [
                'title' => $title,
                'slug' => $slug,
                'summary' => $summary,
                'content' => $content,
                'category_id' => $categoryId,
                'status' => $status,
                'is_featured' => $isFeatured ? 1 : 0,
            ],
        ], $errors];
    }

    /**
     * @param array<string, string> $errors
     */
    private function storeCover(array &$errors): ?string
    {
        try {
            return ImageUpload::store(
                $this->request->file('cover'),
                'articles',
                5 * 1024 * 1024
            );
        } catch (\Throwable $e) {
            $errors['cover'] = '封面上传失败：' . $e->getMessage();
            return null;
        }
    }
}
