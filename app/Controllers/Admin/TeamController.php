<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/TeamController.php
 * 模块：后台团队管理
 * 职责：编辑团队公开资料、研究方向、技术栈、联系方式和展示状态。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Team;
use App\Support\ImageUpload;
use App\Support\Slugger;
use Core\Response;

final class TeamController extends AdminController
{
    public function index(): Response
    {
        $team = (new Team())->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }

        return $this->page('admin/team/edit', '团队信息', 'team', [
            'team' => $team,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '团队信息'],
        ]);
    }

    public function update(): Response
    {
        $model = new Team();
        $team = $model->firstAny();
        if ($team === null) {
            abort(404, '尚未创建团队记录。');
        }

        $teamId = (int) $team['id'];
        $name = $this->text('name', 100);
        $slug = Slugger::normalize($this->text('slug', 100));
        if ($slug === '') {
            $slug = Slugger::fromTitle($name, 'team');
        }
        $shortName = $this->nullableText('short_name', 50);
        $slogan = $this->nullableText('slogan', 200);
        $description = $this->nullableText('description', 20000);
        $researchAreas = $this->nullableText('research_areas', 255);
        $techStack = $this->nullableText('tech_stack', 255);
        $foundedRaw = $this->nullableText('founded_at', 10);
        $foundedAt = $this->dateValue('founded_at');
        $email = $this->nullableText('email', 120);
        $location = $this->nullableText('location', 120);
        $githubUrl = $this->nullableText('github_url', 255);
        $websiteUrl = $this->nullableText('website_url', 255);
        $status = $this->text('status', 20);
        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = '团队名称至少 2 个字符。';
        }
        if (!Slugger::isValid($slug)) {
            $errors['slug'] = '团队别名只能使用小写字母、数字和连字符。';
        } elseif ($model->slugExists($slug, $teamId)) {
            $errors['slug'] = '团队别名已被占用。';
        }
        if ($foundedRaw !== null && $foundedAt === null) {
            $errors['founded_at'] = '成立日期格式应为 YYYY-MM-DD。';
        }
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = '团队邮箱格式不正确。';
        }
        if (!$this->validHttpUrl($githubUrl)) {
            $errors['github_url'] = 'GitHub 地址必须是 http(s) URL。';
        }
        if (!$this->validHttpUrl($websiteUrl)) {
            $errors['website_url'] = '网站地址必须是 http(s) URL。';
        }
        if (!in_array($status, ['active', 'archived'], true)) {
            $errors['status'] = '团队状态不合法。';
        }

        $newLogo = null;
        try {
            $newLogo = ImageUpload::store(
                $this->request->file('logo'),
                'team',
                (int) config('app.uploads.max_size', 20971520)
            );
        } catch (\Throwable $e) {
            $errors['logo'] = 'Logo 上传失败：' . $e->getMessage();
        }

        $old = [
            'name' => $name,
            'slug' => $slug,
            'short_name' => $shortName ?? '',
            'slogan' => $slogan ?? '',
            'description' => $description ?? '',
            'research_areas' => $researchAreas ?? '',
            'tech_stack' => $techStack ?? '',
            'founded_at' => $foundedRaw ?? '',
            'email' => $email ?? '',
            'location' => $location ?? '',
            'github_url' => $githubUrl ?? '',
            'website_url' => $websiteUrl ?? '',
            'status' => $status,
        ];

        if ($errors !== []) {
            if ($newLogo !== null) {
                ImageUpload::removeManaged($newLogo);
            }
            return $this->backWithErrors('/admin/team', $errors, $old);
        }

        $logoPath = (string) ($team['logo_path'] ?? '');
        if ($newLogo !== null) {
            $logoPath = $newLogo;
        } elseif ($this->checkbox('remove_logo')) {
            $logoPath = '';
        }

        $oldLogo = (string) ($team['logo_path'] ?? '');
        $model->updateProfile($teamId, [
            'name' => $name,
            'slug' => $slug,
            'short_name' => $shortName,
            'slogan' => $slogan,
            'description' => $description,
            'research_areas' => $researchAreas,
            'tech_stack' => $techStack,
            'logo_path' => $logoPath === '' ? null : $logoPath,
            'founded_at' => $foundedAt,
            'email' => $email,
            'location' => $location,
            'github_url' => $githubUrl,
            'website_url' => $websiteUrl,
            'status' => $status,
        ]);

        if (($newLogo !== null || $logoPath === '') && $oldLogo !== $logoPath) {
            ImageUpload::removeManaged($oldLogo);
        }

        $this->logAction('team', 'update', '更新团队资料：' . $name, 'team', $teamId);
        $this->success('团队资料已更新。');

        return $this->redirect('/admin/team');
    }
}
