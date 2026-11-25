# TechHub 前台开发实施记录

| 项目 | 内容 |
| --- | --- |
| 实施阶段 | 前台网站开发 |
| 实施日期 | 2026-11-25 |
| 运行环境 | PHP 8.2.36 NTS + MySQL 8.0.19 + PDO |
| 开发访问地址 | `http://127.0.0.1:8090`（PHP 内置服务器） |
| 数据库 | `techhub`，3308 端口独立实例 |
| 当前状态 | 前台内容页、认证、留言、下载、个人资料已完成并实测；后台完整 CRUD、ECharts 已在后续阶段完成，见 [后台管理实施记录](admin-implementation.md) |

> 本文只记录实际完成和实际验证的内容。未实现的模块和未执行的测试在“限制与待办”中明确列出。后台阶段记录见 [后台管理实施记录](admin-implementation.md)。

## 1. 已实现的前台页面

| 路由 | 页面 | 主要数据来源 |
| --- | --- | --- |
| `GET /` | 首页 | `teams`、`team_members`、`projects`、`articles`、`announcements`、`files` |
| `GET /team` | 团队介绍 | `teams`、`team_members`、`projects`、`articles` |
| `GET /members` | 成员列表、搜索、分页 | `team_members` JOIN `users` / `member_profiles` |
| `GET /members/{id}` | 成员详情、参与项目 | `team_members`、`member_profiles`、`project_members`、`projects` |
| `GET /projects` | 项目列表、状态筛选、搜索、分页 | `projects`、`project_members` |
| `GET /projects/{slug}` | 项目详情、成员、技术栈、成果、外链 | `projects`、`project_members`、`team_members`、`member_profiles` |
| `GET /articles` | 文章列表、分类筛选、关键词搜索、分页 | `articles`、`categories`、`users` |
| `GET /articles/{slug}` | 文章详情、作者信息、正文安全渲染、浏览量 | `articles`、`categories`、`users`、`roles`、`member_profiles` |
| `GET /announcements` | 公告列表、置顶排序、分页 | `announcements`、`users` |
| `GET /announcements/{id}` | 公告详情 | `announcements`、`users` |
| `GET /messages` | 已审核留言列表、留言表单、分页 | `messages`、`message_replies` |
| `POST /messages` | 留言提交、CSRF、服务端校验、频率限制 | `messages` |
| `GET /messages/{id}` | 留言详情及管理员回复 | `messages`、`message_replies`、`users` |
| `GET /downloads` | 团队/个人作业下载页 | `files` |
| `GET /downloads/{id}/file` | 受控文件下载、计数、下载日志 | `files`、`file_downloads` |
| `GET /login` | 前台用户登录 | `users`、`roles`、`role_permissions` |
| `POST /login` | 登录提交、Session、CSRF | `users`、`activity_logs` |
| `POST /logout` | 退出登录 | Session、`activity_logs` |
| `GET /profile` | 个人资料与参与项目 | `users`、`member_profiles`、`team_members`、`projects` |

> 按第一阶段需求，**不开放公开注册**，登录页已明确提示账号由超级管理员创建。`/register` 当前返回 404。

## 2. 主要代码文件

### 2.1 Controller

| 文件 | 实现内容 |
| --- | --- |
| `app/Controllers/HomeController.php` | 首页聚合查询 |
| `app/Controllers/TeamController.php` | 团队介绍页 |
| `app/Controllers/MemberController.php` | 成员搜索、分页、详情 |
| `app/Controllers/ProjectController.php` | 项目筛选、分页、详情 |
| `app/Controllers/ArticleController.php` | 分类筛选、搜索、分页、详情及浏览量 |
| `app/Controllers/AnnouncementController.php` | 公告列表与详情 |
| `app/Controllers/MessageController.php` | 留言展示、提交、服务端校验、限流 |
| `app/Controllers/DownloadController.php` | 团队/个人文件分页、路径校验、下载 |
| `app/Controllers/ProfileController.php` | 当前用户与公开成员资料 |
| `app/Controllers/AuthController.php` | 前台/后台登录、退出、目标路径跳转 |

### 2.2 Model

| 文件 | 主要职责 |
| --- | --- |
| `app/Models/Team.php` | 主团队、研究方向、技术栈、成员预览 |
| `app/Models/Member.php` | 成员列表/搜索/详情、参与项目 |
| `app/Models/Project.php` | 项目列表/筛选/搜索/详情/成员 |
| `app/Models/Category.php` | 文章分类与发布数量 |
| `app/Models/Article.php` | 发布文章列表、分类、搜索、详情、浏览量 |
| `app/Models/Announcement.php` | 有效公告列表与详情 |
| `app/Models/Message.php` | 审核通过留言、回复线程、访客留言写入 |
| `app/Models/FileResource.php` | 文件分页、公开文件详情、下载计数 |
| `app/Models/FileDownload.php` | 下载流水写入 |
| `app/Models/User.php` | 登录账号、权限、个人资料聚合 |
| `app/Models/ActivityLog.php` | 登录/退出等操作日志 |

