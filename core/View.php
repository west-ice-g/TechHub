<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/View.php
 * 模块：基础设施
 * 职责：渲染原生 PHP 视图和布局，模板名使用白名单字符避免目录穿越。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        if (!preg_match('/^[A-Za-z0-9_\/-]+$/', $view)) {
            throw new \InvalidArgumentException('非法视图名称');
        }

        $content = self::renderFile($view, $data);

        if ($layout !== null) {
            if (!preg_match('/^[A-Za-z0-9_\/-]+$/', $layout)) {
                throw new \InvalidArgumentException('非法布局名称');
            }

            $data['content'] = $content;
            $content = self::renderFile($layout, $data);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function renderFile(string $template, array $data): string
    {
        $templateFile = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($templateFile)) {
            throw new \RuntimeException('视图不存在：' . $template);
        }

        extract($data, EXTR_SKIP);
        $level = ob_get_level();
        ob_start();

        try {
            require $templateFile;

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }
}
