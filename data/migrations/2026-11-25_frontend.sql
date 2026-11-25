-- ============================================================
-- TechHub 前台页面扩展迁移
-- 日期：2026-11-25
-- 内容：
--   1. teams 增加研究方向与技术栈展示字段
--   2. projects 增加项目成果字段
--   3. 示例团队、成员、项目、文章补充本地封面/头像路径
-- 说明：
--   data/install.sql 已同步包含新字段，本迁移用于已有开发库。
--   本脚本使用 information_schema 判断列是否存在，可重复执行。
-- ============================================================

USE `techhub`;
SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;
SET time_zone = '+08:00';

-- 1. teams.research_areas
SET @column_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'research_areas'
);
SET @sql := IF(
  @column_exists = 0,
  'ALTER TABLE `teams` ADD COLUMN `research_areas` VARCHAR(255) NULL AFTER `description`',
  'SELECT ''teams.research_areas already exists'' AS note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. teams.tech_stack
SET @column_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'tech_stack'
);
SET @sql := IF(
  @column_exists = 0,
  'ALTER TABLE `teams` ADD COLUMN `tech_stack` VARCHAR(255) NULL AFTER `research_areas`',
  'SELECT ''teams.tech_stack already exists'' AS note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. projects.outcomes
SET @column_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'outcomes'
);
SET @sql := IF(
  @column_exists = 0,
  'ALTER TABLE `projects` ADD COLUMN `outcomes` TEXT NULL AFTER `description`',
  'SELECT ''projects.outcomes already exists'' AS note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 示例展示数据
UPDATE `teams`
SET `research_areas` = 'Web 应用开发、数据库系统、前端交互设计',
    `tech_stack` = 'PHP 8.2、MySQL 8.0、Bootstrap 5、JavaScript',
    `logo_path` = 'assets/images/logo-techhub.svg',
    `founded_at` = '2024-09-01'
WHERE `id` = 1;

UPDATE `projects`
SET `outcomes` = '完成团队主页、项目展示、成员关系与留言互动闭环，形成可运行的前后端原型。',
    `cover_path` = 'assets/images/projects/campus-community.svg'
WHERE `id` = 1;

UPDATE `projects`
SET `outcomes` = '完成课程资料分类、关键词检索和结果列表原型，后续继续完善文件预览与权限控制。',
    `cover_path` = 'assets/images/projects/course-helper.svg'
WHERE `id` = 2;

UPDATE `member_profiles`
SET `avatar_path` = 'assets/images/avatars/member-a.svg'
WHERE `user_id` = 3;

UPDATE `member_profiles`
SET `avatar_path` = 'assets/images/avatars/member-b.svg'
WHERE `user_id` = 4;

UPDATE `member_profiles`
SET `avatar_path` = 'assets/images/avatars/member-c.svg'
WHERE `user_id` = 5;

UPDATE `articles`
SET `cover_path` = 'assets/images/articles/article-database.svg'
WHERE `id` = 1;

UPDATE `articles`
SET `cover_path` = 'assets/images/articles/article-mvc.svg'
WHERE `id` = 2;

SELECT 'TechHub frontend migration completed' AS result;
