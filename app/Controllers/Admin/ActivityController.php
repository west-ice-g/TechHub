<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/ActivityController.php
 * 模块：后台操作日志
 * 职责：分页查看操作人、动作、模块、结果、IP 和时间。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use Core\Paginator;
use Core\Response;

final class ActivityController extends AdminController
{
    private const PER_PAGE = 20;

    public function index(): Response
    {
        $module = (string) $this->request->query('module', '');
        $module = preg_match('/^[a-z0-9_.-]{1,50}$/i', $module) === 1 ? $module : '';
        $result = (string) $this->request->query('result', '');
        $result = in_array($result, ['success', 'failure'], true) ? $result : '';
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';

        $model = new ActivityLog();
        $total = $model->countAdmin($module, $result, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/activity/index', '操作日志', 'activity', [
            'logs' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $module, $result, $query),
            'modules' => $model->modules(),
            'module' => $module,
            'result' => $result,
            'query' => $query,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '系统管理'],
            ['label' => '操作日志'],
        ]);
    }
}
