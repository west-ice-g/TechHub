<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/DownloadController.php
 * 模块：作业下载
 * 职责：分页展示团队/个人作业，校验文件路径并流式返回受控下载。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\FileDownload;
use App\Models\FileResource;
use App\Support\DownloadPath;
use Core\Auth;
use Core\Controller;
use Core\Logger;
use Core\Paginator;
use Core\Response;

final class DownloadController extends Controller
{
    private const PER_PAGE = 8;

    /** @var array<string, array{category: string, label: string, description: string, directory: string}> */
    private const TYPES = [
        'team' => [
            'category' => 'team_assignment',
            'label' => '团队作业',
            'description' => '课程团队共同提交的作业资料',
            'directory' => 'data/team',
        ],
        'personal' => [
            'category' => 'personal_assignment',
            'label' => '个人作业',
            'description' => '组员个人提交的作业资料',
            'directory' => 'data/personal',
        ],
    ];

    public function index(): Response
    {
        $type = $this->request->query('type', 'team');
        $type = is_string($type) && isset(self::TYPES[$type]) ? $type : 'team';

        $model = new FileResource();
        $counts = [
            'team' => $model->countPublicByCategory(self::TYPES['team']['category']),
            'personal' => $model->countPublicByCategory(self::TYPES['personal']['category']),
        ];

        $total = $counts[$type];
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);
        $files = $model->publicPageByCategory(
            self::TYPES[$type]['category'],
            self::PER_PAGE,
            $pagination['offset']
        );

        return $this->view('download/index', [
            'title' => '资料下载 · TechHub',
            'type' => $type,
            'types' => self::TYPES,
            'counts' => $counts,
            'files' => $files,
            'pagination' => $pagination,
            'paginationPath' => '/downloads',
            'paginationQuery' => ['type' => $type],
        ]);
    }

    public function download(string $id): Response
    {
        $fileId = (int) $id;
        if ($fileId <= 0) {
            abort(404, '文件不存在。');
        }

        $file = (new FileResource())->findPublicById($fileId);
        if ($file === null) {
            abort(404, '文件不存在或未公开。');
        }

        $config = null;
        foreach (self::TYPES as $candidate) {
            if ($candidate['category'] === (string) $file['category']) {
                $config = $candidate;
                break;
            }
        }

        if ($config === null) {
            abort(404, '该文件不开放下载。');
        }

        $resolvedPath = DownloadPath::resolve(
            BASE_PATH,
            (string) $file['file_path'],
            $config['directory']
        );

        if ($resolvedPath === null) {
            Logger::warning('下载文件路径校验失败', ['file_id' => $fileId]);
            abort(404, '文件不存在或已被移除。');
        }

        $fileModel = new FileResource();
        $downloadModel = new FileDownload();
        $userId = Auth::id();
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        $fileModel->transaction(function () use ($fileModel, $downloadModel, $fileId, $userId, $ip, $userAgent): void {
            $fileModel->incrementDownload($fileId);
            $downloadModel->record($fileId, $userId, $ip, $userAgent);
        });

        return Response::download(
            $resolvedPath,
            (string) $file['original_name'],
            (string) $file['mime_type']
        );
    }
}
