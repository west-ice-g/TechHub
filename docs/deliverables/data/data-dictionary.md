# 完整数据字典（从实际 MySQL 结构生成）

- 数据库：`techhub`
- MySQL：`8.0.19`
- 生成时间：2026-11-25T14:13:25+08:00
- 表数量：17
- 说明：以下字段类型、可空性、默认值、主键/索引和外键均读取自 `information_schema`；行数为生成时实际计数。
- 实际 SQL 未设置列注释，因此字典不生成空“说明”列；字段业务含义由表说明、字段名和上下文表达。

## 表清单

| 序号 | 表名 | 业务用途 | 实际行数 |
| --- | --- | --- | --- |
| 1 | `roles` | 后台角色定义，内置超级管理员、内容编辑、团队成员。 | 3 |
| 2 | `permissions` | 权限码定义，由初始化 SQL 提供，代码按权限码校验。 | 14 |
| 3 | `role_permissions` | 角色与权限多对多映射。 | 25 |
| 4 | `users` | 后台登录账号、角色、状态和登录信息。 | 5 |
| 5 | `member_profiles` | 与账号一对一的公开成员资料。 | 3 |
| 6 | `teams` | 团队实体、研究方向、技术栈、联系方式和展示状态。 | 1 |
| 7 | `team_members` | 团队与用户多对多成员关系及历史状态。 | 3 |
| 8 | `categories` | 文章分类，支持父子层级。 | 3 |
| 9 | `projects` | 项目资料、状态、发布、封面和成果。 | 2 |
| 10 | `project_members` | 项目与团队成员多对多关联及项目角色。 | 5 |
| 11 | `articles` | 技术文章、草稿/发布/归档和浏览量。 | 3 |
| 12 | `announcements` | 团队公告、类型、置顶和有效期。 | 2 |
| 13 | `messages` | 访客留言、审核状态和来源信息。 | 2 |
| 14 | `message_replies` | 管理员对留言的回复，可公开或隐藏。 | 1 |
| 15 | `files` | 团队作业、个人作业和通用资源文件。 | 2 |
| 16 | `file_downloads` | 真实文件下载流水。 | 3 |
| 17 | `activity_logs` | 后台操作审计，记录操作结果。 | 345 |

## 账号与权限

### 4.1 `roles`

后台角色定义，内置超级管理员、内容编辑、团队成员。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `code` | `varchar(50)` | NO | NULL | UNI |  |  |
| `name` | `varchar(50)` | NO | NULL |  |  |  |
| `description` | `varchar(255)` | YES | NULL |  |  |  |
| `is_system` | `tinyint(1)` | NO | 0 |  |  |  |
| `sort_order` | `int` | NO | 0 |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：无

**索引**：`PRIMARY`（唯一：id）；`uk_roles_code`（唯一：code）

**生成时实际行数**：3

### 4.2 `permissions`

权限码定义，由初始化 SQL 提供，代码按权限码校验。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `code` | `varchar(80)` | NO | NULL | UNI |  |  |
| `name` | `varchar(80)` | NO | NULL |  |  |  |
| `module` | `varchar(50)` | NO | NULL | MUL |  |  |
| `description` | `varchar(255)` | YES | NULL |  |  |  |
| `sort_order` | `int` | NO | 0 |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：无

**索引**：`idx_permissions_module`（普通：module）；`PRIMARY`（唯一：id）；`uk_permissions_code`（唯一：code）

**生成时实际行数**：14

### 4.3 `role_permissions`

角色与权限多对多映射。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `role_id` | `bigint unsigned` | NO | NULL | PRI |  |  |
| `permission_id` | `bigint unsigned` | NO | NULL | PRI |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |

**外键**：`permission_id` → `permissions.id`；`role_id` → `roles.id`

**索引**：`idx_role_permissions_permission`（普通：permission_id）；`PRIMARY`（唯一：role_id,permission_id）

**生成时实际行数**：25

### 4.4 `users`

