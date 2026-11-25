<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/TeamController.php
 * 模块：团队信息展示
 * 职责：读取主团队资料和关联统计并渲染团队介绍页。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use App\Models\Project;
use App\Models\Team;
use Core\Controller;
use Core\Response;

final class TeamController extends Controller
{
    public function index(): Response
    {
        $teamModel = new Team();
        $team = $teamModel->primary();
        $teamId = $team === null ? 0 : (int) $team['id'];

        return $this->view('team/index', [
            'title' => '团队介绍 · TechHub',
            'team' => $team,
            'memberCount' => $teamId > 0 ? $teamModel->countMembers($teamId) : 0,
            'projectCount' => (new Project())->countPublic(),
            'articleCount' => (new Article())->countPublished(),
        ]);
    }
}
