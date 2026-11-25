<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/MemberController.php
 * 模块：团队成员展示
 * 职责：分页展示公开成员，并加载成员详情及其参与项目。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Member;
use App\Models\Team;
use Core\Controller;
use Core\Paginator;
use Core\Response;

final class MemberController extends Controller
{
    private const PER_PAGE = 6;

    public function index(): Response
    {
        $q = $this->request->query('q', '');
        $q = is_string($q) && mb_check_encoding($q, 'UTF-8') ? mb_substr(trim($q), 0, 80) : '';

        $team = (new Team())->primary();
        $teamId = $team === null ? 0 : (int) $team['id'];
        $model = new Member();
        $total = $teamId > 0 ? $model->countPublic($teamId, $q) : 0;
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);
        $members = $teamId > 0
            ? $model->publicPage($teamId, self::PER_PAGE, $pagination['offset'], $q)
            : [];

        return $this->view('member/index', [
            'title' => '团队成员 · TechHub',
            'team' => $team,
            'members' => $members,
            'q' => $q,
            'pagination' => $pagination,
            'paginationPath' => '/members',
            'paginationQuery' => ['q' => $q],
        ]);
    }

    public function show(string $id): Response
    {
        $membershipId = (int) $id;
        if ($membershipId <= 0) {
            abort(404, '成员不存在。');
        }

        $model = new Member();
        $member = $model->findPublic($membershipId);
        if ($member === null) {
            abort(404, '成员不存在或未公开。');
        }

        return $this->view('member/show', [
            'title' => $member['display_name'] . ' · 团队成员',
            'member' => $member,
            'projects' => $model->projectsForMember($membershipId),
        ]);
    }
}
