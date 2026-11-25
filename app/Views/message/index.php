<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/message/index.php
 * 模块：留言板
 * 职责：显示留言提交表单和已审核留言列表，支持分页与字段级错误提示。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$oldName = (string) ($old['visitor_name'] ?? '');
$oldEmail = (string) ($old['visitor_email'] ?? '');
$oldContent = (string) ($old['content'] ?? '');
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>留言板</span>
                </nav>
                <div class="eyebrow mt-4">GUESTBOOK</div>
                <h1>留言板</h1>
                <p class="page-hero-lead">
                    对团队、项目或文章有想说的？留下一条留言，审核通过后会公开展示。
                </p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) $pagination['total']) ?></span>
                <span class="page-hero-label">条已通过留言</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5" id="message-form">
                <div class="content-card form-card sticky-lg-top">
                    <div class="eyebrow">LEAVE A MESSAGE</div>
                    <h2 class="h4 mt-2 mb-2">写留言</h2>
                    <p class="text-secondary small mb-4">
                        昵称和内容为必填项；邮箱选填，仅管理员可见，不会公开展示。
                    </p>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger" role="alert">
                            <div class="fw-semibold mb-1">请检查以下内容：</div>
                            <ul class="mb-0 small">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('/messages')) ?>">
                        <?= csrf_field() ?>
                        <div class="hp-field" aria-hidden="true">
                            <label for="website">网站</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="visitor_name" class="form-label">昵称 <span class="text-danger">*</span></label>
                            <input class="form-control <?= isset($errors['visitor_name']) ? 'is-invalid' : '' ?>"
                                   id="visitor_name" name="visitor_name" type="text" maxlength="50"
                                   value="<?= e($oldName) ?>" required>
                            <?php if (isset($errors['visitor_name'])): ?>
                                <div class="invalid-feedback"><?= e($errors['visitor_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="visitor_email" class="form-label">邮箱 <span class="text-secondary small">（选填，不公开）</span></label>
                            <input class="form-control <?= isset($errors['visitor_email']) ? 'is-invalid' : '' ?>"
                                   id="visitor_email" name="visitor_email" type="email" maxlength="120"
                                   value="<?= e($oldEmail) ?>">
                            <?php if (isset($errors['visitor_email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['visitor_email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">留言内容 <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>"
                                      id="content" name="content" rows="6" minlength="5" maxlength="2000"
                                      required placeholder="写下你的问题、建议或想对团队说的话…"><?= e($oldContent) ?></textarea>
                            <?php if (isset($errors['content'])): ?>
                                <div class="invalid-feedback"><?= e($errors['content']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">5～2000 个字符；内容提交后进入待审核状态。</div>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-send me-1" aria-hidden="true"></i>提交留言
                        </button>
                    </form>

                    <div class="form-security-note">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        表单使用 CSRF 校验，留言内容会进行 HTML 转义并经过审核。
                    </div>
                </div>
            </div>

            <div class="col-lg-7" id="message-list">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <div class="eyebrow">COMMUNITY</div>
                        <h2 class="h4 mb-0">最新留言</h2>
                    </div>
                    <span class="small text-secondary">仅展示审核通过内容</span>
                </div>

                <?php if ($messages === []): ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-square display-5 d-block mb-3" aria-hidden="true"></i>
                        暂无已通过留言，欢迎成为第一个留言的人。
                    </div>
                <?php else: ?>
                    <div class="message-list">
                        <?php foreach ($messages as $message): ?>
                            <article class="message-card">
                                <div class="message-card-head">
                                    <span class="message-avatar" aria-hidden="true">
                                        <?= e(mb_substr((string) $message['visitor_name'], 0, 1)) ?>
                                    </span>
                                    <div class="flex-grow-1">
                                        <strong><?= e($message['visitor_name']) ?></strong>
                                        <div class="small text-secondary">
                                            <?= e(format_datetime($message['approved_at'] ?? $message['created_at'], 'Y-m-d H:i')) ?>
                                        </div>
                                    </div>
                                    <span class="badge text-bg-light">
                                        <i class="bi bi-chat-dots me-1" aria-hidden="true"></i><?= e((string) $message['reply_count']) ?>
                                    </span>
                                </div>
                                <p class="message-excerpt"><?= e(excerpt_text($message['content'], 180)) ?></p>
                                <a class="text-link small" href="<?= e(url('/messages/' . $message['id'])) ?>">
                                    查看留言与回复 <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
