# TechHub 功能清单与模块说明

| 项目 | 内容 |
| --- | --- |
| 文档版本 | v0.3（后台管理完成后修订） |
| 编写日期 | 2026-11-25 |
| 适用范围 | 需求评审、数据库设计、MVC 文件划分、任务分派、测试用例设计 |
| 当前状态 | 数据库、MVC、前台完整页面、后台 11 个业务模块、RBAC、上传、审核、日志和 ECharts 已实现；Apache 部署、Git 与课程材料未完成 |

> 本文件细化 `requirements.md` 中的 12 个功能模块，明确每个模块的使用角色、前后台能力、数据表、预期 Model/Controller/View 和验收要点。最终文件命名和任务归属需在团队信息确认后调整，但不得减少模块。

## 1. 优先级与状态说明

| 标记 | 含义 |
| --- | --- |
| P0 | 课程硬性要求，必须完成并可演示 |
| P1 | 可选增强，不得挤占 P0 |
| 待确认 | 依赖团队、环境或课程材料的事实，当前不自行编造 |

所有 12 个模块均为 P0。数据库、认证与 MVC 骨架、前台页面和后台 11 个业务模块已实施；ECharts 已接入真实聚合接口。仍不能把整个项目表述为最终验收完成：Apache PHP 8.2 部署、Git 提交、真实资料和六份课程材料尚未完成。

## 2. 模块总览

| 模块编号 | 模块名称 | 主要使用角色 | 主要数据表 | 对应需求 |
| --- | --- | --- | --- | --- |
| M01 | 用户与身份认证 | 团队成员、内容编辑、超级管理员 | `users`, `roles`, `activity_logs` | FR-AUTH-* |
| M02 | 团队信息管理 | 访客、超级管理员 | `teams`, `team_members`, `activity_logs` | FR-TEAM-* |
| M03 | 团队成员管理 | 访客、团队成员、超级管理员 | `team_members`, `users`, `member_profiles`, `project_members` | FR-MEMBER-* |
| M04 | 项目成果管理 | 访客、内容编辑、超级管理员 | `projects`, `project_members`, `team_members` | FR-PROJECT-* |
| M05 | 技术文章管理 | 访客、内容编辑、超级管理员 | `articles`, `categories`, `users` | FR-ARTICLE-* |
| M06 | 文章分类管理 | 访客、内容编辑、超级管理员 | `categories`, `articles` | FR-CATEGORY-* |
| M07 | 团队公告管理 | 访客、内容编辑、超级管理员 | `announcements`, `users` | FR-ANNOUNCEMENT-* |
| M08 | 留言与回复 | 访客、内容编辑、超级管理员 | `messages`, `message_replies`, `users` | FR-MESSAGE-* |
| M09 | 文件资源管理 | 访客、内容编辑、超级管理员 | `files`, `file_downloads`, `team_members` | FR-FILE-* |
| M10 | 后台数据统计 | 内容编辑、超级管理员 | 各业务表（实时聚合），含 `categories`、`file_downloads` | FR-STAT-* |
| M11 | 角色与权限管理 | 超级管理员 | `roles`, `permissions`, `role_permissions`, `users` | FR-RBAC-* |
| M12 | 操作日志 | 超级管理员 | `activity_logs`, `users` | FR-LOG-* |

### 2.1 实际实现状态

| 模块 | 状态 | 说明 |
| --- | --- | --- |
| M01 用户与身份认证 | 部分完成 | 前后台登录、退出、限流、Session、目标回跳已实现；修改本人密码未实现 |
| M02 团队信息管理 | 完成 | 团队资料、研究方向、技术栈、Logo、展示状态 |
| M03 团队成员管理 | 完成 | 关系、职位、介绍、头像、离队、引用保护 |
| M04 项目成果管理 | 完成 | CRUD、封面、状态、成员关联、上下线 |
| M05 技术文章管理 | 完成 | CRUD、封面、草稿/发布/归档、前台搜索 |
| M06 文章分类管理 | 完成 | CRUD、父子关系、循环检测、启停、删除保护 |
| M07 团队公告管理 | 完成 | CRUD、类型、置顶、有效期、发布/下线 |
| M08 留言与回复 | 完成 | 提交、审核、通过/拒绝、回复、删除 |
| M09 文件资源管理 | 完成 | 上传、编辑、分类、公开/隐藏、下载、物理删除 |
| M10 后台数据统计 | 完成 | 统计卡片 + 4 张 ECharts，真实 SQL 聚合 |
| M11 角色与权限管理 | 完成 | 角色 CRUD、权限矩阵、账号 CRUD、角色分配、超级管理员保护 |
| M12 操作日志 | 完成 | 操作人、模块、动作、目标、结果、IP、UA、筛选 |

