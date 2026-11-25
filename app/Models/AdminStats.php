<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/AdminStats.php
 * 模块：后台数据统计
 * 职责：从 MySQL 聚合仪表盘总数、项目状态、文章趋势和成员参与项目数据。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use DateTimeImmutable;

final class AdminStats extends Model
{
    /** @var array<string, string> */
    public const PROJECT_STATUS_LABELS = [
        'planning' => '规划中',
        'ongoing' => '进行中',
        'completed' => '已完成',
        'archived' => '已归档',
    ];

    /** @var array<string, string> */
    public const ARTICLE_STATUS_LABELS = [
        'draft' => '草稿',
        'published' => '已发布',
        'archived' => '已归档',
    ];

    /** @var array<string, string> */
    public const ANNOUNCEMENT_STATUS_LABELS = [
        'draft' => '草稿',
        'published' => '已发布',
        'archived' => '已下线',
    ];

    /** @var array<string, string> */
    public const MESSAGE_STATUS_LABELS = [
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
    ];

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'members' => (int) $this->fetchValue(
                "SELECT COUNT(*)
                 FROM team_members tm
                 INNER JOIN users u ON u.id = tm.user_id
                 WHERE tm.status = 'active' AND u.status = 'active'"
            ),
            'projects' => (int) $this->fetchValue('SELECT COUNT(*) FROM projects'),
            'articles' => (int) $this->fetchValue('SELECT COUNT(*) FROM articles'),
            'messages' => (int) $this->fetchValue('SELECT COUNT(*) FROM messages'),
            'pending_messages' => (int) $this->fetchValue("SELECT COUNT(*) FROM messages WHERE status = 'pending'"),
            'files' => (int) $this->fetchValue('SELECT COUNT(*) FROM files'),
            'users' => (int) $this->fetchValue('SELECT COUNT(*) FROM users'),
            'downloads' => (int) $this->fetchValue('SELECT COALESCE(SUM(download_count), 0) FROM files'),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function projectStatusDistribution(): array
    {
        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS total
             FROM projects
             GROUP BY status'
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        $labels = [];
        $values = [];
        foreach (self::PROJECT_STATUS_LABELS as $status => $label) {
            if (($counts[$status] ?? 0) > 0) {
                $labels[] = $label;
                $values[] = $counts[$status];
            }
        }

        if ($labels === []) {
            $labels[] = '暂无项目';
            $values[] = 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * 最近 6 个月文章发布趋势，缺失月份补 0。
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function articleTrend(int $months = 6): array
    {
        return $this->monthlyTrend(
            "SELECT DATE_FORMAT(published_at, '%Y-%m') AS ym, COUNT(*) AS total
             FROM articles
             WHERE status = 'published'
               AND published_at IS NOT NULL
               AND published_at >= :start
             GROUP BY ym",
            $months
        );
    }

    /**
     * 最近 6 个月项目新增趋势，缺失月份补 0。
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function projectTrend(int $months = 6): array
    {
        return $this->monthlyTrend(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
             FROM projects
             WHERE created_at >= :start
             GROUP BY ym",
            $months
        );
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function memberParticipation(int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $rows = $this->fetchAll(
            "SELECT u.display_name, COUNT(pm.project_id) AS project_count
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN project_members pm ON pm.team_member_id = tm.id
             WHERE tm.status = 'active' AND u.status = 'active'
             GROUP BY tm.id, u.display_name
             ORDER BY project_count DESC, tm.sort_order, tm.id
             LIMIT {$limit}"
        );

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = (string) $row['display_name'];
            $values[] = (int) $row['project_count'];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array<string, int>
     */
    public function messageStatusDistribution(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS total FROM messages GROUP BY status');
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $result)) {
                $result[$status] = (int) $row['total'];
            }
        }

        return $result;
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function monthlyTrend(string $sql, int $months): array
    {
        $months = max(3, min(12, $months));
        $current = new DateTimeImmutable('first day of this month 00:00:00');
        $start = $current->modify('-' . ($months - 1) . ' months');

        $rows = $this->fetchAll($sql, ['start' => $start->format('Y-m-d H:i:s')]);
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['ym']] = (int) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $key = $month->format('Y-m');
            $labels[] = $month->format('Y年n月');
            $values[] = $counts[$key] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
