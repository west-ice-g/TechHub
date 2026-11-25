<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Member.php
 * 模块：团队成员展示
 * 职责：分页查询公开成员、成员详情及参与项目。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Member extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicPage(int $teamId, int $limit, int $offset, string $query = ''): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);
        [$filterSql, $params] = $this->searchFilter($query);

        return $this->fetchAll(
            "SELECT tm.id AS membership_id, tm.position_title, tm.responsibilities,
                    tm.joined_at, tm.left_at, tm.status AS member_status, tm.sort_order,
                    u.id AS user_id, u.display_name, u.username,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id
               AND tm.status IN ('active', 'alumni')
               AND u.status = 'active'
               {$filterSql}
             ORDER BY CASE tm.status WHEN 'active' THEN 0 ELSE 1 END, tm.sort_order, tm.id
             LIMIT {$limit} OFFSET {$offset}",
            array_merge(['team_id' => $teamId], $params)
        );
    }

    public function countPublic(int $teamId, string $query = ''): int
    {
        [$filterSql, $params] = $this->searchFilter($query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id
               AND tm.status IN ('active', 'alumni')
               AND u.status = 'active'
               {$filterSql}",
            array_merge(['team_id' => $teamId], $params)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublic(int $membershipId): ?array
    {
        return $this->fetchOne(
            "SELECT tm.id AS membership_id, tm.position_title, tm.responsibilities,
                    tm.joined_at, tm.left_at, tm.status AS member_status,
                    u.id AS user_id, u.display_name, u.username,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url,
                    t.id AS team_id, t.name AS team_name, t.slug AS team_slug
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             INNER JOIN teams t ON t.id = tm.team_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.id = :id
               AND tm.status IN ('active', 'alumni')
               AND u.status = 'active'
             LIMIT 1",
            ['id' => $membershipId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function projectsForMember(int $membershipId): array
    {
        return $this->fetchAll(
            "SELECT p.id, p.title, p.slug, p.summary, p.status, p.cover_path, p.tech_stack,
                    pm.role_in_project, pm.joined_at, pm.left_at
             FROM project_members pm
             INNER JOIN projects p ON p.id = pm.project_id
             WHERE pm.team_member_id = :membership_id
               AND p.is_public = 1
             ORDER BY p.sort_order, p.start_date DESC, p.id DESC",
            ['membership_id' => $membershipId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(
        int $teamId,
        int $limit,
        int $offset,
        string $query = '',
        string $status = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($query, $status);
        $params['team_id'] = $teamId;

        return $this->fetchAll(
            "SELECT tm.id, tm.team_id, tm.user_id, tm.position_title, tm.responsibilities,
                    tm.joined_at, tm.left_at, tm.status, tm.sort_order,
                    u.username, u.display_name, u.email, u.status AS user_status,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.team_member_id = tm.id) AS project_count
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id {$where}
             ORDER BY CASE tm.status WHEN 'active' THEN 0 WHEN 'alumni' THEN 1 ELSE 2 END,
                      tm.sort_order, tm.id
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(int $teamId, string $query = '', string $status = ''): int
    {
        [$where, $params] = $this->adminFilter($query, $status);
        $params['team_id'] = $teamId;

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id {$where}",
            $params
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAdmin(int $membershipId): ?array
    {
        return $this->fetchOne(
            'SELECT tm.id, tm.team_id, tm.user_id, tm.position_title, tm.responsibilities,
                    tm.joined_at, tm.left_at, tm.status, tm.sort_order,
                    u.username, u.display_name, u.email, u.status AS user_status,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.id = :id
             LIMIT 1',
            ['id' => $membershipId]
        );
    }

    /**
     * 可加入当前团队且账号启用的用户。
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableUsers(int $teamId): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.email
             FROM users u
             WHERE u.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM team_members tm
                   WHERE tm.team_id = :team_id AND tm.user_id = u.id
               )
             ORDER BY u.display_name, u.id",
            ['team_id' => $teamId]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createMembership(int $teamId, array $data): int
    {
        return $this->transaction(function () use ($teamId, $data): int {
            $membershipId = $this->insert(
                'INSERT INTO team_members
                    (team_id, user_id, position_title, responsibilities, joined_at, left_at, status, sort_order)
                 VALUES
                    (:team_id, :user_id, :position_title, :responsibilities, :joined_at, NULL, :status, :sort_order)',
                [
                    'team_id' => $teamId,
                    'user_id' => $data['user_id'],
                    'position_title' => $data['position_title'],
                    'responsibilities' => $data['responsibilities'],
                    'joined_at' => $data['joined_at'],
                    'status' => $data['status'],
                    'sort_order' => $data['sort_order'],
                ]
            );
            $this->upsertProfile((int) $data['user_id'], $data);

            return $membershipId;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateMembership(int $membershipId, int $userId, array $data): void
    {
        $this->transaction(function () use ($membershipId, $userId, $data): void {
            $this->execute(
                'UPDATE team_members
                 SET position_title = :position_title, responsibilities = :responsibilities,
                     joined_at = :joined_at, left_at = :left_at, status = :status, sort_order = :sort_order
                 WHERE id = :id',
                [
                    'id' => $membershipId,
                    'position_title' => $data['position_title'],
                    'responsibilities' => $data['responsibilities'],
                    'joined_at' => $data['joined_at'],
                    'left_at' => $data['left_at'],
                    'status' => $data['status'],
                    'sort_order' => $data['sort_order'],
                ]
            );
            $this->upsertProfile($userId, $data);
        });
    }

    public function projectReferenceCount(int $membershipId): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM project_members WHERE team_member_id = :id',
            ['id' => $membershipId]
        );
    }

    public function fileReferenceCount(int $membershipId): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM files WHERE related_member_id = :id',
            ['id' => $membershipId]
        );
    }

    public function deleteMembership(int $membershipId): void
    {
        $this->execute('DELETE FROM team_members WHERE id = :id', ['id' => $membershipId]);
    }

    public function setStatus(int $membershipId, string $status): void
    {
        $leftAt = $status === 'active' ? null : date('Y-m-d');
        $this->execute(
            'UPDATE team_members
             SET status = :status, left_at = :left_at
             WHERE id = :id',
            ['id' => $membershipId, 'status' => $status, 'left_at' => $leftAt]
        );
    }

    /**
     * 项目成员选择，包含在队和已离队成员。
     *
     * @return array<int, array<string, mixed>>
     */
    public function memberOptions(int $teamId): array
    {
        return $this->fetchAll(
            "SELECT tm.id, tm.position_title, tm.status, u.display_name, u.username, mp.avatar_path
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id
               AND tm.status IN ('active', 'alumni')
             ORDER BY CASE tm.status WHEN 'active' THEN 0 ELSE 1 END, tm.sort_order, tm.id",
            ['team_id' => $teamId]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertProfile(int $userId, array $data): void
    {
        $this->execute(
            'INSERT INTO member_profiles
                (user_id, bio, skills, avatar_path, public_email, github_url)
             VALUES
                (:user_id, :bio, :skills, :avatar_path, :public_email, :github_url)
             ON DUPLICATE KEY UPDATE
                bio = VALUES(bio),
                skills = VALUES(skills),
                avatar_path = VALUES(avatar_path),
                public_email = VALUES(public_email),
                github_url = VALUES(github_url)',
            [
                'user_id' => $userId,
                'bio' => $data['bio'],
                'skills' => $data['skills'],
                'avatar_path' => $data['avatar_path'],
                'public_email' => $data['public_email'],
                'github_url' => $data['github_url'],
            ]
        );
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function adminFilter(string $query, string $status): array
    {
        $where = '';
        $params = [];

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (u.display_name LIKE :q_name OR u.username LIKE :q_username OR tm.position_title LIKE :q_position OR mp.skills LIKE :q_skills)';
            $params['q_name'] = $like;
            $params['q_username'] = $like;
            $params['q_position'] = $like;
            $params['q_skills'] = $like;
        }

        if (in_array($status, ['active', 'alumni', 'removed'], true)) {
            $where .= ' AND tm.status = :status';
            $params['status'] = $status;
        }

        return [$where, $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function searchFilter(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['', []];
        }

        $like = '%' . $query . '%';

        return [
            'AND (u.display_name LIKE :q_name OR tm.position_title LIKE :q_title OR mp.skills LIKE :q_skills)',
            ['q_name' => $like, 'q_title' => $like, 'q_skills' => $like],
        ];
    }
}