## 3. 权限码与角色映射

### 3.1 权限码清单

| 权限码 | 名称 | 适用范围 |
| --- | --- | --- |
| `dashboard.view` | 访问后台首页 | 所有后台角色 |
| `profile.edit` | 修改本人公开档案 | 有成员档案的后台角色 |
| `stats.view` | 查看统计数据和图表接口 | 内容编辑、超级管理员 |
| `team.edit` | 编辑团队信息 | 超级管理员 |
| `member.manage` | 管理成员档案 | 超级管理员 |
| `project.manage` | 管理项目及成员关联 | 内容编辑、超级管理员 |
| `article.manage` | 管理文章 | 内容编辑、超级管理员 |
| `category.manage` | 管理文章分类 | 内容编辑、超级管理员 |
| `announcement.manage` | 管理公告 | 内容编辑、超级管理员 |
| `message.moderate` | 审核、回复、删除留言 | 内容编辑、超级管理员 |
| `file.manage` | 上传、隐藏、删除文件 | 内容编辑、超级管理员 |
| `user.manage` | 管理后台账号 | 超级管理员 |
| `role.manage` | 管理角色与权限 | 超级管理员 |
| `activity.view` | 查看操作日志 | 超级管理员 |

### 3.2 角色预设

| 角色代码 | 角色名称 | 权限 |
| --- | --- | --- |
| `member` | 团队成员 | `dashboard.view`, `profile.edit` |
| `editor` | 内容编辑 | `dashboard.view`, `profile.edit`, `stats.view`, `project.manage`, `article.manage`, `category.manage`, `announcement.manage`, `message.moderate`, `file.manage` |
| `super_admin` | 超级管理员 | 全部权限 |

> `profile.edit` 只允许修改与当前账号关联的 `team_members` 记录中的安全字段；没有关联档案时不得凭空创建成员。

## 4. 模块详细说明

### 4.1 M01 用户与身份认证

**目标**：确保后台只被真实、有效、已授权的账号使用。

| 维度 | 功能 |
| --- | --- |
| 前台 | 不开放注册；可在页脚提供后台登录入口。登录页展示统一品牌与错误提示。 |
| 后台 | 用户名/密码登录、退出、修改密码、会话失效处理；无权限页面 403。 |
| 主要操作 | 登录、退出、修改本人密码、失效会话清理 |
| 主要 Model | `User`（按用户名查询、更新密码、更新最后登录信息、状态判断） |
| 主要 Controller | `AuthController`（登录页、登录提交、退出、密码页） |
| 主要 View | `auth/login.php`, `auth/password.php` |
| 数据表 | `users`, `roles`, `permissions`, `role_permissions`, `activity_logs` |
| 安全规则 | `password_hash()`/`password_verify()`；登录后 `session_regenerate_id()`；统一错误文案；POST 退出 + CSRF；停用账号拒绝登录 |
| 验收 | 正确账号可登录；错误密码/停用账号被拒绝；退出后受保护页面跳登录；普通成员无法访问用户管理。 |

**边界说明**：账号管理（创建、改角色、停用、重置密码）由 M11 负责，M01 只负责认证过程。

### 4.2 M02 团队信息管理

**目标**：维护全站唯一的团队公开资料。

| 维度 | 功能 |
| --- | --- |
| 前台 | 首页 Hero 区、团队页展示名称、口号、简介、Logo、成立时间、公开联系方式、GitHub/网站链接。 |
| 后台 | 超级管理员编辑单条团队资料，上传/更换 Logo。 |
| 主要操作 | 查看、编辑、Logo 上传 |
| 主要 Model | `Team` |
| 主要 Controller | `HomeController`（首页聚合）、`TeamController`（团队页）、`Admin\TeamController`（后台） |
| 主要 View | `home/index.php`, `team/index.php`, `admin/team/edit.php` |
| 数据表 | `teams`, `team_members`, `activity_logs` |
| 规则 | 单团队、`id = 1`；所有 URL 校验协议；更新人记录在 `updated_by`；不存在记录时由初始化 SQL 写入占位记录。 |
| 验收 | 修改后前台立即显示；数据库 `updated_by`/`updated_at` 变化；非法 URL 被拒绝。 |

