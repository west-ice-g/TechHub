<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/UserController.php
 * 模块：用户与权限管理
 * 职责：后台账号列表、搜索、新增、编辑、角色分配、状态管理和安全删除。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Support\ImageUpload;
use Core\Auth;
use Core\Paginator;
use Core\Response;

final class UserController extends AdminController
{
    private const PER_PAGE = 15;

    public function index(): Response
    {
        $this->requireSuperAdmin();

        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';
        $roleId = (int) $this->request->query('role_id', 0);
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, ['active', 'disabled'], true) ? $status : '';

        $model = new User();
        $total = $model->countAdmin($query, $roleId, $status);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/users/index', '用户管理', 'users', [
            'users' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $query, $roleId, $status),
            'roles' => (new Role())->all(),
            'query' => $query,
            'roleId' => $roleId,
            'status' => $status,
            'pagination' => $pagination,
            'paginationPath' => '/admin/users',
            'paginationQuery' => ['q' => $query, 'role_id' => $roleId > 0 ? $roleId : null, 'status' => $status],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '系统管理'],
            ['label' => '用户管理'],
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
            return $this->backWithErrors('/admin/users/create', $errors, $data['old']);
        }

        $model = new User();
        $id = $model->createAdmin(
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['display_name'],
            $data['email'],
            $data['role_id'],
            $data['status']
        );

        $this->logAction('users', 'create', '创建后台账号：' . $data['username'], 'user', $id);
        $this->success('账号已创建。');

        return $this->redirect('/admin/users');
    }

    public function edit(string $id): Response
    {
        $this->requireSuperAdmin();
        $user = (new User())->findAdmin((int) $id);
        if ($user === null) {
            abort(404, '账号不存在。');
        }

        return $this->formPage($user);
    }

    public function update(string $id): Response
    {
        $this->requireSuperAdmin();
        $userId = (int) $id;
        $model = new User();
        $user = $model->findAdmin($userId);
        if ($user === null) {
            abort(404, '账号不存在。');
        }

        [$data, $errors] = $this->validateInput($userId, false);
        $isSelf = $userId === Auth::id();

        if ($isSelf) {
            if ($data['role_id'] !== (int) $user['role_id']) {
                $errors['role_id'] = '不能修改自己的角色。';
            }
            if ($data['status'] !== 'active') {
                $errors['status'] = '不能停用当前登录账号。';
            }
        }

        if (
            (int) $user['role_id'] === $this->superAdminRoleId()
            && ($data['role_id'] !== (int) $user['role_id'] || $data['status'] !== 'active')
            && $model->countActiveSuperAdmins($userId) === 0
        ) {
            $errors['role_id'] = '系统必须至少保留一个启用的超级管理员。';
        }

        if ($errors !== []) {
            return $this->backWithErrors('/admin/users/' . $userId . '/edit', $errors, $data['old']);
        }

        $hash = $data['password'] === '' ? null : password_hash($data['password'], PASSWORD_DEFAULT);
        $model->updateAdmin(
            $userId,
            $data['display_name'],
            $data['email'],
            $data['role_id'],
            $data['status'],
            $hash
        );

        $this->logAction('users', 'update', '更新后台账号：' . $user['username'], 'user', $userId);
        $this->success('账号信息已更新。');

        return $this->redirect('/admin/users');
    }

    public function status(string $id): Response
    {
        $this->requireSuperAdmin();
        $userId = (int) $id;
        $model = new User();
        $user = $model->findAdmin($userId);
        if ($user === null) {
            abort(404, '账号不存在。');
        }

        if ($userId === Auth::id()) {
            $this->error('不能停用当前登录账号。');
            return $this->redirect('/admin/users');
        }

        $target = $user['status'] === 'active' ? 'disabled' : 'active';
        if (
            $target === 'disabled'
            && (int) $user['role_id'] === $this->superAdminRoleId()
            && $model->countActiveSuperAdmins($userId) === 0
        ) {
            $this->error('系统必须至少保留一个启用的超级管理员。');
            return $this->redirect('/admin/users');
        }

        $model->setStatus($userId, $target);
        $this->logAction('users', 'status', '将账号 ' . $user['username'] . ' 状态改为 ' . $target, 'user', $userId);
        $this->success($target === 'active' ? '账号已启用。' : '账号已停用。');

        return $this->redirect('/admin/users');
    }

    public function destroy(string $id): Response
    {
        $this->requireSuperAdmin();
        $userId = (int) $id;
        $model = new User();
        $user = $model->findAdmin($userId);
        if ($user === null) {
            abort(404, '账号不存在。');
        }

        if ($userId === Auth::id()) {
            $this->error('不能删除当前登录账号。');
            return $this->redirect('/admin/users');
        }

        if (
            (int) $user['role_id'] === $this->superAdminRoleId()
            && $model->countActiveSuperAdmins($userId) === 0
        ) {
            $this->error('系统必须至少保留一个启用的超级管理员。');
            return $this->redirect('/admin/users');
        }

        $dependencies = $model->dependencyCounts($userId);
        $blocking = array_filter($dependencies, static fn (int $count): bool => $count > 0);
        if ($blocking !== []) {
            $this->error('该账号已关联团队成员、项目、文章、公告、回复或文件，不能物理删除，请改为停用。');
            return $this->redirect('/admin/users');
        }

        $avatarPath = $model->avatarPath($userId);
        $model->deleteUser($userId);
        ImageUpload::removeManaged($avatarPath);
        $this->logAction('users', 'delete', '删除后台账号：' . $user['username'], 'user', $userId);
        $this->success('账号已删除。');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed>|null $user
     */
    private function formPage(?array $user): Response
    {
        $editing = $user !== null;

        return $this->page('admin/users/form', $editing ? '编辑账号' : '新增账号', 'users', [
            'user' => $user,
            'roles' => (new Role())->all(),
            'editing' => $editing,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '用户管理', 'url' => '/admin/users'],
            ['label' => $editing ? '编辑账号' : '新增账号'],
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?int $exceptId, bool $creating): array
    {
        $username = $creating ? $this->text('username', 50) : '';
        $displayName = $this->text('display_name', 50);
        $email = $this->nullableText('email', 120);
        $roleId = $this->integer('role_id');
        $status = $this->text('status', 20);
        $password = $this->request->post('password', '');
        $passwordConfirm = $this->request->post('password_confirm', '');
        $password = is_string($password) ? $password : '';
        $passwordConfirm = is_string($passwordConfirm) ? $passwordConfirm : '';

        $errors = [];
        if ($creating) {
            if (preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) !== 1) {
                $errors['username'] = '用户名需为 3–50 位字母、数字、下划线、点或连字符。';
            } elseif ((new User())->usernameExists($username)) {
                $errors['username'] = '该用户名已存在。';
            }
        }

        if (mb_strlen($displayName) < 2) {
            $errors['display_name'] = '显示名称至少 2 个字符。';
        }

        if ($email !== null) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = '邮箱格式不正确。';
            } elseif ((new User())->emailExists($email, $exceptId)) {
                $errors['email'] = '该邮箱已被其他账号使用。';
            }
        }

        if ((new Role())->find($roleId) === null) {
            $errors['role_id'] = '请选择有效角色。';
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $errors['status'] = '账号状态不合法。';
        }

        if ($creating || $password !== '') {
            if (mb_strlen($password) < 8 || strlen($password) > 72) {
                $errors['password'] = '密码至少 8 个字符，且不超过 72 字节。';
            } elseif ($password !== $passwordConfirm) {
                $errors['password_confirm'] = '两次输入的密码不一致。';
            }
        }

        $old = [
            'username' => $username,
            'display_name' => $displayName,
            'email' => $email ?? '',
            'role_id' => $roleId,
            'status' => $status,
        ];

        return [[
            'username' => $username,
            'display_name' => $displayName,
            'email' => $email,
            'role_id' => $roleId,
            'status' => $status,
            'password' => $password,
            'old' => $old,
        ], $errors];
    }

    private function superAdminRoleId(): int
    {
        $role = (new Role())->findByCode('super_admin');

        return $role === null ? 0 : (int) $role['id'];
    }
}
