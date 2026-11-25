# TechHub 后台管理实施记录

| 项目 | 内容 |
| --- | --- |
| 实施阶段 | 第四阶段：后台管理系统 |
| 实施日期 | 2026-11-25 |
| 运行环境 | PHP 8.2.36 NTS + MySQL 8.0.19 + PDO |
| 开发访问地址 | `http://127.0.0.1:8090`（PHP 内置服务器） |
| 数据库 | `techhub`，3308 端口独立实例 |
| 界面 | Bootstrap 5.3.3 + Bootstrap Icons + 本地 Apache ECharts 5.5.1 |
| 当前状态 | 后台 11 个业务模块、统计图表、RBAC、操作日志已完成并通过真实 HTTP/数据库回归；Apache 部署、Git 提交和课程材料仍待后续 |

> 本文只记录实际实现和实际执行结果。测试脚本运行后会清理测试业务数据，保留真实操作日志。原始证据见 [evidence 目录](evidence/)。

## 1. 后台布局

后台采用仿 AdminLTE 的管理界面，在原有 MVC 架构上新增：

- 深色左侧导航栏，按权限动态显示模块；
- 固定顶部栏：页面标题、前台入口、账号菜单和退出；
- 当前账号、角色和头像首字符；
- 面包屑导航；
- 统一 Flash 成功/错误提示；
- 主内容卡片、表格、筛选器和表单；
- 移动端使用 Bootstrap Offcanvas 抽屉导航；
- `admin.css` 单独维护后台视觉，不覆盖前台样式。

主要文件：

- `app/Views/layouts/admin.php`
- `public/assets/css/admin.css`
- `public/assets/js/admin-charts.js`

## 2. 后台页面与路由

| 模块 | 路由 | 权限码 | 实现内容 |
| --- | --- | --- | --- |
| 数据统计首页 | `GET /admin` | `dashboard.view` | 真实统计卡片、当前团队、最近日志；有 `stats.view` 时加载 ECharts |
| 图表接口 | `GET /admin/api/stats` | `stats.view` | 受 Session 和权限保护的 JSON 聚合数据 |
| 团队信息 | `GET/POST /admin/team` | `team.edit` | 资料、方向、技术栈、联系方式、Logo、展示状态 |
| 成员列表 | `GET /admin/members` | `member.manage` | 搜索、状态筛选、分页、项目数 |
| 新增成员 | `GET/POST /admin/members/create` | `member.manage` | 选择账号、职位、职责、加入日期、公开资料、头像 |
| 编辑成员 | `GET/POST /admin/members/{id}/edit` | `member.manage` | 编辑档案与照片，移除头像 |
| 成员离队/删除 | `POST /admin/members/{id}/remove|delete` | `member.manage` | 离队保留项目历史；删除前检查项目/文件引用 |
| 项目列表 | `GET /admin/projects` | `project.manage` | 搜索、状态/发布筛选、分页 |
| 新增/编辑项目 | `GET/POST /admin/projects...` | `project.manage` | 封面、状态、发布、推荐、成员与项目角色 |
| 项目上下线 | `POST /admin/projects/{id}/visibility` | `project.manage` | 切换 `is_public`，前台立即生效 |
| 删除项目 | `POST /admin/projects/{id}/delete` | `project.manage` | 级联删除项目成员关联并清理封面 |
| 分类列表 | `GET /admin/categories` | `category.manage` | 层级、文章数、子分类数、状态 |
| 新增/编辑分类 | `GET/POST /admin/categories...` | `category.manage` | 父分类、别名、排序、启停，防循环引用 |
| 分类启停/删除 | `POST /admin/categories/{id}/status|delete` | `category.manage` | 有文章或子分类时拒绝删除 |
| 文章列表 | `GET /admin/articles` | `article.manage` | 分类/状态筛选、搜索、分页 |
| 新增/编辑文章 | `GET/POST /admin/articles...` | `article.manage` | 分类、摘要、正文、封面、草稿/发布/归档 |
| 文章状态 | `POST /admin/articles/{id}/status` | `article.manage` | 发布、转草稿、归档 |
| 公告列表 | `GET /admin/announcements` | `announcement.manage` | 类型/状态筛选、搜索、置顶 |
| 新增/编辑公告 | `GET/POST /admin/announcements...` | `announcement.manage` | 类型、正文、有效期、置顶、发布 |
| 公告上下线 | `POST /admin/announcements/{id}/status` | `announcement.manage` | 发布/下线/草稿 |
| 留言列表 | `GET /admin/messages` | `message.moderate` | 状态标签、搜索、分页、回复数 |
| 留言详情 | `GET /admin/messages/{id}` | `message.moderate` | 访客信息、内容、IP、UA、回复线程 |
| 留言审核/回复/删除 | `POST /admin/messages/{id}/status|reply|delete` | `message.moderate` | 通过/拒绝、公开/隐藏回复、级联删除 |
| 文件列表 | `GET /admin/files` | `file.manage` | 分类/状态筛选、公开状态、下载 |
| 上传文件 | `GET/POST /admin/files/upload` | `file.manage` | 受控目录、扩展名/MIME/大小校验、成员关联 |
| 编辑文件 | `GET/POST /admin/files/{id}/edit` | `file.manage` | 标题、说明、成员、公开状态 |
| 文件状态/下载/删除 | `POST/GET /admin/files/{id}/...` | `file.manage` | 启停、公开切换、受控下载、物理删除 |
| 用户列表 | `GET /admin/users` | `user.manage` | 搜索、角色/状态筛选、分页 |
| 新增/编辑用户 | `GET/POST /admin/users...` | `user.manage` | 账号、显示名、邮箱、角色、密码、状态 |
| 用户启停/删除 | `POST /admin/users/{id}/status|delete` | `user.manage` | 禁止自停用/自删除，删除前检查引用 |
| 角色列表 | `GET /admin/roles` | `role.manage` | 账号数、权限数、系统角色标记 |
| 新增/编辑角色 | `GET/POST /admin/roles...` | `role.manage` | 角色代码、名称、说明、排序 |
| 权限矩阵 | `POST /admin/roles/{id}/permissions` | `role.manage` | 全量权限勾选，服务端白名单校验 |
| 操作日志 | `GET /admin/activity` | `activity.view` | 操作人、模块、动作、目标、结果、IP、时间、筛选 |

