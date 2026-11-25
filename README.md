# TechHub

高校技术团队展示与管理平台。前台展示团队、成员、项目、文章、公告和课程资料，后台提供内容管理、留言审核、文件管理、RBAC 和 ECharts 统计。

## 1. 快速部署

推荐使用 **Docker Compose**。只需 Docker Engine/Desktop 和 Compose v2。

```bash
# 1. 准备环境变量
cp .env.example .env

# 2. 编辑 .env，至少修改以下密码
#   MYSQL_ROOT_PASSWORD
#   MYSQL_PASSWORD
#   DB_PASSWORD（必须与 MYSQL_PASSWORD 相同）
#   INITIAL_ADMIN_PASSWORD

# 3. 启动
chmod +x deploy.sh
./deploy.sh up
```

启动后访问：

```text
前台：http://localhost:8090
后台：http://localhost:8090/admin/login
```

`deploy.sh up` 会自动完成：

1. 构建 PHP 8.2 + Apache 镜像；
2. 启动 MySQL 8.0；
3. 首次启动时导入 `data/install.sql`；
4. 执行 `data/migrations/` 下的迁移；
5. 按 `.env` 初始化管理员和编辑账号密码；
6. 输出访问地址。

### 1.1 第一次登录后台

```text
用户名：admin
密码：.env 中的 INITIAL_ADMIN_PASSWORD
```

首次启动时 `docker/init-admin.php` 会把密码写入数据库的 `password_hash`，数据库中不保存明文。管理员登录过后，重复执行部署不会重置密码。

如果确实需要重置：

```bash
# 在 .env 中设置 RESET_ADMIN_PASSWORD=1，并修改 INITIAL_ADMIN_PASSWORD
./deploy.sh init-admin
# 完成后建议改回 RESET_ADMIN_PASSWORD=0
```

### 1.2 常用服务命令

```bash
./deploy.sh up         # 构建、启动、迁移、初始化管理员
./deploy.sh start      # 启动已有容器
./deploy.sh stop       # 停止容器
./deploy.sh restart    # 重启容器
./deploy.sh down       # 移除容器，保留数据库卷和文件
./deploy.sh logs       # 查看日志
./deploy.sh status     # 查看容器状态
./deploy.sh migrate    # 对已有数据库执行迁移
./deploy.sh init-admin # 初始化/重置管理员密码
./deploy.sh backup     # 备份数据库和上传文件
```

`down` 不会删除数据库卷和上传文件；不要随意执行 `docker compose down -v`，它会删除数据库数据。

### 1.3 指定其他环境文件

```bash
ENV_FILE=.env.production ./deploy.sh up
```

## 2. Docker 部署结构

| 服务/文件 | 作用 |
| --- | --- |
| `app` | PHP 8.2 + Apache，运行 TechHub |
| `db` | MySQL 8.0，首次启动自动导入 SQL |
| `docker-compose.yml` | 服务编排、端口、健康检查、卷 |
| `Dockerfile` | 构建 PHP 8.2 + Apache 镜像 |
| `deploy.sh` | 一条命令启动、迁移、备份和管理员初始化 |
| `docker/entrypoint.sh` | 创建和授权运行目录 |
| `docker/init-admin.php` | 从环境变量初始化管理员/编辑账号 |
| `.env.example` | 环境变量模板，不包含真实密码 |

### 2.1 数据持久化

| 数据 | 位置/卷 |
| --- | --- |
| MySQL 数据 | Docker 命名卷 `techhub_db_data` |
| PHP Session | Docker 命名卷 `techhub_sessions` |
| 团队作业文件 | `data/team/` |
| 个人作业文件 | `data/personal/` |
| 通用资源文件 | `data/resources/` |
| 上传图片 | `public/uploads/` |
| 应用日志 | `storage/logs/` |

团队/个人/资源文件和上传图片使用宿主机绑定目录，可直接备份；数据库和 Session 使用命名卷。

