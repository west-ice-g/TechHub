<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Article.php
 * 模块：技术文章管理
 * 职责：查询已发布文章、分类筛选、搜索、详情及浏览量更新。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Article extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestPublished(int $limit = 3): array
    {
        $limit = max(1, min(20, $limit));

        return $this->fetchAll(
            "SELECT a.id, a.title, a.slug, a.summary, a.published_at, a.view_count, a.is_featured,
                    a.cover_path, c.name AS category_name, c.slug AS category_slug,
                    u.display_name AS author_name
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             WHERE a.status = 'published'
             ORDER BY a.published_at DESC, a.id DESC
             LIMIT {$limit}"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publishedPage(int $limit, int $offset, string $categorySlug = '', string $query = ''): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);
        [$filterSql, $params] = $this->filter($categorySlug, $query);

        return $this->fetchAll(
            "SELECT a.id, a.title, a.slug, a.summary, a.cover_path, a.published_at,
                    a.view_count, a.is_featured, c.name AS category_name, c.slug AS category_slug,
                    u.display_name AS author_name
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             WHERE a.status = 'published'
               {$filterSql}
             ORDER BY a.published_at DESC, a.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countPublished(string $categorySlug = '', string $query = ''): int
    {
        [$filterSql, $params] = $this->filter($categorySlug, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             WHERE a.status = 'published' {$filterSql}",
            $params
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->fetchOne(
            "SELECT a.id, a.title, a.slug, a.summary, a.content, a.cover_path,
                    a.status, a.is_featured, a.view_count, a.published_at, a.created_at, a.updated_at,
                    c.id AS category_id, c.name AS category_name, c.slug AS category_slug,
                    u.id AS author_id, u.display_name AS author_name,
                    r.name AS author_role, mp.avatar_path AS author_avatar, mp.bio AS author_bio
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE a.slug = :slug AND a.status = 'published'
             LIMIT 1",
            ['slug' => $slug]
        );
    }

    public function incrementViewCount(int $articleId): void
    {
        $this->execute(
            'UPDATE articles SET view_count = view_count + 1 WHERE id = :id',
            ['id' => $articleId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(
        int $limit,
        int $offset,
        string $status = '',
        int $categoryId = 0,
        string $query = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($status, $categoryId, $query);

        return $this->fetchAll(
            "SELECT a.id, a.category_id, a.author_id, a.title, a.slug, a.summary, a.cover_path,
                    a.status, a.is_featured, a.view_count, a.published_at, a.created_at, a.updated_at,
                    c.name AS category_name, u.display_name AS author_name
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             WHERE 1 = 1 {$where}
             ORDER BY a.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $status = '', int $categoryId = 0, string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($status, $categoryId, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             WHERE 1 = 1 {$where}",
            $params
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAdmin(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT a.*, c.name AS category_name, u.display_name AS author_name
             FROM articles a
             INNER JOIN categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.author_id
             WHERE a.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM articles WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createArticle(array $data): int
    {
        return $this->insert(
            'INSERT INTO articles
                (category_id, author_id, title, slug, summary, content, cover_path, status,
                 is_featured, published_at)
             VALUES
                (:category_id, :author_id, :title, :slug, :summary, :content, :cover_path, :status,
                 :is_featured, :published_at)',
            [
                'category_id' => $data['category_id'],
                'author_id' => $data['author_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'cover_path' => $data['cover_path'],
                'status' => $data['status'],
                'is_featured' => $data['is_featured'],
                'published_at' => $data['published_at'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateArticle(int $id, array $data): void
    {
        $this->execute(
            'UPDATE articles
             SET category_id = :category_id, title = :title, slug = :slug, summary = :summary,
                 content = :content, cover_path = :cover_path, status = :status,
                 is_featured = :is_featured, published_at = :published_at
             WHERE id = :id',
            [
                'id' => $id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'cover_path' => $data['cover_path'],
                'status' => $data['status'],
                'is_featured' => $data['is_featured'],
                'published_at' => $data['published_at'],
            ]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        if ($status === 'published') {
            $this->execute(
                "UPDATE articles
                 SET status = 'published', published_at = COALESCE(published_at, CURRENT_TIMESTAMP)
                 WHERE id = :id",
                ['id' => $id]
            );
            return;
        }

        if ($status === 'draft') {
            $this->execute(
                "UPDATE articles SET status = 'draft', published_at = NULL WHERE id = :id",
                ['id' => $id]
            );
            return;
        }

        $this->execute(
            "UPDATE articles SET status = 'archived' WHERE id = :id",
            ['id' => $id]
        );
    }

    public function deleteArticle(int $id): void
    {
        $this->execute('DELETE FROM articles WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminFilter(string $status, int $categoryId, string $query): array
    {
        $where = '';
        $params = [];

        if (in_array($status, ['draft', 'published', 'archived'], true)) {
            $where .= ' AND a.status = :status';
            $params['status'] = $status;
        }

        if ($categoryId > 0) {
            $where .= ' AND a.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (a.title LIKE :q_title OR a.slug LIKE :q_slug OR a.summary LIKE :q_summary OR a.content LIKE :q_content)';
            $params['q_title'] = $like;
            $params['q_slug'] = $like;
            $params['q_summary'] = $like;
            $params['q_content'] = $like;
        }

        return [$where, $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function filter(string $categorySlug, string $query): array
    {
        $sql = '';
        $params = [];

        if ($categorySlug !== '') {
            $sql .= ' AND c.slug = :category_slug';
            $params['category_slug'] = $categorySlug;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $sql .= ' AND (a.title LIKE :q_title OR a.summary LIKE :q_summary OR a.content LIKE :q_content)';
            $params['q_title'] = $like;
            $params['q_summary'] = $like;
            $params['q_content'] = $like;
        }

        return [$sql, $params];
    }
}
