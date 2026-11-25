<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/ProjectController.php
 * 模块：后台项目管理
 * 职责：项目增删改查、封面上传、状态、发布下线、项目成员关联。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Member;
use App\Models\Project;
use App\Models\Team;
use App\Support\ImageUpload;
use App\Support\Slugger;
use Core\Auth;
use Core\Paginator;
use Core\Response;

final class ProjectController extends AdminController
{
    private const PER_PAGE = 12;

    /** @var array<int, string> */
    private const STATUSES = ['planning', 'ongoing', 'completed', 'archived'];

    public function index(): Response
    {
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, self::STATUSES, true) ? $status : '';
        $visibility = (string) $this->request->query('visibility', '');
        $visibility = in_array($visibility, ['public', 'hidden'], true) ? $visibility : '';
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';

        $model = new Project();
        $total = $model->countAdmin($status, $visibility, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/projects/index', '项目管理', 'projects', [
            'projects' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $status, $visibility, $query),
            'query' => $query,
            'status' => $status,
            'visibility' => $visibility,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '项目管理'],
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

        [$data, $errors] = $this->validateInput(null, (int) $team['id']);
        $newCover = $this->storeCover($errors);
        if ($errors !== []) {
            if ($newCover !== null) {
                ImageUpload::removeManaged($newCover);
            }
            return $this->backWithErrors('/admin/projects/create', $errors, $data['old']);
        }

        $data['cover_path'] = $newCover;
        $data['created_by'] = Auth::id();
        $data['team_id'] = (int) $team['id'];
        $model = new Project();
        $projectId = $model->transaction(function () use ($model, $data): int {
            $id = $model->createProject($data);
            $model->syncMembers($id, $data['member_ids'], $data['member_roles'], $data['start_date']);
            return $id;
        });

        $this->logAction('projects', 'create', '创建项目：' . $data['title'], 'project', $projectId);
        $this->success('项目已创建。');

        return $this->redirect('/admin/projects');
    }

    public function edit(string $id): Response
    {
        $project = (new Project())->findAdmin((int) $id);
        if ($project === null) {
            abort(404, '项目不存在。');
        }

        return $this->formPage($project);
    }

    public function update(string $id): Response
    {
        $projectId = (int) $id;
        $model = new Project();
        $project = $model->findAdmin($projectId);
        if ($project === null) {
            abort(404, '项目不存在。');
        }

        [$data, $errors] = $this->validateInput($project, (int) $project['team_id']);
        $newCover = $this->storeCover($errors);
        if ($errors !== []) {
            if ($newCover !== null) {
                ImageUpload::removeManaged($newCover);
            }
            return $this->backWithErrors('/admin/projects/' . $projectId . '/edit', $errors, $data['old']);
        }

        $coverPath = (string) ($project['cover_path'] ?? '');
        if ($newCover !== null) {
            $coverPath = $newCover;
        } elseif ($this->checkbox('remove_cover')) {
            $coverPath = '';
        }
        $oldCover = (string) ($project['cover_path'] ?? '');
        $data['cover_path'] = $coverPath === '' ? null : $coverPath;
        $model->transaction(function () use ($model, $projectId, $data): void {
            $model->updateProject($projectId, $data);
            $model->syncMembers($projectId, $data['member_ids'], $data['member_roles'], $data['start_date']);
        });

        if (($newCover !== null || $coverPath === '') && $oldCover !== $coverPath) {
            ImageUpload::removeManaged($oldCover);
        }

        $this->logAction('projects', 'update', '更新项目：' . $data['title'], 'project', $projectId);
        $this->success('项目已更新。');

        return $this->redirect('/admin/projects');
    }

    public function visibility(string $id): Response
    {
        $projectId = (int) $id;
        $model = new Project();
        $project = $model->findAdmin($projectId);
        if ($project === null) {
            abort(404, '项目不存在。');
        }

        $target = (int) $project['is_public'] === 1 ? false : true;
        $model->setPublic($projectId, $target);
        $this->logAction('projects', 'visibility', ($target ? '发布' : '下线') . '项目：' . $project['title'], 'project', $projectId);
        $this->success($target ? '项目已发布到前台。' : '项目已从前台下线。');

        return $this->redirect('/admin/projects');
    }

    public function destroy(string $id): Response
    {
        $projectId = (int) $id;
        $model = new Project();
        $project = $model->findAdmin($projectId);
        if ($project === null) {
            abort(404, '项目不存在。');
        }

        $model->deleteProject($projectId);
        ImageUpload::removeManaged((string) ($project['cover_path'] ?? ''));
        $this->logAction('projects', 'delete', '删除项目：' . $project['title'], 'project', $projectId);
        $this->success('项目已删除，项目成员关联已解除。');

        return $this->redirect('/admin/projects');
    }

    /**
     * @param array<string, mixed>|null $project
     */
    private function formPage(?array $project): Response
    {
        $team = (new Team())->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }

        $editing = $project !== null;
        $teamId = $editing ? (int) $project['team_id'] : (int) $team['id'];
        $projectModel = new Project();
        $selectedMembers = $editing ? $projectModel->projectMemberIds((int) $project['id']) : [];
        $memberRoles = [];
        if ($editing) {
            foreach ($projectModel->projectMemberRows((int) $project['id']) as $row) {
                $memberRoles[(int) $row['team_member_id']] = (string) ($row['role_in_project'] ?? '');
            }
        }

        return $this->page('admin/projects/form', $editing ? '编辑项目' : '新增项目', 'projects', [
            'project' => $project,
            'team' => $team,
            'editing' => $editing,
            'memberOptions' => (new Member())->memberOptions($teamId),
            'selectedMembers' => $selectedMembers,
            'memberRoles' => $memberRoles,
            'statusOptions' => [
                'planning' => '规划中',
                'ongoing' => '进行中',
                'completed' => '已完成',
                'archived' => '已归档',
            ],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '项目管理', 'url' => '/admin/projects'],
            ['label' => $editing ? '编辑项目' : '新增项目'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $project
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?array $project, int $teamId): array
    {
        $title = $this->text('title', 150);
        $slugInput = $this->text('slug', 160);
        $slug = Slugger::normalize($slugInput);
        if ($slug === '') {
            $slug = Slugger::fromTitle($title, 'project');
        }
        $summary = $this->text('summary', 500);
        $description = $this->text('description', 30000);
        $outcomes = $this->nullableText('outcomes', 10000);
        $techStack = $this->nullableText('tech_stack', 255);
        $repoUrl = $this->nullableText('repo_url', 255);
        $demoUrl = $this->nullableText('demo_url', 255);
        $status = $this->text('status', 20);
        $isPublic = $this->checkbox('is_public');
        $isFeatured = $this->checkbox('is_featured');
        $sortOrder = $this->integer('sort_order', 0);
        $startRaw = $this->nullableText('start_date', 10);
        $startDate = $this->dateValue('start_date');
        $endRaw = $this->nullableText('end_date', 10);
        $endDate = $this->dateValue('end_date');
        $errors = [];

        $memberIds = $this->postedMemberIds();
        $memberRoles = $this->postedMemberRoles();
        $allowedMemberIds = [];
        foreach ((new Member())->memberOptions($teamId) as $option) {
            $allowedMemberIds[] = (int) $option['id'];
        }
        foreach ($memberIds as $memberId) {
            if (!in_array($memberId, $allowedMemberIds, true)) {
                $errors['members'] = '项目成员选择不合法。';
                break;
            }
        }

        if (mb_strlen($title) < 2) {
            $errors['title'] = '项目标题至少 2 个字符。';
        }
        if (!Slugger::isValid($slug)) {
            $errors['slug'] = 'URL 别名只能使用小写字母、数字和连字符。';
        } elseif ((new Project())->slugExists($slug, $project === null ? null : (int) $project['id'])) {
            $errors['slug'] = 'URL 别名已被其他项目使用。';
        }
        if (mb_strlen($summary) < 5) {
            $errors['summary'] = '项目摘要至少 5 个字符。';
        }
        if (mb_strlen($description) < 10) {
            $errors['description'] = '项目介绍至少 10 个字符。';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = '项目状态不合法。';
        }
        if ($sortOrder < -999 || $sortOrder > 999) {
            $errors['sort_order'] = '排序值需在 -999 到 999 之间。';
        }
        if ($startRaw !== null && $startDate === null) {
            $errors['start_date'] = '开始日期格式应为 YYYY-MM-DD。';
        }
        if ($endRaw !== null && $endDate === null) {
            $errors['end_date'] = '结束日期格式应为 YYYY-MM-DD。';
        }
        if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
            $errors['end_date'] = '结束日期不能早于开始日期。';
        }
        if (!$this->validHttpUrl($repoUrl)) {
            $errors['repo_url'] = '代码仓库地址必须是 http(s) URL。';
        }
        if (!$this->validHttpUrl($demoUrl)) {
            $errors['demo_url'] = '演示地址必须是 http(s) URL。';
        }

        return [[
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'description' => $description,
            'outcomes' => $outcomes,
            'cover_path' => null,
            'tech_stack' => $techStack,
            'repo_url' => $repoUrl,
            'demo_url' => $demoUrl,
            'status' => $status,
            'is_public' => $isPublic ? 1 : 0,
            'is_featured' => $isFeatured ? 1 : 0,
            'sort_order' => $sortOrder,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'member_ids' => $memberIds,
            'member_roles' => $memberRoles,
            'old' => [
                'title' => $title,
                'slug' => $slug,
                'summary' => $summary,
                'description' => $description,
                'outcomes' => $outcomes ?? '',
                'tech_stack' => $techStack ?? '',
                'repo_url' => $repoUrl ?? '',
                'demo_url' => $demoUrl ?? '',
                'status' => $status,
                'is_public' => $isPublic ? 1 : 0,
                'is_featured' => $isFeatured ? 1 : 0,
                'sort_order' => $sortOrder,
                'start_date' => $startRaw ?? '',
                'end_date' => $endRaw ?? '',
                'members' => $memberIds,
                'member_roles' => $memberRoles,
            ],
        ], $errors];
    }

    /**
     * @return array<int, int>
     */
    private function postedMemberIds(): array
    {
        $raw = $this->request->post('members', []);
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, string>
     */
    private function postedMemberRoles(): array
    {
        $raw = $this->request->post('member_roles', []);
        if (!is_array($raw)) {
            return [];
        }

        $roles = [];
        foreach ($raw as $id => $value) {
            if (!is_numeric($id) || !is_string($value)) {
                continue;
            }
            $value = trim($value);
            if (mb_check_encoding($value, 'UTF-8')) {
                $roles[(int) $id] = mb_substr($value, 0, 80);
            }
        }

        return $roles;
    }

    /**
     * @param array<string, string> $errors
     */
    private function storeCover(array &$errors): ?string
    {
        try {
            return ImageUpload::store(
                $this->request->file('cover'),
                'projects',
                5 * 1024 * 1024
            );
        } catch (\Throwable $e) {
            $errors['cover'] = '封面上传失败：' . $e->getMessage();
            return null;
        }
    }
}
