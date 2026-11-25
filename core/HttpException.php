<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/HttpException.php
 * 模块：基础设施
 * 职责：携带 HTTP 状态码的可预期异常。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class HttpException extends \RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status,
        string $message = '',
        private readonly array $headers = []
    ) {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
