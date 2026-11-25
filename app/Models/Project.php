<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Project.php
 * 模块：项目成果管理
 * 职责：查询公开项目列表、筛选、详情与项目成员。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Project extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function featured(int $limit = 3): array
    {
        $limit = max(1, min(20, $limit));

        return $this->fetchAll(
            "SELECT id, team_id, title, slug, summary, tech_stack, cover_path, repo_url, demo_url,
                    status, is_featured, start_date, end_date, created_at
             FROM projects
             WHERE is_public = 1
             ORDER BY is_featured DESC, sort_order ASC, id DESC
             LIMIT {$limit}"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicPage(int $limit, int $offset, string $status = '', string $query = ''): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);
        [$filterSql, $params] = $this->filter($status, $query);

        return $this->fetchAll(
            "SELECT p.id, p.team_id, p.title, p.slug, p.summary, p.tech_stack, p.cover_path,
                    p.repo_url, p.demo_url, p.status, p.is_featured, p.start_date, p.end_date,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count
             FROM projects p
             WHERE p.is_public = 1
               {$filterSql}
             ORDER BY p.is_featured DESC, p.sort_order, p.start_date DESC, p.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countPublic(string $status = '', string $query = ''): int
    {
        [$filterSql, $params] = $this->filter($status, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM projects p WHERE p.is_public = 1 {$filterSql}",
            $params
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicBySlug(string $slug): ?array
    {
        return $this->fetchOne(
            "SELECT p.id, p.team_id, p.title, p.slug, p.summary, p.description, p.outcomes,
                    p.cover_path, p.tech_stack, p.repo_url, p.demo_url, p.status, p.is_featured,
                    p.start_date, p.end_date, p.created_at, p.updated_at,
                    t.name AS team_name
             FROM projects p
             INNER JOIN teams t ON t.id = p.team_id
             WHERE p.slug = :slug AND p.is_public = 1
             LIMIT 1",
            ['slug' => $slug]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function membersForProject(int $projectId): array
    {
        return $this->fetchAll(
            "SELECT tm.id AS membership_id, tm.position_title, tm.status AS member_status,
                    pm.role_in_project, pm.joined_at, pm.left_at,
                    u.id AS user_id, u.display_name, u.username,
                    mp.avatar_path, mp.skills
             FROM project_members pm
             INNER JOIN team_members tm ON tm.id = pm.team_member_id
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE pm.project_id = :project_id
               AND u.status = 'active'
             ORDER BY CASE tm.status WHEN 'active' THEN 0 ELSE 1 END, tm.sort_order, tm.id",
            ['project_id' => $projectId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(
        int $limit,
        int $offset,
        string $status = '',
        string $visibility = '',
        string $query = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($status, $visibility, $query);

        return $this->fetchAll(
            "SELECT p.id, p.team_id, p.title, p.slug, p.summary, p.cover_path, p.tech_stack,
                    p.status, p.is_public, p.is_featured, p.sort_order, p.start_date, p.end_date,
                    p.created_at, p.updated_at, t.name AS team_name,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count
             FROM projects p
             INNER JOIN teams t ON t.id = p.team_id
             WHERE 1 = 1 {$where}
             ORDER BY p.sort_order, p.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $status = '', string $visibility = '', string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($status, $visibility, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM projects p
             INNER JOIN teams t ON t.id = p.team_id
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
            'SELECT p.*, t.name AS team_name
             FROM projects p
             INNER JOIN teams t ON t.id = p.team_id
             WHERE p.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM projects WHERE slug = :slug';
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
    public function createProject(array $data): int
    {
        return $this->insert(
            'INSERT INTO projects
                (team_id, title, slug, summary, description, outcomes, cover_path, tech_stack,
                 repo_url, demo_url, status, is_public, is_featured, sort_order,
                 start_date, end_date, created_by)
             VALUES
                (:team_id, :title, :slug, :summary, :description, :outcomes, :cover_path, :tech_stack,
                 :repo_url, :demo_url, :status, :is_public, :is_featured, :sort_order,
                 :start_date, :end_date, :created_by)',
            [
                'team_id' => $data['team_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'outcomes' => $data['outcomes'],
                'cover_path' => $data['cover_path'],
                'tech_stack' => $data['tech_stack'],
                'repo_url' => $data['repo_url'],
                'demo_url' => $data['demo_url'],
                'status' => $data['status'],
                'is_public' => $data['is_public'],
                'is_featured' => $data['is_featured'],
                'sort_order' => $data['sort_order'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'created_by' => $data['created_by'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProject(int $id, array $data): void
    {
        $this->execute(
            'UPDATE projects
             SET title = :title, slug = :slug, summary = :summary, description = :description,
                 outcomes = :outcomes, cover_path = :cover_path, tech_stack = :tech_stack,
                 repo_url = :repo_url, demo_url = :demo_url, status = :status,
                 is_public = :is_public, is_featured = :is_featured, sort_order = :sort_order,
                 start_date = :start_date, end_date = :end_date
             WHERE id = :id',
            [
                'id' => $id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'outcomes' => $data['outcomes'],
                'cover_path' => $data['cover_path'],
                'tech_stack' => $data['tech_stack'],
                'repo_url' => $data['repo_url'],
                'demo_url' => $data['demo_url'],
                'status' => $data['status'],
                'is_public' => $data['is_public'],
                'is_featured' => $data['is_featured'],
                'sort_order' => $data['sort_order'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]
        );
    }

    public function setPublic(int $id, bool $public): void
    {
        $this->execute(
            'UPDATE projects SET is_public = :public WHERE id = :id',
            ['id' => $id, 'public' => $public ? 1 : 0]
        );
    }

    public function deleteProject(int $id): void
    {
        $this->execute('DELETE FROM projects WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function projectMemberRows(int $projectId): array
    {
        return $this->fetchAll(
            'SELECT pm.team_member_id, pm.role_in_project, pm.joined_at, pm.left_at,
                    tm.team_id, tm.status, u.display_name, u.username
             FROM project_members pm
             INNER JOIN team_members tm ON tm.id = pm.team_member_id
             INNER JOIN users u ON u.id = tm.user_id
             WHERE pm.project_id = :project_id
             ORDER BY tm.sort_order, tm.id',
            ['project_id' => $projectId]
        );
    }

    /**
     * @return array<int, int>
     */
    public function projectMemberIds(int $projectId): array
    {
        $rows = $this->projectMemberRows($projectId);

        return array_values(array_map(static fn (array $row): int => (int) $row['team_member_id'], $rows));
    }

    /**
     * @param array<int, int> $memberIds
     * @param array<int, string> $roles
     */
    public function syncMembers(int $projectId, array $memberIds, array $roles, ?string $joinedAt = null): void
    {
        $this->transaction(function () use ($projectId, $memberIds, $roles, $joinedAt): void {
            $existing = [];
            foreach ($this->projectMemberRows($projectId) as $row) {
                $existing[(int) $row['team_member_id']] = (string) $row['joined_at'];
            }

            $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds))));
            $allowed = [];
            if ($memberIds !== []) {
                $allowedRows = $this->fetchAll(
                    'SELECT id FROM team_members WHERE id IN (' . implode(',', $memberIds) . ')'
                );
                $allowed = array_map(static fn (array $row): int => (int) $row['id'], $allowedRows);
            }

            $this->execute('DELETE FROM project_members WHERE project_id = :project_id', ['project_id' => $projectId]);

            foreach ($allowed as $membershipId) {
                $role = trim((string) ($roles[$membershipId] ?? ''));
                $role = mb_substr($role, 0, 80);
                $this->execute(
                    'INSERT INTO project_members (project_id, team_member_id, role_in_project, joined_at)
                     VALUES (:project_id, :team_member_id, :role_in_project, :joined_at)',
                    [
                        'project_id' => $projectId,
                        'team_member_id' => $membershipId,
                        'role_in_project' => $role === '' ? null : $role,
                        'joined_at' => $existing[$membershipId] ?? ($joinedAt ?? date('Y-m-d')),
                    ]
                );
            }
        });
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminFilter(string $status, string $visibility, string $query): array
    {
        $where = '';
        $params = [];

        if (in_array($status, ['planning', 'ongoing', 'completed', 'archived'], true)) {
            $where .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        if ($visibility === 'public') {
            $where .= ' AND p.is_public = 1';
        } elseif ($visibility === 'hidden') {
            $where .= ' AND p.is_public = 0';
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (p.title LIKE :q_title OR p.slug LIKE :q_slug OR p.summary LIKE :q_summary OR p.tech_stack LIKE :q_stack)';
            $params['q_title'] = $like;
            $params['q_slug'] = $like;
            $params['q_summary'] = $like;
            $params['q_stack'] = $like;
        }

        return [$where, $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function filter(string $status, string $query): array
    {
        $allowedStatuses = ['planning', 'ongoing', 'completed', 'archived'];
        $sql = '';
        $params = [];

        if (in_array($status, $allowedStatuses, true)) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $sql .= ' AND (p.title LIKE :q_title OR p.summary LIKE :q_summary OR p.tech_stack LIKE :q_stack)';
            $params['q_title'] = $like;
            $params['q_summary'] = $like;
            $params['q_stack'] = $like;
        }

        return [$sql, $params];
    }
}