用户管理和角色权限另在控制器中执行 `requireSuperAdmin()`，即使未来把 `user.manage` 或 `role.manage` 分配给普通角色，也不能借此修改管理员权限。

## 3. 代码文件

### 3.1 后台 Controller

```text
app/Controllers/Admin/AdminController.php
app/Controllers/Admin/DashboardController.php
app/Controllers/Admin/UserController.php
app/Controllers/Admin/RoleController.php
app/Controllers/Admin/TeamController.php
app/Controllers/Admin/MemberController.php
app/Controllers/Admin/ProjectController.php
app/Controllers/Admin/CategoryController.php
app/Controllers/Admin/ArticleController.php
app/Controllers/Admin/AnnouncementController.php
app/Controllers/Admin/MessageController.php
app/Controllers/Admin/FileController.php
app/Controllers/Admin/ActivityController.php
```

`AdminController` 统一处理后台视图数据、面包屑、表单错误/旧值 PRG、Flash 和操作日志，避免每个模块重复实现。

### 3.2 Model

新增或扩展：

```text
app/Models/AdminStats.php     # 仪表盘聚合和图表数据
app/Models/Role.php           # 角色、权限矩阵、角色权限事务
app/Models/User.php           # 后台账号 CRUD、关联检查、超级管理员保护
app/Models/Team.php           # 团队编辑
app/Models/Member.php         # 成员关系、公开资料、项目引用检查
app/Models/Project.php        # 项目 CRUD、成员同步
app/Models/Category.php       # 分类 CRUD、循环检测
app/Models/Article.php        # 文章 CRUD、状态管理
app/Models/Announcement.php   # 公告 CRUD、状态管理
app/Models/Message.php        # 审核、回复、后台列表
app/Models/FileResource.php   # 文件上传记录、状态、删除
app/Models/ActivityLog.php    # 结果字段、筛选和分页
```

### 3.3 View

```text
app/Views/layouts/admin.php
app/Views/admin/partials/page-header.php
app/Views/admin/partials/form-errors.php
app/Views/admin/partials/pagination.php
app/Views/admin/dashboard/index.php
app/Views/admin/team/edit.php
app/Views/admin/members/{index,form}.php
app/Views/admin/projects/{index,form}.php
app/Views/admin/categories/{index,form}.php
app/Views/admin/articles/{index,form}.php
app/Views/admin/announcements/{index,form}.php
app/Views/admin/messages/{index,show}.php
app/Views/admin/files/{index,form}.php
app/Views/admin/users/{index,form}.php
app/Views/admin/roles/{index,form}.php
app/Views/admin/activity/index.php
```

### 3.4 前端与支持类

- `public/assets/css/admin.css`：后台整体布局、表格、表单、图表卡片和移动端；
- `public/assets/js/echarts.min.js`：本地 Apache ECharts 5.5.1；
- `public/assets/js/echarts.LICENSE.txt`：ECharts Apache-2.0 许可证；
- `public/assets/js/admin-charts.js`：从受权限保护接口取数并绘制 4 张图；
- `app/Support/ImageUpload.php`：后台图片保存和受管文件清理；
- `app/Support/Slugger.php`：URL 别名生成和校验。