### 4.3 M03 团队成员管理

**目标**：公开展示团队成员，并让成员维护自己的公开档案。

| 维度 | 功能 |
| --- | --- |
| 前台 | 成员列表（支持在队/离队标签）、成员详情、成员参与项目。隐藏成员不显示。 |
| 后台 | 超级管理员查看全部成员、新增、编辑、排序、隐藏、关联/解除后台账号；团队成员编辑本人有限字段。 |
| 主要操作 | 列表、详情、创建、编辑、隐藏、排序、账号关联、本人资料编辑 |
| 主要 Model | `TeamMember`（列表、详情、关联项目、更新本人字段） |
| 主要 Controller | `MemberController`（前台）、`Admin\MemberController`（管理）、`Admin\ProfileController`（本人资料） |
| 主要 View | `member/index.php`, `member/show.php`, `admin/member/index.php`, `admin/member/form.php`, `admin/profile/edit.php` |
| 数据表 | `team_members`, `users`, `member_profiles`, `projects`, `project_members`, `activity_logs` |
| 规则 | 一个 `user_id` 最多关联一个成员；`user_id` 可为空；成员状态 `active/alumni/hidden`；排序值仅管理员可改；公开邮箱需主动填写。 |
| 验收 | 后台新增成员后前台出现；隐藏后前台 404/不出现；成员不能编辑他人或管理字段；关联项目正确展示。 |

### 4.4 M04 项目成果管理

**目标**：展示团队项目成果，并建立项目—成员多对多关系。

| 维度 | 功能 |
| --- | --- |
| 前台 | 项目列表、状态筛选、关键词筛选、分页；项目详情、封面、技术栈、仓库/演示链接、参与成员。 |
| 后台 | 内容编辑/超级管理员新增、编辑、隐藏、删除、排序、维护参与成员。 |
| 主要操作 | 列表、筛选、详情、新增、编辑、成员关联、隐藏/删除 |
| 主要 Model | `Project`, `ProjectMember`, `TeamMember`（选择成员） |
| 主要 Controller | `ProjectController`（前台）、`Admin\ProjectController`（后台） |
| 主要 View | `project/index.php`, `project/show.php`, `admin/project/index.php`, `admin/project/form.php` |
| 数据表 | `projects`, `project_members`, `team_members`, `users`, `activity_logs` |
| 规则 | 仅 `is_public = 1` 的项目在前台可见；项目成员更新使用事务；删除项目级联清理关联，但不删除成员；状态为 planning/ongoing/completed/archived。 |
| 验收 | 新增项目及关联成员后前后台均正确；删除关联不产生孤儿记录；无权限角色无法进入后台项目管理。 |

### 4.5 M05 技术文章管理

**目标**：建立可发布、分类、检索的技术内容体系。

| 维度 | 功能 |
| --- | --- |
| 前台 | 已发布文章列表、关键词搜索、分类筛选、分页；文章详情、作者、分类、发布时间、浏览量、封面。 |
| 后台 | 内容编辑/超级管理员新建、编辑、保存草稿、发布、归档、删除。 |
| 主要操作 | 列表、搜索、详情、创建、编辑、状态切换、删除 |
| 主要 Model | `Article`, `Category` |
| 主要 Controller | `ArticleController`（前台）、`Admin\ArticleController`（后台） |
| 主要 View | `article/index.php`, `article/show.php`, `admin/article/index.php`, `admin/article/form.php` |
| 数据表 | `articles`, `categories`, `users`, `activity_logs` |
| 规则 | 只展示 `status = published` 的文章；草稿/归档不在前台；详情更新 `view_count`；`slug` 唯一；正文使用受控 Markdown 或安全换行，不执行原始 HTML；作者取当前登录用户。 |
| 验收 | 草稿不可在前台检索；发布后出现；打开详情后数据库浏览量增加；搜索结果与数据库一致。 |

### 4.6 M06 文章分类管理

**目标**：维护文章分类树并支撑前台筛选。

