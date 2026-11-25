<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/ActivityLog.php
 * 模块：操作日志
 * 职责：向 activity_logs 追加后台登录和关键操作记录。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class ActivityLog extends Model
{
    /**
     * @param array<string, mixed> $data
     */
    public function record(array $data): void
    {
        $this->execute(
            'INSERT INTO activity_logs
                (user_id, actor_username, module, action, target_type, target_id, description, result, ip_address, user_agent)
             VALUES
                (:user_id, :actor_username, :module, :action, :target_type, :target_id, :description, :result, :ip_address, :user_agent)',
            [
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
                'actor_username' => isset($data['actor_username']) ? mb_substr((string) $data['actor_username'], 0, 50) : null,
                'module' => mb_substr((string) ($data['module'] ?? 'system'), 0, 50),
                'action' => mb_substr((string) ($data['action'] ?? 'unknown'), 0, 50),
                'target_type' => isset($data['target_type']) ? mb_substr((string) $data['target_type'], 0, 50) : null,
                'target_id' => isset($data['target_id']) ? (int) $data['target_id'] : null,
                'description' => isset($data['description']) ? mb_substr((string) $data['description'], 0, 500) : null,
                'result' => ($data['result'] ?? 'success') === 'failure' ? 'failure' : 'success',
                'ip_address' => isset($data['ip_address']) ? mb_substr((string) $data['ip_address'], 0, 45) : null,
                'user_agent' => isset($data['user_agent']) ? mb_substr((string) $data['user_agent'], 0, 500) : null,
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        return $this->fetchAll(
            'SELECT id, user_id, actor_username, module, action, description, result, ip_address, created_at
             FROM activity_logs
             ORDER BY id DESC
             LIMIT ' . $limit
        );
    }

    public function countAll(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM activity_logs');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(int $limit, int $offset, string $module = '', string $result = '', string $query = ''): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($module, $result, $query);

        return $this->fetchAll(
            "SELECT id, user_id, actor_username, module, action, target_type, target_id,
                    description, result, ip_address, created_at
             FROM activity_logs
             WHERE 1 = 1 {$where}
             ORDER BY id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $module = '', string $result = '', string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($module, $result, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM activity_logs WHERE 1 = 1 {$where}",
            $params
        );
    }

    /**
     * @return array<int, string>
     */
    public function modules(): array
    {
        $rows = $this->fetchAll('SELECT DISTINCT module FROM activity_logs ORDER BY module');

        return array_values(array_map(static fn (array $row): string => (string) $row['module'], $rows));
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function adminFilter(string $module, string $result, string $query): array
    {
        $where = '';
        $params = [];

        if ($module !== '' && preg_match('/^[a-z0-9_.-]{1,50}$/i', $module) === 1) {
            $where .= ' AND module = :module';
            $params['module'] = $module;
        }

        if ($result === 'success' || $result === 'failure') {
            $where .= ' AND result = :result';
            $params['result'] = $result;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (actor_username LIKE :q_actor OR action LIKE :q_action OR description LIKE :q_description OR ip_address LIKE :q_ip)';
            $params['q_actor'] = $like;
            $params['q_action'] = $like;
            $params['q_description'] = $like;
            $params['q_ip'] = $like;
        }

        return [$where, $params];
    }
}
