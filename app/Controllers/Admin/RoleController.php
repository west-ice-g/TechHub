<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/RoleController.php
 * 模块：角色与权限管理
 * 职责：角色增改、权限矩阵配置和安全删除；仅超级管理员可访问。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Role;
use Core\Response;

final class RoleController extends AdminController
{
    public function index(): Response
    {
        $this->requireSuperAdmin();

        return $this->page('admin/roles/index', '角色权限', 'roles', [
            'roles' => (new Role())->allWithCounts(),
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '系统管理'],
            ['label' => '角色权限'],
        ]);
    }

    public function create(): Response
    {
        $this->requireSuperAdmin();

        return $this->formPage(null);
    }

    public function store(): Response
    {
        $this->requireSuperAdmin();
        [$data, $errors] = $this->validateInput(null, true);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/roles/create', $errors, $data['old']);
        }

        $id = (new Role())->create($data['code'], $data['name'], $data['description'], $data['sort_order']);
        $this->logAction('roles', 'create', '创建角色：' . $data['code'], 'role', $id);
        $this->success('角色已创建，请继续配置权限。');

        return $this->redirect('/admin/roles/' . $id . '/edit');
    }

    public function edit(string $id): Response
    {
        $this->requireSuperAdmin();
        $role = (new Role())->find((int) $id);
        if ($role === null) {
            abort(404, '角色不存在。');
        }

        return $this->formPage($role);
    }

    public function update(string $id): Response
    {
        $this->requireSuperAdmin();
        $roleId = (int) $id;
        $role = (new Role())->find($roleId);
        if ($role === null) {
            abort(404, '角色不存在。');
        }

        [$data, $errors] = $this->validateInput($roleId, false);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/roles/' . $roleId . '/edit', $errors, $data['old']);
        }

        (new Role())->update($roleId, $data['name'], $data['description'], $data['sort_order']);
        $this->logAction('roles', 'update', '更新角色：' . $role['code'], 'role', $roleId);
        $this->success('角色信息已更新。');

        return $this->redirect('/admin/roles/' . $roleId . '/edit');
    }

    public function permissions(string $id): Response
    {
        $this->requireSuperAdmin();
        $roleId = (int) $id;
        $role = (new Role())->find($roleId);
        if ($role === null) {
            abort(404, '角色不存在。');
        }

        if ($role['code'] === 'super_admin') {
            $this->error('超级管理员权限由系统固定，不能通过界面降权。');
            return $this->redirect('/admin/roles/' . $roleId . '/edit');
        }

        $raw = $this->request->post('permissions', []);
        $permissionIds = [];
        if (is_array($raw)) {
            foreach ($raw as $value) {
                if (is_numeric($value) && (int) $value > 0) {
                    $permissionIds[] = (int) $value;
                }
            }
        }

        (new Role())->syncPermissions($roleId, array_values(array_unique($permissionIds)));
        $this->logAction('roles', 'permissions', '更新角色权限：' . $role['code'], 'role', $roleId);
        $this->success('角色权限已保存。');

        return $this->redirect('/admin/roles/' . $roleId . '/edit');
    }

    public function destroy(string $id): Response
    {
        $this->requireSuperAdmin();
        $roleId = (int) $id;
        $model = new Role();
        $role = $model->find($roleId);
        if ($role === null) {
            abort(404, '角色不存在。');
        }

        if ((int) $role['is_system'] === 1) {
            $this->error('系统角色不能删除。');
            return $this->redirect('/admin/roles');
        }

        if ($model->countUsers($roleId) > 0) {
            $this->error('该角色仍有账号使用，不能删除。');
            return $this->redirect('/admin/roles');
        }

        $model->delete($roleId);
        $this->logAction('roles', 'delete', '删除角色：' . $role['code'], 'role', $roleId);
        $this->success('角色已删除。');

        return $this->redirect('/admin/roles');
    }

    /**
     * @param array<string, mixed>|null $role
     */
    private function formPage(?array $role): Response
    {
        $editing = $role !== null;
        $roleModel = new Role();

        return $this->page('admin/roles/form', $editing ? '编辑角色' : '新增角色', 'roles', [
            'role' => $role,
            'editing' => $editing,
            'permissionItems' => $roleModel->permissions(),
            'rolePermissionIds' => $editing ? $roleModel->permissionIdsForRole((int) $role['id']) : [],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '角色权限', 'url' => '/admin/roles'],
            ['label' => $editing ? '编辑角色' : '新增角色'],
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?int $roleId, bool $creating): array
    {
        $code = $this->text('code', 50);
        $name = $this->text('name', 50);
        $description = $this->nullableText('description', 255);
        $sortOrder = $this->integer('sort_order', 0);
        $errors = [];

        if ($creating && preg_match('/^[a-z][a-z0-9_]{2,49}$/', $code) !== 1) {
            $errors['code'] = '角色代码需为 3–50 位小写字母、数字或下划线，并以字母开头。';
        } elseif ($creating && (new Role())->codeExists($code)) {
            $errors['code'] = '角色代码已存在。';
        }

        if (mb_strlen($name) < 2) {
            $errors['name'] = '角色名称至少 2 个字符。';
        }

        if ($sortOrder < -999 || $sortOrder > 999) {
            $errors['sort_order'] = '排序值需在 -999 到 999 之间。';
        }

        return [[
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'old' => ['code' => $code, 'name' => $name, 'description' => $description ?? '', 'sort_order' => $sortOrder],
        ], $errors];
    }
}
