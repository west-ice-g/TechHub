<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/ArticleController.php
 * 模块：技术文章展示
 * 职责：分页展示文章、按分类筛选、关键词搜索，并加载文章详情和浏览量。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use App\Models\Category;
use Core\Controller;
use Core\Paginator;
use Core\Response;

final class ArticleController extends Controller
{
    private const PER_PAGE = 5;

    public function index(): Response
    {
        $categorySlug = $this->request->query('category', '');
        $categorySlug = is_string($categorySlug) && mb_check_encoding($categorySlug, 'UTF-8')
            ? trim($categorySlug)
            : '';

        $q = $this->request->query('q', '');
        $q = is_string($q) && mb_check_encoding($q, 'UTF-8') ? mb_substr(trim($q), 0, 80) : '';

        $categoryModel = new Category();
        $categories = $categoryModel->enabledWithPublishedCounts();
        $activeCategory = null;
        if ($categorySlug !== '') {
            $activeCategory = $categoryModel->findEnabledBySlug($categorySlug);
            if ($activeCategory === null) {
                abort(404, '文章分类不存在。');
            }
        }

        $model = new Article();
        $total = $model->countPublished($categorySlug, $q);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);
        $articles = $model->publishedPage(self::PER_PAGE, $pagination['offset'], $categorySlug, $q);

        return $this->view('article/index', [
            'title' => ($activeCategory === null ? '技术文章' : $activeCategory['name'] . ' · 技术文章') . ' · TechHub',
            'articles' => $articles,
            'categories' => $categories,
            'categorySlug' => $categorySlug,
            'activeCategory' => $activeCategory,
            'q' => $q,
            'pagination' => $pagination,
            'paginationPath' => '/articles',
            'paginationQuery' => ['category' => $categorySlug, 'q' => $q],
        ]);
    }

    public function show(string $slug): Response
    {
        $model = new Article();
        $article = $model->findPublishedBySlug($slug);
        if ($article === null) {
            abort(404, '文章不存在或未发布。');
        }

        $model->incrementViewCount((int) $article['id']);
        $article['view_count'] = (int) $article['view_count'] + 1;

        return $this->view('article/show', [
            'title' => $article['title'] . ' · 技术文章',
            'article' => $article,
        ]);
    }
}