## 3. 本地 PHP + MySQL + Apache 部署

不使用 Docker 时，按以下步骤部署。

### 3.1 环境要求

- PHP 8.2+
- MySQL 8.0+
- Apache 2.4+（或 Nginx）
- PHP 扩展：`pdo_mysql`、`fileinfo`、`mbstring`、`openssl`、`curl`

### 3.2 创建数据库

```sql
CREATE DATABASE `techhub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

CREATE USER 'techhub_app'@'127.0.0.1'
  IDENTIFIED BY '替换为应用强密码';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON `techhub`.* TO 'techhub_app'@'127.0.0.1';

FLUSH PRIVILEGES;
```

### 3.3 导入数据库

```bash
mysql -u root -p --default-character-set=utf8mb4 < data/install.sql
mysql -u root -p techhub < data/migrations/2026-11-25_frontend.sql
mysql -u root -p techhub < data/migrations/2026-11-25_admin.sql
mysql -u root -p techhub < data/migrations/2026-11-25_admin_demo_dates.sql
```

验证：

```sql
USE techhub;
SHOW TABLES;
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema = 'techhub' AND table_type = 'BASE TABLE';
SELECT COUNT(*) FROM information_schema.referential_constraints
WHERE constraint_schema = 'techhub';
```

应为 17 张表、21 个外键。

### 3.4 配置 `.env`

```bash
cp .env.example .env
```

本地直接运行 PHP 时修改：

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8090
APP_PORT=8090

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=techhub
DB_USERNAME=techhub_app
DB_PASSWORD=替换为与 MySQL 应用账号一致的密码
DB_CHARSET=utf8mb4
```

如果使用 Docker，保持 `DB_HOST=db`，不要把 `db` 改为 `127.0.0.1`。

### 3.5 启动

开发验证可以先用 PHP 内置服务器：

```bash
php -S 127.0.0.1:8090 -t public public/router.php
```

生产环境应使用 Apache/Nginx，Web 根目录必须指向 `public/`。Apache 虚拟主机示例：

```apache
<VirtualHost *:80>
    ServerName techhub.example.com
    DocumentRoot "/var/www/techhub/public"

    <Directory "/var/www/techhub/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "/var/log/apache2/techhub-error.log"
    CustomLog "/var/log/apache2/techhub-access.log" combined
</VirtualHost>
```

启用 `mod_rewrite`，按服务器实际 PHP-FPM/Fcgid 方式处理 `.php`。上传目录不能执行 PHP 脚本。

### 3.6 文件权限

以下目录必须可写：

```text
storage/sessions/
storage/logs/
public/uploads/
data/team/
data/personal/
data/resources/
```

Linux 示例：

```bash
chown -R www-data:www-data storage public/uploads data/team data/personal data/resources
chmod -R 775 storage public/uploads data/team data/personal data/resources
chmod 640 .env
```

`.env` 禁止提交 Git，禁止放在 `public/` 下。

## 4. 网站使用

### 4.1 前台入口

| 地址 | 页面 |
| --- | --- |
| `/` | 首页：团队、数据、项目、文章、公告、下载入口 |
| `/team` | 团队介绍 |
| `/members` | 成员列表、搜索、分页 |
| `/projects` | 项目列表、状态筛选、搜索 |
| `/articles` | 文章列表、分类筛选、搜索 |
| `/announcements` | 公告 |
| `/messages` | 留言板 |
| `/downloads` | 团队作业 / 个人作业 |
| `/login` | 前台登录 |

系统不开放公开注册。前台账号由超级管理员在后台创建。

### 4.2 后台入口

```text
http://localhost:8090/admin/login
```

后台菜单：

- 数据统计首页
- 团队信息
- 团队成员
- 项目管理
- 文章分类
- 文章管理
- 公告管理
- 留言管理
- 文件管理
- 用户管理
- 角色权限
- 操作日志

不同角色看到的菜单不同，权限以服务端校验为准。

### 4.3 常见管理流程

