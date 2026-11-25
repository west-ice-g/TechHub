-- ============================================================
-- TechHub 后台阶段演示时间迁移
-- 文件：data/migrations/2026-11-25_admin_demo_dates.sql
-- 适用：MySQL 8.0.19，可重复执行
--
-- 说明：只调整明确标记为“示例”的项目与文章日期，使后台统计图表
-- 能展示当前实现周期内的真实数据库聚合结果；不修改业务状态和内容。
-- ============================================================

USE `techhub`;

UPDATE `projects`
SET `start_date` = '2026-06-01',
    `end_date` = '2026-08-31',
    `created_at` = '2026-06-02 09:00:00',
    `updated_at` = '2026-11-01 09:00:00'
WHERE `id` = 1 AND `slug` = 'campus-tech-community';

UPDATE `projects`
SET `start_date` = '2026-09-01',
    `created_at` = '2026-09-02 09:00:00',
    `updated_at` = '2026-11-01 09:00:00'
WHERE `id` = 2 AND `slug` = 'course-resource-helper';

UPDATE `project_members`
SET `joined_at` = '2026-06-01'
WHERE `project_id` = 1;

UPDATE `project_members`
SET `joined_at` = '2026-09-01'
WHERE `project_id` = 2;

UPDATE `articles`
SET `published_at` = '2026-08-20 10:00:00',
    `created_at` = '2026-08-20 09:30:00',
    `updated_at` = '2026-08-20 10:00:00'
WHERE `id` = 1 AND `slug` = 'database-design-from-requirements';

UPDATE `articles`
SET `published_at` = '2026-10-15 14:00:00',
    `created_at` = '2026-10-15 13:30:00',
    `updated_at` = '2026-10-15 14:00:00'
WHERE `id` = 2 AND `slug` = 'php-mvc-request-lifecycle';

UPDATE `articles`
SET `created_at` = '2026-11-10 09:00:00',
    `updated_at` = '2026-11-10 09:00:00'
WHERE `id` = 3 AND `slug` = 'draft-project-retrospective';
