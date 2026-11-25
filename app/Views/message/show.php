<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/message/show.php
 * 模块：留言详情
 * 职责：展示单条已审核留言及管理员公开回复。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<section class="section-block section-top-space">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <a class="back-link" href="<?= e(url('/messages')) ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> 返回留言板
                </a>

                <article class="message-detail-card mt-4">
                    <div class="message-card-head">
                        <span class="message-avatar message-avatar-lg" aria-hidden="true">
                            <?= e(mb_substr((string) $message['visitor_name'], 0, 1)) ?>
                        </span>
                        <div class="flex-grow-1">
                            <div class="eyebrow">MESSAGE</div>
                            <h1 class="h3 mb-1"><?= e($message['visitor_name']) ?></h1>
                            <div class="small text-secondary">
                                发布于 <?= e(format_datetime($message['approved_at'] ?? $message['created_at'], 'Y-m-d H:i')) ?>
                            </div>
                        </div>
                    </div>
                    <div class="message-full-content">
                        <?= nl2br(e($message['content'])) ?>
                    </div>
                </article>

                <div class="section-heading mt-5">
                    <div>
                        <div class="eyebrow">REPLIES</div>
                        <h2>管理员回复</h2>
                    </div>
                    <span class="section-count"><?= e((string) count($replies)) ?> 条回复</span>
                </div>

                <?php if ($replies === []): ?>
                    <div class="empty-state empty-state-sm">
                        <i class="bi bi-hourglass-split me-2" aria-hidden="true"></i>管理员尚未回复这条留言。
                    </div>
                <?php else: ?>
                    <div class="reply-list">
                        <?php foreach ($replies as $reply): ?>
                            <article class="reply-card">
                                <div class="reply-head">
                                    <span class="badge text-bg-primary">
                                        <i class="bi bi-patch-check me-1" aria-hidden="true"></i>管理员
                                    </span>
                                    <strong><?= e($reply['admin_name']) ?></strong>
                                    <span class="small text-secondary ms-auto">
                                        <?= e(format_datetime($reply['created_at'], 'Y-m-d H:i')) ?>
                                    </span>
                                </div>
                                <div class="reply-content"><?= nl2br(e($reply['content'])) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="article-footer-actions mt-4">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/messages')) ?>">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>返回留言板
                    </a>
                    <a class="btn btn-primary" href="<?= e(url('/messages#message-form')) ?>">
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>我要留言
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