**添加成员**

1. 先在“用户管理”创建账号并分配角色；
2. 进入“团队成员” → “添加成员”；
3. 选择尚未加入团队的账号；
4. 填写职位、职责、加入日期、介绍和头像；
5. 保存后前台成员页显示。

**发布项目**

1. 进入“项目管理” → “新增项目”；
2. 填写标题、摘要、介绍、技术栈和成果；
3. 上传封面；
4. 勾选项目成员并填写项目内角色；
5. 勾选“发布到前台”；
6. 保存后前台项目列表可见。

**发布文章**

1. 进入“文章管理” → “新增文章”；
2. 选择分类，填写摘要和正文；
3. 草稿不会出现在前台；
4. 发布后前台可见；
5. 可转草稿或归档。

**审核留言**

1. 进入“留言管理”；
2. 打开待审核留言；
3. 点击“通过审核”；
4. 填写回复并选择公开或仅后台可见；
5. 访客邮箱不会在前台公开。

**上传作业文件**

1. 进入“文件管理” → “上传文件”；
2. 选择团队作业、个人作业或通用资源；
3. 填写标题、说明和关联成员；
4. 设置公开状态和启用状态；
5. 团队文件保存到 `data/team`，个人文件保存到 `data/personal`。

## 5. 常见故障排查

| 现象 | 排查 |
| --- | --- |
| Docker 容器启动失败 | `./deploy.sh logs` 查看 app/db 日志 |
| 数据库连接失败 | 检查 `.env` 中 `DB_HOST`、端口、用户名和密码 |
| 首页 500 | 查看 `storage/logs/app-*.log` |
| 后台 403 | 当前角色无权限，检查“角色权限”配置 |
| CSRF 失败 | 返回 403，刷新页面重新提交 |
| 上传失败 | 检查 `public/uploads` 和 `data/*` 权限、PHP 上传限制 |
| 页面 404（非首页） | Apache 检查 `AllowOverride All` 和 `mod_rewrite`；Nginx 检查 `try_files` |
| 中文乱码 | 确认数据库/连接字符集为 utf8mb4 |
| ECharts 空白 | 使用有 `stats.view` 权限的账号登录，访问 `/admin/api/stats` |
| Docker 数据丢失 | 是否执行过 `docker compose down -v`；正常停止用 `./deploy.sh down` |

## 6. 项目结构

```text
TechHub/
├─ app/Controllers/        # 前台和后台控制器
├─ app/Models/             # PDO 数据访问
├─ app/Views/              # 前台、后台、错误页
├─ app/Middleware/         # Auth / Guest / Permission / CSRF
├─ core/                   # Router、Database、Model、Auth、Session、Upload
├─ config/                 # app、database、routes
├─ data/
│  ├─ install.sql
│  ├─ migrations/
│  ├─ team/                # 团队作业
│  ├─ personal/            # 个人作业
│  └─ resources/           # 通用资源
├─ public/                 # Web 根目录
│  ├─ assets/
│  └─ uploads/
├─ storage/                # Session、日志
├─ Dockerfile
├─ docker-compose.yml
├─ deploy.sh
├─ docker/
└─ .env.example
```

## 7. 技术栈

- PHP 8.2，原生 MVC，无 Laravel/ThinkPHP
- MySQL 8.0，PDO 原生预处理
- Bootstrap 5.3.3、Bootstrap Icons、Apache ECharts，全部本地化
- Apache 2.4（Docker 容器内已验证）
- Docker Compose v2
- 17 张业务表、21 个外键

## 8. 安全说明

- `.env` 已被 `.gitignore` 忽略，禁止提交真实密码；
- 密码只保存 `password_hash`；
- 所有后台路由执行登录和权限校验；
- 所有 POST 执行 CSRF 校验；
- 文件上传校验扩展名、MIME 和大小；
- 作业文件位于 Web 根目录之外，下载经过受控路径校验；
- 上传目录禁止执行脚本。