全部业务查询使用 PDO 预处理语句；分页 `LIMIT/OFFSET` 只使用控制器和 `Paginator` 生成的整数。

### 2.3 View 与公共组件

| 文件/目录 | 内容 |
| --- | --- |
| `app/Views/home/index.php` | 首页 |
| `app/Views/team/index.php` | 团队介绍 |
| `app/Views/member/*` | 成员列表和详情 |
| `app/Views/project/*` | 项目列表和详情 |
| `app/Views/article/*` | 文章列表和详情 |
| `app/Views/announcement/*` | 公告列表和详情 |
| `app/Views/message/*` | 留言板、留言详情 |
| `app/Views/download/index.php` | 团队/个人作业下载 |
| `app/Views/profile/index.php` | 个人资料 |
| `app/Views/auth/front-login.php` | 前台登录 |
| `app/Views/partials/avatar.php` | 头像/安全首字符占位 |
| `app/Views/partials/pagination.php` | 通用分页组件 |
| `app/Views/layouts/app.php` | 全站导航、登录状态、Flash、页脚 |

### 2.4 基础设施与支持类

| 文件 | 内容 |
| --- | --- |
| `core/Paginator.php` | 页码、每页数量、边界和空数据计算 |
| `app/Support/Markdown.php` | 先转义后生成白名单标签的安全 Markdown 子集 |
| `app/Support/DownloadPath.php` | 绝对路径/目录遍历/越界目录/缺失文件校验 |
| `core/Response.php` | 新增文件流式下载响应和 `HEAD` 空响应 |
| `app/Middleware/AuthMiddleware.php` | 前台 `/login`、后台 `/admin/login` 分流 |
| `app/Middleware/GuestMiddleware.php` | 已登录用户访问登录页跳转到个人资料或后台 |
| `app/Controllers/AuthController.php` | 共用认证逻辑，区分前后台登录跳转 |

### 2.5 前端资源

- `public/assets/css/frontend.css`：前台页面、卡片、详情、表单、响应式样式；
- `public/assets/css/bootstrap-icons.min.css` 与 `public/assets/fonts/`：本地 Bootstrap Icons；
- `public/assets/images/logo-techhub.svg`：示例团队 Logo；
- `public/assets/images/projects/`：2 张项目封面；
- `public/assets/images/avatars/`：3 张成员头像；
- `public/assets/images/articles/`：2 张文章封面；
- 所有图片均为本地静态 SVG 示例资源，不依赖外部图片 CDN。

## 3. 数据库变更

新增迁移文件：

```text
data/migrations/2026-11-25_frontend.sql
```

变更内容：

1. `teams` 增加 `research_areas VARCHAR(255)`；
2. `teams` 增加 `tech_stack VARCHAR(255)`；
3. `projects` 增加 `outcomes TEXT`；
4. 为示例团队、成员、项目、文章写入本地展示图片路径；
5. 迁移使用 `information_schema` 判断列是否存在，实际连续执行两次成功。

同时已同步更新 `data/install.sql`。使用替换数据库名后的临时库连续导入两次，结果：

- 17 张业务表；
- 21 个外键；
- 5 个示例用户、1 个团队、3 个成员关系、2 个项目、3 篇文章；
- 新字段和示例展示数据存在；
- 临时验证库已删除。

## 4. 安全实现

### 4.1 SQL 与输出

- 全部业务查询使用 PDO 预处理；
- 搜索参数分开命名，避免原生预处理重复占位符问题；
- 视图动态文本统一 `e()` 转义；
- 项目描述、项目成果、文章正文、公告正文使用受控 Markdown 渲染；
- Markdown 先转义原始内容，只生成 `h1~h3`、`p`、`ul`、`ol`、`blockquote`、`pre/code`、`strong`、`em`、`a` 等白名单标签。

### 4.2 留言防护

- `POST /messages` 走 CSRF 中间件；
- 昵称 2～50 字符、内容 5～2000 字符、邮箱格式校验；
- 校验 UTF-8 编码，非法编码返回表单错误而不是 500；
- 蜜罐字段 `website`；
- Session 级 30 秒冷却和一小时最多 3 次提交；
- 新留言固定 `pending`，前台只读取 `approved`；
- 留言详情仅展示 `visible` 管理员回复；
- 访客邮箱不公开；
- 实测 `<script>` 内容在详情页以 `&lt;script&gt;` 呈现，不执行。

