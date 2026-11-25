<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/auth/login.php
 * 模块：身份认证
 * 职责：显示后台登录表单并提交 CSRF Token。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="auth-card">
                    <div class="eyebrow">SECURE ACCESS</div>
                    <h1 class="h3 mt-2 mb-2">后台登录</h1>
                    <p class="text-secondary mb-4">仅限已分配后台角色的团队成员使用。</p>

                    <form method="post" action="<?= e(url('/admin/login')) ?>" autocomplete="on">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input class="form-control form-control-lg" id="username" name="username"
                                   type="text" autocomplete="username" maxlength="50" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">密码</label>
                            <input class="form-control form-control-lg" id="password" name="password"
                                   type="password" autocomplete="current-password" maxlength="4096" required>
                        </div>
                        <button class="btn btn-primary btn-lg w-100" type="submit">登录后台</button>
                    </form>

                    <div class="auth-footnote">
                        <a href="<?= e(url('/')) ?>">← 返回首页</a>
                        <span>账号由超级管理员创建</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
