<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/messages/index.php
 * 模块：留言管理
 * 职责：显示留言列表、审核状态、回复数与快捷审核操作。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已拒绝'];
$statusBadges = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'];
$heading = '留言管理';
$description = '审核访客留言并发布管理员回复；待审核留言不会出现在前台。';
$kicker = 'MESSAGES';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('/admin/messages')) ?>">
        全部（<?= e((string) array_sum($counts)) ?>）
    </a>
    <?php foreach ($statusLabels as $key => $label): ?>
        <a class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"
           href="<?= e(url('/admin/messages?status=' . $key)) ?>">
            <?= e($label) ?>（<?= e((string) $counts[$key]) ?>）
        </a>
    <?php endforeach; ?>
</div>

<form class="admin-filter" method="get" action="<?= e(url('/admin/messages')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="100"
                   value="<?= e($query) ?>" placeholder="昵称、邮箱、内容或 IP">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1" for="status">状态</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">全部状态</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/messages')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>访客</th>
                <th>内容</th>
                <th>状态</th>
                <th>回复</th>
                <th>提交时间</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($messages === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-chat-square-text" aria-hidden="true"></i>没有符合条件的留言。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td class="text-secondary">#<?= e((string) $message['id']) ?></td>
                    <td>
                        <strong><?= e($message['visitor_name']) ?></strong>
                        <div class="small text-secondary"><?= e($message['visitor_email'] ?? '未留邮箱') ?></div>
                        <div class="small text-secondary"><?= e($message['ip_address'] ?? '—') ?></div>
                    </td>
                    <td style="max-width:360px;">
                        <div class="small text-truncate-2"><?= e(excerpt_text((string) $message['content'], 100)) ?></div>
                    </td>
                    <td><span class="badge <?= e($statusBadges[$message['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabels[$message['status']] ?? $message['status']) ?></span></td>
                    <td>
                        <?= e((string) $message['visible_reply_count']) ?> 公开
                        <div class="small text-secondary">共 <?= e((string) $message['reply_count']) ?></div>
                    </td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($message['created_at'])) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/messages/' . $message['id'])) ?>">查看/回复</a>
                            <?php if ($message['status'] !== 'approved'): ?>
                                <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定通过该留言的审核吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-outline-success" type="submit">通过</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($message['status'] !== 'rejected'): ?>
                                <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定拒绝该留言吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="rejected">
                                    <button class="btn btn-outline-warning" type="submit">拒绝</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/delete')) ?>" class="d-inline"
                                  data-confirm="删除留言会同时删除回复，不可恢复。确定吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger" type="submit">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $paginationPath = '/admin/messages';
    $paginationQuery = ['q' => $query, 'status' => $status];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
