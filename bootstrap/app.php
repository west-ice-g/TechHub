<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：bootstrap/app.php
 * 模块：应用启动
 * 职责：注册自动加载、加载环境与配置、启动安全 Session、配置日志与异常处理。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use Core\Config;
use Core\Env;
use Core\HttpException;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\View;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => BASE_PATH . '/app/',
        'Core\\' => BASE_PATH . '/core/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $directory . $relative . '.php';

        if (is_file($file)) {
            require $file;
        }

        return;
    }
});

require BASE_PATH . '/core/helpers.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config/app.php', 'app');
Config::load(BASE_PATH . '/config/database.php', 'database');

date_default_timezone_set((string) config('app.timezone', 'Asia/Shanghai'));

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

Session::start((array) config('app.session', []));

set_exception_handler(static function (Throwable $exception): void {
    Logger::exception($exception);

    $status = $exception instanceof HttpException ? $exception->status() : 500;
    $titles = [
        400 => '400 请求错误',
        403 => '403 无权访问',
        404 => '404 页面不存在',
        405 => '405 方法不允许',
        419 => '419 页面已过期',
        500 => '500 服务器错误',
    ];

    $title = $titles[$status] ?? '请求处理失败';
    $message = $exception instanceof HttpException
        ? $exception->getMessage()
        : '系统暂时无法处理该请求，请稍后重试。';

    if ($status === 500 && (bool) config('app.debug', false)) {
        $message = '调试提示：' . $exception->getMessage();
    }

    $view = in_array($status, [400, 403, 404, 405, 419], true)
        ? 'errors/' . $status
        : 'errors/500';

    $body = View::render($view, [
        'title' => $title,
        'message' => $message,
    ], 'layouts/error');

    $response = Response::html($body, $status);
    if ($exception instanceof HttpException) {
        foreach ($exception->headers() as $name => $value) {
            $response->withHeader($name, $value);
        }
    }
    $response->send();
});
