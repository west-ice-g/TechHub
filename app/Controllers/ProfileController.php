<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/ProfileController.php
 * 模块：个人资料
 * 职责：显示当前登录账号、关联成员资料及参与项目。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Member;
use App\Models\User;
use Core\Auth;
use Core\Controller;
use Core\Response;
use Core\Session;

final class ProfileController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();
        if ($userId === null) {
            Session::flash('error', '请先登录。');

            return $this->redirect('/login');
        }

        $profile = (new User())->findFrontProfile($userId);
        if ($profile === null) {
            abort(404, '个人资料不存在。');
        }

        $projects = [];
        if (!empty($profile['membership_id'])) {
            $projects = (new Member())->projectsForMember((int) $profile['membership_id']);
        }

        return $this->view('profile/index', [
            'title' => '个人资料 · TechHub',
            'profile' => $profile,
            'projects' => $projects,
        ]);
    }
}
