<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Category.php
 * 模块：文章分类
 * 职责：读取启用分类及其已发布文章数量，供前台筛选和详情使用。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Category extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function enabledWithPublishedCounts(): array
    {
        return $this->fetchAll(
            "SELECT c.id, c.parent_id, c.name, c.slug, c.description, c.sort_order,
                    COUNT(a.id) AS article_count
             FROM categories c
             LEFT JOIN articles a ON a.category_id = c.id AND a.status = 'published'
             WHERE c.status = 'enabled'
             GROUP BY c.id, c.parent_id, c.name, c.slug, c.description, c.sort_order
             ORDER BY c.sort_order, c.id"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminAll(): array
    {
        return $this->fetchAll(
            'SELECT c.id, c.parent_id, c.name, c.slug, c.description, c.sort_order, c.status,
                    c.created_at, p.name AS parent_name,
                    (SELECT COUNT(*) FROM articles a WHERE a.category_id = c.id) AS article_count,
                    (SELECT COUNT(*) FROM categories child WHERE child.parent_id = c.id) AS child_count
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.sort_order, c.id'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, parent_id, name, slug, description, sort_order, status, created_at, updated_at
             FROM categories
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    public function createCategory(
        ?int $parentId,
        string $name,
        string $slug,
        ?string $description,
        int $sortOrder,
        string $status
    ): int {
        return $this->insert(
            'INSERT INTO categories (parent_id, name, slug, description, sort_order, status)
             VALUES (:parent_id, :name, :slug, :description, :sort_order, :status)',
            [
                'parent_id' => $parentId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
                'status' => $status,
            ]
        );
    }

    public function updateCategory(
        int $id,
        ?int $parentId,
        string $name,
        string $slug,
        ?string $description,
        int $sortOrder,
        string $status
    ): void {
        $this->execute(
            'UPDATE categories
             SET parent_id = :parent_id, name = :name, slug = :slug,
                 description = :description, sort_order = :sort_order, status = :status
             WHERE id = :id',
            [
                'id' => $id,
                'parent_id' => $parentId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
                'status' => $status,
            ]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE categories SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function deleteCategory(int $id): void
    {
        $this->execute('DELETE FROM categories WHERE id = :id', ['id' => $id]);
    }

    public function articleCount(int $id): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM articles WHERE category_id = :id',
            ['id' => $id]
        );
    }

    public function childCount(int $id): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM categories WHERE parent_id = :id',
            ['id' => $id]
        );
    }

    public function wouldCreateCycle(int $id, ?int $parentId): bool
    {
        if ($parentId === null || $parentId <= 0) {
            return false;
        }

        $visited = [];
        $cursor = $parentId;
        for ($i = 0; $i < 30 && $cursor > 0; $i++) {
            if ($cursor === $id || isset($visited[$cursor])) {
                return true;
            }
            $visited[$cursor] = true;
            $parent = $this->find($cursor);
            if ($parent === null) {
                return true;
            }
            $cursor = $parent['parent_id'] === null ? 0 : (int) $parent['parent_id'];
        }

        return $cursor > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findEnabledBySlug(string $slug): ?array
    {
        return $this->fetchOne(
            "SELECT id, parent_id, name, slug, description, sort_order
             FROM categories
             WHERE slug = :slug AND status = 'enabled'
             LIMIT 1",
            ['slug' => $slug]
        );
    }
}
