<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Support/Markdown.php
 * 模块：内容展示
 * 职责：将受控 Markdown 子集渲染为安全 HTML；先转义原文，只生成白名单标签。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Support;

final class Markdown
{
    /**
     * 支持的语法：标题、段落、无序/有序列表、引用、代码块、分隔线、粗体、斜体、行内代码和 http(s) 链接。
     */
    public static function render(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);
        $count = count($lines);
        $index = 0;
        $html = [];

        while ($index < $count) {
            $line = $lines[$index];

            if (trim($line) === '') {
                $index++;
                continue;
            }

            if (preg_match('/^```([A-Za-z0-9_-]*)\s*$/', $line, $match)) {
                $language = preg_replace('/[^A-Za-z0-9_-]/', '', $match[1]) ?? '';
                $code = [];
                $index++;

                while ($index < $count && !preg_match('/^```\s*$/', $lines[$index])) {
                    $code[] = $lines[$index];
                    $index++;
                }

                if ($index < $count) {
                    $index++;
                }

                $class = $language === '' ? '' : ' class="language-' . e($language) . '"';
                $html[] = '<pre class="article-code"><code' . $class . '>'
                    . e(implode("\n", $code))
                    . '</code></pre>';
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/u', $line, $match)) {
                $level = strlen($match[1]);
                $html[] = '<h' . $level . '>' . self::inline(trim($match[2])) . '</h' . $level . '>';
                $index++;
                continue;
            }

            if (preg_match('/^>\s?(.*)$/u', $line)) {
                $quotes = [];
                while ($index < $count && preg_match('/^>\s?(.*)$/u', $lines[$index], $match)) {
                    $quotes[] = trim($match[1]);
                    $index++;
                }
                $html[] = '<blockquote class="article-quote">' . self::inline(implode(' ', $quotes)) . '</blockquote>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/u', $line)) {
                $items = [];
                while ($index < $count && preg_match('/^[-*]\s+(.+)$/u', $lines[$index], $match)) {
                    $items[] = '<li>' . self::inline(trim($match[1])) . '</li>';
                    $index++;
                }
                $html[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/u', $line)) {
                $items = [];
                while ($index < $count && preg_match('/^\d+\.\s+(.+)$/u', $lines[$index], $match)) {
                    $items[] = '<li>' . self::inline(trim($match[1])) . '</li>';
                    $index++;
                }
                $html[] = '<ol>' . implode('', $items) . '</ol>';
                continue;
            }

            if (preg_match('/^(?:---+|\*\*\*+|___+)\s*$/', $line)) {
                $html[] = '<hr>';
                $index++;
                continue;
            }

            $paragraph = [];
            while ($index < $count) {
                $candidate = $lines[$index];
                if (trim($candidate) === '' || self::startsBlock($candidate)) {
                    break;
                }
                $paragraph[] = trim($candidate);
                $index++;
            }

            if ($paragraph !== []) {
                $html[] = '<p>' . self::inline(implode(' ', $paragraph)) . '</p>';
            }
        }

        return implode("\n", $html);
    }

    private static function startsBlock(string $line): bool
    {
        return (bool) preg_match('/^(#{1,3}\s|>\s?|[-*]\s+|\d+\.\s+|```)/u', $line);
    }

    private static function inline(string $text): string
    {
        $escaped = e($text);

        $escaped = preg_replace_callback('/`([^`]+)`/u', static function (array $match): string {
            return '<code>' . $match[1] . '</code>';
        }, $escaped) ?? $escaped;

        $escaped = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/su', '<em>$1</em>', $escaped) ?? $escaped;

        return preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u',
            static function (array $match): string {
                return '<a href="' . $match[2] . '" target="_blank" rel="noopener noreferrer">'
                    . $match[1] . '</a>';
            },
            $escaped
        ) ?? $escaped;
    }
}
