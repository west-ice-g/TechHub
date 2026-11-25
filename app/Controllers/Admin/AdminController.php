<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/AdminController.php
 * 模块：后台基础控制器
 * 职责：统一后台视图数据、表单状态、Flash 提示和操作日志写入。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use Core\Auth;
use Core\Controller;
use Core\Logger;
use Core\Response;
use Core\Session;

abstract class AdminController extends Controller
{
    /**
     * 渲染后台页面，并统一注入当前用户、权限、面包屑和一次性表单状态。
     *
     * @param array<string, mixed> $data
     * @param array<int, array{label: string, url?: string}> $breadcrumbs
     */
    protected function page(
        string $template,
        string $title,
        string $activeMenu,
        array $data = [],
        array $breadcrumbs = []
    ): Response {
        $data['pageTitle'] = $title;
        $data['activeMenu'] = $activeMenu;
        $data['currentUser'] = Auth::user();
        $data['permissions'] = Auth::permissions();
        $data['breadcrumbs'] = $this->breadcrumbs($breadcrumbs, $title);
        $data['formErrors'] = Session::pull('_admin_form_errors', []);
        $data['formOld'] = Session::pull('_admin_form_old', []);
        $data['formErrors'] = is_array($data['formErrors']) ? $data['formErrors'] : [];
        $data['formOld'] = is_array($data['formOld']) ? $data['formOld'] : [];

        return $this->view($template, $data, 'layouts/admin');
    }

    /**
     * 表单校验失败时保存错误和旧输入，使用 PRG 避免重复提交。
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    protected function backWithErrors(string $path, array $errors, array $old = []): Response
    {
        Session::put('_admin_form_errors', $errors);
        Session::put('_admin_form_old', $old);

        return $this->redirect($path);
    }

    protected function success(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function error(string $message): void
    {
        Session::flash('error', $message);
    }

    /**
     * 写入后台操作日志；日志失败不阻断主业务，但要写系统日志。
     *
     * @param string $result success|failure
     */
    protected function logAction(
        string $module,
        string $action,
        string $description,
        ?string $targetType = null,
        ?int $targetId = null,
        string $result = 'success'
    ): void {
        $user = Auth::user();

        try {
            (new ActivityLog())->record([
                'user_id' => Auth::id(),
                'actor_username' => $user === null ? null : (string) $user['username'],
                'module' => $module,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'description' => $description,
                'result' => $result === 'failure' ? 'failure' : 'success',
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('后台操作日志写入失败', [
                'module' => $module,
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected function requireSuperAdmin(): void
    {
        if (!Auth::isSuperAdmin()) {
            abort(403, '该操作仅允许超级管理员执行。');
        }
    }

    protected function text(string $key, int $maxLength = 255): string
    {
        $value = $this->request->post($key, '');
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if (!mb_check_encoding($value, 'UTF-8')) {
            return '';
        }

        return mb_substr($value, 0, max(0, $maxLength));
    }

    protected function nullableText(string $key, int $maxLength = 255): ?string
    {
        $value = $this->text($key, $maxLength);

        return $value === '' ? null : $value;
    }

    protected function integer(string $key, int $default = 0): int
    {
        $value = $this->request->post($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    protected function checkbox(string $key): bool
    {
        $value = $this->request->post($key, '0');

        return in_array($value, ['1', 1, 'on', true], true);
    }

    /**
     * 返回合法 Y-m-d 日期或 null。
     */
    protected function dateValue(string $key): ?string
    {
        $value = $this->nullableText($key, 10);
        if ($value === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    protected function validHttpUrl(?string $value): bool
    {
        return $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $value) === 1;
    }

    /**
     * @param array<int, array{label: string, url?: string}> $breadcrumbs
     * @return array<int, array{label: string, url?: string}>
     */
    private function breadcrumbs(array $breadcrumbs, string $title): array
    {
        if ($breadcrumbs === []) {
            return [
                ['label' => '后台首页', 'url' => '/admin'],
                ['label' => $title],
            ];
        }

        return $breadcrumbs;
    }
}
