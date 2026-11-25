<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Message.php
 * 模块：留言与回复
 * 职责：写入访客留言，分页读取已通过留言，读取留言详情及管理员回复。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Message extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function approvedPage(int $limit, int $offset): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        return $this->fetchAll(
            "SELECT m.id, m.visitor_name, m.content, m.approved_at, m.created_at,
                    (SELECT COUNT(*)
                     FROM message_replies mr
                     WHERE mr.message_id = m.id AND mr.status = 'visible') AS reply_count
             FROM messages m
             WHERE m.status = 'approved'
             ORDER BY m.approved_at DESC, m.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function countApproved(): int
    {
        return (int) $this->fetchValue("SELECT COUNT(*) FROM messages WHERE status = 'approved'");
    }

    public function countPending(): int
    {
        return (int) $this->fetchValue("SELECT COUNT(*) FROM messages WHERE status = 'pending'");
    }

    /**
     * @return array{message: array<string, mixed>, replies: array<int, array<string, mixed>>}|null
     */
    public function findApprovedWithReplies(int $id): ?array
    {
        $message = $this->fetchOne(
            "SELECT id, visitor_name, content, approved_at, created_at
             FROM messages
             WHERE id = :id AND status = 'approved'
             LIMIT 1",
            ['id' => $id]
        );

        if ($message === null) {
            return null;
        }

        $replies = $this->fetchAll(
            "SELECT mr.id, mr.content, mr.created_at, u.display_name AS admin_name
             FROM message_replies mr
             INNER JOIN users u ON u.id = mr.admin_user_id
             WHERE mr.message_id = :message_id AND mr.status = 'visible'
             ORDER BY mr.id",
            ['message_id' => $id]
        );

        return ['message' => $message, 'replies' => $replies];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(int $limit, int $offset, string $status = '', string $query = ''): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($status, $query);

        return $this->fetchAll(
            "SELECT m.id, m.visitor_name, m.visitor_email, m.content, m.status,
                    m.ip_address, m.approved_at, m.created_at,
                    (SELECT COUNT(*) FROM message_replies mr WHERE mr.message_id = m.id) AS reply_count,
                    (SELECT COUNT(*) FROM message_replies mr2 WHERE mr2.message_id = m.id AND mr2.status = 'visible') AS visible_reply_count
             FROM messages m
             WHERE 1 = 1 {$where}
             ORDER BY CASE m.status WHEN 'pending' THEN 0 ELSE 1 END, m.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $status = '', string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($status, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM messages m WHERE 1 = 1 {$where}",
            $params
        );
    }

    /**
     * @return array{message: array<string, mixed>, replies: array<int, array<string, mixed>>}|null
     */
    public function findAdminWithReplies(int $id): ?array
    {
        $message = $this->fetchOne(
            'SELECT m.id, m.visitor_name, m.visitor_email, m.content, m.status, m.ip_address,
                    m.user_agent, m.approved_at, m.created_at, m.updated_at
             FROM messages m
             WHERE m.id = :id
             LIMIT 1',
            ['id' => $id]
        );
        if ($message === null) {
            return null;
        }

        $replies = $this->fetchAll(
            'SELECT mr.id, mr.content, mr.status, mr.created_at, mr.updated_at,
                    u.display_name AS admin_name, u.username AS admin_username
             FROM message_replies mr
             INNER JOIN users u ON u.id = mr.admin_user_id
             WHERE mr.message_id = :message_id
             ORDER BY mr.id',
            ['message_id' => $id]
        );

        return ['message' => $message, 'replies' => $replies];
    }

    public function setStatus(int $id, string $status): void
    {
        $approvedAt = $status === 'approved' ? date('Y-m-d H:i:s') : null;
        $this->execute(
            'UPDATE messages
             SET status = :status, approved_at = :approved_at
             WHERE id = :id',
            ['id' => $id, 'status' => $status, 'approved_at' => $approvedAt]
        );
    }

    public function addReply(int $messageId, int $adminUserId, string $content, string $status = 'visible'): int
    {
        return $this->insert(
            'INSERT INTO message_replies (message_id, admin_user_id, content, status)
             VALUES (:message_id, :admin_user_id, :content, :status)',
            [
                'message_id' => $messageId,
                'admin_user_id' => $adminUserId,
                'content' => mb_substr($content, 0, 2000),
                'status' => $status === 'hidden' ? 'hidden' : 'visible',
            ]
        );
    }

    public function deleteMessage(int $id): void
    {
        $this->execute('DELETE FROM messages WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS total FROM messages GROUP BY status');
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    public function createVisitor(
        string $visitorName,
        ?string $visitorEmail,
        string $content,
        ?string $ipAddress,
        ?string $userAgent
    ): int {
        return $this->insert(
            'INSERT INTO messages
                (visitor_name, visitor_email, content, status, ip_address, user_agent)
             VALUES
                (:visitor_name, :visitor_email, :content, :status, :ip_address, :user_agent)',
            [
                'visitor_name' => mb_substr($visitorName, 0, 50),
                'visitor_email' => $visitorEmail !== null ? mb_substr($visitorEmail, 0, 120) : null,
                'content' => mb_substr($content, 0, 2000),
                'status' => 'pending',
                'ip_address' => $ipAddress !== null ? mb_substr($ipAddress, 0, 45) : null,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
            ]
        );
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function adminFilter(string $status, string $query): array
    {
        $where = '';
        $params = [];

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where .= ' AND m.status = :status';
            $params['status'] = $status;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (m.visitor_name LIKE :q_name OR m.visitor_email LIKE :q_email OR m.content LIKE :q_content OR m.ip_address LIKE :q_ip)';
            $params['q_name'] = $like;
            $params['q_email'] = $like;
            $params['q_content'] = $like;
            $params['q_ip'] = $like;
        }

        return [$where, $params];
    }
}