## 4. 统计图表

使用 MySQL 聚合，不写死前端数据：

1. 团队成员数量：`team_members + users` 的启用成员数；
2. 项目总数：`COUNT(projects)`；
3. 文章总数：`COUNT(articles)`；
4. 留言总数及待审核数：`messages`；
5. 文件总数、累计下载、后台账号数；
6. 项目状态分布饼图：`GROUP BY projects.status`；
7. 文章发布趋势折线图：最近 6 个月 `published_at`；
8. 项目数量统计柱状图：最近 6 个月 `created_at`；
9. 成员参与项目数量条形图：`team_members LEFT JOIN project_members`。

接口 `GET /admin/api/stats` 返回 JSON；前端 `admin-charts.js` 通过同源 `fetch` 加载。ECharts 渲染出 4 个 canvas 是浏览器实测结果。

### 4.1 示例日期调整

原来的示例文章/项目日期在 2025 年，最近 6 个月趋势图会全部为 0。为让课程演示窗口内的图表能反映真实数据库聚合，新增可重复执行的：

```text
data/migrations/2026-11-25_admin_demo_dates.sql
```

仅调整明确标记为“示例”的项目、文章及其时间字段到 2026 年 6–11 月。没有篡改业务状态，也没有把测试结果写入文档。正式数据替换后，图表会按真实日期重新聚合。

## 5. 数据库变更

新增后台迁移：

```text
data/migrations/2026-11-25_admin.sql
```

内容：

- `activity_logs` 增加 `result ENUM('success','failure') NOT NULL DEFAULT 'success'`；
- 增加 `idx_activity_logs_result (result, created_at)`；
- 迁移使用 `information_schema` 判断字段和索引，可重复执行。

`data/install.sql` 已同步 `result` 字段和索引。后台所有关键写操作写入日志，登录失败也记录为 `failure`。

## 6. 安全实现

- 所有后台路由声明 `auth`；未登录跳转 `/admin/login`；
- 所有管理路由声明权限码，由 `PermissionMiddleware` 每次请求服务端校验；
- 用户与角色模块额外要求 `Auth::isSuperAdmin()`；
- 所有 POST 路由声明 CSRF，Token 不匹配返回 403；
- 输出统一 `e()` 转义；
- 表单使用 PRG，校验错误保留非密码字段；
- 删除操作使用 POST + JS 二次确认；
- 用户名、邮箱、角色、状态、日期、URL、枚举值全部服务端校验；
- 图片上传限制扩展名、真实 MIME、图片内容、大小，保存到 `public/uploads/` 随机文件名；
- 文件上传限制为文档扩展名，按分类写入 `data/team`、`data/personal`、`data/resources`；
- 物理文件删除前通过 `DownloadPath` 做真实路径边界校验；
- 超级管理员不能停用/删除自己，不能被自己降级；
- 系统必须保留至少一个启用的超级管理员；
- 超级管理员角色的权限矩阵不能在界面降权；
- 有业务引用的用户、角色、分类、成员关系拒绝物理删除；
- 操作日志记录操作人、模块、动作、目标、结果、IP、UA 和时间，不保存密码。

## 7. 实际测试结果

### 7.1 PHP 与既有回归

原始文件：[PHP 语法检查](evidence/php-lint-2026-11-25.txt)、[基础测试](evidence/smoke-2026-11-25.txt)、[前台测试](evidence/frontend-smoke-2026-11-25.txt)、[后台测试](evidence/admin-smoke-2026-11-25.txt)、[安装校验](evidence/install-2026-11-25.txt)、[迁移校验](evidence/migrations-2026-11-25.txt)、[浏览器检查](evidence/browser-admin-2026-11-25.txt)、[静态资源检查](evidence/admin-assets-2026-11-25.txt)。

| 项目 | 结果 |
| --- | --- |
| 全部 PHP 文件 `php -l` | 通过 |
| `tests/smoke.php` | 14 passed, 0 failed |
| `tests/frontend_smoke.php` | 34 passed, 0 failed |
| `tests/admin_smoke.php` | **79 passed, 0 failed**，连续执行两次结果一致 |
| 全新临时库执行 `install.sql` 两次 | 17 表、21 外键、`activity_logs.result` 存在 |
| 后台迁移重复执行 | 成功，无重复字段/索引错误 |

### 7.2 后台 HTTP 页面

管理员登录后逐一访问 30 个后台 GET 页面/接口：全部 200。

包括：仪表盘、统计 API、团队、成员列表/新增/编辑、项目列表/新增/编辑、分类列表/新增/编辑、文章列表/新增/编辑、公告列表/新增/编辑、留言列表/详情、文件列表/上传/编辑、用户列表/新增/编辑、角色列表/新增/编辑、操作日志。

