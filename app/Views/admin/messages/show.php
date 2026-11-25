<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/messages/show.php
 * 模块：留言管理
 * 职责：显示留言详情、审核状态、回复列表和回复表单。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已拒绝'];
$statusBadges = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'];
$heading = '留言 #' . (int) $message['id'];
$description = '留言内容属于访客输入，页面输出已统一 HTML 转义。';
$kicker = 'MESSAGE DETAIL';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/messages')) . '">返回留言列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="admin-card mb-3">
            <div class="admin-card-head">
                <div>
                    <h2><?= e($message['visitor_name']) ?></h2>
                    <p><?= e(format_datetime($message['created_at'])) ?> · <?= e($message['ip_address'] ?? '—') ?></p>
                </div>
                <span class="badge <?= e($statusBadges[$message['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabels[$message['status']] ?? $message['status']) ?></span>
            </div>
            <div class="border rounded-3 bg-light p-3 mb-3" style="white-space:normal;">
                <?= nl2br(e($message['content']), false) ?>
            </div>
            <dl class="row small mb-0">
                <dt class="col-sm-3 text-secondary">邮箱</dt>
                <dd class="col-sm-9"><?= e($message['visitor_email'] ?? '—') ?></dd>
                <dt class="col-sm-3 text-secondary">审核时间</dt>
                <dd class="col-sm-9"><?= e(format_datetime($message['approved_at'] ?? null)) ?></dd>
                <dt class="col-sm-3 text-secondary">User-Agent</dt>
                <dd class="col-sm-9 text-break"><?= e(mb_substr((string) ($message['user_agent'] ?? '—'), 0, 300)) ?></dd>
            </dl>

            <div class="admin-divider"></div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($message['status'] !== 'approved'): ?>
                    <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/status')) ?>"
                          data-confirm="确定通过该留言的审核吗？通过后将出现在前台。">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="approved">
                        <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>通过审核</button>
                    </form>
                <?php endif; ?>
                <?php if ($message['status'] !== 'rejected'): ?>
                    <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/status')) ?>"
                          data-confirm="确定拒绝该留言吗？">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="rejected">
                        <button class="btn btn-warning btn-sm" type="submit">拒绝</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/delete')) ?>"
                      data-confirm="删除留言会同时删除回复，不可恢复。确定吗？">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm" type="submit">删除留言</button>
                </form>
            </div>
            <?php if ($message['status'] !== 'approved'): ?>
                <div class="admin-note mt-3">
                    当前留言未通过审核，管理员回复会保存，但在留言通过审核前不会出现在前台。
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>管理员回复</h2>
                    <p>共 <?= e((string) count($replies)) ?> 条</p>
                </div>
            </div>
            <?php if ($replies === []): ?>
                <div class="admin-empty"><i class="bi bi-reply" aria-hidden="true"></i>尚未回复。</div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="border rounded-3 p-3 mb-2">
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <strong class="small"><?= e($reply['admin_name']) ?> <span class="text-secondary">@<?= e($reply['admin_username']) ?></span></strong>
                            <span class="badge <?= $reply['status'] === 'visible' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $reply['status'] === 'visible' ? '公开' : '隐藏' ?>
                            </span>
                        </div>
                        <div class="small"><?= nl2br(e($reply['content']), false) ?></div>
                        <div class="small text-secondary mt-2"><?= e(format_datetime($reply['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="admin-form-card">
            <div class="admin-form-section">
                <h2>发布回复</h2>
                <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/reply')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="content">回复内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="7" maxlength="2000" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reply-status">可见性</label>
                        <select class="form-select" id="reply-status" name="status">
                            <option value="visible">公开显示</option>
                            <option value="hidden">仅后台可见</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1" aria-hidden="true"></i>发布回复</button>
                </form>
            </div>
        </div>

        <div class="admin-note mt-3">
            前台留言详情仅展示已通过留言的 visible 回复；访客邮箱不会在前台公开。
        </div>
    </div>
</div>