| 维度 | 功能 |
| --- | --- |
| 前台 | 文章列表按分类筛选，仅显示启用分类和已发布文章。 |
| 后台 | 内容编辑/超级管理员新增、编辑、排序、启用/停用、删除分类。 |
| 主要操作 | 分类列表、新增、编辑、排序、状态切换、删除 |
| 主要 Model | `Category` |
| 主要 Controller | `Admin\CategoryController`（后台；前台筛选由 `ArticleController` 处理） |
| 主要 View | `admin/category/index.php`, `admin/category/form.php` |
| 数据表 | `categories`, `articles`, `activity_logs` |
| 规则 | 最多两级；禁止自引用/循环；slug 唯一；有关联文章时禁止删除；停用分类不在前台显示。 |
| 验收 | 分类树正确显示；有关联文章删除被拒绝且提示明确；前台分类筛选结果只含已发布文章。 |

### 4.7 M07 团队公告管理

**目标**：发布有时效性的团队通知。

| 维度 | 功能 |
| --- | --- |
| 前台 | 公告列表（置顶优先、未过期）、公告详情；首页展示最新/置顶公告。 |
| 后台 | 内容编辑/超级管理员新建、编辑、发布、归档、删除、置顶、设置过期时间。 |
| 主要操作 | 列表、详情、创建、编辑、状态切换、置顶、删除 |
| 主要 Model | `Announcement` |
| 主要 Controller | `AnnouncementController`（前台）、`Admin\AnnouncementController`（后台） |
| 主要 View | `announcement/index.php`, `announcement/show.php`, `admin/announcement/index.php`, `admin/announcement/form.php` |
| 数据表 | `announcements`, `users`, `activity_logs` |
| 规则 | 仅已发布且未过期公告进入公开列表；置顶排序优先；过期时间不得早于发布时间；发布人自动记录。 |
| 验收 | 置顶公告排在前；过期公告从前台列表消失；草稿不可被公开访问。 |

### 4.8 M08 留言与回复

**目标**：提供访客互动渠道，同时防止垃圾留言和直接公开未审核内容。

| 维度 | 功能 |
| --- | --- |
| 前台 | 留言表单（昵称、邮箱、内容）、已审核留言展示、管理员回复展示；不展示邮箱。 |
| 后台 | 待审核列表、通过/拒绝/删除、回复留言、按状态筛选。 |
| 主要操作 | 提交留言、审核、拒绝、回复、删除 |
| 主要 Model | `Message`（顶级留言、回复、状态统计） |
| 主要 Controller | `MessageController`（前台）、`Admin\MessageController`（后台） |
| 主要 View | `message/index.php`, `admin/message/index.php`, `admin/message/show.php` |
| 数据表 | `messages`, `message_replies`, `users`, `activity_logs` |
| 规则 | 新留言 `pending`；前台只展示 `approved`；管理员回复写入独立的 `message_replies` 表并通过 `message_id` 关联；CSRF、长度、频率限制；输出 HTML 转义。 |
| 验收 | 提交后前台不显示；审核通过后显示；回复正确关联；访客邮箱不公开；恶意 HTML 不执行。 |

### 4.9 M09 文件资源管理

**目标**：管理课程资料与团队文件，提供课程要求的团队/个人作业下载链接。

| 维度 | 功能 |
| --- | --- |
| 前台 | “资料下载”页：团队作业、个人作业、其他资源三个分区；文件信息、大小、上传时间、下载次数；受控下载。 |
| 后台 | 上传、编辑元数据、隐藏、删除、查看下载记录；关联个人作业所属成员。 |
| 主要操作 | 上传、编辑、隐藏/删除、公开访问、下载、计数、日志记录 |
| 主要 Model | `FileResource`, `FileDownload` |
| 主要 Controller | `FileController`（前台/下载）、`Admin\FileController`（后台） |
| 主要 View | `file/index.php`, `admin/file/index.php`, `admin/file/form.php`, `admin/file/downloads.php` |
| 数据表 | `files`, `file_downloads`, `team_members`, `users`, `activity_logs` |
| 规则 | 扩展名白名单、真实 MIME 检测、大小限制、随机文件名、存储于 Web 根目录之外；只允许公开且启用文件下载；下载流式输出；下载计数与日志写入数据库。 |
| 验收 | 团队作业和个人作业入口明显可点击；`.php` 或其他危险文件无法上传；下载后 `download_count` 和 `file_downloads` 增加；隐藏文件不可下载。 |

