<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Announcement.php
 * 模块：团队公告管理
 * 职责：查询有效公告列表、公告详情和公告统计。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Announcement extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestActive(int $limit = 3): array
    {
        $limit = max(1, min(20, $limit));

        return $this->fetchAll(
            "SELECT id, title, summary, type, is_pinned, published_at, expires_at
             FROM announcements
             WHERE status = 'published'
               AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
             ORDER BY is_pinned DESC, published_at DESC, id DESC
             LIMIT {$limit}"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activePage(int $limit, int $offset): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        return $this->fetchAll(
            "SELECT a.id, a.title, a.summary, a.type, a.is_pinned, a.published_at, a.expires_at,
                    u.display_name AS publisher_name
             FROM announcements a
             INNER JOIN users u ON u.id = a.publisher_id
             WHERE a.status = 'published'
               AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP)
             ORDER BY a.is_pinned DESC, a.published_at DESC, a.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function countActive(): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM announcements
             WHERE status = 'published'
               AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(
        int $limit,
        int $offset,
        string $status = '',
        string $type = '',
        string $query = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($status, $type, $query);

        return $this->fetchAll(
            "SELECT a.id, a.publisher_id, a.title, a.summary, a.type, a.is_pinned, a.status,
                    a.published_at, a.expires_at, a.created_at, a.updated_at,
                    u.display_name AS publisher_name
             FROM announcements a
             INNER JOIN users u ON u.id = a.publisher_id
             WHERE 1 = 1 {$where}
             ORDER BY a.is_pinned DESC, a.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $status = '', string $type = '', string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($status, $type, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM announcements a
             INNER JOIN users u ON u.id = a.publisher_id
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
            'SELECT a.*, u.display_name AS publisher_name
             FROM announcements a
             INNER JOIN users u ON u.id = a.publisher_id
             WHERE a.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAnnouncement(array $data): int
    {
        return $this->insert(
            'INSERT INTO announcements
                (publisher_id, title, summary, content, type, is_pinned, status, published_at, expires_at)
             VALUES
                (:publisher_id, :title, :summary, :content, :type, :is_pinned, :status, :published_at, :expires_at)',
            [
                'publisher_id' => $data['publisher_id'],
                'title' => $data['title'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'type' => $data['type'],
                'is_pinned' => $data['is_pinned'],
                'status' => $data['status'],
                'published_at' => $data['published_at'],
                'expires_at' => $data['expires_at'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAnnouncement(int $id, array $data): void
    {
        $this->execute(
            'UPDATE announcements
             SET title = :title, summary = :summary, content = :content, type = :type,
                 is_pinned = :is_pinned, status = :status, published_at = :published_at,
                 expires_at = :expires_at
             WHERE id = :id',
            [
                'id' => $id,
                'title' => $data['title'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'type' => $data['type'],
                'is_pinned' => $data['is_pinned'],
                'status' => $data['status'],
                'published_at' => $data['published_at'],
                'expires_at' => $data['expires_at'],
            ]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        if ($status === 'published') {
            $this->execute(
                "UPDATE announcements
                 SET status = 'published', published_at = COALESCE(published_at, CURRENT_TIMESTAMP)
                 WHERE id = :id",
                ['id' => $id]
            );
            return;
        }

        if ($status === 'draft') {
            $this->execute(
                "UPDATE announcements SET status = 'draft', published_at = NULL WHERE id = :id",
                ['id' => $id]
            );
            return;
        }

        $this->execute(
            "UPDATE announcements SET status = 'archived' WHERE id = :id",
            ['id' => $id]
        );
    }

    public function deleteAnnouncement(int $id): void
    {
        $this->execute('DELETE FROM announcements WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminFilter(string $status, string $type, string $query): array
    {
        $where = '';
        $params = [];

        if (in_array($status, ['draft', 'published', 'archived'], true)) {
            $where .= ' AND a.status = :status';
            $params['status'] = $status;
        }
        if (in_array($type, ['general', 'event', 'recruitment', 'system'], true)) {
            $where .= ' AND a.type = :type';
            $params['type'] = $type;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (a.title LIKE :q_title OR a.summary LIKE :q_summary OR a.content LIKE :q_content)';
            $params['q_title'] = $like;
            $params['q_summary'] = $like;
            $params['q_content'] = $like;
        }

        return [$where, $params];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT a.id, a.title, a.summary, a.content, a.type, a.is_pinned,
                    a.published_at, a.expires_at, a.created_at, a.updated_at,
                    u.display_name AS publisher_name
             FROM announcements a
             INNER JOIN users u ON u.id = a.publisher_id
             WHERE a.id = :id
               AND a.status = 'published'
               AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP)
             LIMIT 1",
            ['id' => $id]
        );
    }
}