后台登录账号、角色、状态和登录信息。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `username` | `varchar(50)` | NO | NULL | UNI |  |  |
| `password_hash` | `varchar(255)` | NO | NULL |  |  |  |
| `display_name` | `varchar(50)` | NO | NULL |  |  |  |
| `email` | `varchar(120)` | YES | NULL | UNI |  |  |
| `role_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `status` | `enum('active','disabled')` | NO | active | MUL |  |  |
| `last_login_at` | `datetime` | YES | NULL |  |  |  |
| `last_login_ip` | `varchar(45)` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`role_id` → `roles.id`

**索引**：`idx_users_role`（普通：role_id）；`idx_users_status`（普通：status）；`PRIMARY`（唯一：id）；`uk_users_email`（唯一：email）；`uk_users_username`（唯一：username）

**生成时实际行数**：5

### 4.5 `member_profiles`

与账号一对一的公开成员资料。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `user_id` | `bigint unsigned` | NO | NULL | UNI |  |  |
| `bio` | `text` | YES | NULL |  |  |  |
| `skills` | `varchar(255)` | YES | NULL |  |  |  |
| `avatar_path` | `varchar(255)` | YES | NULL |  |  |  |
| `public_email` | `varchar(120)` | YES | NULL |  |  |  |
| `github_url` | `varchar(255)` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`user_id` → `users.id`

**索引**：`PRIMARY`（唯一：id）；`uk_member_profiles_user`（唯一：user_id）

**生成时实际行数**：3

## 团队与项目

### 4.6 `teams`

团队实体、研究方向、技术栈、联系方式和展示状态。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `name` | `varchar(100)` | NO | NULL |  |  |  |
| `slug` | `varchar(100)` | NO | NULL | UNI |  |  |
| `short_name` | `varchar(50)` | YES | NULL |  |  |  |
| `slogan` | `varchar(200)` | YES | NULL |  |  |  |
| `description` | `text` | YES | NULL |  |  |  |
| `research_areas` | `varchar(255)` | YES | NULL |  |  |  |
| `tech_stack` | `varchar(255)` | YES | NULL |  |  |  |
| `logo_path` | `varchar(255)` | YES | NULL |  |  |  |
| `founded_at` | `date` | YES | NULL |  |  |  |
| `email` | `varchar(120)` | YES | NULL |  |  |  |
| `location` | `varchar(120)` | YES | NULL |  |  |  |
| `github_url` | `varchar(255)` | YES | NULL |  |  |  |
| `website_url` | `varchar(255)` | YES | NULL |  |  |  |
| `status` | `enum('active','archived')` | NO | active | MUL |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：无

**索引**：`idx_teams_status`（普通：status）；`PRIMARY`（唯一：id）；`uk_teams_slug`（唯一：slug）

**生成时实际行数**：1

### 4.7 `team_members`

团队与用户多对多成员关系及历史状态。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `team_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `user_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `position_title` | `varchar(80)` | YES | NULL |  |  |  |
| `responsibilities` | `varchar(255)` | YES | NULL |  |  |  |
| `joined_at` | `date` | NO | NULL |  |  |  |
| `left_at` | `date` | YES | NULL |  |  |  |
| `status` | `enum('active','alumni','removed')` | NO | active | MUL |  |  |
| `sort_order` | `int` | NO | 0 |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`team_id` → `teams.id`；`user_id` → `users.id`

**索引**：`idx_team_members_status_sort`（普通：status,sort_order）；`idx_team_members_user`（普通：user_id）；`PRIMARY`（唯一：id）；`uk_team_members_team_user`（唯一：team_id,user_id）

**生成时实际行数**：3

## 内容管理

### 4.8 `categories`

文章分类，支持父子层级。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `parent_id` | `bigint unsigned` | YES | NULL | MUL |  |  |
| `name` | `varchar(80)` | NO | NULL |  |  |  |
| `slug` | `varchar(100)` | NO | NULL | UNI |  |  |
| `description` | `varchar(255)` | YES | NULL |  |  |  |
| `sort_order` | `int` | NO | 0 |  |  |  |
| `status` | `enum('enabled','disabled')` | NO | enabled | MUL |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`parent_id` → `categories.id`

**索引**：`idx_categories_parent`（普通：parent_id）；`idx_categories_status_sort`（普通：status,sort_order）；`PRIMARY`（唯一：id）；`uk_categories_slug`（唯一：slug）

**生成时实际行数**：3

## 团队与项目

### 4.9 `projects`

项目资料、状态、发布、封面和成果。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `team_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `title` | `varchar(150)` | NO | NULL |  |  |  |
| `slug` | `varchar(160)` | NO | NULL | UNI |  |  |
| `summary` | `varchar(500)` | NO | NULL |  |  |  |
| `description` | `longtext` | NO | NULL |  |  |  |
| `outcomes` | `text` | YES | NULL |  |  |  |
| `cover_path` | `varchar(255)` | YES | NULL |  |  |  |
| `tech_stack` | `varchar(255)` | YES | NULL |  |  |  |
| `repo_url` | `varchar(255)` | YES | NULL |  |  |  |
| `demo_url` | `varchar(255)` | YES | NULL |  |  |  |
| `status` | `enum('planning','ongoing','completed','archived')` | NO | ongoing |  |  |  |
| `is_public` | `tinyint(1)` | NO | 1 |  |  |  |
| `is_featured` | `tinyint(1)` | NO | 0 | MUL |  |  |
| `sort_order` | `int` | NO | 0 |  |  |  |
| `start_date` | `date` | YES | NULL |  |  |  |
| `end_date` | `date` | YES | NULL |  |  |  |
| `created_by` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`created_by` → `users.id`；`team_id` → `teams.id`

