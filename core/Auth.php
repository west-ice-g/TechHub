<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Auth.php
 * 模块：安全
 * 职责：使用 Session + MySQL 完成登录、退出、当前用户、登录限流和权限判断。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

use App\Models\ActivityLog;
use App\Models\User;

final class Auth
{
    private const SESSION_USER = '_auth_user_id';
    private const SESSION_ATTEMPTS = '_login_attempts';

    private static bool $resolved = false;
    private static ?array $currentUser = null;
    /** @var array<int, string>|null */
    private static ?array $permissionCodes = null;

    /**
     * 尝试登录，失败使用统一外部提示；内部写入安全日志。
     */
    public static function attempt(string $username, string $password, ?Request $request = null): bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        if (self::isLockedOut()) {
            Logger::warning('登录被限流', ['username' => $username]);
            self::logEvent($request, [
                'module' => 'auth',
                'action' => 'login_locked',
                'actor_username' => $username,
                'description' => '登录尝试过于频繁，已临时限制',
                'result' => 'failure',
            ]);

            return false;
        }

        $model = new User();
        $user = $model->findForAuth($username);
        $valid = $user !== null
            && $user['status'] === 'active'
            && password_verify($password, (string) $user['password_hash']);

        if (!$valid) {
            self::recordFailedAttempt();
            self::logEvent($request, [
                'user_id' => $user['id'] ?? null,
                'module' => 'auth',
                'action' => 'login_failed',
                'actor_username' => $username,
                'description' => '登录失败：凭据错误或账号不可用',
                'result' => 'failure',
            ]);

            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $model->updatePasswordHash(
                (int) $user['id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        Session::regenerate();
        Session::put(self::SESSION_USER, (int) $user['id']);
        Session::forget(self::SESSION_ATTEMPTS);
        Csrf::rotate();
        $model->updateLastLogin((int) $user['id'], $request?->ip());
        self::resetCache();

        self::logEvent($request, [
            'user_id' => (int) $user['id'],
            'module' => 'auth',
            'action' => 'login',
            'actor_username' => (string) $user['username'],
            'description' => '登录成功',
        ]);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$currentUser;
        }

        self::$resolved = true;
        $userId = Session::get(self::SESSION_USER);
        if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        $user = (new User())->findById((int) $userId);
        if ($user === null || $user['status'] !== 'active') {
            Session::forget(self::SESSION_USER);
            self::resetCache();

            return null;
        }

        self::$currentUser = $user;

        return self::$currentUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        $user = self::user();
        if ($user === null) {
            return [];
        }

        if (self::$permissionCodes === null) {
            self::$permissionCodes = (new User())->permissionCodesForRole((int) $user['role_id']);
        }

        return self::$permissionCodes;
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    public static function isSuperAdmin(): bool
    {
        $user = self::user();

        return $user !== null && ($user['role_code'] ?? '') === 'super_admin';
    }

    public static function logout(?Request $request = null): void
    {
        $user = self::user();
        if ($user !== null) {
            self::logEvent($request, [
                'user_id' => (int) $user['id'],
                'module' => 'auth',
                'action' => 'logout',
                'actor_username' => (string) $user['username'],
                'description' => '退出登录',
            ]);
        }

        Session::forget(self::SESSION_USER);
        Session::forget(self::SESSION_ATTEMPTS);
        Session::forget('intended_url');
        Session::regenerate();
        Csrf::rotate();
        self::resetCache();
    }

    public static function isLockedOut(): bool
    {
        $attempts = Session::get(self::SESSION_ATTEMPTS, []);
        if (!is_array($attempts)) {
            return false;
        }

        $cutoff = time() - (int) config('app.security.login_lockout_seconds', 600);
        $recent = array_values(array_filter(
            $attempts,
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
        ));
        Session::put(self::SESSION_ATTEMPTS, $recent);

        return count($recent) >= (int) config('app.security.login_max_attempts', 5);
    }

    public static function remainingLockoutSeconds(): int
    {
        $attempts = Session::get(self::SESSION_ATTEMPTS, []);
        if (!is_array($attempts) || $attempts === []) {
            return 0;
        }

        $oldest = (int) min(array_map(static fn (mixed $v): int => (int) $v, $attempts));
        $unlockAt = $oldest + (int) config('app.security.login_lockout_seconds', 600);

        return max(0, $unlockAt - time());
    }

    private static function recordFailedAttempt(): void
    {
        $attempts = Session::get(self::SESSION_ATTEMPTS, []);
        if (!is_array($attempts)) {
            $attempts = [];
        }

        $cutoff = time() - (int) config('app.security.login_lockout_seconds', 600);
        $attempts = array_values(array_filter(
            $attempts,
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
        ));
        $attempts[] = time();
        Session::put(self::SESSION_ATTEMPTS, array_slice($attempts, -10));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function logEvent(?Request $request, array $data): void
    {
        $data['ip_address'] = $request?->ip();
        $data['user_agent'] = $request?->userAgent();

        try {
            (new ActivityLog())->record($data);
        } catch (\Throwable $e) {
            Logger::warning('操作日志写入失败', ['exception' => $e->getMessage(), 'action' => $data['action'] ?? 'unknown']);
        }
    }

    private static function resetCache(): void
    {
        self::$resolved = false;
        self::$currentUser = null;
        self::$permissionCodes = null;
    }
}
