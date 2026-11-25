<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Response.php
 * 模块：基础设施
 * 职责：封装 HTML、JSON、重定向响应及基础安全响应头。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Response
{
    /** @param array<int, string> $headers */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = [],
        private ?string $filePath = null
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function json(array $payload, int $status = 200): self
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self(
            $body === false ? '{"success":false}' : $body,
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        if (preg_match('/[\r\n]/', $location)) {
            throw new \InvalidArgumentException('非法重定向地址');
        }

        return new self('', $status, ['Location' => $location]);
    }

    public static function download(string $filePath, string $downloadName, string $mimeType): self
    {
        $downloadName = basename(str_replace('\\', '/', $downloadName));
        $downloadName = trim((string) preg_replace('/[\r\n"]/', '', $downloadName));
        if ($downloadName === '') {
            $downloadName = 'download';
        }

        $fallback = (string) preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName);
        if ($fallback === '' || $fallback === '_.' || $fallback === '_') {
            $fallback = 'download';
        }

        $mimeType = trim((string) preg_replace('/[\r\n]/', '', $mimeType));
        if (!preg_match('#^[A-Za-z0-9.+-]+/[A-Za-z0-9.+-]+$#', $mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $fallback,
                rawurlencode($downloadName)
            ),
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ];

        $size = @filesize($filePath);
        if (is_int($size)) {
            $headers['Content-Length'] = (string) $size;
        }

        return new self('', 200, $headers, $filePath);
    }

    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function withoutBody(): self
    {
        $this->body = '';
        $this->filePath = null;

        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        if (preg_match('/[\r\n]/', $name . $value)) {
            throw new \InvalidArgumentException('非法响应头');
        }

        $this->headers[$name] = $value;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->securityHeaders() as $name => $value) {
                header($name . ': ' . $value);
            }

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->filePath !== null && is_file($this->filePath) && is_readable($this->filePath)) {
            readfile($this->filePath);
            return;
        }

        echo $this->body;
    }

    /** @return array<string, string> */
    private function securityHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
        ];
    }
}