**索引**：`idx_projects_created_by`（普通：created_by）；`idx_projects_featured`（普通：is_featured,sort_order）；`idx_projects_team_public`（普通：team_id,is_public,status）；`PRIMARY`（唯一：id）；`uk_projects_slug`（唯一：slug）

**生成时实际行数**：2

### 4.10 `project_members`

项目与团队成员多对多关联及项目角色。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `project_id` | `bigint unsigned` | NO | NULL | PRI |  |  |
| `team_member_id` | `bigint unsigned` | NO | NULL | PRI |  |  |
| `role_in_project` | `varchar(80)` | YES | NULL |  |  |  |
| `joined_at` | `date` | NO | NULL |  |  |  |
| `left_at` | `date` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |

**外键**：`team_member_id` → `team_members.id`；`project_id` → `projects.id`

**索引**：`idx_project_members_member`（普通：team_member_id）；`PRIMARY`（唯一：project_id,team_member_id）

**生成时实际行数**：5

## 内容管理

### 4.11 `articles`

技术文章、草稿/发布/归档和浏览量。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `category_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `author_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `title` | `varchar(200)` | NO | NULL |  |  |  |
| `slug` | `varchar(220)` | NO | NULL | UNI |  |  |
| `summary` | `varchar(500)` | NO | NULL |  |  |  |
| `content` | `longtext` | NO | NULL |  |  |  |
| `cover_path` | `varchar(255)` | YES | NULL |  |  |  |
| `status` | `enum('draft','published','archived')` | NO | draft | MUL |  |  |
| `is_featured` | `tinyint(1)` | NO | 0 | MUL |  |  |
| `view_count` | `int unsigned` | NO | 0 |  |  |  |
| `published_at` | `datetime` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`author_id` → `users.id`；`category_id` → `categories.id`

**索引**：`idx_articles_author`（普通：author_id）；`idx_articles_category_status`（普通：category_id,status）；`idx_articles_featured`（普通：is_featured,published_at）；`idx_articles_status_published`（普通：status,published_at）；`PRIMARY`（唯一：id）；`uk_articles_slug`（唯一：slug）

**生成时实际行数**：3

### 4.12 `announcements`

