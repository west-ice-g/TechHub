<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Role.php
 * 模块：角色与权限管理
 * 职责：读取角色、权限项并维护角色-权限映射。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Role extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function allWithCounts(): array
    {
        return $this->fetchAll(
            'SELECT r.id, r.code, r.name, r.description, r.is_system, r.sort_order, r.created_at,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
             FROM roles r
             ORDER BY r.sort_order, r.id'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, code, name, description, is_system, sort_order
             FROM roles
             ORDER BY sort_order, id'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, code, name, description, is_system, sort_order, created_at, updated_at
             FROM roles
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCode(string $code): ?array
    {
        return $this->fetchOne(
            'SELECT id, code, name, description, is_system, sort_order
             FROM roles
             WHERE code = :code
             LIMIT 1',
            ['code' => $code]
        );
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM roles WHERE code = :code';
        $params = ['code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    public function create(string $code, string $name, ?string $description, int $sortOrder): int
    {
        return $this->insert(
            'INSERT INTO roles (code, name, description, is_system, sort_order)
             VALUES (:code, :name, :description, 0, :sort_order)',
            [
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function update(int $id, string $name, ?string $description, int $sortOrder): void
    {
        $this->execute(
            'UPDATE roles
             SET name = :name, description = :description, sort_order = :sort_order
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM roles WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function permissions(): array
    {
        return $this->fetchAll(
            'SELECT id, code, name, module, description, sort_order
             FROM permissions
             ORDER BY sort_order, id'
        );
    }

    /**
     * @return array<int, int>
     */
    public function permissionIdsForRole(int $roleId): array
    {
        $rows = $this->fetchAll(
            'SELECT permission_id FROM role_permissions WHERE role_id = :role_id ORDER BY permission_id',
            ['role_id' => $roleId]
        );

        return array_values(array_map(static fn (array $row): int => (int) $row['permission_id'], $rows));
    }

    /**
     * @param array<int, int> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->transaction(function () use ($roleId, $permissionIds): void {
            $this->execute('DELETE FROM role_permissions WHERE role_id = :role_id', ['role_id' => $roleId]);

            $validRows = $permissionIds === []
                ? []
                : $this->fetchAll(
                    'SELECT id FROM permissions WHERE id IN (' . implode(',', array_map('intval', $permissionIds)) . ')'
                );

            foreach ($validRows as $row) {
                $this->execute(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)',
                    ['role_id' => $roleId, 'permission_id' => (int) $row['id']]
                );
            }
        });
    }

    public function countUsers(int $roleId): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM users WHERE role_id = :role_id',
            ['role_id' => $roleId]
        );
    }
}
