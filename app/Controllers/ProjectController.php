<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/ProjectController.php
 * 模块：项目成果展示
 * 职责：分页展示公开项目，加载项目详情、项目成员和项目成果。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use Core\Controller;
use Core\Paginator;
use Core\Response;

final class ProjectController extends Controller
{
    private const PER_PAGE = 6;

    public function index(): Response
    {
        $status = $this->request->query('status', '');
        $status = is_string($status) ? trim($status) : '';
        if (!in_array($status, ['planning', 'ongoing', 'completed', 'archived'], true)) {
            $status = '';
        }

        $q = $this->request->query('q', '');
        $q = is_string($q) && mb_check_encoding($q, 'UTF-8') ? mb_substr(trim($q), 0, 80) : '';

        $model = new Project();
        $total = $model->countPublic($status, $q);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);
        $projects = $model->publicPage(self::PER_PAGE, $pagination['offset'], $status, $q);

        return $this->view('project/index', [
            'title' => '项目成果 · TechHub',
            'projects' => $projects,
            'status' => $status,
            'q' => $q,
            'pagination' => $pagination,
            'paginationPath' => '/projects',
            'paginationQuery' => ['status' => $status, 'q' => $q],
        ]);
    }

    public function show(string $slug): Response
    {
        $model = new Project();
        $project = $model->findPublicBySlug($slug);
        if ($project === null) {
            abort(404, '项目不存在或未公开。');
        }

        return $this->view('project/show', [
            'title' => $project['title'] . ' · 项目',
            'project' => $project,
            'members' => $model->membersForProject((int) $project['id']),
        ]);
    }
}