### 4.3 下载防护

- 仅允许数据库 `is_public = 1` 且状态 `active` 的文件；
- `team_assignment` 只允许 `data/team/`，`personal_assignment` 只允许 `data/personal/`；
- 拒绝绝对路径、Windows 盘符、空字节和 `..` 目录遍历；
- 使用 `realpath` 检查文件确实位于允许目录内；
- 文件不存在或越界时返回 404，不泄露真实路径；
- 下载使用随机存储名和原始文件名分离；
- 下载成功更新 `files.download_count` 并写入 `file_downloads`；
- 直接访问 `/data/team/...` 返回 404。

### 4.4 认证

- 前台和后台共用密码验证、登录限流、Session 重建和日志；
- 前台访问受保护的个人资料页跳转 `/login`，后台仍跳转 `/admin/login`；
- 登录成功使用 `session_regenerate_id(true)`；
- 不开放注册；
- 退出使用 POST + CSRF。

## 5. 实际测试结果

### 5.1 CLI 测试

```text
php tests/smoke.php
```

结果：**14 项通过，0 项失败**。

```text
php tests/frontend_smoke.php
```

结果：**34 项通过，0 项失败**。

新增前台测试覆盖：

- 团队资料、研究方向、技术栈、Logo；
- 成员列表/详情/头像/参与项目；
- 项目列表/详情/成果/封面/成员；
- 分类、文章列表、分类筛选、文章搜索、文章详情；
- 公告列表/详情；
- 已审核留言与管理员回复；
- 团队/个人文件列表及公开文件元数据；
- 下载合法路径、目录遍历、跨目录和缺失文件拒绝；
- 个人资料与团队关系；
- 分页超出边界和空数据；
- Markdown 标题、列表和安全转义。

### 5.2 HTTP 页面验证

| 页面 | 结果 |
| --- | --- |
| 首页 | 200，真实读取团队/成员/项目/文章/公告 |
| 团队页 | 200，展示研究方向/技术栈/联系方式 |
| 成员列表 | 200 |
| 成员详情 `/members/1` | 200，展示参与项目 |
| 项目列表 | 200 |
| 项目详情 | 200，展示成果和成员 |
| 文章列表 | 200 |
| 文章分类筛选 | 200 |
| 文章搜索（UTF-8） | 200 |
| 文章详情 | 200 |
| 公告列表/详情 | 200 |
| 留言列表/详情 | 200 |
| 下载页（team/personal） | 200 |
| 前台登录页 | 200 |
| 未发布文章 | 404 |
| 不存在成员/项目 | 404 |
| 不存在下载文件 | 404 |
| 注册路由 | 404（需求不允许公开注册） |

### 5.3 数据库真实交互证据

| 验证项 | 实际结果 |
| --- | --- |
| 成员个人中心 | `member_a` 登录后 302 到 `/profile`，页面 200，展示示例成员、团队和参与项目 |
| 退出登录 | 302 到首页，再次访问 `/profile` 返回 302 |
| 文章浏览量 | 实测从 13 增至 14（后续浏览继续增加） |
| 留言提交 | 正确 UTF-8 表单返回 302；数据库中新增 `pending` 留言 |
| 留言审核隔离 | 新提交留言未出现在公开留言列表 |
| 留言 CSRF | 无 Token POST 返回 403 |
| 留言非法编码 | 返回 302 + 表单错误，无 500 |
| 留言 XSS | 审核后详情页原始 `<script>` 不存在，转义形式存在 |
| 留言隐私 | 提交的邮箱未出现在公开详情 HTML |
| 下载团队作业 | 200，文件内容 378 字节；`download_count` 从 1 增至 2，下载日志新增 |
| 下载个人作业 | 200，文件内容 312 字节 |
| 目录穿越 | `data/team/../personal/...` 被拒绝；跨分类目录被拒绝 |
| 直接访问 data 路径 | 404 |
| 分页 `page=2/99` | 200，分页边界安全回退 |
| SQL 注入形状搜索 | 200，预处理未产生异常或绕过 |

> 测试留言已从数据库清理；下载测试产生了真实的下载计数和 `file_downloads` 记录，未回滚。当前示例文件计数为团队作业 2 次、个人作业 1 次。

### 5.4 移动端

使用 Playwright + 系统 Edge，以 **Pixel 5** 设备视口进行页面渲染截图检查：

- 首页：`docs/assets/screenshots/home-mobile.png`
- 留言页：`docs/assets/screenshots/mobile-messages-top.png`
- 下载页：`docs/assets/screenshots/mobile-downloads-top.png`
- 文章详情：`docs/assets/screenshots/mobile-article-top.png`
- 成员列表：`docs/assets/screenshots/mobile-members-top.png`
- 桌面首页：`docs/assets/screenshots/home-desktop.png`