团队公告、类型、置顶和有效期。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `publisher_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `title` | `varchar(200)` | NO | NULL |  |  |  |
| `summary` | `varchar(500)` | YES | NULL |  |  |  |
| `content` | `longtext` | NO | NULL |  |  |  |
| `type` | `enum('general','event','recruitment','system')` | NO | general |  |  |  |
| `is_pinned` | `tinyint(1)` | NO | 0 |  |  |  |
| `status` | `enum('draft','published','archived')` | NO | draft | MUL |  |  |
| `published_at` | `datetime` | YES | NULL |  |  |  |
| `expires_at` | `datetime` | YES | NULL | MUL |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`publisher_id` → `users.id`

**索引**：`idx_announcements_expires`（普通：expires_at）；`idx_announcements_public`（普通：status,is_pinned,published_at）；`idx_announcements_publisher`（普通：publisher_id）；`PRIMARY`（唯一：id）

**生成时实际行数**：2

## 留言与文件

### 4.13 `messages`

访客留言、审核状态和来源信息。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `visitor_name` | `varchar(50)` | NO | NULL |  |  |  |
| `visitor_email` | `varchar(120)` | YES | NULL | MUL |  |  |
| `content` | `text` | NO | NULL |  |  |  |
| `status` | `enum('pending','approved','rejected')` | NO | pending | MUL |  |  |
| `ip_address` | `varchar(45)` | YES | NULL |  |  |  |
| `user_agent` | `varchar(500)` | YES | NULL |  |  |  |
| `approved_at` | `datetime` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：无

**索引**：`idx_messages_email`（普通：visitor_email）；`idx_messages_status_created`（普通：status,created_at）；`PRIMARY`（唯一：id）

**生成时实际行数**：2

### 4.14 `message_replies`

管理员对留言的回复，可公开或隐藏。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `message_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `admin_user_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `content` | `text` | NO | NULL |  |  |  |
| `status` | `enum('visible','hidden')` | NO | visible |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`admin_user_id` → `users.id`；`message_id` → `messages.id`

**索引**：`idx_message_replies_admin`（普通：admin_user_id）；`idx_message_replies_message_status`（普通：message_id,status）；`PRIMARY`（唯一：id）

**生成时实际行数**：1

### 4.15 `files`

团队作业、个人作业和通用资源文件。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `uploader_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `related_member_id` | `bigint unsigned` | YES | NULL | MUL |  |  |
| `title` | `varchar(200)` | NO | NULL |  |  |  |
| `original_name` | `varchar(255)` | NO | NULL |  |  |  |
| `stored_name` | `varchar(120)` | NO | NULL | UNI |  |  |
| `file_path` | `varchar(255)` | NO | NULL |  |  |  |
| `extension` | `varchar(20)` | NO | NULL |  |  |  |
| `mime_type` | `varchar(120)` | NO | NULL |  |  |  |
| `size_bytes` | `bigint unsigned` | NO | NULL |  |  |  |
| `category` | `enum('team_assignment','personal_assignment','resource')` | NO | NULL | MUL |  |  |
| `description` | `varchar(500)` | YES | NULL |  |  |  |
| `download_count` | `int unsigned` | NO | 0 | MUL |  |  |
| `is_public` | `tinyint(1)` | NO | 1 |  |  |  |
| `status` | `enum('active','hidden')` | NO | active |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED |  |
| `updated_at` | `datetime` | NO | CURRENT_TIMESTAMP |  | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |  |

**外键**：`related_member_id` → `team_members.id`；`uploader_id` → `users.id`

**索引**：`idx_files_category_public`（普通：category,is_public,status）；`idx_files_downloads`（普通：download_count）；`idx_files_member`（普通：related_member_id）；`idx_files_uploader`（普通：uploader_id）；`PRIMARY`（唯一：id）；`uk_files_stored_name`（唯一：stored_name）

**生成时实际行数**：2

### 4.16 `file_downloads`

真实文件下载流水。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `file_id` | `bigint unsigned` | NO | NULL | MUL |  |  |
| `user_id` | `bigint unsigned` | YES | NULL | MUL |  |  |
| `ip_address` | `varchar(45)` | YES | NULL |  |  |  |
| `user_agent` | `varchar(500)` | YES | NULL |  |  |  |
| `downloaded_at` | `datetime` | NO | CURRENT_TIMESTAMP | MUL | DEFAULT_GENERATED |  |

**外键**：`file_id` → `files.id`；`user_id` → `users.id`

**索引**：`idx_file_downloads_file_time`（普通：file_id,downloaded_at）；`idx_file_downloads_time`（普通：downloaded_at）；`idx_file_downloads_user`（普通：user_id）；`PRIMARY`（唯一：id）

**生成时实际行数**：3

## 审计

### 4.17 `activity_logs`

后台操作审计，记录操作结果。

| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | NULL | PRI | auto_increment |  |
| `user_id` | `bigint unsigned` | YES | NULL | MUL |  |  |
| `actor_username` | `varchar(50)` | YES | NULL |  |  |  |
| `module` | `varchar(50)` | NO | NULL | MUL |  |  |
| `action` | `varchar(50)` | NO | NULL |  |  |  |
| `target_type` | `varchar(50)` | YES | NULL |  |  |  |
| `target_id` | `bigint unsigned` | YES | NULL |  |  |  |
| `description` | `varchar(500)` | YES | NULL |  |  |  |
| `result` | `enum('success','failure')` | NO | success | MUL |  |  |
| `ip_address` | `varchar(45)` | YES | NULL |  |  |  |
| `user_agent` | `varchar(500)` | YES | NULL |  |  |  |
| `created_at` | `datetime` | NO | CURRENT_TIMESTAMP | MUL | DEFAULT_GENERATED |  |

**外键**：`user_id` → `users.id`

**索引**：`idx_activity_logs_created`（普通：created_at）；`idx_activity_logs_module_time`（普通：module,created_at）；`idx_activity_logs_result`（普通：result,created_at）；`idx_activity_logs_user_time`（普通：user_id,created_at）；`PRIMARY`（唯一：id）

**生成时实际行数**：345