**课程硬性链接方案**：

- 主导航或页脚固定展示“资料下载”；
- `/downloads` 页面顶部提供两个固定入口：
  - “团队作业下载” → `/downloads?category=team_assignment`
  - “个人作业下载” → `/downloads?category=personal_assignment`
- 每个文件提供 `/downloads/{id}/file` 受控链接；
- 最终演示前由管理员真实上传至少一份团队作业和一份个人作业（内容待团队提供，不伪造）。

### 4.10 M10 后台数据统计

**目标**：用真实数据库聚合结果驱动 ECharts 图表。

| 维度 | 功能 |
| --- | --- |
| 前台 | 无 |
| 后台 | 统计卡片与 4 张图表：项目状态、近 6 个月文章发布、近 6 个月项目新增、成员参与项目数。 |
| 主要操作 | 查看统计页、读取统计 JSON |
| 主要 Model | `AdminStats`（只读聚合查询） |
| 主要 Controller | `Admin\DashboardController`（页面与 JSON 接口） |
| 主要 View/JS | `admin/dashboard/index.php`, `public/assets/js/admin-charts.js` |
| 数据表 | `projects`, `articles`, `messages`, `files`, `team_members`, `project_members`, `users` |
| 规则 | 所有数值来自 SQL `COUNT`/`GROUP BY`；不得前端硬编码；空数据如实显示 0；接口要求 `stats.view`；无权限 403。 |
| 验收 | 数据库内容变化后统计 API 和图表同步变化；普通成员访问统计接口返回 403；浏览器实测 4 个 canvas。 |

**实际图表与数据源**：

| 图表 | 类型 | 聚合来源 |
| --- | --- | --- |
| 核心指标卡片 | 数字卡片 | 各表 `COUNT(*)`、`SUM(files.download_count)` |
| 项目状态分布 | 饼图 | `projects.status` 分组 |
| 文章发布趋势 | 折线图 | 最近 6 个月 `articles.published_at` |
| 项目数量统计 | 柱状图 | 最近 6 个月 `projects.created_at` |
| 成员参与项目数量 | 横向条形图 | `team_members LEFT JOIN project_members` |

### 4.11 M11 角色与权限管理

**目标**：实现可维护的 RBAC，并管理后台账号。

| 维度 | 功能 |
| --- | --- |
| 前台 | 无 |
| 后台 | 角色列表/新建/编辑；为角色勾选权限；账号列表/创建/编辑/分配角色/停用/重置密码。 |
| 主要操作 | 角色 CRUD、权限分配、账号 CRUD、状态切换、密码重置 |
| 主要 Model | `Role`, `Permission`, `User` |
| 主要 Controller | `Admin\RoleController`, `Admin\UserController` |
| 主要 View | `admin/roles/index.php`, `admin/roles/form.php`, `admin/users/index.php`, `admin/users/form.php` |
| 数据表 | `roles`, `permissions`, `role_permissions`, `users`, `activity_logs` |
| 规则 | 内置角色不可删除；权限定义不可随意新增；禁止删除/停用当前最后一名超级管理员；角色权限保存使用事务；所有变更写日志；不能通过参数越权。 |
| 验收 | 新角色权限生效；移除权限后菜单与接口均拒绝；普通编辑无法访问角色管理；不能删除超级管理员。 |

### 4.12 M12 操作日志

**目标**：为后台关键操作提供可追溯记录。

| 维度 | 功能 |
| --- | --- |
| 前台 | 无 |
| 后台 | 日志列表、按用户/模块/动作/时间/关键词筛选、分页查看。 |
| 主要操作 | 自动写入日志、筛选查看 |
| 主要 Model | `ActivityLog` |
| 主要 Controller | `Admin\ActivityController` |
| 主要 View | `admin/activity/index.php` |
| 数据表 | `activity_logs`, `users` |
| 规则 | 记录操作人快照、动作、目标、IP、UA、时间；追加型，不提供普通编辑；不记录密码/完整敏感内容；只有 `activity.view` 可访问。 |
| 验收 | 新增文章、修改权限、上传文件、审核留言后日志可查；筛选条件真实生效；普通成员无法访问日志页。 |

## 5. 前台页面清单