检查结果：

- 导航折叠按钮可见，点击后菜单可展开，8 个导航链接均存在；
- 使用移动视口测量了首页、团队、成员、成员详情、项目、项目详情、文章、文章详情、公告、留言、留言详情、下载、登录共 13 个页面；
- 所有页面 `document.documentElement.scrollWidth <= window.innerWidth`，未检测到横向溢出；
- 表单、下载卡片和文章正文在移动视口下正常换行；
- 主要页面浏览器控制台无脚本错误，补齐 favicon 后无额外 404 静态资源。

未在实体手机上测试。

## 6. 已知限制与后续待办

> 以下列表记录前台阶段结束时的状态；后台阶段已解决其中部分问题，解决情况以 [后台管理实施记录](admin-implementation.md) 为准。

1. **不开放公开注册**：按第一阶段需求，账号由超级管理员创建。
2. **个人资料只读**：可查看账号和公开成员资料，修改本人资料/密码仍未实现。
3. **留言审核后台已实现**：后台支持审核、回复、隐藏/公开和删除。
4. **后台业务 CRUD 已实现**：团队、成员、项目、文章、分类、公告、留言、文件、用户、角色和日志已完成。
5. **文件上传界面已实现**：团队/个人/通用资源上传、编辑、公开/隐藏和物理删除已接入。
6. **ECharts 已接入**：4 张图从受保护的真实聚合接口取数。
7. **Apache 未切换到 PHP 8.2**：当前运行验证使用 PHP 内置服务器；Apache 虚拟主机配置尚未完成。
8. **移动端为设备仿真测试**：未在实体手机上测试。
9. **示例数据仍是演示内容**：真实团队资料待提供。

## 7. 本阶段新增/修改的主要文件

```text
app/Controllers/TeamController.php
app/Controllers/MemberController.php
app/Controllers/ProjectController.php
app/Controllers/ArticleController.php
app/Controllers/AnnouncementController.php
app/Controllers/MessageController.php
app/Controllers/DownloadController.php
app/Controllers/ProfileController.php
app/Controllers/AuthController.php
app/Controllers/HomeController.php
app/Models/Team.php
app/Models/Member.php
app/Models/Project.php
app/Models/Category.php
app/Models/Article.php
app/Models/Announcement.php
app/Models/Message.php
app/Models/FileResource.php
app/Models/FileDownload.php
app/Models/User.php
app/Support/Markdown.php
app/Support/DownloadPath.php
core/Paginator.php
core/Response.php
core/helpers.php
app/Views/home/index.php
app/Views/team/index.php
app/Views/member/index.php
app/Views/member/show.php
app/Views/project/index.php
app/Views/project/show.php
app/Views/article/index.php
app/Views/article/show.php
app/Views/announcement/index.php
app/Views/announcement/show.php
app/Views/message/index.php
app/Views/message/show.php
app/Views/download/index.php
app/Views/profile/index.php
app/Views/auth/front-login.php
app/Views/partials/avatar.php
app/Views/partials/pagination.php
app/Views/layouts/app.php
public/assets/css/frontend.css
public/assets/css/bootstrap-icons.min.css
public/assets/fonts/bootstrap-icons.woff
public/assets/fonts/bootstrap-icons.woff2
public/assets/images/**
data/install.sql
data/migrations/2026-11-25_frontend.sql
config/routes.php
tests/frontend_smoke.php
docs/assets/screenshots/**
```

## 8. 后台阶段补充

前台阶段完成后，后台管理系统已在同一 MVC 架构下实现并验证，覆盖团队、成员、项目、分类、文章、公告、留言审核/回复、文件上传/下载、用户、角色权限、操作日志和 ECharts 统计。该部分页面、代码文件、数据库变更、安全实现和 79 项真实 HTTP 集成测试记录见：

- [后台管理实施记录](admin-implementation.md)

后台阶段新增：

- `activity_logs.result` 字段和迁移；
- 后台布局与 12 个后台 Controller；
- 本地 ECharts 5.5.1 和图表接口；
- 后台 CRUD、RBAC、上传校验和操作审计。

## 9. 结论

前台网站已具备完整可运行的展示、搜索、筛选、分页、成员/项目/文章详情、留言提交与回复展示、登录/个人资料和团队/个人作业受控下载能力。页面数据均来自 MySQL，主要交互已完成实际数据库验证。

后台管理阶段也已完成真实 CRUD、权限、审核、日志和图表验证。项目整体仍待 Apache PHP 8.2 部署、Git 提交、真实团队资料和六份课程材料；不能把当前状态表述为整个课程项目最终验收完成。
