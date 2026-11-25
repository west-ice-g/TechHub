<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Paginator.php
 * 模块：基础设施
 * 职责：统一计算页码、每页数量、偏移量和上一页/下一页边界。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Paginator
{
    /**
     * @return array{page:int, per_page:int, total:int, last_page:int, offset:int, from:int, to:int}
     */
    public static function resolve(mixed $requestedPage, int $total, int $perPage = 6): array
    {
        $total = max(0, $total);
        $perPage = max(1, min(50, $perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));

        $page = is_numeric($requestedPage) ? (int) $requestedPage : 1;
        $page = max(1, min($page, $lastPage));

        $offset = ($page - 1) * $perPage;
        $from = $total === 0 ? 0 : $offset + 1;
        $to = min($total, $offset + $perPage);

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'offset' => $offset,
            'from' => $from,
            'to' => $to,
        ];
    }
}
