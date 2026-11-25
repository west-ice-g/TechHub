# TechHub

> 高校技术团队展示与管理平台

**当前阶段：第四阶段（后台管理系统）— 已完成并验证，等待下一阶段指令。**
最后更新：2026-11-25

## 当前真实状态

| 项目 | 状态 |
| --- | --- |
| 数据库 | MySQL 8.0.19 独立实例（端口 3308），`techhub` 库已初始化 |
| 数据表 | **17 张业务表、21 个外键**；`activity_logs` 已增加操作结果字段 |
| PHP | PHP 8.2.36 NTS；PDO MySQL、fileinfo、mbstring、openssl 等扩展已启用 |
| MVC 基础架构 | Router、Controller、Model、Database、View、Auth、Session、CSRF、Middleware 已实现 |
| 前台 | 首页、团队、成员、项目、文章、公告、留言、下载、登录、个人资料已完成并连接 MySQL |
| 后台布局 | 侧边栏、顶部栏、面包屑、用户区、统一提示、AdminLTE 风格响应式布局已完成 |
| 后台业务模块 | 团队、成员、项目、分类、文章、公告、留言审核/回复、文件、用户、角色权限、操作日志已完成真实 CRUD |
| 数据统计 | 4 张 ECharts 图表和统计卡片，全部从 MySQL 聚合接口取数 |
| RBAC | 服务端路由权限 + 菜单权限 + 用户/角色模块超级管理员二次校验已实测 |
| 文件安全 | 图片/文档扩展名、真实 MIME、大小、受控目录、路径边界、物理删除已完成并实测 |
| 操作日志 | 记录操作人、模块、动作、目标、结果、IP、UA、时间，后台可筛选 |
| 测试 | 基础 14 项 + 前台 34 项 + 后台 79 项全部通过；后台 30 个 GET 页面/接口 200 |
| 浏览器验证 | Edge Headless：4 个 ECharts canvas 正常，无应用脚本异常；390px 下 16 个后台页面无横向溢出 |
| Docker Compose | **已实际冷启动验证：PHP 8.2 + Apache、MySQL 8.0、17 表/21 外键、容器内三套测试通过** |
| Apache（宿主机） | 仍使用 PHP 7.3.4，尚未切换到 PHP 8.2；Docker 容器内 Apache 已使用 PHP 8.2 |
| Git | 本地仓库已初始化并配置远程 `origin`；**尚未提交/推送** |
| 课程文档/PPT | 已生成 5 份 PDF、15 页 PPTX 和 Markdown 源文件；学校模板适配、真实资料替换和正式定稿仍待完成 |

**当前可运行地址（本地开发服务器）**：`http://127.0.0.1:8090`

> 当前使用 PHP 内置开发服务器验证，不是 Apache 最终部署。内置服务器进程关闭后需重新启动；Apache 的 PHP 8.2 虚拟主机配置留待部署阶段完成。

后台截图见 [后台仪表盘](docs/assets/screenshots/admin-dashboard-desktop.png)、[后台图表下半区](docs/assets/screenshots/admin-dashboard-charts-lower.png)、[移动端后台](docs/assets/screenshots/admin-dashboard-mobile.png)。详细实施与测试记录见 [后台管理实施记录](docs/admin-implementation.md)。

## 已实现内容

### 1. 数据库

- MySQL 8.0.19，InnoDB，`utf8mb4_0900_ai_ci`；
- 17 张表、21 个外键；
- `data/install.sql` 可重复导入；
- 角色、权限、管理员、示例团队、成员、项目、文章、公告、留言、文件和下载记录；
- `activity_logs.result` 记录操作成功/失败；
- 默认管理员密码只存哈希；明文仅保存在本机未提交的 `.env`；
- 表格结构、关系、数据字典与迁移记录见 [数据库设计文档](docs/database-design.md)。

### 2. PHP MVC 与安全基础

```text
public/index.php
  → bootstrap/app.php
  → core/Router.php
  → app/Middleware（Auth / Guest / Permission / CSRF）
  → app/Controllers
  → app/Models
  → core/View.php
  → app/Views
```

已实现 PDO 原生预处理、事务、Session 安全、CSRF、HTML 转义、密码哈希、登录限流、RBAC、上传校验、统一异常处理和日志。

### 3. 前台网站

- 首页：团队、核心数据、项目、成员、文章、公告、下载入口；
- 团队介绍：研究方向、技术栈、联系方式；
- 成员列表/详情：头像、职位、方向、公开资料、参与项目；
- 项目列表/详情：封面、技术栈、成员、状态、成果；
- 文章列表/详情：分类筛选、搜索、作者、浏览量、安全 Markdown；
- 公告：置顶、类型、有效期、详情；
- 留言：CSRF、蜜罐、编码校验、频率限制、待审核隔离、管理员回复展示；
- 登录/个人资料：前台登录、退出、只读资料；不开放公开注册；
- 作业下载：团队/个人分类、分页、受控路径、下载计数和流水；
- 响应式布局、本地 Bootstrap/Bootstrap Icons/示例图片。

