<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/HomeController.php
 * 模块：首页
 * 职责：聚合数据库中的团队、成员、项目、文章、公告和资源统计并渲染首页。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\FileResource;
use App\Models\Project;
use App\Models\Team;
use Core\Controller;
use Core\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        $teamModel = new Team();
        $team = $teamModel->primary();
        $teamId = $team === null ? 0 : (int) $team['id'];

        $members = $teamId > 0 ? $teamModel->members($teamId, 4) : [];
        $projects = (new Project())->featured(3);
        $articles = (new Article())->latestPublished(3);
        $announcements = (new Announcement())->latestActive(3);
        $fileModel = new FileResource();

        $stats = [
            'members' => $teamId > 0 ? $teamModel->countMembers($teamId) : 0,
            'projects' => (new Project())->countPublic(),
            'articles' => (new Article())->countPublished(),
            'files' => $fileModel->countPublic(),
            'team_files' => $fileModel->countPublicByCategory('team_assignment'),
            'personal_files' => $fileModel->countPublicByCategory('personal_assignment'),
        ];

        return $this->view('home/index', [
            'title' => 'TechHub · 高校技术团队展示与管理平台',
            'team' => $team,
            'members' => $members,
            'projects' => $projects,
            'articles' => $articles,
            'announcements' => $announcements,
            'stats' => $stats,
        ]);
    }
}