### 7.3 真实增删改查

`tests/admin_smoke.php` 覆盖并通过：

- 新增用户、编辑显示名、停用、停用后登录失败、重新启用、删除；
- 新用户以普通团队成员角色登录，只能访问后台首页；项目/统计/用户页面被 403；
- 编辑账号即使携带有效 CSRF，访问用户管理仍为 403；
- 新增角色、配置 2 项权限、编辑、删除；
- 新增分类、编辑、停用、删除；
- 新增项目并关联 2 名成员、前台可见、下线后前台 404、重新发布、编辑成员、删除并级联；
- 新增草稿文章、草稿前台 404、发布后前台 200、编辑、删除；
- 新增并发布公告、前台可见、删除；
- 新增团队成员、写入公开资料、上传头像、编辑成员、移除头像和物理文件、离队、删除关系；
- 上传团队文件、写数据库、后台下载、前台列表、前台受控下载、编辑、隐藏后下载 404、重新启用、删除数据库记录和物理文件；
- 拒绝 `.php` 文件上传；
- 访客留言待审核、前台隔离、后台审核、公开回复、访客邮箱不公开、删除留言和回复；
- 编辑团队信息并同步前台、下架后前台不显示、恢复展示和原始标语；
- 操作日志包含成功结果和目标类型。

### 7.4 安全回归

| 测试 | 实际结果 |
| --- | --- |
| 后台 POST 无 CSRF | 403（Apache 不接受非标准 419） |
| 编辑账号访问用户/角色 | 403 |
| 编辑账号带有效 CSRF 新增用户 | 403 |
| 普通成员访问项目/统计 | 403 |
| 普通成员后台菜单 | 不显示项目管理、用户管理 |
| 超级管理员自我降级 | 被拒绝，数据库角色未变 |
| 超级管理员删除自己 | 被拒绝，账号保留 |
| 超级管理员角色权限降权 | 被拒绝，仍为 14 项权限 |
| `.php` 上传 | 被拒绝，无数据库记录 |
| 伪装 JPG 头像 | 被拒绝（MIME/图片内容校验），原头像保留 |

### 7.5 浏览器与响应式

使用系统 Edge Headless + CDP 设备仿真：

- 桌面 1440×1000：后台仪表盘加载 4 个 ECharts canvas；
- 图表无“正在加载”卡住，未发现应用 JavaScript 异常；多数完整运行控制台为 0 个错误；
- 后台 16 个页面在 390px 移动视口下 `scrollWidth == innerWidth`，无横向溢出；
- 移动端 Offcanvas 菜单可打开，`show` 状态生效；
- 所有测试页面静态资源本地加载，不依赖 CDN。

截图：

- `docs/assets/screenshots/admin-dashboard-desktop.png`
- `docs/assets/screenshots/admin-dashboard-charts-lower.png`
- `docs/assets/screenshots/admin-dashboard-mobile.png`
- `docs/assets/screenshots/admin-sidebar-mobile.png`
- `docs/assets/screenshots/admin-users-desktop.png`
- `docs/assets/screenshots/admin-projects-desktop.png`
- `docs/assets/screenshots/admin-messages-desktop.png`

## 8. 运行与测试命令

```bat
D:\XiaoPiMianBan\phpstudy_pro\Extensions\php\php8.2.36nts\php.exe tests\admin_smoke.php
```

要求开发服务器运行在 `http://127.0.0.1:8090`，本地 `.env` 中有管理员和编辑账号密码。测试脚本会自动创建并清理测试数据，不删除示例业务数据，不清空操作日志。

## 9. 已知限制

1. **Apache 尚未切换到 PHP 8.2**：后台通过 PHP 内置服务器验证，最终 Apache 虚拟主机仍待部署阶段完成。
2. **Git 尚未提交/推送**：本地仓库和远程已配置，但没有提交记录。
3. **修改本人资料/修改密码页面未实现**：`profile.edit` 已存在，当前个人资料只读；密码可由超级管理员重置。
4. **权限项固定由初始化 SQL 提供**：界面只做角色-权限映射，不提供新增权限码。
5. **项目仅使用当前主团队**：项目编辑自动归属第一个团队，未实现多团队切换。
6. **示例数据仍需替换**：团队名称、成员、项目和文章仍是演示数据。
7. **真实移动设备未测试**：当前为 Edge 设备仿真。
8. **六份课程文档、PPT、真实分工与 Git 记录未完成**。

## 10. 结论

后台管理阶段已完成 11 个业务模块的真实数据库 CRUD、RBAC、上传、审核、日志和 ECharts 统计，并通过 79 项真实 HTTP 集成测试。项目仍不能整体宣称最终验收通过：Apache 部署、Git 提交、真实团队资料和课程材料尚未完成。