| 路由 | 页面 | 数据来源 | 权限 |
| --- | --- | --- | --- |
| `GET /` | 首页 | 团队、精选项目、最新文章、置顶公告、下载入口 | 公开 |
| `GET /team` | 团队介绍 | `teams`, `team_members`, `member_profiles` | 公开 |
| `GET /members` | 成员列表 | `team_members`（可见状态） | 公开 |
| `GET /members/{id}` | 成员详情 | 成员、参与项目 | 公开 |
| `GET /projects` | 项目列表 | `projects`（可见），筛选/分页 | 公开 |
| `GET /projects/{slug}` | 项目详情 | 项目、项目成员 | 公开 |
| `GET /articles` | 文章列表 | 已发布文章、分类，搜索/筛选/分页 | 公开 |
| `GET /articles/{slug}` | 文章详情 | 文章、作者、分类，更新浏览量 | 公开 |
| `GET /announcements` | 公告列表 | 已发布且未过期公告 | 公开 |
| `GET /announcements/{id}` | 公告详情 | 公告、发布人 | 公开 |
| `GET /messages` | 留言板 | 已审核留言及回复 | 公开 |
| `POST /messages` | 提交留言 | 写入 `messages` | 公开 + CSRF |
| `GET /downloads` | 资料下载 | 公开文件，支持分类筛选 | 公开 |
| `GET /downloads/{id}/file` | 文件下载 | 校验后流式输出、计数、日志 | 公开文件 |
| `GET /search` | 全站搜索（可选） | 文章/项目/公告 | 公开 |
| `GET /admin/login` | 后台登录 | 账号校验 | 公开 |
| 404/403 | 错误页 | 无 | 视情况 |

**需特别保留的课程演示入口**：

1. 页脚或导航中的“资料下载”；
2. 资料下载页的“团队作业”和“个人作业”两个筛选入口；
3. 后台统计图表页；
4. 操作日志页；
5. 登录失败与权限不足演示路径。

## 6. 后台菜单与路由规划

| 菜单 | 主要路由（相对路径） | 所需权限 |
| --- | --- | --- |
| 仪表盘/统计 | `GET /admin`, `GET /admin/api/stats` | `dashboard.view` / `stats.view` |
| 团队信息 | `GET /admin/team`, `POST /admin/team` | `team.edit` |
| 成员管理 | `GET /admin/members` 及创建/编辑/删除路由 | `member.manage` |
| 项目管理 | `/admin/projects` 及创建/编辑/删除 | `project.manage` |
| 文章管理 | `/admin/articles` 及创建/编辑/删除/状态 | `article.manage` |
| 分类管理 | `/admin/categories` 及 CRUD | `category.manage` |
| 公告管理 | `/admin/announcements` 及 CRUD | `announcement.manage` |
| 留言管理 | `/admin/messages` 及审核/回复/删除 | `message.moderate` |
| 文件管理 | `/admin/files` 及上传/隐藏/删除/下载记录 | `file.manage` |
| 账号管理 | `/admin/users` 及创建/编辑/停用/重置密码 | `user.manage` |
| 角色权限 | `/admin/roles` 及角色/权限分配 | `role.manage` |
| 操作日志 | `GET /admin/activity` | `activity.view` |
| 退出 | `POST /admin/logout` | 登录 + CSRF |

**路由约束**：

- 所有删除动作使用 `POST`，不得用 `GET` 直接删除；
- 所有后台路由在路由表或中间件中声明权限码；
- 资源 ID 必须校验存在性和类型；
- 列表筛选、分页参数必须有边界限制；
- 对用户输入的路由参数进行严格匹配，避免目录穿越或异常对象注入。

## 7. 跨模块业务规则

### 7.1 可见性与状态

1. 前台默认只显示已发布/可见/有效的数据：
   - 项目：`is_public = 1`；
   - 文章：`status = 'published'`；
   - 公告：`status = 'published'` 且 `expires_at IS NULL OR expires_at > NOW()`；
   - 成员：`status IN ('active','alumni')`；`hidden` 状态不展示；
   - 文件：`status = 'active'` 且 `is_public = 1`；
   - 留言：`status = 'approved'`。
2. 后台列表可以看到草稿、隐藏和待审核数据，但需权限。
3. 状态切换必须经过数据库更新，不能仅前端隐藏。
4. 404/403 页面不泄露后台数据详情。

