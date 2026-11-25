<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/AnnouncementController.php
 * 模块：团队公告展示
 * 职责：分页展示有效公告并加载公告详情。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Announcement;
use Core\Controller;
use Core\Paginator;
use Core\Response;

final class AnnouncementController extends Controller
{
    private const PER_PAGE = 6;

    public function index(): Response
    {
        $model = new Announcement();
        $total = $model->countActive();
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->view('announcement/index', [
            'title' => '团队公告 · TechHub',
            'announcements' => $model->activePage(self::PER_PAGE, $pagination['offset']),
            'pagination' => $pagination,
            'paginationPath' => '/announcements',
            'paginationQuery' => [],
        ]);
    }

    public function show(string $id): Response
    {
        $announcementId = (int) $id;
        if ($announcementId <= 0) {
            abort(404, '公告不存在。');
        }

        $announcement = (new Announcement())->findActiveById($announcementId);
        if ($announcement === null) {
            abort(404, '公告不存在或已过期。');
        }

        return $this->view('announcement/show', [
            'title' => $announcement['title'] . ' · 团队公告',
            'announcement' => $announcement,
        ]);
    }
}
