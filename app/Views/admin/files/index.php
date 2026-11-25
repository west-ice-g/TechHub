<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/files/index.php
 * 模块：文件管理
 * 职责：显示团队/个人作业文件、公开状态、下载与操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/files/upload')) . '"><i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>上传文件</a>';
$heading = '文件管理';
$description = '团队作业保存到 data/team，个人作业保存到 data/personal；所有下载经过受控路径校验。';
$kicker = 'FILES';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $category === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('/admin/files')) ?>">
        全部（<?= e((string) array_sum($counts)) ?>）
    </a>
    <?php foreach ($categories as $key => $config): ?>
        <a class="btn btn-sm <?= $category === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"
           href="<?= e(url('/admin/files?category=' . $key)) ?>">
            <?= e($config['label']) ?>（<?= e((string) $counts[$key]) ?>）
        </a>
    <?php endforeach; ?>
</div>

<form class="admin-filter" method="get" action="<?= e(url('/admin/files')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="100"
                   value="<?= e($query) ?>" placeholder="标题、原始文件名或说明">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="category">分类</label>
            <select class="form-select form-select-sm" id="category" name="category">
                <option value="">全部分类</option>
                <?php foreach ($categories as $key => $config): ?>
                    <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($config['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="status">状态</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">全部状态</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>隐藏</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/files')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>文件</th>
                <th>分类</th>
                <th>大小</th>
                <th>关联成员</th>
                <th>公开 / 状态</th>
                <th>下载</th>
                <th>上传时间</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($files === []): ?>
                <tr><td colspan="8"><div class="admin-empty"><i class="bi bi-folder2-open" aria-hidden="true"></i>没有符合条件的文件。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td>
                        <strong><?= e($file['title']) ?></strong>
                        <div class="small text-secondary"><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i><?= e($file['original_name']) ?></div>
                    </td>
                    <td><?= e($categories[$file['category']]['label'] ?? $file['category']) ?></td>
                    <td class="text-nowrap"><?= e(format_file_size((int) $file['size_bytes'])) ?></td>
                    <td><?= e($file['member_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= (int) $file['is_public'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= (int) $file['is_public'] === 1 ? '公开' : '不公开' ?>
                        </span>
                        <span class="badge <?= $file['status'] === 'active' ? 'text-bg-primary' : 'text-bg-dark' ?>">
                            <?= $file['status'] === 'active' ? '启用' : '隐藏' ?>
                        </span>
                    </td>
                    <td><?= e((string) $file['download_count']) ?> 次</td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($file['created_at'])) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-secondary" href="<?= e(url('/admin/files/' . $file['id'] . '/download')) ?>">下载</a>
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/files/' . $file['id'] . '/edit')) ?>">编辑</a>
                            <form method="post" action="<?= e(url('/admin/files/' . $file['id'] . '/status')) ?>" class="d-inline"
                                  data-confirm="确定要<?= $file['status'] === 'active' ? '隐藏' : '启用' ?>该文件吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-<?= $file['status'] === 'active' ? 'warning' : 'success' ?>" type="submit">
                                    <?= $file['status'] === 'active' ? '隐藏' : '启用' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/files/' . $file['id'] . '/visibility')) ?>" class="d-inline"
                                  data-confirm="确定要<?= (int) $file['is_public'] === 1 ? '取消公开' : '公开' ?>该文件吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-secondary" type="submit">
                                    <?= (int) $file['is_public'] === 1 ? '取消公开' : '公开' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/files/' . $file['id'] . '/delete')) ?>" class="d-inline"
                                  data-confirm="删除会同时删除物理文件和下载记录，不可恢复。确定吗？">
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
    $paginationPath = '/admin/files';
    $paginationQuery = ['q' => $query, 'status' => $status, 'category' => $category];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