### 7.2 删除策略

1. 有业务引用的角色、分类、用户等默认不可物理删除或先解除引用。
2. 项目与成员关联、角色权限关联通过事务和级联表维护。
3. 文件删除需同时处理数据库元数据与物理文件；失败时保留可追踪错误。
4. 操作日志不提供普通删除入口。
5. 删除按钮必须有二次确认和 CSRF。

### 7.3 搜索与分页

- 所有列表使用服务端分页，默认 10～15 条/页；
- 搜索参数统一长度限制和空值处理；
- 对 `LIKE` 查询转义 `%`、`_`；
- 排序字段由白名单枚举，不能将用户输入直接拼接到 `ORDER BY`；
- 搜索无结果时显示空状态，不伪造示例结果。

### 7.4 表单与反馈

- 所有写操作使用 POST；
- 成功后使用 PRG（Post/Redirect/Get）避免重复提交；
- 表单错误保留已填内容（不回显密码）；
- Flash 消息区分成功、失败、警告；
- 破坏性操作提示明确的对象名称。

### 7.5 演示数据

- 只使用明确标记为“示例”的数据，不虚构真实团队或人物；
- 演示数据写入数据库，通过 SQL 初始化或后台录入；
- 不得在 PHP 模板或 JavaScript 中硬编码业务数据集；
- 真实团队资料在用户提供后替换占位数据。

## 8. 验收用演示路径（已可执行）

以下步骤用于最终答辩演示；后台增删改查、留言审核、文件上传/下载、权限、日志和图表已按真实 HTTP 测试执行，详细结果见 `admin-implementation.md`：

1. 打开前台首页，展示团队信息、项目、文章、公告和团队/个人作业入口。
2. 进入项目列表筛选，打开项目详情，核对页面与数据库记录。
3. 打开文章详情，刷新前后查看 `view_count` 增加。
4. 提交一条留言，确认前台未显示；登录后台审核通过并回复，再回前台确认显示。
5. 后台新增一条项目/文章/公告，提交后用 SQL 或列表核对新增记录。
6. 尝试上传不允许的文件类型，展示拒绝；上传合法 PDF，前台下载并检查下载次数和日志。
7. 打开后台统计图表，新增数据后刷新，说明图表数据来自聚合 SQL。
8. 用成员账号尝试访问角色管理，展示 403；超级管理员查看操作日志。
9. 查看团队作业和个人作业下载链接，确认可下载。
10. 用 Git 记录和成员文件说明每人独立编写的 Model/View/Controller。

## 9. 与课程要求的对应关系

| 课程要求 | 本项目的落实位置 |
| --- | --- |
| 表数量 > 10，约 15 张 | `database-design.md` 实际设计 17 张业务表 |
| 原生 PHP MVC | `architecture-plan.md` 的目录、路由、Controller/Model/View 分层 |
| 每人一个 Model/View/Controller | `development-plan.md` 的工作包与真实分工表 |
| PHP 文件注释头 | `development-plan.md` 编码规范与模板 |
| `data/install.sql` | 已创建并连续导入两次验证 |
| 团队/个人作业下载链接 | M09 前台下载页 + 后台文件管理，已实测 |
| 前后台可演示 | M01～M12 全部为 P0；前后台已可演示 |
| ECharts 动态图表 | M10 已完成真实 SQL 聚合 + JSON 接口 |
| 六份文档 + PPT | 已有需求、数据库、架构、前后台记录、开发计划；完整材料待完成 |
| 禁止硬编码/造假 | 需求、数据库、架构、开发计划均规定真实数据与真实证据要求 |

## 10. 待确认事项（与本文件相关）

1. 团队成员是否全部需要后台账号；如只有部分人登录，公开成员档案仍可保留。
2. 是否允许内容编辑管理成员和团队信息，还是仅超级管理员。
3. 成员资料中允许公开哪些字段（邮箱、GitHub、个人网站等）。
4. 文件上限、扩展名白名单是否符合课程提交要求。
5. 是否需要“个人作业”与具体成员的强关联展示。
6. 前台品牌色、字体、Logo、团队名称和真实文案。
7. 是否需要多级文章分类或仅一级分类。
8. 演示数据是否由团队提供，还是允许生成明确的“示例数据”。
