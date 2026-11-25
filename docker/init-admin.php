<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：docker/init-admin.php
 * 模块：部署初始化
 * 职责：按环境变量设置首次管理员/编辑账号密码；不输出明文密码。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/app.php';

use Core\Database;

/**
 * Set a password for an existing seeded user, without printing it.
 */
$setPassword = static function (PDO $db, string $username, string $password, bool $reset, string $label): void {
    if ($username === '') {
        fwrite(STDERR, "{$label}用户名为空，跳过。\n");
        return;
    }

    if ($password === '') {
        fwrite(STDERR, "{$label}密码为空，跳过。\n");
        return;
    }

    if (mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72) {
        throw new RuntimeException("{$label}密码至少 8 个字符且不超过 72 字节。");
    }

    if (str_starts_with($password, 'change-me')) {
        throw new RuntimeException("{$label}密码不能使用 .env.example 中的占位值。");
    }

    $stmt = $db->prepare('SELECT id, last_login_at FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!is_array($user)) {
        fwrite(STDERR, "未找到{$label}账号 {$username}，请先导入 data/install.sql。\n");
        return;
    }

    if ($user['last_login_at'] !== null && !$reset) {
        echo "{$label}已登录过，跳过密码初始化。\n";
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $update = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $update->execute(['hash' => $hash, 'id' => (int) $user['id']]);
    echo "{$label}密码已按环境变量初始化（未输出明文）。\n";
};

try {
    $db = Database::connection();
    $reset = (string) env('RESET_ADMIN_PASSWORD', '0') === '1';
    $setPassword(
        $db,
        trim((string) env('INITIAL_ADMIN_USERNAME', 'admin')),
        (string) env('INITIAL_ADMIN_PASSWORD', ''),
        $reset,
        '管理员'
    );

    $editorPassword = (string) env('INITIAL_EDITOR_PASSWORD', '');
    if ($editorPassword !== '') {
        $setPassword(
            $db,
            trim((string) env('INITIAL_EDITOR_USERNAME', 'editor')),
            $editorPassword,
            $reset,
            '编辑账号'
        );
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