### 4. 后台管理系统

- AdminLTE 风格响应式布局；
- 数据统计首页：真实卡片 + 4 张 ECharts 图表；
- 用户管理：搜索、新增、编辑、角色分配、启停、安全删除；
- 团队管理：资料、方向、技术栈、联系方式、Logo、展示状态；
- 成员管理：新增、编辑、职位、介绍、头像、离队、删除；
- 项目管理：封面、状态、发布/下线、推荐、成员与项目角色；
- 文章与分类：分类 CRUD、文章草稿/发布/归档、封面上传；
- 公告管理：类型、置顶、有效期、发布/下线；
- 留言管理：搜索、审核、回复、删除；
- 文件管理：上传、编辑、公开/隐藏、受控下载、物理删除；
- 角色权限：角色 CRUD、权限矩阵、菜单访问控制、服务端接口校验；
- 操作日志：成功/失败结果、模块/动作/目标/IP/时间筛选；
- 所有后台 POST 均有 CSRF，删除有二次确认。

### 5. 统计图表

| 图表 | 数据来源 |
| --- | --- |
| 项目状态分布饼图 | `projects.status` 聚合 |
| 文章发布趋势折线图 | 最近 6 个月 `articles.published_at` |
| 项目数量统计柱状图 | 最近 6 个月 `projects.created_at` |
| 成员参与项目数量条形图 | `team_members` 与 `project_members` 关联计数 |

统计接口：`GET /admin/api/stats`，要求登录和 `stats.view` 权限。ECharts 5.5.1 已本地化，许可证见 [第三方资源说明](docs/third-party.md)。

## 本阶段实测结果

| 验证项 | 结果 |
| --- | --- |
| PHP 全部文件语法检查 | 通过 |
| `tests/smoke.php` | 14 passed, 0 failed |
| `tests/frontend_smoke.php` | 34 passed, 0 failed |
| `tests/admin_smoke.php` | **79 passed, 0 failed**，连续两次一致 |
| 后台 30 个 GET 页面/接口 | 全部 200 |
| 未登录后台 | 302 到登录页 |
| 缺少 CSRF 的后台 POST | 403（Apache 对非标准 419 返回 500，因此使用标准状态码） |
| 编辑账号访问用户/角色 | 403 |
| 普通成员访问项目/统计 | 403 |
| 超级管理员自我降级/删除 | 被拒绝，数据库未变化 |
| 超级管理员角色降权 | 被拒绝，权限仍为 14 项 |
| 用户/角色/项目/文章/公告/分类/成员 CRUD | 真实数据库写入、更新、删除通过 |
| 项目成员同步 | 事务写入，删除项目级联清理 |
| 留言审核/回复 | 真实 pending → approved → visible reply → delete 通过 |
| 文件上传/下载/隐藏/删除 | 数据库与物理文件同步，路径边界校验通过 |
| `.php` 上传 | 被拒绝，无记录 |
| 伪装 JPG 头像 | 被拒绝，原头像保留 |
| ECharts | 桌面加载 4 个 canvas，无应用脚本异常 |
| 移动端后台 | 390px 下 16 个页面无横向溢出，抽屉菜单可打开 |
| `install.sql` 临时新库连续导入两次 | 17 表、21 外键、结果字段存在 |
| 后台迁移重复执行 | 通过 |

完整测试记录见 [后台管理实施记录](docs/admin-implementation.md)、[前台开发实施记录](docs/implementation-notes.md) 和 [数据库设计文档](docs/database-design.md)。

## 尚未完成

1. Apache PHP 8.2 虚拟主机配置和最终部署；
2. 修改本人资料/修改密码页面（当前个人资料只读，密码由超级管理员重置）；
3. 学校模板适配、PPT 最终定稿与答辩材料归档；
4. 真实团队资料替换示例数据；
5. Git 提交/推送策略确认与实际提交；
6. 团队成员真实的 M/V/C 分工与逐人 Git 记录；
7. 真实移动设备测试、性能测试、备份恢复演练；
8. 自定义权限码界面（当前权限项由初始化 SQL 固定提供）。

## 技术栈

- PHP 8.2.36（NTS）
- MySQL 8.0.19
- PDO + 预处理语句
- 原生 PHP MVC
- HTML5 / CSS3 / JavaScript
- Bootstrap 5.3.3（本地）
- Bootstrap Icons 1.11.3（本地）
- Apache ECharts 5.5.1（本地）
- Apache 2.4 + FastCGI/Fcgid（后续配置）
- Git

