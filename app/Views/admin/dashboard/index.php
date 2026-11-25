<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/dashboard/index.php
 * 模块：后台数据统计首页
 * 职责：展示 MySQL 统计卡片、ECharts 动态图表和最近操作日志。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$canStats = in_array('stats.view', $permissions, true);
$canActivity = in_array('activity.view', $permissions, true);

$cards = [
    ['label' => '团队成员', 'value' => $summary['members'], 'hint' => '在队且账号启用', 'icon' => 'bi-person-badge'],
    ['label' => '项目总数', 'value' => $summary['projects'], 'hint' => 'projects 全表', 'icon' => 'bi-kanban'],
    ['label' => '文章总数', 'value' => $summary['articles'], 'hint' => '含草稿与归档', 'icon' => 'bi-journal-text'],
    ['label' => '留言总数', 'value' => $summary['messages'], 'hint' => '待审核 ' . $summary['pending_messages'] . ' 条', 'icon' => 'bi-chat-square-text'],
    ['label' => '文件总数', 'value' => $summary['files'], 'hint' => 'files 全表', 'icon' => 'bi-folder2-open'],
    ['label' => '累计下载', 'value' => $summary['downloads'], 'hint' => '下载计数合计', 'icon' => 'bi-cloud-arrow-down'],
    ['label' => '后台账号', 'value' => $summary['users'], 'hint' => 'users 表', 'icon' => 'bi-people-fill'],
    ['label' => '当前团队', 'value' => $team === null ? 0 : 1, 'hint' => $team['name'] ?? '尚未配置', 'icon' => 'bi-buildings'],
];

$actions = '';
if (in_array('message.moderate', $permissions, true)) {
    $actions .= '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/messages')) . '">处理留言</a>';
}
if (in_array('article.manage', $permissions, true)) {
    $actions .= '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/articles/create')) . '">写文章</a>';
}
?>

<?php
$heading = '数据统计首页';
$description = '统计与图表直接查询 MySQL，不使用固定假数据。';
$kicker = 'DASHBOARD';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="admin-stat-grid">
    <?php foreach ($cards as $card): ?>
        <div class="admin-stat-card">
            <div class="admin-stat-card-head">
                <span><?= e($card['label']) ?></span>
                <span class="admin-stat-icon"><i class="bi <?= e($card['icon']) ?>" aria-hidden="true"></i></span>
            </div>
            <div class="admin-stat-value"><?= e((string) $card['value']) ?></div>
            <div class="admin-stat-hint"><?= e($card['hint']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($canStats): ?>
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>项目状态分布</h2>
                    <p>projects.status 聚合</p>
                </div>
                <span class="badge text-bg-light border">饼图</span>
            </div>
            <div class="admin-chart" id="chartProjectStatus" data-chart="project-status">
                <div class="admin-chart-loading">正在加载数据库统计…</div>
            </div>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>文章发布趋势</h2>
                    <p>最近 6 个月已发布文章数量</p>
                </div>
                <span class="badge text-bg-light border">折线图</span>
            </div>
            <div class="admin-chart" id="chartArticleTrend" data-chart="article-trend">
                <div class="admin-chart-loading">正在加载数据库统计…</div>
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>项目数量统计</h2>
                    <p>最近 6 个月新增项目数量</p>
                </div>
                <span class="badge text-bg-light border">柱状图</span>
            </div>
            <div class="admin-chart" id="chartProjectTrend" data-chart="project-trend">
                <div class="admin-chart-loading">正在加载数据库统计…</div>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>成员参与项目数量</h2>
                    <p>在队成员关联项目数</p>
                </div>
                <span class="badge text-bg-light border">条形图</span>
            </div>
            <div class="admin-chart" id="chartMemberParticipation" data-chart="member-participation">
                <div class="admin-chart-loading">正在加载数据库统计…</div>
            </div>
        </section>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="<?= $canActivity ? 'col-lg-5' : 'col-12' ?>">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>当前团队</h2>
                    <p>第一个 active 团队</p>
                </div>
                <?php if (in_array('team.edit', $permissions, true)): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/team')) ?>">编辑</a>
                <?php endif; ?>
            </div>
            <?php if ($team !== null): ?>
                <h3 class="h6 fw-bold"><?= e($team['name']) ?></h3>
                <p class="text-secondary small mb-3"><?= e(excerpt_text((string) ($team['description'] ?? ''), 120)) ?></p>
                <dl class="row small mb-0">
                    <dt class="col-4 text-secondary">研究方向</dt>
                    <dd class="col-8"><?= e($team['research_areas'] ?? '—') ?></dd>
                    <dt class="col-4 text-secondary">技术栈</dt>
                    <dd class="col-8"><?= e($team['tech_stack'] ?? '—') ?></dd>
                    <dt class="col-4 text-secondary">公开邮箱</dt>
                    <dd class="col-8"><?= e($team['email'] ?? '—') ?></dd>
                    <dt class="col-4 text-secondary">成立日期</dt>
                    <dd class="col-8"><?= e($team['founded_at'] ?? '—') ?></dd>
                </dl>
            <?php else: ?>
                <div class="admin-empty"><i class="bi bi-buildings" aria-hidden="true"></i>尚未配置 active 团队。</div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($canActivity): ?>
    <div class="col-lg-7">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>最近操作</h2>
                    <p>activity_logs 最新记录</p>
                </div>
                <?php if (in_array('activity.view', $permissions, true)): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/activity')) ?>">全部日志</a>
                <?php endif; ?>
            </div>
            <?php if ($latestLogs === []): ?>
                <div class="admin-empty"><i class="bi bi-clock-history" aria-hidden="true"></i>暂无操作日志。</div>
            <?php else: ?>
                <ul class="admin-activity-list">
                    <?php foreach ($latestLogs as $log): ?>
                        <li>
                            <span class="admin-activity-dot" aria-hidden="true"></span>
                            <div class="min-w-0">
                                <strong>
                                    <?= e($log['actor_username'] ?? '—') ?>
                                    · <code class="admin-code"><?= e($log['module'] . '/' . $log['action']) ?></code>
                                    <?php if (($log['result'] ?? 'success') === 'failure'): ?>
                                        <span class="badge text-bg-danger">失败</span>
                                    <?php endif; ?>
                                </strong>
                                <div class="small text-secondary text-truncate-2"><?= e($log['description'] ?? '') ?></div>
                                <small><?= e(format_datetime($log['created_at'])) ?> · <?= e($log['ip_address'] ?? '—') ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>
</div>

<?php if ($canStats): ?>
<div id="adminChartsApp" data-stats-url="<?= e(url('/admin/api/stats')) ?>"></div>
<script src="<?= e(asset('js/echarts.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/admin-charts.js')) ?>" defer></script>
<?php endif; ?>
