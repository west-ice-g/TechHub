<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/activity/index.php
 * 模块：操作日志
 * 职责：显示操作审计记录并支持模块、结果和关键词筛选。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$heading = '操作日志';
$description = '记录后台操作人、模块、动作、目标、结果、IP 和时间；日志由服务端写入。';
$kicker = 'ACTIVITY LOG';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/activity')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="100"
                   value="<?= e($query) ?>" placeholder="操作人、动作、说明或 IP">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1" for="module">模块</label>
            <select class="form-select form-select-sm" id="module" name="module">
                <option value="">全部模块</option>
                <?php foreach ($modules as $item): ?>
                    <option value="<?= e($item) ?>" <?= $module === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="result">结果</label>
            <select class="form-select form-select-sm" id="result" name="result">
                <option value="">全部</option>
                <option value="success" <?= $result === 'success' ? 'selected' : '' ?>>成功</option>
                <option value="failure" <?= $result === 'failure' ? 'selected' : '' ?>>失败</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/activity')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>时间</th>
                <th>操作人</th>
                <th>模块 / 动作</th>
                <th>目标</th>
                <th>说明</th>
                <th>结果</th>
                <th>IP</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-clock-history" aria-hidden="true"></i>没有符合条件的日志。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($log['created_at'])) ?></td>
                    <td>
                        <strong><?= e($log['actor_username'] ?? '—') ?></strong>
                        <div class="small text-secondary">ID: <?= e((string) ($log['user_id'] ?? '—')) ?></div>
                    </td>
                    <td>
                        <code class="admin-code"><?= e($log['module']) ?></code>
                        <div class="small text-secondary"><?= e($log['action']) ?></div>
                    </td>
                    <td class="small text-secondary">
                        <?= e($log['target_type'] ?? '—') ?><?= $log['target_id'] !== null ? ' #' . e((string) $log['target_id']) : '' ?>
                    </td>
                    <td class="small" style="max-width:320px;"><?= e($log['description'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= ($log['result'] ?? 'success') === 'success' ? 'text-bg-success' : 'text-bg-danger' ?>">
                            <?= ($log['result'] ?? 'success') === 'success' ? '成功' : '失败' ?>
                        </span>
                    </td>
                    <td class="small text-secondary text-nowrap"><?= e($log['ip_address'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $paginationPath = '/admin/activity';
    $paginationQuery = ['q' => $query, 'module' => $module, 'result' => $result];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
