<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/DashboardController.php
 * 模块：后台仪表盘
 * 职责：读取真实数据库统计、组装图表接口并渲染后台首页。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\AdminStats;
use App\Models\Team;
use Core\Auth;
use Core\Response;

final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $stats = new AdminStats();

        return $this->page('admin/dashboard/index', '数据统计首页', 'dashboard', [
            'team' => (new Team())->primary(),
            'summary' => $stats->summary(),
            'latestLogs' => (new ActivityLog())->latest(6),
            'currentUser' => Auth::user(),
            'permissions' => Auth::permissions(),
        ], [
            ['label' => '后台首页'],
        ]);
    }

    /**
     * 后台图表读取的 JSON 接口；数据全部来自 MySQL。
     */
    public function stats(): Response
    {
        $stats = new AdminStats();

        return $this->json([
            'success' => true,
            'generated_at' => date('c'),
            'summary' => $stats->summary(),
            'project_status' => $stats->projectStatusDistribution(),
            'article_trend' => $stats->articleTrend(6),
            'project_trend' => $stats->projectTrend(6),
            'member_participation' => $stats->memberParticipation(8),
            'message_status' => $stats->messageStatusDistribution(),
            'project_status_labels' => AdminStats::PROJECT_STATUS_LABELS,
        ]);
    }
}
