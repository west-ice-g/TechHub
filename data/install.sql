-- ============================================================
-- TechHub 数据库初始化脚本
-- 目标数据库：MySQL 8.0+
-- 字符集：utf8mb4 / utf8mb4_0900_ai_ci
-- 说明：
--   1. 本脚本用于首次安装和重复初始化演示环境。
--   2. 使用 CREATE TABLE IF NOT EXISTS 与 INSERT IGNORE/幂等种子，
--      重复执行不会重复创建表或重复插入演示数据。
--   3. 本脚本不删除已有业务数据；结构升级请使用独立迁移脚本。
--   4. 脚本包含数据库创建语句，建议使用有建库权限的账号执行。
--   5. 默认管理员密码只保存 password_hash 结果，不保存明文。
-- ============================================================

SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;
SET time_zone = '+08:00';

CREATE DATABASE IF NOT EXISTS `techhub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `techhub`;

-- ------------------------------------------------------------
-- 1. roles 角色表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 2. permissions 权限定义表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(80) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_code` (`code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 3. role_permissions 角色权限关联表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission`
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 4. users 用户表
--    同时保存后台登录信息和公开成员资料。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_status` (`status`),
  CONSTRAINT `fk_users_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 4.1 member_profiles 公开成员资料表
--     与 users 形成 1:0..1 关系：一个账号最多一份公开资料，
--     后台账号可以没有公开资料（例如系统管理员）。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `bio` TEXT NULL,
  `skills` VARCHAR(255) NULL,
  `avatar_path` VARCHAR(255) NULL,
  `public_email` VARCHAR(120) NULL,
  `github_url` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_profiles_user` (`user_id`),
  CONSTRAINT `fk_member_profiles_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 5. teams 团队表
--    当前界面按单团队使用，但表结构保留可扩展的团队实体。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `slogan` VARCHAR(200) NULL,
  `description` TEXT NULL,
  `research_areas` VARCHAR(255) NULL,
  `tech_stack` VARCHAR(255) NULL,
  `logo_path` VARCHAR(255) NULL,
  `founded_at` DATE NULL,
  `email` VARCHAR(120) NULL,
  `location` VARCHAR(120) NULL,
  `github_url` VARCHAR(255) NULL,
  `website_url` VARCHAR(255) NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_teams_slug` (`slug`),
  KEY `idx_teams_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 6. team_members 团队成员关系表
--    软状态保留历史：成员离队时更新 status/left_at，不删除记录。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `position_title` VARCHAR(80) NULL,
  `responsibilities` VARCHAR(255) NULL,
  `joined_at` DATE NOT NULL,
  `left_at` DATE NULL,
  `status` ENUM('active','alumni','removed') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_members_team_user` (`team_id`, `user_id`),
  KEY `idx_team_members_user` (`user_id`),
  KEY `idx_team_members_status_sort` (`status`, `sort_order`),
  CONSTRAINT `fk_team_members_team`
    FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_team_members_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_team_members_dates`
    CHECK (`left_at` IS NULL OR `left_at` >= `joined_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 7. categories 文章分类表（支持两级父子分类）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_status_sort` (`status`, `sort_order`),
  CONSTRAINT `fk_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 8. projects 项目表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `outcomes` TEXT NULL,
  `cover_path` VARCHAR(255) NULL,
  `tech_stack` VARCHAR(255) NULL,
  `repo_url` VARCHAR(255) NULL,
  `demo_url` VARCHAR(255) NULL,
  `status` ENUM('planning','ongoing','completed','archived') NOT NULL DEFAULT 'ongoing',
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_projects_slug` (`slug`),
  KEY `idx_projects_team_public` (`team_id`, `is_public`, `status`),
  KEY `idx_projects_created_by` (`created_by`),
  KEY `idx_projects_featured` (`is_featured`, `sort_order`),
  CONSTRAINT `fk_projects_team`
    FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_projects_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_projects_dates`
    CHECK (`start_date` IS NULL OR `end_date` IS NULL OR `end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 9. project_members 项目成员关联表
--    成员记录通过 team_members 软状态保留，因此此处使用 RESTRICT，
--    避免删除成员时破坏项目历史。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_members` (
  `project_id` BIGINT UNSIGNED NOT NULL,
  `team_member_id` BIGINT UNSIGNED NOT NULL,
  `role_in_project` VARCHAR(80) NULL,
  `joined_at` DATE NOT NULL,
  `left_at` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`, `team_member_id`),
  KEY `idx_project_members_member` (`team_member_id`),
  CONSTRAINT `fk_project_members_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_members_member`
    FOREIGN KEY (`team_member_id`) REFERENCES `team_members` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_project_members_dates`
    CHECK (`left_at` IS NULL OR `left_at` >= `joined_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 10. articles 技术文章表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `articles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `author_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `cover_path` VARCHAR(255) NULL,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_articles_slug` (`slug`),
  KEY `idx_articles_status_published` (`status`, `published_at`),
  KEY `idx_articles_category_status` (`category_id`, `status`),
  KEY `idx_articles_author` (`author_id`),
  KEY `idx_articles_featured` (`is_featured`, `published_at`),
  CONSTRAINT `fk_articles_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_articles_author`
    FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 11. announcements 团队公告表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `publisher_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `summary` VARCHAR(500) NULL,
  `content` LONGTEXT NOT NULL,
  `type` ENUM('general','event','recruitment','system') NOT NULL DEFAULT 'general',
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcements_public` (`status`, `is_pinned`, `published_at`),
  KEY `idx_announcements_expires` (`expires_at`),
  KEY `idx_announcements_publisher` (`publisher_id`),
  CONSTRAINT `fk_announcements_publisher`
    FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_announcements_dates`
    CHECK (`published_at` IS NULL OR `expires_at` IS NULL OR `expires_at` >= `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 12. messages 访客留言表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_name` VARCHAR(50) NOT NULL,
  `visitor_email` VARCHAR(120) NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `approved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_status_created` (`status`, `created_at`),
  KEY `idx_messages_email` (`visitor_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 13. message_replies 留言回复表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `message_replies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` BIGINT UNSIGNED NOT NULL,
  `admin_user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_message_replies_message_status` (`message_id`, `status`),
  KEY `idx_message_replies_admin` (`admin_user_id`),
  CONSTRAINT `fk_message_replies_message`
    FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_replies_admin`
    FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 14. files 文件资源表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uploader_id` BIGINT UNSIGNED NOT NULL,
  `related_member_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(200) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(120) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `extension` VARCHAR(20) NOT NULL,
  `mime_type` VARCHAR(120) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL,
  `category` ENUM('team_assignment','personal_assignment','resource') NOT NULL,
  `description` VARCHAR(500) NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active','hidden') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_files_stored_name` (`stored_name`),
  KEY `idx_files_category_public` (`category`, `is_public`, `status`),
  KEY `idx_files_member` (`related_member_id`),
  KEY `idx_files_uploader` (`uploader_id`),
  KEY `idx_files_downloads` (`download_count`),
  CONSTRAINT `fk_files_uploader`
    FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_files_member`
    FOREIGN KEY (`related_member_id`) REFERENCES `team_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 15. file_downloads 文件下载记录表（补充表）
--    用于真实下载趋势与统计，区分于后台操作审计。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `file_downloads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_file_downloads_file_time` (`file_id`, `downloaded_at`),
  KEY `idx_file_downloads_time` (`downloaded_at`),
  KEY `idx_file_downloads_user` (`user_id`),
  CONSTRAINT `fk_file_downloads_file`
    FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_downloads_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------------------------------------
-- 16. activity_logs 后台操作日志表
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `actor_username` VARCHAR(50) NULL,
  `module` VARCHAR(50) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `target_type` VARCHAR(50) NULL,
  `target_id` BIGINT UNSIGNED NULL,
  `description` VARCHAR(500) NULL,
  `result` ENUM('success','failure') NOT NULL DEFAULT 'success',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_time` (`user_id`, `created_at`),
  KEY `idx_activity_logs_module_time` (`module`, `created_at`),
  KEY `idx_activity_logs_result` (`result`, `created_at`),
  KEY `idx_activity_logs_created` (`created_at`),
  CONSTRAINT `fk_activity_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 初始化数据：角色与权限
-- ============================================================
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `is_system`, `sort_order`)
VALUES
  (1, 'super_admin', '超级管理员', '拥有全部后台权限，可管理用户与角色', 1, 1),
  (2, 'editor', '内容编辑', '负责项目、文章、分类、公告、留言与文件管理', 1, 2),
  (3, 'member', '团队成员', '可登录后台并维护本人公开资料', 1, 3)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `is_system` = VALUES(`is_system`),
  `sort_order` = VALUES(`sort_order`);

INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `sort_order`)
VALUES
  (1, 'dashboard.view', '访问后台首页', 'dashboard', '访问后台仪表盘', 1),
  (2, 'profile.edit', '修改本人资料', 'profile', '维护本人公开成员资料', 2),
  (3, 'stats.view', '查看统计数据', 'stats', '查看后台统计页面和图表接口', 3),
  (4, 'team.edit', '编辑团队信息', 'team', '维护团队公开资料', 4),
  (5, 'member.manage', '管理团队成员', 'members', '新增、编辑、停用和排序成员关系', 5),
  (6, 'project.manage', '管理项目', 'projects', '维护项目及项目成员', 6),
  (7, 'article.manage', '管理文章', 'articles', '维护文章和发布状态', 7),
  (8, 'category.manage', '管理分类', 'categories', '维护文章分类', 8),
  (9, 'announcement.manage', '管理公告', 'announcements', '维护公告和置顶状态', 9),
  (10, 'message.moderate', '管理留言', 'messages', '审核留言和发布回复', 10),
  (11, 'file.manage', '管理文件', 'files', '上传、隐藏和删除文件资源', 11),
  (12, 'user.manage', '管理账号', 'users', '创建、编辑、停用后台账号', 12),
  (13, 'role.manage', '管理角色权限', 'roles', '维护角色及角色权限映射', 13),
  (14, 'activity.view', '查看操作日志', 'activity', '查看后台操作审计记录', 14)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `module` = VALUES(`module`),
  `description` = VALUES(`description`),
  `sort_order` = VALUES(`sort_order`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7),
  (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14),
  (2, 1), (2, 2), (2, 3), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10), (2, 11),
  (3, 1), (3, 2);