不使用 Laravel、ThinkPHP、Vue、React 等大型框架；不使用 ORM 隐藏 SQL；不依赖 CDN。

## 数据库概览（17 张表）

| 分组 | 表 |
| --- | --- |
| 后台账号与 RBAC | `users`、`roles`、`permissions`、`role_permissions`、`member_profiles` |
| 团队 | `teams`、`team_members` |
| 项目 | `projects`、`project_members` |
| 内容 | `categories`、`articles`、`announcements` |
| 互动与文件 | `messages`、`message_replies`、`files`、`file_downloads` |
| 审计 | `activity_logs` |

完整说明见 [数据库设计文档](docs/database-design.md)。

## 目录结构

```text
TechHub/
├─ app/
│  ├─ Controllers/
│  │  ├─ Admin/            # 后台 12 个控制器
│  │  └─ *.php             # 前台、认证、下载
│  ├─ Middleware/          # 登录、游客、权限、CSRF
│  ├─ Models/              # 前台与后台数据访问
│  ├─ Support/             # Markdown、下载路径、图片上传、Slug
│  └─ Views/
│     ├─ admin/            # 后台页面
│     ├─ layouts/          # 前台、后台、错误布局
│     └─ *.php             # 前台页面
├─ bootstrap/app.php
├─ config/
│  ├─ app.php
│  ├─ database.php
│  └─ routes.php
├─ core/                   # Router、Model、Auth、Session、CSRF 等
├─ data/
│  ├─ install.sql
│  ├─ migrations/
│  ├─ team/                # 团队作业（Web 根之外）
│  ├─ personal/            # 个人作业（Web 根之外）
│  └─ resources/           # 通用资源（Web 根之外）
├─ docs/
│  ├─ admin-implementation.md
│  ├─ implementation-notes.md
│  ├─ database-design.md
│  ├─ third-party.md
│  └─ assets/screenshots/
├─ public/
│  ├─ index.php
│  ├─ router.php
│  ├─ .htaccess
│  ├─ favicon.ico
│  ├─ assets/
│  │  ├─ css/
│  │  ├─ js/               # Bootstrap、ECharts、admin-charts.js
│  │  ├─ fonts/
│  │  └─ images/
│  └─ uploads/             # 用户上传图片（不执行脚本）
├─ storage/
│  ├─ logs/
│  └─ sessions/
├─ tests/
│  ├─ smoke.php
│  ├─ frontend_smoke.php
│  └─ admin_smoke.php
├─ Dockerfile
├─ docker-compose.yml
├─ deploy.sh
├─ .env.example
├─ .dockerignore
├─ docker/              # Apache/PHP 容器配置、入口脚本、管理员初始化
├─ .gitignore
└─ README.md
```

## 文档索引

| 文档 | 内容 | 状态 |
| --- | --- | --- |
| [需求规格说明书](docs/requirements.md) | 项目背景、角色、功能/非功能需求、边界与验收 | 已更新实施状态 |
| [功能清单与模块说明](docs/feature-list.md) | 12 模块、权限矩阵、路由、验收 | 已更新实施状态 |
| [数据库初步方案](docs/database-plan.md) | 第一阶段初步方案 | **已被数据库设计文档取代** |
| [数据库设计文档](docs/database-design.md) | 17 表结构、E-R、数据字典、索引、实际验证 | 已更新后台字段与迁移 |
| [技术架构与目录规划](docs/architecture-plan.md) | MVC、环境、安全、部署架构 | 已更新后台实施状态 |
| [前台开发实施记录](docs/implementation-notes.md) | 前台页面、文件、安全、实际测试 | 已完成 |
| [后台管理实施记录](docs/admin-implementation.md) | 后台模块、代码、数据库变更、79 项测试、截图 | 已完成 |
| [第三方资源说明](docs/third-party.md) | Bootstrap、Icons、ECharts 版本与许可证 | 已完成 |
| [开发计划与文档规划](docs/development-plan.md) | 阶段、分工模板、文档与测试计划 | 已更新实际进度 |
| [课程设计交付文件](docs/deliverables/README.md) | 需求、设计、实现、用户手册、部署文档 PDF 与项目展示 PPTX | 初稿已生成；模板适配和真实资料替换待完成 |

## Docker Compose 快速部署（推荐）

需要 Docker Engine/Desktop 和 Compose v2。

```bash
cp .env.example .env
# 编辑 .env：填写 MYSQL_ROOT_PASSWORD、MYSQL_PASSWORD、INITIAL_ADMIN_PASSWORD
chmod +x deploy.sh
./deploy.sh up
```

