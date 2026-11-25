<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/auth/front-login.php
 * 模块：用户登录
 * 职责：展示前台用户登录表单、隐私提示和登录入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<section class="login-section">
    <div class="container">
        <div class="row justify-content-center align-items-stretch g-0 login-shell">
            <div class="col-lg-6 login-intro">
                <div class="eyebrow text-info">MEMBER ACCESS</div>
                <h1>登录 TechHub</h1>
                <p class="login-lead">
                    登录后可查看个人资料、进入管理后台，并继续参与团队内容维护。
                </p>
                <div class="login-feature-list">
                    <div class="login-feature">
                        <span class="icon-tile"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                        <div>
                            <strong>个人资料</strong>
                            <span>查看账号与公开成员档案</span>
                        </div>
                    </div>
                    <div class="login-feature">
                        <span class="icon-tile"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                        <div>
                            <strong>服务端权限校验</strong>
                            <span>后台功能按账号角色开放</span>
                        </div>
                    </div>
                    <div class="login-feature">
                        <span class="icon-tile"><i class="bi bi-lock" aria-hidden="true"></i></span>
                        <div>
                            <strong>安全会话</strong>
                            <span>HttpOnly Cookie 与 CSRF 防护</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="login-form-wrap">
                    <div class="mb-4">
                        <div class="eyebrow">SIGN IN</div>
                        <h2 class="h3 mt-2 mb-2">成员登录</h2>
                        <p class="text-secondary mb-0">使用超级管理员创建的账号登录。</p>
                    </div>

                    <form method="post" action="<?= e(url('/login')) ?>" autocomplete="on">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                                <input class="form-control" id="username" name="username" type="text"
                                       autocomplete="username" maxlength="50" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">密码</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-key" aria-hidden="true"></i></span>
                                <input class="form-control" id="password" name="password" type="password"
                                       autocomplete="current-password" maxlength="4096" required>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg w-100" type="submit">
                            登录
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <div class="login-footnote">
                        <span><i class="bi bi-info-circle me-1" aria-hidden="true"></i>暂不开放公开注册</span>
                        <a href="<?= e(url('/')) ?>">返回首页</a>
                    </div>
                    <div class="login-admin-link">
                        管理员如需进入后台，可使用同一账号登录后从右上角菜单进入。
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