-- ============================================================
-- 初始化数据：默认账号
-- 账号：admin / 编辑演示账号 editor / 三个示例成员账号
-- 密码仅以 password_hash 保存，明文记录在本地 .env（不提交 Git）。
-- ============================================================
INSERT IGNORE INTO `users`
  (`id`, `username`, `password_hash`, `display_name`, `email`, `role_id`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 'admin', '$2y$10$sn.N8Ov7NyGnD4VhdIWDKuO9MUCgSHf4KyD4Lv9.QwcYPPdgA38HK', '系统管理员', 'admin@techhub.local', 1, 'active', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (2, 'editor', '$2y$10$1DgRt6oszk.EV7oqWfWJm..ZikTy4VTguyfneV.Bym1s20oGEhBty', '示例内容编辑', 'editor@techhub.local', 2, 'active', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (3, 'member_a', '$2y$10$GoXmN/DkH23WO3F32MBZvOUTi6i4PBmXT2fCXdthDpGuj5jM3EkEm', '示例成员A', 'member-a@techhub.local', 3, 'active', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (4, 'member_b', '$2y$10$ZuHP5FEzn194Dvd0jrmIWO1Xzt6hwYOz/a/Z3cijIP/B/jvgzxpNS', '示例成员B', 'member-b@techhub.local', 3, 'active', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (5, 'member_c', '$2y$10$RsaTABeP7h06HJYfeBcimecPBXoF3U4MtNctdpt8p2W2DR66x7N/a', '示例成员C', 'member-c@techhub.local', 3, 'active', '2025-11-01 09:00:00', '2025-11-01 09:00:00');

INSERT IGNORE INTO `member_profiles`
  (`id`, `user_id`, `bio`, `skills`, `avatar_path`, `public_email`, `created_at`, `updated_at`)
VALUES
  (1, 3, '示例后端方向成员，用于演示成员详情、项目参与和团队关系。', 'PHP, MySQL, Docker', 'assets/images/avatars/member-a.svg', 'member-a@example.com', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (2, 4, '示例前端方向成员，用于演示项目成员关联。', 'HTML, CSS, JavaScript', 'assets/images/avatars/member-b.svg', 'member-b@example.com', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (3, 5, '示例设计方向成员，用于演示团队展示。', 'UI/UX, Figma', 'assets/images/avatars/member-c.svg', NULL, '2025-11-01 09:00:00', '2025-11-01 09:00:00');

-- ============================================================
-- 初始化数据：示例团队与成员关系
-- 所有带“示例”的人名/项目/文章均为演示数据，不代表真实团队。
-- ============================================================
INSERT IGNORE INTO `teams`
  (`id`, `name`, `slug`, `short_name`, `slogan`, `description`, `research_areas`, `tech_stack`, `logo_path`, `founded_at`, `email`, `location`, `github_url`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 'TechHub 示例技术团队', 'techhub-demo', 'TechHub', '把想法做成可以运行的作品',
   '这是用于课程设计与功能演示的示例团队介绍。团队关注 Web 应用开发、数据库系统与前端交互，正式团队资料待团队提供后替换。',
   'Web 应用开发、数据库系统、前端交互设计', 'PHP 8.2、MySQL 8.0、Bootstrap 5、JavaScript',
   'assets/images/logo-techhub.svg', '2024-09-01',
   'team@example.com', '示例大学 · 示例实验室', 'https://github.com/west-ice-g/TechHub', 'active',
   '2025-11-01 09:00:00', '2025-11-01 09:00:00');

INSERT IGNORE INTO `team_members`
  (`id`, `team_id`, `user_id`, `position_title`, `responsibilities`, `joined_at`, `status`, `sort_order`, `created_at`, `updated_at`)
VALUES
  (1, 1, 3, '示例队长 / 后端', '项目规划、PHP MVC、数据库设计', '2025-03-01', 'active', 1, '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (2, 1, 4, '示例前端成员', '页面实现、交互与响应式布局', '2025-03-01', 'active', 2, '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (3, 1, 5, '示例设计成员', '视觉设计、内容排版与素材整理', '2025-03-01', 'active', 3, '2025-11-01 09:00:00', '2025-11-01 09:00:00');

-- ============================================================
-- 初始化数据：文章分类、项目、项目成员
-- 演示时间：示例项目/文章日期位于 2026 年 6–11 月，仅用于图表与列表演示。
-- ============================================================
INSERT IGNORE INTO `categories`
  (`id`, `parent_id`, `name`, `slug`, `description`, `sort_order`, `status`)
VALUES
  (1, NULL, '技术实践', 'tech-practice', '技术实践与项目经验', 1, 'enabled'),
  (3, NULL, '数据库设计', 'database-design', 'MySQL 与数据建模', 3, 'enabled');

INSERT IGNORE INTO `categories`
  (`id`, `parent_id`, `name`, `slug`, `description`, `sort_order`, `status`)
VALUES
  (2, 1, '前端开发', 'frontend', 'HTML、CSS、JavaScript 与界面实现', 2, 'enabled');

INSERT IGNORE INTO `projects`
  (`id`, `team_id`, `title`, `slug`, `summary`, `description`, `outcomes`, `cover_path`, `tech_stack`, `repo_url`, `status`, `is_public`, `is_featured`, `sort_order`, `start_date`, `end_date`, `created_by`, `created_at`, `updated_at`)
VALUES
  (1, 1, '示例项目：校园技术社区', 'campus-tech-community',
   '一个用于演示团队项目展示、成员关联和成果详情的示例项目。',
   '该项目为示例数据，用于验证前台项目列表、项目详情、成员关系和后台项目管理。正式项目内容待替换。',
   '完成团队主页、项目展示、成员关系与留言互动闭环，形成可运行的前后端原型。',
   'assets/images/projects/campus-community.svg',
   'PHP 8.2 / MySQL 8.0 / Bootstrap 5', 'https://github.com/west-ice-g/TechHub',
   'completed', 1, 1, 1, '2026-06-01', '2026-08-31', 1, '2026-06-02 09:00:00', '2026-11-01 09:00:00'),
  (2, 1, '示例项目：课程资料检索助手', 'course-resource-helper',
   '用于演示进行中项目、技术栈和项目成员关系的示例项目。',
   '该项目为示例数据，用于验证项目状态筛选和后台编辑。正式项目内容待替换。',
   '完成课程资料分类、关键词检索和结果列表原型，后续继续完善文件预览与权限控制。',
   'assets/images/projects/course-helper.svg',
   'PHP / PDO / JavaScript', NULL, 'ongoing', 1, 0, 2, '2026-09-01', NULL, 2, '2026-09-02 09:00:00', '2026-11-01 09:00:00');

INSERT IGNORE INTO `project_members`
  (`project_id`, `team_member_id`, `role_in_project`, `joined_at`, `created_at`)
VALUES
  (1, 1, '后端与数据库', '2026-06-01', '2026-11-01 09:00:00'),
  (1, 2, '前端实现', '2026-06-01', '2026-11-01 09:00:00'),
  (1, 3, '界面设计', '2026-06-01', '2026-11-01 09:00:00'),
  (2, 2, '前端与交互', '2026-09-01', '2026-11-01 09:00:00'),
  (2, 3, '视觉设计', '2026-09-01', '2026-11-01 09:00:00');

-- ============================================================
-- 初始化数据：文章、公告
-- ============================================================
INSERT IGNORE INTO `articles`
  (`id`, `category_id`, `author_id`, `title`, `slug`, `summary`, `content`, `cover_path`, `status`, `is_featured`, `view_count`, `published_at`, `created_at`, `updated_at`)
VALUES
  (1, 3, 1, '示例文章：从需求到数据库表设计', 'database-design-from-requirements',
   '以 TechHub 为例，说明如何从业务需求拆分实体、关系和约束。',
   '## 从需求到实体\n\nTechHub 的数据库围绕团队、成员、项目、文章、公告、留言和文件展开。\n\n## 关系设计\n\n- 团队与成员是多对多关系；\n- 项目与团队成员是多对多关系；\n- 留言与回复是一对多关系。\n\n> 本文是演示文章，正式内容由团队补充。',
   'assets/images/articles/article-database.svg',
   'published', 1, 12, '2026-08-20 10:00:00', '2026-08-20 09:30:00', '2026-08-20 10:00:00'),
  (2, 2, 2, '示例文章：原生 PHP MVC 的请求生命周期', 'php-mvc-request-lifecycle',
   '说明统一入口、路由、控制器、模型和视图如何协作。',
   '## 请求入口\n\n所有请求进入 public/index.php，然后交给 Router 分发。\n\n## 分层\n\nController 负责接收请求，Model 负责数据库访问，View 负责展示。\n\n> 本文是演示文章，正式内容由团队补充。',
   'assets/images/articles/article-mvc.svg',
   'published', 0, 8, '2026-10-15 14:00:00', '2026-10-15 13:30:00', '2026-10-15 14:00:00'),
  (3, 1, 2, '示例草稿：课程项目复盘', 'draft-project-retrospective',
   '这是一篇仅后台可见的草稿，用于验证文章状态过滤。',
   '草稿内容不会出现在前台。',
   NULL,
   'draft', 0, 0, NULL, '2026-11-10 09:00:00', '2026-11-10 09:00:00');

INSERT IGNORE INTO `announcements`
  (`id`, `publisher_id`, `title`, `summary`, `content`, `type`, `is_pinned`, `status`, `published_at`, `expires_at`, `created_at`, `updated_at`)
VALUES
  (1, 1, '示例公告：课程演示环境已初始化', 'TechHub 示例数据已写入数据库。',
   '这是系统初始化生成的示例公告，用于验证公告列表、置顶、详情和过期时间展示。正式公告待团队发布。',
   'system', 1, 'published', '2025-11-01 09:00:00', '2026-12-31 23:59:59', '2025-11-01 09:00:00', '2025-11-01 09:00:00'),
  (2, 2, '示例公告：项目成果征集', '欢迎成员补充项目与技术文章。',
   '这是示例公告，不代表真实活动安排。',
   'general', 0, 'published', '2025-11-05 09:00:00', NULL, '2025-11-05 09:00:00', '2025-11-05 09:00:00');

-- ============================================================
-- 初始化数据：留言、回复
-- ============================================================
INSERT IGNORE INTO `messages`
  (`id`, `visitor_name`, `visitor_email`, `content`, `status`, `ip_address`, `user_agent`, `approved_at`, `created_at`, `updated_at`)
VALUES
  (1, '示例访客', 'visitor@example.com', '请问团队作业和个人作业可以在哪里下载？',
   'approved', '127.0.0.1', 'seed-data', '2025-11-06 10:10:00', '2025-11-06 10:00:00', '2025-11-06 10:10:00'),
  (2, '待审核示例访客', 'pending@example.com', '这是一条待审核示例留言，用于验证审核流程。',
   'pending', '127.0.0.1', 'seed-data', NULL, '2025-11-06 11:00:00', '2025-11-06 11:00:00');

INSERT IGNORE INTO `message_replies`
  (`id`, `message_id`, `admin_user_id`, `content`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1, '可以在前台“资料下载”页面分别选择团队作业和个人作业分类。', 'visible',
   '2025-11-06 10:20:00', '2025-11-06 10:20:00');

-- ============================================================
-- 初始化数据：示例文件与下载记录
-- 文件实体位于 data/team 与 data/personal，不在 Web 根目录。
-- ============================================================
INSERT IGNORE INTO `files`
  (`id`, `uploader_id`, `related_member_id`, `title`, `original_name`, `stored_name`, `file_path`, `extension`, `mime_type`, `size_bytes`, `category`, `description`, `download_count`, `is_public`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 1, NULL, '示例团队作业', 'team-sample.txt', 'seed-team-sample.txt', 'data/team/team-sample.txt', 'txt', 'text/plain', 378, 'team_assignment',
   '课程演示用团队作业，正式资料待团队替换。', 1, 1, 'active', '2025-11-07 09:00:00', '2025-11-07 09:00:00'),
  (2, 1, 1, '示例个人作业', 'personal-sample.txt', 'seed-personal-sample.txt', 'data/personal/personal-sample.txt', 'txt', 'text/plain', 312, 'personal_assignment',
   '课程演示用个人作业，正式资料待实际组员替换。', 0, 1, 'active', '2025-11-07 09:00:00', '2025-11-07 09:00:00');

INSERT IGNORE INTO `file_downloads`
  (`id`, `file_id`, `user_id`, `ip_address`, `user_agent`, `downloaded_at`)
VALUES
  (1, 1, NULL, '127.0.0.1', 'seed-data', '2025-11-07 10:00:00');

-- ============================================================
-- 初始化数据：示例操作日志
-- ============================================================
INSERT IGNORE INTO `activity_logs`
  (`id`, `user_id`, `actor_username`, `module`, `action`, `target_type`, `target_id`, `description`, `ip_address`, `user_agent`, `created_at`)
VALUES
  (1, 1, 'admin', 'system', 'seed', NULL, NULL, '执行 install.sql 初始化示例数据', '127.0.0.1', 'install.sql', '2025-11-01 09:00:00');

-- 结束