默认访问 `http://localhost:8090`。`deploy.sh` 会构建 PHP 8.2 + Apache 镜像、启动 MySQL、执行安装 SQL 和迁移、初始化管理员密码。

常用命令：

```bash
./deploy.sh stop       # 停止
./deploy.sh start      # 启动
./deploy.sh logs       # 日志
./deploy.sh backup     # 备份数据库和文件
./deploy.sh down       # 移除容器，保留数据
./deploy.sh test       # 容器内运行三套测试
```

数据持久化：

- MySQL：命名卷 `techhub_db_data`
- 团队/个人作业：`data/team/`、`data/personal/`
- 上传图片：`public/uploads/`
- Session：命名卷 `techhub_sessions`（避免宿主机旧 Session 用户不一致）
- 日志：`storage/`

Docker 冷启动和容器内测试已完成；也可用 `ENV_FILE=.env.production ./deploy.sh up` 指定环境文件。Docker 相关文件：`Dockerfile`、`docker-compose.yml`、`.env.example`、`deploy.sh`、`docker/`。完整两种部署方式见 [部署文档](<docs/deliverables/冰冰冰队_部署文档(2412888_2411259_2413238_2412509).md>)。

## 本地开发启动命令

> 以下命令适用于不使用 Docker 的本机开发环境。正式部署优先使用 Docker Compose，或按部署文档使用 Apache。

### 启动数据库（若未运行）

```bat
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe" --defaults-file="D:\XiaoPiMianBan\phpstudy_pro\Extensions\MySQL8TechHub\my.ini" --console
```

### 启动 PHP 开发服务器

```bat
D:\XiaoPiMianBan\phpstudy_pro\Extensions\php\php8.2.36nts\php.exe -S 127.0.0.1:8090 -t public public/router.php
```

### 运行测试

```bat
D:\XiaoPiMianBan\phpstudy_pro\Extensions\php\php8.2.36nts\php.exe tests\smoke.php
D:\XiaoPiMianBan\phpstudy_pro\Extensions\php\php8.2.36nts\php.exe tests\frontend_smoke.php
D:\XiaoPiMianBan\phpstudy_pro\Extensions\php\php8.2.36nts\php.exe tests\admin_smoke.php
```

`tests/admin_smoke.php` 需要开发服务器已启动，并会自动清理测试业务数据。

### 管理员账号

- 用户名：`admin`
- 初始密码：仅保存在本地 `.env` 的 `INITIAL_ADMIN_PASSWORD`，不写入数据库明文或 Git 仓库。
- 后台地址：`/admin/login`；前台登录地址：`/login`。

## 安全要点

- PDO 预处理，不拼接用户输入；
- 密码 `password_hash()` / `password_verify()`；
- Session HttpOnly、SameSite=Lax、登录后重建 ID；
- 所有后台路由声明登录和权限码；
- 所有 POST 声明 CSRF，失败返回 403；
- 视图统一 `e()` 转义；
- 用户、角色模块额外要求超级管理员；
- 超级管理员不能自停用、自删除、自我降级，系统保留至少一个启用超级管理员；
- 图片上传校验扩展名、真实 MIME、图片内容、大小；
- 作业文件上传只允许文档扩展名，按分类保存到 Web 根之外；
- 下载和物理删除经过 `realpath` 目录边界校验；
- 用户上传目录禁止脚本执行；
- `.env` 被 `.gitignore` 忽略，SQL 不含数据库密码；
- 错误页不输出堆栈，日志不保存密码。

## 课程要求落实情况

| 要求 | 当前状态 |
| --- | --- |
| 表 > 10，约 15 张 | 已完成 17 张，21 个外键 |
| PHP MVC | 已完成并验证 |
| 每人独立 M/V/C | 待团队名单和真实分工，后续逐人落实 |
| 每个 PHP 文件注释头 | 已添加；作者信息待实际组员填写 |
| `data/install.sql` | 已创建、临时新库连续导入两次验证 |
| 团队/个人作业下载 | 前台专用下载页 + 后台文件管理，受控下载与日志已实测 |
| 前后台演示 | 前台完整，后台 11 个模块可演示 |
| ECharts 动态图表 | 已完成，4 张图从真实聚合接口取数 |
| 六份文档 + PPT | 5 份 PDF + 15 页 PPTX 已生成；学校模板适配与最终定稿待完成 |

## 下一步

1. 配置 Apache PHP 8.2 虚拟主机并做最终部署回归；
2. 决定并执行 Git 提交/推送，形成真实贡献记录；
3. 替换真实团队、成员、项目和文章资料；
4. 补修改密码/本人资料编辑；
5. 按学校模板完成课程文档和 PPT 定稿，补充真实资料与 Git 记录。
