<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/FileController.php
 * 模块：后台文件管理
 * 职责：上传、编辑、公开/隐藏、安全删除和受控下载团队/个人作业文件。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\FileResource;
use App\Models\Member;
use App\Models\Team;
use App\Support\DownloadPath;
use Core\Auth;
use Core\Paginator;
use Core\Response;
use Core\Upload;

final class FileController extends AdminController
{
    private const PER_PAGE = 15;

    /** @var array<string, array{label: string, directory: string}> */
    private const CATEGORIES = [
        'team_assignment' => ['label' => '团队作业', 'directory' => 'data/team'],
        'personal_assignment' => ['label' => '个人作业', 'directory' => 'data/personal'],
        'resource' => ['label' => '通用资源', 'directory' => 'data/resources'],
    ];

    public function index(): Response
    {
        $category = (string) $this->request->query('category', '');
        $category = isset(self::CATEGORIES[$category]) ? $category : '';
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, ['active', 'hidden'], true) ? $status : '';
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';
        $model = new FileResource();
        $total = $model->countAdmin($category, $status, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/files/index', '文件管理', 'files', [
            'files' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $category, $status, $query),
            'counts' => $model->categoryCounts(),
            'categories' => self::CATEGORIES,
            'category' => $category,
            'status' => $status,
            'query' => $query,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '互动与资源'],
            ['label' => '文件管理'],
        ]);
    }

    public function upload(): Response
    {
        return $this->formPage(null);
    }

    public function store(): Response
    {
        $title = $this->text('title', 200);
        $category = $this->text('category', 30);
        $description = $this->nullableText('description', 500);
        $relatedMemberId = $this->integer('related_member_id', 0);
        $relatedMemberId = $relatedMemberId > 0 ? $relatedMemberId : null;
        $isPublic = $this->checkbox('is_public');
        $status = $this->text('status', 20);
        $errors = [];

        if (mb_strlen($title) < 2) {
            $errors['title'] = '文件标题至少 2 个字符。';
        }
        if (!isset(self::CATEGORIES[$category])) {
            $errors['category'] = '文件分类不合法。';
        }
        if (!in_array($status, ['active', 'hidden'], true)) {
            $errors['status'] = '文件状态不合法。';
        }
        if ($relatedMemberId !== null && (new Member())->findAdmin($relatedMemberId) === null) {
            $errors['related_member_id'] = '关联成员不存在。';
        }

        $stored = null;
        $uploadedAbsolute = null;
        if ($errors === []) {
            $file = $this->request->file('file');
            try {
                $extensions = (array) config('app.uploads.document_extensions', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt']);
                $directory = self::CATEGORIES[$category]['directory'];
                $stored = Upload::store(
                    $file ?? [],
                    BASE_PATH . '/' . $directory,
                    array_values(array_map('strval', $extensions)),
                    (int) config('app.uploads.max_size', 20971520)
                );
                $uploadedAbsolute = $stored['file_path'];
            } catch (\Throwable $e) {
                $errors['file'] = '文件上传失败：' . $e->getMessage();
            }
        }

        if ($errors !== []) {
            if ($uploadedAbsolute !== null && is_file($uploadedAbsolute)) {
                @unlink($uploadedAbsolute);
            }
            return $this->backWithErrors('/admin/files/upload', $errors, [
                'title' => $title,
                'category' => $category,
                'description' => $description ?? '',
                'related_member_id' => $relatedMemberId ?? 0,
                'is_public' => $isPublic ? 1 : 0,
                'status' => $status,
            ]);
        }

        $storedName = $stored['stored_name'];
        $relativePath = self::CATEGORIES[$category]['directory'] . '/' . $storedName;
        $model = new FileResource();
        try {
            $id = $model->createFile([
                'uploader_id' => (int) Auth::id(),
                'related_member_id' => $relatedMemberId,
                'title' => $title,
                'original_name' => $stored['original_name'],
                'stored_name' => $storedName,
                'file_path' => $relativePath,
                'extension' => $stored['extension'],
                'mime_type' => $stored['mime_type'],
                'size_bytes' => $stored['size'],
                'category' => $category,
                'description' => $description,
                'is_public' => $isPublic ? 1 : 0,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            if ($uploadedAbsolute !== null && is_file($uploadedAbsolute)) {
                @unlink($uploadedAbsolute);
            }
            $this->logAction('files', 'upload', '文件入库失败：' . $title, 'file', null, 'failure');
            $this->error('文件记录保存失败，请重试。');
            return $this->redirect('/admin/files');
        }

        $this->logAction('files', 'upload', '上传文件：' . $title, 'file', $id);
        $this->success('文件已上传。');

        return $this->redirect('/admin/files');
    }

    public function edit(string $id): Response
    {
        $file = (new FileResource())->findAdmin((int) $id);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        return $this->formPage($file);
    }

    public function update(string $id): Response
    {
        $fileId = (int) $id;
        $model = new FileResource();
        $file = $model->findAdmin($fileId);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        $title = $this->text('title', 200);
        $description = $this->nullableText('description', 500);
        $relatedMemberId = $this->integer('related_member_id', 0);
        $relatedMemberId = $relatedMemberId > 0 ? $relatedMemberId : null;
        $isPublic = $this->checkbox('is_public');
        $status = $this->text('status', 20);
        $errors = [];

        if (mb_strlen($title) < 2) {
            $errors['title'] = '文件标题至少 2 个字符。';
        }
        if (!in_array($status, ['active', 'hidden'], true)) {
            $errors['status'] = '文件状态不合法。';
        }
        if ($relatedMemberId !== null && (new Member())->findAdmin($relatedMemberId) === null) {
            $errors['related_member_id'] = '关联成员不存在。';
        }

        if ($errors !== []) {
            return $this->backWithErrors('/admin/files/' . $fileId . '/edit', $errors, [
                'title' => $title,
                'description' => $description ?? '',
                'related_member_id' => $relatedMemberId ?? 0,
                'is_public' => $isPublic ? 1 : 0,
                'status' => $status,
            ]);
        }

        $model->updateFile($fileId, [
            'title' => $title,
            'description' => $description,
            'related_member_id' => $relatedMemberId,
            'is_public' => $isPublic ? 1 : 0,
            'status' => $status,
        ]);
        $this->logAction('files', 'update', '更新文件信息：' . $title, 'file', $fileId);
        $this->success('文件信息已更新。');

        return $this->redirect('/admin/files');
    }

    public function status(string $id): Response
    {
        $fileId = (int) $id;
        $model = new FileResource();
        $file = $model->findAdmin($fileId);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        $target = $file['status'] === 'active' ? 'hidden' : 'active';
        $model->setStatus($fileId, $target);
        $this->logAction('files', 'status', '文件状态改为 ' . $target . '：' . $file['title'], 'file', $fileId);
        $this->success($target === 'active' ? '文件已启用。' : '文件已隐藏。');

        return $this->redirect('/admin/files');
    }

    public function visibility(string $id): Response
    {
        $fileId = (int) $id;
        $model = new FileResource();
        $file = $model->findAdmin($fileId);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        $target = (int) $file['is_public'] !== 1;
        $model->setPublic($fileId, $target);
        $this->logAction('files', 'visibility', ($target ? '公开' : '取消公开') . '文件：' . $file['title'], 'file', $fileId);
        $this->success($target ? '文件已公开。' : '文件已取消公开。');

        return $this->redirect('/admin/files');
    }

    public function download(string $id): Response
    {
        $fileId = (int) $id;
        $file = (new FileResource())->findAdmin($fileId);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        $category = (string) $file['category'];
        if (!isset(self::CATEGORIES[$category])) {
            abort(404, '文件分类不合法。');
        }

        $resolved = DownloadPath::resolve(
            BASE_PATH,
            (string) $file['file_path'],
            self::CATEGORIES[$category]['directory']
        );
        if ($resolved === null) {
            abort(404, '文件不存在或路径不合法。');
        }

        return Response::download($resolved, (string) $file['original_name'], (string) $file['mime_type']);
    }

    public function destroy(string $id): Response
    {
        $fileId = (int) $id;
        $model = new FileResource();
        $file = $model->findAdmin($fileId);
        if ($file === null) {
            abort(404, '文件不存在。');
        }

        $category = (string) $file['category'];
        if (!isset(self::CATEGORIES[$category])) {
            $this->error('文件分类不合法，拒绝删除。');
            return $this->redirect('/admin/files');
        }

        $resolved = DownloadPath::resolve(
            BASE_PATH,
            (string) $file['file_path'],
            self::CATEGORIES[$category]['directory']
        );
        if ($resolved === null) {
            $this->error('物理文件不存在或不在受控目录，拒绝删除记录。');
            return $this->redirect('/admin/files');
        }

        if (!@unlink($resolved)) {
            $this->error('物理文件删除失败，请检查文件权限。');
            return $this->redirect('/admin/files');
        }

        $model->deleteFile($fileId);
        $this->logAction('files', 'delete', '删除文件：' . $file['title'], 'file', $fileId);
        $this->success('文件及其记录已删除。');

        return $this->redirect('/admin/files');
    }

    /**
     * @param array<string, mixed>|null $file
     */
    private function formPage(?array $file): Response
    {
        $team = (new Team())->firstAny();
        $memberOptions = $team === null ? [] : (new Member())->memberOptions((int) $team['id']);

        return $this->page('admin/files/form', $file === null ? '上传文件' : '编辑文件', 'files', [
            'file' => $file,
            'editing' => $file !== null,
            'categories' => self::CATEGORIES,
            'memberOptions' => $memberOptions,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '文件管理', 'url' => '/admin/files'],
            ['label' => $file === null ? '上传文件' : '编辑文件'],
        ]);
    }
}
