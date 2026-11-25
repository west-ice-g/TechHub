<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/AuthController.php
 * 模块：用户与身份认证
 * 职责：提供前台/后台登录、退出和认证失败处理；不开放公开注册。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Response;
use Core\Session;

final class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->view('auth/login', [
            'title' => '后台登录',
        ], 'layouts/app');
    }

    public function showFrontLogin(): Response
    {
        return $this->view('auth/front-login', [
            'title' => '用户登录',
        ], 'layouts/app');
    }

    public function login(): Response
    {
        return $this->processLogin('/admin/login', '/admin', true);
    }

    public function frontLogin(): Response
    {
        return $this->processLogin('/login', '/profile', false);
    }

    private function processLogin(string $loginPath, string $fallbackPath, bool $adminContext): Response
    {
        if (Auth::isLockedOut()) {
            $seconds = max(1, Auth::remainingLockoutSeconds());
            Session::flash('error', '登录尝试次数过多，请在约 ' . (int) ceil($seconds / 60) . ' 分钟后重试。');

            return $this->redirect($loginPath);
        }

        $rawUsername = $this->request->post('username', '');
        $rawPassword = $this->request->post('password', '');

        if (!is_string($rawUsername) || !is_string($rawPassword)) {
            Session::flash('error', '用户名或密码格式不正确。');

            return $this->redirect($loginPath);
        }

        $username = trim($rawUsername);
        $password = $rawPassword;

        if ($username === '' || $password === '') {
            Session::flash('error', '请输入用户名和密码。');

            return $this->redirect($loginPath);
        }

        if (
            !mb_check_encoding($username, 'UTF-8')
            || mb_strlen($username) > 50
            || mb_strlen($password) > 4096
        ) {
            Session::flash('error', '用户名或密码格式不正确。');

            return $this->redirect($loginPath);
        }

        if (!Auth::attempt($username, $password, $this->request)) {
            Session::flash('error', '用户名或密码错误，或账号当前不可用。');

            return $this->redirect($loginPath);
        }

        $intended = Session::pull('intended_url', $fallbackPath);
        $isInternal = is_string($intended)
            && str_starts_with($intended, '/')
            && !str_starts_with($intended, '//')
            && !preg_match('/[\r\n]/', $intended);

        if (!$isInternal) {
            $intended = $fallbackPath;
        }

        if ($adminContext && !preg_match('#^/admin(?:/|$)#', $intended)) {
            $intended = $fallbackPath;
        }

        if (!$adminContext && str_starts_with($intended, '/admin')) {
            $intended = $fallbackPath;
        }

        Session::flash('success', '登录成功，欢迎回来。');

        return $this->redirect($intended);
    }

    public function logout(): Response
    {
        Auth::logout($this->request);
        Session::flash('success', '已安全退出登录。');

        return $this->redirect('/');
    }
}
