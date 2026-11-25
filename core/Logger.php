<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Logger.php
 * 模块：基础设施
 * 职责：写入应用日志，过滤密码、Token 等敏感键，避免把密钥输出到页面。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = sprintf(
            "[%s] %s %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            self::sanitizeMessage($message),
            $context === [] ? '' : ' ' . self::encodeContext($context)
        );

        $file = $directory . '/app-' . date('Y-m-d') . '.log';
        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($line));
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function exception(\Throwable $exception): void
    {
        self::error($exception::class . ': ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function encodeContext(array $context): string
    {
        $filtered = [];
        foreach ($context as $key => $value) {
            if (preg_match('/pass|secret|token|authorization|cookie/i', (string) $key)) {
                $filtered[$key] = '[REDACTED]';
                continue;
            }
            $filtered[$key] = $value;
        }

        $encoded = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{"context":"unencodable"}' : $encoded;
    }

    private static function sanitizeMessage(string $message): string
    {
        return str_replace(["\r", "\n"], ' ', $message);
    }
}
