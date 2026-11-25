<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/MemberController.php
 * 模块：后台成员管理
 * 职责：团队成员列表、新增、编辑、职位/介绍/头像维护、离队与安全删除。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Member;
use App\Models\Team;
use App\Support\ImageUpload;
use Core\Paginator;
use Core\Response;

final class MemberController extends AdminController
{
    private const PER_PAGE = 15;

    public function index(): Response
    {
        $team = (new Team())->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }

        $teamId = (int) $team['id'];
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, ['active', 'alumni', 'removed'], true) ? $status : '';

        $model = new Member();
        $total = $model->countAdmin($teamId, $query, $status);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/members/index', '团队成员', 'members', [
            'team' => $team,
            'members' => $model->adminPage($teamId, self::PER_PAGE, $pagination['offset'], $query, $status),
            'query' => $query,
            'status' => $status,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '团队成员'],
        ]);
    }

    public function create(): Response
    {
        return $this->formPage(null);
    }

    public function store(): Response
    {
        $team = (new Team())->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }
        $teamId = (int) $team['id'];

        [$data, $errors] = $this->validateInput(null);
        $candidate = (new Member())->availableUsers($teamId);
        $validUserIds = array_map(static fn (array $user): int => (int) $user['id'], $candidate);
        if (!in_array((int) $data['user_id'], $validUserIds, true)) {
            $errors['user_id'] = '请选择尚未加入当前团队的启用账号。';
        } else {
            foreach ($candidate as $user) {
                if ((int) $user['id'] === (int) $data['user_id']) {
                    $data['display_name'] = (string) $user['display_name'];
                    break;
                }
            }
        }

        $newAvatar = $this->storeAvatar($errors);
        if ($errors !== []) {
            if ($newAvatar !== null) {
                ImageUpload::removeManaged($newAvatar);
            }
            return $this->backWithErrors('/admin/members/create', $errors, $data['old']);
        }

        $data['avatar_path'] = $newAvatar;
        $membershipId = (new Member())->createMembership($teamId, $data);
        $this->logAction('members', 'create', '添加团队成员：' . $data['display_name'], 'team_member', $membershipId);
        $this->success('团队成员已添加。');

        return $this->redirect('/admin/members');
    }

    public function edit(string $id): Response
    {
        $membership = (new Member())->findAdmin((int) $id);
        if ($membership === null) {
            abort(404, '成员关系不存在。');
        }

        return $this->formPage($membership);
    }

    public function update(string $id): Response
    {
        $membershipId = (int) $id;
        $model = new Member();
        $membership = $model->findAdmin($membershipId);
        if ($membership === null) {
            abort(404, '成员关系不存在。');
        }

        [$data, $errors] = $this->validateInput($membership);
        $newAvatar = $this->storeAvatar($errors);
        if ($errors !== []) {
            if ($newAvatar !== null) {
                ImageUpload::removeManaged($newAvatar);
            }
            return $this->backWithErrors('/admin/members/' . $membershipId . '/edit', $errors, $data['old']);
        }

        $avatarPath = (string) ($membership['avatar_path'] ?? '');
        if ($newAvatar !== null) {
            $avatarPath = $newAvatar;
        } elseif ($this->checkbox('remove_avatar')) {
            $avatarPath = '';
        }
        $oldAvatar = (string) ($membership['avatar_path'] ?? '');
        $data['avatar_path'] = $avatarPath === '' ? null : $avatarPath;
        $model->updateMembership($membershipId, (int) $membership['user_id'], $data);

        if (($newAvatar !== null || $avatarPath === '') && $oldAvatar !== $avatarPath) {
            ImageUpload::removeManaged($oldAvatar);
        }

        $this->logAction('members', 'update', '更新团队成员：' . $membership['display_name'], 'team_member', $membershipId);
        $this->success('团队成员资料已更新。');

        return $this->redirect('/admin/members');
    }

    public function remove(string $id): Response
    {
        $membershipId = (int) $id;
        $model = new Member();
        $membership = $model->findAdmin($membershipId);
        if ($membership === null) {
            abort(404, '成员关系不存在。');
        }

        $model->setStatus($membershipId, 'removed');
        $this->logAction('members', 'remove', '成员离队：' . $membership['display_name'], 'team_member', $membershipId);
        $this->success('成员已标记为离队，项目历史保留。');

        return $this->redirect('/admin/members');
    }

    public function destroy(string $id): Response
    {
        $membershipId = (int) $id;
        $model = new Member();
        $membership = $model->findAdmin($membershipId);
        if ($membership === null) {
            abort(404, '成员关系不存在。');
        }

        if ($model->projectReferenceCount($membershipId) > 0) {
            $this->error('该成员已关联项目，不能物理删除；请使用“标记离队”。');
            return $this->redirect('/admin/members');
        }

        if ($model->fileReferenceCount($membershipId) > 0) {
            $this->error('该成员仍关联个人文件，不能物理删除；请先调整文件归属。');
            return $this->redirect('/admin/members');
        }

        $model->deleteMembership($membershipId);
        $this->logAction('members', 'delete', '删除成员关系：' . $membership['display_name'], 'team_member', $membershipId);
        $this->success('成员关系已删除，账号与个人资料保留。');

        return $this->redirect('/admin/members');
    }

    /**
     * @param array<string, mixed>|null $membership
     */
    private function formPage(?array $membership): Response
    {
        $team = (new Team())->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }

        $editing = $membership !== null;
        $model = new Member();

        return $this->page('admin/members/form', $editing ? '编辑成员' : '添加成员', 'members', [
            'membership' => $membership,
            'team' => $team,
            'editing' => $editing,
            'candidates' => $editing ? [] : $model->availableUsers((int) $team['id']),
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '团队成员', 'url' => '/admin/members'],
            ['label' => $editing ? '编辑成员' : '添加成员'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $membership
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?array $membership): array
    {
        $editing = $membership !== null;
        $userId = $editing ? (int) $membership['user_id'] : $this->integer('user_id');
        $position = $this->nullableText('position_title', 80);
        $responsibilities = $this->nullableText('responsibilities', 255);
        $joinedRaw = $this->nullableText('joined_at', 10);
        $joinedAt = $this->dateValue('joined_at');
        $leftRaw = $this->nullableText('left_at', 10);
        $leftAt = $this->dateValue('left_at');
        $status = $this->text('status', 20);
        $sortOrder = $this->integer('sort_order', 0);
        $bio = $this->nullableText('bio', 5000);
        $skills = $this->nullableText('skills', 255);
        $publicEmail = $this->nullableText('public_email', 120);
        $githubUrl = $this->nullableText('github_url', 255);
        $errors = [];

        if ($userId <= 0) {
            $errors['user_id'] = '请选择账号。';
        }
        if ($joinedAt === null) {
            $errors['joined_at'] = '请选择有效的加入日期。';
        }
        if ($leftRaw !== null && $leftAt === null) {
            $errors['left_at'] = '离队日期格式应为 YYYY-MM-DD。';
        }
        if ($joinedAt !== null && $leftAt !== null && $leftAt < $joinedAt) {
            $errors['left_at'] = '离队日期不能早于加入日期。';
        }
        if (!in_array($status, ['active', 'alumni', 'removed'], true)) {
            $errors['status'] = '成员状态不合法。';
        }
        if ($status !== 'active' && $leftAt === null) {
            $leftAt = date('Y-m-d');
        }
        if ($status === 'active') {
            $leftAt = null;
        }
        if ($sortOrder < -999 || $sortOrder > 999) {
            $errors['sort_order'] = '排序值需在 -999 到 999 之间。';
        }
        if ($publicEmail !== null && filter_var($publicEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors['public_email'] = '公开邮箱格式不正确。';
        }
        if (!$this->validHttpUrl($githubUrl)) {
            $errors['github_url'] = 'GitHub 地址必须是 http(s) URL。';
        }

        $displayName = (string) ($membership['display_name'] ?? $this->text('display_name', 50));
        $old = [
            'user_id' => $userId,
            'position_title' => $position ?? '',
            'responsibilities' => $responsibilities ?? '',
            'joined_at' => $joinedRaw ?? '',
            'left_at' => $leftRaw ?? '',
            'status' => $status,
            'sort_order' => $sortOrder,
            'bio' => $bio ?? '',
            'skills' => $skills ?? '',
            'public_email' => $publicEmail ?? '',
            'github_url' => $githubUrl ?? '',
        ];

        return [[
            'user_id' => $userId,
            'display_name' => $displayName,
            'position_title' => $position,
            'responsibilities' => $responsibilities,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
            'status' => $status,
            'sort_order' => $sortOrder,
            'bio' => $bio,
            'skills' => $skills,
            'avatar_path' => null,
            'public_email' => $publicEmail,
            'github_url' => $githubUrl,
            'old' => $old,
        ], $errors];
    }

    /**
     * @param array<string, string> $errors
     */
    private function storeAvatar(array &$errors): ?string
    {
        try {
            return ImageUpload::store(
                $this->request->file('avatar'),
                'avatars',
                4 * 1024 * 1024
            );
        } catch (\Throwable $e) {
            $errors['avatar'] = '头像上传失败：' . $e->getMessage();
            return null;
        }
    }
}
