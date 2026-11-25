<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/User.php
 * 模块：用户与身份认证
 * 职责：查询登录用户、角色、权限和账号列表，更新登录/密码字段。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class User extends Model
{
    /**
     * 登录时使用，包含密码哈希，只允许 Auth 内部消费。
     *
     * @return array<string, mixed>|null
     */
    public function findForAuth(string $username): ?array
    {
        return $this->fetchOne(
            'SELECT u.id, u.username, u.password_hash, u.display_name, u.email, u.role_id, u.status,
                    u.last_login_at, u.last_login_ip,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username
             LIMIT 1',
            ['username' => $username]
        );
    }

    /**
     * 当前登录用户视图，不返回密码哈希。
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT u.id, u.username, u.display_name, u.email, u.role_id, u.status,
                    u.last_login_at, u.last_login_ip, u.created_at,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, string>
     */
    public function permissionCodesForRole(int $roleId): array
    {
        $rows = $this->fetchAll(
            'SELECT p.code
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.sort_order, p.id',
            ['role_id' => $roleId]
        );

        return array_values(array_map(static fn (array $row): string => (string) $row['code'], $rows));
    }

    public function updateLastLogin(int $id, ?string $ip): void
    {
        $this->execute(
            'UPDATE users
             SET last_login_at = CURRENT_TIMESTAMP, last_login_ip = :ip
             WHERE id = :id',
            ['id' => $id, 'ip' => $ip]
        );
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = :hash WHERE id = :id',
            ['id' => $id, 'hash' => $hash]
        );
    }

    /**
     * 后台账号列表，用于验证权限拦截和后续账号管理。
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithRoles(): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.username, u.display_name, u.email, u.status, u.last_login_at,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             ORDER BY u.id'
        );
    }

    /**
     * 前台“个人资料”页使用的只读账号/成员资料。
     *
     * @return array<string, mixed>|null
     */
    public function findFrontProfile(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT u.id, u.username, u.display_name, u.email, u.created_at, u.last_login_at,
                    r.code AS role_code, r.name AS role_name,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url,
                    tm.id AS membership_id, tm.position_title, tm.joined_at,
                    tm.status AS member_status, t.id AS team_id, t.name AS team_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             LEFT JOIN team_members tm ON tm.id = (
                 SELECT tm2.id
                 FROM team_members tm2
                 WHERE tm2.user_id = u.id
                 ORDER BY CASE tm2.status WHEN 'active' THEN 0 ELSE 1 END, tm2.id
                 LIMIT 1
             )
             LEFT JOIN teams t ON t.id = tm.team_id
             WHERE u.id = :id AND u.status = 'active'
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function countAll(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM users');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(int $limit, int $offset, string $query = '', int $roleId = 0, string $status = ''): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($query, $roleId, $status);

        return $this->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.email, u.status, u.last_login_at,
                    u.created_at, r.code AS role_code, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE 1 = 1 {$where}
             ORDER BY u.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $query = '', int $roleId = 0, string $status = ''): int
    {
        [$where, $params] = $this->adminFilter($query, $roleId, $status);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
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
            'SELECT u.id, u.username, u.display_name, u.email, u.role_id, u.status,
                    u.last_login_at, u.last_login_ip, u.created_at, u.updated_at,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        $params = ['username' => $username];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    public function createAdmin(
        string $username,
        string $passwordHash,
        string $displayName,
        ?string $email,
        int $roleId,
        string $status
    ): int {
        return $this->insert(
            'INSERT INTO users (username, password_hash, display_name, email, role_id, status)
             VALUES (:username, :password_hash, :display_name, :email, :role_id, :status)',
            [
                'username' => $username,
                'password_hash' => $passwordHash,
                'display_name' => $displayName,
                'email' => $email,
                'role_id' => $roleId,
                'status' => $status,
            ]
        );
    }

    public function updateAdmin(
        int $id,
        string $displayName,
        ?string $email,
        int $roleId,
        string $status,
        ?string $passwordHash = null
    ): void {
        $sql = 'UPDATE users
                SET display_name = :display_name, email = :email, role_id = :role_id, status = :status';
        $params = [
            'id' => $id,
            'display_name' => $displayName,
            'email' => $email,
            'role_id' => $roleId,
            'status' => $status,
        ];

        if ($passwordHash !== null) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = $passwordHash;
        }

        $sql .= ' WHERE id = :id';
        $this->execute($sql, $params);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE users SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function deleteUser(int $id): void
    {
        $this->execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
    }

    public function countActiveSuperAdmins(?int $exceptId = null): int
    {
        $sql = "SELECT COUNT(*)
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE r.code = 'super_admin' AND u.status = 'active'";
        $params = [];
        if ($exceptId !== null) {
            $sql .= ' AND u.id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params);
    }

    /**
     * @return array<string, int>
     */
    public function dependencyCounts(int $id): array
    {
        return [
            'team_memberships' => (int) $this->fetchValue('SELECT COUNT(*) FROM team_members WHERE user_id = :id', ['id' => $id]),
            'projects' => (int) $this->fetchValue('SELECT COUNT(*) FROM projects WHERE created_by = :id', ['id' => $id]),
            'articles' => (int) $this->fetchValue('SELECT COUNT(*) FROM articles WHERE author_id = :id', ['id' => $id]),
            'announcements' => (int) $this->fetchValue('SELECT COUNT(*) FROM announcements WHERE publisher_id = :id', ['id' => $id]),
            'replies' => (int) $this->fetchValue('SELECT COUNT(*) FROM message_replies WHERE admin_user_id = :id', ['id' => $id]),
            'files' => (int) $this->fetchValue('SELECT COUNT(*) FROM files WHERE uploader_id = :id', ['id' => $id]),
        ];
    }

    public function avatarPath(int $id): ?string
    {
        $value = $this->fetchValue(
            'SELECT avatar_path FROM member_profiles WHERE user_id = :id LIMIT 1',
            ['id' => $id]
        );

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminFilter(string $query, int $roleId, string $status): array
    {
        $where = '';
        $params = [];

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (u.username LIKE :q_username OR u.display_name LIKE :q_display OR u.email LIKE :q_email)';
            $params['q_username'] = $like;
            $params['q_display'] = $like;
            $params['q_email'] = $like;
        }

        if ($roleId > 0) {
            $where .= ' AND u.role_id = :role_id';
            $params['role_id'] = $roleId;
        }

        if ($status === 'active' || $status === 'disabled') {
            $where .= ' AND u.status = :status';
            $params['status'] = $status;
        }

        return [$where, $params];
    }
}
