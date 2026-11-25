<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/partials/avatar.php
 * 模块：公共组件
 * 职责：输出成员头像；无头像时使用姓名首字符作为安全占位。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$avatarUrl = $avatarUrl ?? null;
$avatarName = (string) ($avatarName ?? '?');
$avatarClass = (string) ($avatarClass ?? 'avatar-md');
$avatarInitial = mb_substr(trim($avatarName) !== '' ? trim($avatarName) : '?', 0, 1, 'UTF-8');
?>
<?php if (is_string($avatarUrl) && $avatarUrl !== ''): ?>
    <img class="<?= e($avatarClass) ?> avatar-image" src="<?= e($avatarUrl) ?>"
         alt="<?= e($avatarName) ?>的头像" loading="lazy" width="120" height="120">
<?php else: ?>
    <span class="<?= e($avatarClass) ?> avatar-fallback" aria-hidden="true"><?= e($avatarInitial) ?></span>
<?php endif; ?>
