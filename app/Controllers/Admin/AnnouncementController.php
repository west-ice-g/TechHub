<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/AnnouncementController.php
 * 模块：后台公告管理
 * 职责：公告增删改查、类型、置顶、发布与下线。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Announcement;
use Core\Auth;
use Core\Paginator;
use Core\Response;

final class AnnouncementController extends AdminController
{
    private const PER_PAGE = 15;

    /** @var array<int, string> */
    private const STATUSES = ['draft', 'published', 'archived'];
    /** @var array<int, string> */
    private const TYPES = ['general', 'event', 'recruitment', 'system'];

    public function index(): Response
    {
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, self::STATUSES, true) ? $status : '';
        $type = (string) $this->request->query('type', '');
        $type = in_array($type, self::TYPES, true) ? $type : '';
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';

        $model = new Announcement();
        $total = $model->countAdmin($status, $type, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/announcements/index', '公告管理', 'announcements', [
            'announcements' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $status, $type, $query),
            'query' => $query,
            'status' => $status,
            'type' => $type,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '公告管理'],
        ]);
    }

    public function create(): Response
    {
        return $this->formPage(null);
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validateInput(null);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/announcements/create', $errors, $data['old']);
        }

        $data['publisher_id'] = Auth::id();
        $data['published_at'] = $data['status'] === 'published' ? date('Y-m-d H:i:s') : null;
        $id = (new Announcement())->createAnnouncement($data);
        $this->logAction('announcements', 'create', '创建公告：' . $data['title'], 'announcement', $id);
        $this->success('公告已创建。');

        return $this->redirect('/admin/announcements');
    }

    public function edit(string $id): Response
    {
        $announcement = (new Announcement())->findAdmin((int) $id);
        if ($announcement === null) {
            abort(404, '公告不存在。');
        }

        return $this->formPage($announcement);
    }

    public function update(string $id): Response
    {
        $announcementId = (int) $id;
        $model = new Announcement();
        $announcement = $model->findAdmin($announcementId);
        if ($announcement === null) {
            abort(404, '公告不存在。');
        }

        [$data, $errors] = $this->validateInput($announcement);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/announcements/' . $announcementId . '/edit', $errors, $data['old']);
        }

        if ($data['status'] === 'published') {
            $data['published_at'] = $announcement['published_at'] ?? date('Y-m-d H:i:s');
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        } else {
            $data['published_at'] = $announcement['published_at'] ?? null;
        }

        $model->updateAnnouncement($announcementId, $data);
        $this->logAction('announcements', 'update', '更新公告：' . $data['title'], 'announcement', $announcementId);
        $this->success('公告已更新。');

        return $this->redirect('/admin/announcements');
    }

    public function status(string $id): Response
    {
        $announcementId = (int) $id;
        $model = new Announcement();
        $announcement = $model->findAdmin($announcementId);
        if ($announcement === null) {
            abort(404, '公告不存在。');
        }

        $status = $this->text('status', 20);
        if (!in_array($status, self::STATUSES, true)) {
            $this->error('公告状态不合法。');
            return $this->redirect('/admin/announcements');
        }

        $model->setStatus($announcementId, $status);
        $this->logAction('announcements', 'status', '公告状态改为 ' . $status . '：' . $announcement['title'], 'announcement', $announcementId);
        $this->success('公告状态已更新。');

        return $this->redirect('/admin/announcements');
    }

    public function destroy(string $id): Response
    {
        $announcementId = (int) $id;
        $model = new Announcement();
        $announcement = $model->findAdmin($announcementId);
        if ($announcement === null) {
            abort(404, '公告不存在。');
        }

        $model->deleteAnnouncement($announcementId);
        $this->logAction('announcements', 'delete', '删除公告：' . $announcement['title'], 'announcement', $announcementId);
        $this->success('公告已删除。');

        return $this->redirect('/admin/announcements');
    }

    /**
     * @param array<string, mixed>|null $announcement
     */
    private function formPage(?array $announcement): Response
    {
        $editing = $announcement !== null;

        return $this->page('admin/announcements/form', $editing ? '编辑公告' : '新增公告', 'announcements', [
            'announcement' => $announcement,
            'editing' => $editing,
            'typeOptions' => [
                'general' => '普通公告',
                'event' => '活动通知',
                'recruitment' => '招募信息',
                'system' => '系统通知',
            ],
            'statusOptions' => [
                'draft' => '草稿',
                'published' => '发布',
                'archived' => '已下线',
            ],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '公告管理', 'url' => '/admin/announcements'],
            ['label' => $editing ? '编辑公告' : '新增公告'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $announcement
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?array $announcement): array
    {
        $title = $this->text('title', 200);
        $summary = $this->nullableText('summary', 500);
        $content = $this->text('content', 60000);
        $type = $this->text('type', 20);
        $status = $this->text('status', 20);
        $isPinned = $this->checkbox('is_pinned');
        $expiresRaw = $this->nullableText('expires_at', 10);
        $expiresAt = $this->dateValue('expires_at');
        $errors = [];

        if (mb_strlen($title) < 2) {
            $errors['title'] = '公告标题至少 2 个字符。';
        }
        if ($summary !== null && mb_strlen($summary) > 500) {
            $errors['summary'] = '公告摘要过长。';
        }
        if (mb_strlen($content) < 10) {
            $errors['content'] = '公告正文至少 10 个字符。';
        }
        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = '公告类型不合法。';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = '公告状态不合法。';
        }
        if ($expiresRaw !== null && $expiresAt === null) {
            $errors['expires_at'] = '过期日期格式应为 YYYY-MM-DD。';
        }
        if ($status === 'published' && $expiresAt !== null && $expiresAt < date('Y-m-d')) {
            $errors['expires_at'] = '发布中的公告不能设置已过去的过期日期。';
        }

        return [[
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'type' => $type,
            'status' => $status,
            'is_pinned' => $isPinned ? 1 : 0,
            'expires_at' => $expiresAt,
            'published_at' => null,
            'old' => [
                'title' => $title,
                'summary' => $summary ?? '',
                'content' => $content,
                'type' => $type,
                'status' => $status,
                'is_pinned' => $isPinned ? 1 : 0,
                'expires_at' => $expiresRaw ?? '',
            ],
        ], $errors];
    }
}
