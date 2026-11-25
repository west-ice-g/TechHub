<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/Team.php
 * 模块：团队信息管理
 * 职责：读取主团队资料、展示成员和团队统计。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Team extends Model
{
    /**
     * 当前界面使用第一个 active 团队；保留 teams 实体便于后续扩展。
     *
     * @return array<string, mixed>|null
     */
    public function primary(): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, slug, short_name, slogan, description, research_areas, tech_stack,
                    logo_path, founded_at, email, location, github_url, website_url, status,
                    created_at, updated_at
             FROM teams
             WHERE status = 'active'
             ORDER BY id
             LIMIT 1"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function members(int $teamId, int $limit = 6): array
    {
        $limit = max(1, min(50, $limit));

        return $this->fetchAll(
            "SELECT tm.id AS membership_id, tm.position_title, tm.responsibilities,
                    tm.joined_at, tm.status AS member_status,
                    u.id AS user_id, u.display_name, u.username,
                    mp.bio, mp.skills, mp.avatar_path, mp.public_email, mp.github_url
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN member_profiles mp ON mp.user_id = u.id
             WHERE tm.team_id = :team_id
               AND tm.status IN ('active', 'alumni')
               AND u.status = 'active'
             ORDER BY CASE tm.status WHEN 'active' THEN 0 ELSE 1 END, tm.sort_order, tm.id
             LIMIT {$limit}",
            ['team_id' => $teamId]
        );
    }

    public function countMembers(int $teamId): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             WHERE tm.team_id = :team_id AND tm.status = 'active' AND u.status = 'active'",
            ['team_id' => $teamId]
        );
    }

    public function countAll(): int
    {
        return (int) $this->fetchValue("SELECT COUNT(*) FROM teams WHERE status = 'active'");
    }

    /**
     * @return array<string, mixed>|null
     */
    public function firstAny(): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, slug, short_name, slogan, description, research_areas, tech_stack,
                    logo_path, founded_at, email, location, github_url, website_url, status,
                    created_at, updated_at
             FROM teams
             ORDER BY CASE status WHEN \'active\' THEN 0 ELSE 1 END, id
             LIMIT 1'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, slug, short_name, slogan, description, research_areas, tech_stack,
                    logo_path, founded_at, email, location, github_url, website_url, status,
                    created_at, updated_at
             FROM teams
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allForSelect(): array
    {
        return $this->fetchAll(
            "SELECT id, name, status
             FROM teams
             ORDER BY CASE status WHEN 'active' THEN 0 ELSE 1 END, id"
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM teams WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) $this->fetchValue($sql, $params) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $id, array $data): void
    {
        $this->execute(
            'UPDATE teams
             SET name = :name, slug = :slug, short_name = :short_name, slogan = :slogan,
                 description = :description, research_areas = :research_areas, tech_stack = :tech_stack,
                 logo_path = :logo_path, founded_at = :founded_at, email = :email, location = :location,
                 github_url = :github_url, website_url = :website_url, status = :status
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'short_name' => $data['short_name'],
                'slogan' => $data['slogan'],
                'description' => $data['description'],
                'research_areas' => $data['research_areas'],
                'tech_stack' => $data['tech_stack'],
                'logo_path' => $data['logo_path'],
                'founded_at' => $data['founded_at'],
                'email' => $data['email'],
                'location' => $data['location'],
                'github_url' => $data['github_url'],
                'website_url' => $data['website_url'],
                'status' => $data['status'],
            ]
        );
    }
}
