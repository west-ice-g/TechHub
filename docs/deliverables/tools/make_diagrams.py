# -*- coding: utf-8 -*-
"""Generate TechHub course-design diagrams from the actual schema."""
import json
from pathlib import Path

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch, Rectangle
from matplotlib.font_manager import FontProperties

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / 'assets'
ASSETS.mkdir(parents=True, exist_ok=True)
SCHEMA = json.loads((ROOT / 'data' / 'schema.json').read_text(encoding='utf-8'))
FONT = FontProperties(fname=r'C:\Windows\Fonts\msyh.ttc')
FONT_B = FontProperties(fname=r'C:\Windows\Fonts\msyhbd.ttc')

NAVY = '#0f172a'
BLUE = '#2563eb'
TEAL = '#0d9488'
ORANGE = '#f59e0b'
SLATE = '#64748b'
BG = '#f8fafc'
GROUP_COLORS = {
    '账号权限': ('#dbeafe', '#1d4ed8'),
    '团队项目': ('#ccfbf1', '#0f766e'),
    '内容互动': ('#fef3c7', '#b45309'),
    '审计': ('#e2e8f0', '#475569'),
}
GROUPS = {
    'roles': '账号权限', 'permissions': '账号权限', 'role_permissions': '账号权限',
    'users': '账号权限', 'member_profiles': '账号权限',
    'teams': '团队项目', 'team_members': '团队项目', 'projects': '团队项目', 'project_members': '团队项目',
    'categories': '内容互动', 'articles': '内容互动', 'announcements': '内容互动',
    'messages': '内容互动', 'message_replies': '内容互动', 'files': '内容互动', 'file_downloads': '内容互动',
    'activity_logs': '审计',
}

# 17 tables: (x, y), with a compact set of key fields on the diagram.
BOXES = {
    'roles': (0.9, 8.42, ['id PK', 'code', 'name']),
    'permissions': (3.2, 8.42, ['id PK', 'code', 'module']),
    'role_permissions': (5.5, 8.42, ['role_id PK/FK', 'permission_id PK/FK', '']),
    'users': (7.8, 8.42, ['id PK', 'username', 'role_id FK']),
    'member_profiles': (10.1, 8.42, ['id PK', 'user_id FK', 'bio']),
    'teams': (7.8, 6.95, ['id PK', 'name', 'status']),
    'team_members': (5.5, 6.95, ['id PK', 'team_id FK', 'user_id FK']),
    'categories': (0.9, 6.95, ['id PK', 'parent_id FK', 'slug']),
    'projects': (7.8, 5.15, ['id PK', 'team_id FK', 'status']),
    'project_members': (5.5, 5.15, ['project_id PK/FK', 'team_member_id PK/FK', 'role_in_project']),
    'articles': (0.9, 5.15, ['id PK', 'category_id FK', 'author_id FK']),
    'announcements': (3.2, 5.15, ['id PK', 'publisher_id FK', 'status']),
    'messages': (0.9, 3.35, ['id PK', 'status', 'visitor_name']),
    'message_replies': (3.2, 3.35, ['id PK', 'message_id FK', 'admin_user_id FK']),
    'files': (5.5, 3.35, ['id PK', 'uploader_id FK', 'related_member_id FK']),
    'file_downloads': (7.8, 3.35, ['id PK', 'file_id FK', 'user_id FK']),
    'activity_logs': (10.1, 3.35, ['id PK', 'user_id FK', 'result']),
}

FK_EDGES = [
    ('roles', 'users'), ('roles', 'role_permissions'), ('permissions', 'role_permissions'),
    ('users', 'member_profiles'), ('users', 'team_members'), ('teams', 'team_members'),
    ('teams', 'projects'), ('team_members', 'project_members'), ('projects', 'project_members'),
    ('users', 'articles'), ('categories', 'articles'), ('users', 'announcements'),
    ('messages', 'message_replies'), ('users', 'message_replies'), ('users', 'files'),
    ('team_members', 'files'), ('files', 'file_downloads'), ('users', 'file_downloads'),
    ('users', 'activity_logs'), ('categories', 'categories'),
]


def rounded(ax, x, y, w, h, face, edge, lw=1.4, radius=0.12, z=3):
    patch = FancyBboxPatch((x, y), w, h, boxstyle=f'round,pad=0.02,rounding_size={radius}',
                           facecolor=face, edgecolor=edge, linewidth=lw, zorder=z)
    ax.add_patch(patch)
    return patch


def text(ax, x, y, s, size=8.5, color=NAVY, weight='normal', ha='left', va='center', z=5):
    ax.text(x, y, s, fontproperties=FONT_B if weight == 'bold' else FONT,
            fontsize=size, color=color, ha=ha, va=va, zorder=z)


def center(box):
    x, y, _ = box
    return x + 0.95, y + 0.47


def draw_er():
    fig, ax = plt.subplots(figsize=(16.8, 10.7), dpi=220)
    ax.set_xlim(0, 12.4)
    ax.set_ylim(1.85, 10.45)
    ax.axis('off')
    fig.patch.set_facecolor('white')
    ax.add_patch(Rectangle((0, 0), 12.8, 12, facecolor='white', edgecolor='none', zorder=0))

    text(ax, 0.15, 10.15, 'TechHub 数据库 E-R 图', 19, NAVY, 'bold')
    text(ax, 0.15, 9.86, '实际 MySQL 8.0.19 结构：17 张业务表、21 个外键；PK=主键，FK=外键', 9, SLATE)

    # Legend at the bottom, away from table boxes.
    lx = 0.55
    for i, (name, (fill, edge_c)) in enumerate(GROUP_COLORS.items()):
        rounded(ax, lx + i * 2.45, 2.35, 0.22, 0.13, fill, edge_c, lw=1)
        text(ax, lx + i * 2.45 + 0.3, 2.42, name, 7.8, SLATE)

    # Draw relationships first.
    for src, dst in FK_EDGES:
        sx, sy = center(BOXES[src])
        tx, ty = center(BOXES[dst])
        if src == dst:
            x, y, _ = BOXES[src]
            ax.annotate('', xy=(x + 0.95, y + 0.05), xytext=(x + 0.35, y + 0.05),
                        arrowprops=dict(arrowstyle='->', color='#94a3b8', lw=1, connectionstyle='arc3,rad=-1.2'),
                        zorder=1)
            continue
        ax.annotate('', xy=(tx, ty), xytext=(sx, sy),
                    arrowprops=dict(arrowstyle='-|>', color='#94a3b8', lw=1.05,
                                    shrinkA=34, shrinkB=34), zorder=1)

    for name, (x, y, fields) in BOXES.items():
        group = GROUPS[name]
        fill, edge_c = GROUP_COLORS[group]
        rounded(ax, x, y, 1.9, 0.94, fill, edge_c)
        text(ax, x + 0.12, y + 0.76, name, 8.7, NAVY, 'bold')
        text(ax, x + 1.78, y + 0.76, f'{next(t for t in SCHEMA["tables"] if t["name"] == name)["rows"]} 行',
             6.9, edge_c, ha='right')
        for j, field in enumerate(fields):
            if field:
                text(ax, x + 0.12, y + 0.53 - j * 0.2, field, 6.9, '#334155')

    text(ax, 0.15, 2.02,
         '说明：菱形关系由外键表达；多对多由 bridge 表表达：role_permissions、team_members、project_members。'
         '完整字段类型、默认值和约束见“完整数据字典”。', 7.8, SLATE)
    fig.savefig(ASSETS / 'er-diagram.png', dpi=220, bbox_inches='tight', facecolor='white')
    plt.close(fig)


def arrow(ax, x1, y1, x2, y2, color=BLUE, style='-|>', lw=1.5, rad=0.0):
    ax.add_patch(FancyArrowPatch((x1, y1), (x2, y2), arrowstyle=style, mutation_scale=12,
                                 linewidth=lw, color=color, connectionstyle=f'arc3,rad={rad}', zorder=2))


def draw_architecture():
    fig, ax = plt.subplots(figsize=(15, 8.8), dpi=220)
    ax.set_xlim(0, 15); ax.set_ylim(0, 9.45); ax.axis('off'); fig.patch.set_facecolor('white')
    text(ax, 0.4, 9.15, 'TechHub 系统总体架构', 19, NAVY, 'bold')
    text(ax, 0.4, 8.78, '原生 PHP MVC · 前端控制器 · PDO · MySQL 8.0 · 本地 Bootstrap / ECharts', 9, SLATE)

    layers = [
        ('用户层', 7.15, 1.2, '#eff6ff', '#3b82f6', '访客浏览器 · 团队成员 · 内容编辑 · 超级管理员'),
        ('Web 入口', 5.85, 1.2, '#ecfeff', '#0891b2', 'Apache 2.4 / PHP 8.2 · public/index.php · .htaccess 重写'),
        ('应用核心', 4.55, 1.2, '#f0fdf4', '#16a34a', 'Router → Middleware（Auth / Guest / Permission / CSRF）→ Controller'),
        ('业务层', 3.25, 1.2, '#fefce8', '#ca8a04', 'Controller · Model · Support（Markdown / DownloadPath / Upload）'),
        ('展示层', 1.95, 1.2, '#fdf2f8', '#db2777', 'View 模板 · layouts/app · layouts/admin · JSON Response'),
        ('数据层', 0.65, 1.2, '#f1f5f9', '#475569', 'PDO 预处理 · MySQL 8.0.19 · InnoDB · 17 表 / 21 外键'),
    ]
    for title, y, h, fill, edge_c, desc in layers:
        rounded(ax, 2.5, y, 9.9, h, fill, edge_c, lw=1.6, radius=0.15)
        text(ax, 2.8, y + 0.78, title, 10, edge_c, 'bold')
        text(ax, 4.55, y + 0.78, desc, 8.5, '#334155')
    for y in [7.05, 5.75, 4.45, 3.15, 1.85]:
        arrow(ax, 7.45, y, 7.45, y - 0.42, '#64748b')

    rounded(ax, 0.45, 2.15, 1.65, 3.9, '#fff7ed', ORANGE, lw=1.4)
    text(ax, 0.68, 5.7, '本地资源', 9, '#9a3412', 'bold')
    for i, s in enumerate(['Bootstrap 5', 'Bootstrap Icons', 'ECharts 5.5.1', '示例 SVG', '上传目录']):
        text(ax, 0.68, 5.2 - i * 0.52, '· ' + s, 7.8, '#7c2d12')
    rounded(ax, 12.75, 2.15, 1.8, 3.9, '#f8fafc', '#475569', lw=1.4)
    text(ax, 12.98, 5.7, '安全边界', 9, '#334155', 'bold')
    for i, s in enumerate(['Session 安全', 'CSRF Token', 'RBAC 服务端', '上传白名单', '路径边界']):
        text(ax, 12.98, 5.2 - i * 0.52, '· ' + s, 7.6, '#475569')
    fig.savefig(ASSETS / 'architecture.png', dpi=220, bbox_inches='tight', facecolor='white')
    plt.close(fig)


def draw_mvc():
    fig, ax = plt.subplots(figsize=(14, 7.2), dpi=220)
    ax.set_xlim(0, 14); ax.set_ylim(0, 7); ax.axis('off'); fig.patch.set_facecolor('white')
    text(ax, 0.4, 6.55, 'TechHub MVC 请求生命周期', 19, NAVY, 'bold')
    text(ax, 0.4, 6.2, '所有请求进入统一入口，经路由和中间件后交给 Controller；Model 访问 MySQL，View 输出模板。', 9, SLATE)
    steps = [
        (0.55, 3.1, 'public/index.php\n统一入口', '#eff6ff', '#2563eb'),
        (2.95, 3.1, 'Router\n方法 / 路径 / 中间件', '#ecfeff', '#0891b2'),
        (5.35, 3.1, 'Middleware\nAuth · Guest · Permission · CSRF', '#f0fdf4', '#16a34a'),
        (7.75, 3.1, 'Controller\n接收请求 / 参数校验', '#fefce8', '#ca8a04'),
        (10.15, 3.1, 'Model\nPDO 预处理 SQL', '#f5f3ff', '#7c3aed'),
        (12.55, 3.1, 'MySQL\n17 张业务表', '#fff7ed', '#ea580c'),
    ]
    for x, y, label, fill, edge_c in steps:
        rounded(ax, x, y, 1.95, 1.25, fill, edge_c, lw=1.4)
        text(ax, x + 0.98, y + 0.62, label, 8.5, NAVY, 'bold', ha='center')
    for x in [2.5, 4.9, 7.3, 9.7, 12.1]:
        arrow(ax, x, 3.72, x + 0.35, 3.72, '#64748b')
    rounded(ax, 7.75, 0.65, 4.4, 1.3, '#fdf2f8', '#db2777', lw=1.4)
    text(ax, 9.95, 1.35, 'View 模板 / JSON Response\nlayouts/app · layouts/admin', 8.7, NAVY, 'bold', ha='center')
    arrow(ax, 8.7, 3.05, 8.7, 2.0, '#db2777')
    text(ax, 10.25, 2.5, '业务数据与页面结构', 7.8, '#9d174d')
    text(ax, 0.55, 1.3, '安全贯穿点：PDO 预处理、HTML 转义、CSRF 校验、登录限流、RBAC 权限码、上传与下载路径校验。', 9, '#334155')
    fig.savefig(ASSETS / 'mvc-flow.png', dpi=220, bbox_inches='tight', facecolor='white')
    plt.close(fig)


def draw_modules():
    fig, ax = plt.subplots(figsize=(14.5, 8.6), dpi=220)
    ax.set_xlim(0, 14.5); ax.set_ylim(0, 8.6); ax.axis('off'); fig.patch.set_facecolor('white')
    text(ax, 0.4, 8.12, 'TechHub 系统功能模块图', 19, NAVY, 'bold')
    text(ax, 0.4, 7.75, '11 个后台业务模块 + 完整前台展示；所有模块共用用户、权限、日志和 MySQL 数据层。', 9, SLATE)
    modules = [
        ('用户与认证', '登录 / 退出 / 限流 / 资料查看', '#eff6ff', '#2563eb'),
        ('团队信息', '团队资料 / 方向 / 技术栈 / Logo / 状态', '#ccfbf1', '#0f766e'),
        ('团队成员', '成员关系 / 职位 / 简介 / 头像 / 离队', '#f0fdf4', '#16a34a'),
        ('项目管理', '项目 CRUD / 封面 / 状态 / 成员 / 上下线', '#fefce8', '#ca8a04'),
        ('文章管理', '分类 / 草稿 / 发布 / 归档 / 搜索', '#fff7ed', '#ea580c'),
        ('公告管理', '类型 / 置顶 / 有效期 / 发布下线', '#fdf2f8', '#db2777'),
        ('留言互动', '提交 / 审核 / 回复 / 删除', '#f5f3ff', '#7c3aed'),
        ('文件资源', '上传 / 下载 / 公开 / 隐藏 / 物理删除', '#ecfeff', '#0891b2'),
        ('角色权限', '角色 CRUD / 权限矩阵 / 菜单 / 接口', '#f1f5f9', '#475569'),
        ('操作日志', '操作人 / 模块 / 动作 / 结果 / IP', '#f8fafc', '#334155'),
        ('数据统计', '统计卡片 / 4 张 ECharts / JSON 接口', '#e0f2fe', '#0369a1'),
        ('前台展示', '首页 / 团队 / 成员 / 项目 / 文章 / 公告 / 下载', '#fdf4ff', '#a21caf'),
    ]
    for i, (title, desc, fill, edge_c) in enumerate(modules):
        row, col = divmod(i, 4)
        x = 0.45 + col * 3.5
        y = 5.7 - row * 2.25
        rounded(ax, x, y, 3.15, 1.55, fill, edge_c, lw=1.3)
        text(ax, x + 0.2, y + 1.1, title, 10, edge_c, 'bold')
        # Wrap description to two lines.
        words = desc.split(' / ')
        lines = [' / '.join(words[:2]), ' / '.join(words[2:])] if len(words) > 2 else [desc]
        for j, line in enumerate(lines[:2]):
            text(ax, x + 0.2, y + 0.65 - j * 0.3, line, 7.3, '#334155')
    fig.savefig(ASSETS / 'modules.png', dpi=220, bbox_inches='tight', facecolor='white')
    plt.close(fig)


def draw_business_flow():
    fig, ax = plt.subplots(figsize=(15.5, 8.2), dpi=220)
    ax.set_xlim(0, 15.5); ax.set_ylim(0, 8.2); ax.axis('off'); fig.patch.set_facecolor('white')
    text(ax, 0.4, 7.7, 'TechHub 系统业务流程', 19, NAVY, 'bold')
    text(ax, 0.4, 7.32, '访客浏览与互动、后台内容管理、数据库更新与前台展示形成闭环。', 9, SLATE)

    # Visitor row
    rounded(ax, 0.45, 5.0, 14.65, 1.65, '#f8fafc', '#cbd5e1', lw=1)
    text(ax, 0.7, 6.35, '访客 / 普通用户', 10, '#334155', 'bold')
    visitor = [
        (0.7, 5.35, '访问首页', '#eff6ff', '#2563eb'),
        (2.95, 5.35, '浏览团队/项目/文章', '#eff6ff', '#2563eb'),
        (5.55, 5.35, '搜索 / 筛选 / 分页', '#eff6ff', '#2563eb'),
        (8.05, 5.35, '提交留言 / 下载资料', '#eff6ff', '#2563eb'),
        (11.0, 5.35, '前台展示更新', '#ecfeff', '#0891b2'),
        (13.0, 5.35, '操作日志 / 下载日志', '#f1f5f9', '#475569'),
    ]
    for x, y, label, fill, edge_c in visitor:
        rounded(ax, x, y, 1.85, 0.9, fill, edge_c, lw=1.2)
        text(ax, x + 0.92, y + 0.45, label, 7.5, NAVY, 'bold', ha='center')
    for x in [2.55, 4.8, 7.75, 10.55, 12.85]:
        arrow(ax, x, 5.8, x + 0.28, 5.8, '#64748b')

    # Admin row
    rounded(ax, 0.45, 2.2, 14.65, 1.65, '#f8fafc', '#cbd5e1', lw=1)
    text(ax, 0.7, 3.55, '后台管理员', 10, '#334155', 'bold')
    admin = [
        (0.7, 2.55, '登录 / Session', '#f5f3ff', '#7c3aed'),
        (2.95, 2.55, 'Role + Permission', '#f5f3ff', '#7c3aed'),
        (5.35, 2.55, '内容 CRUD', '#fefce8', '#ca8a04'),
        (7.75, 2.55, '审核 / 回复', '#fefce8', '#ca8a04'),
        (10.0, 2.55, '文件上传/受控下载', '#f0fdf4', '#16a34a'),
        (12.55, 2.55, '统计与审计', '#f1f5f9', '#475569'),
    ]
    for x, y, label, fill, edge_c in admin:
        rounded(ax, x, y, 1.95, 0.9, fill, edge_c, lw=1.2)
        text(ax, x + 0.98, y + 0.45, label, 7.4, NAVY, 'bold', ha='center')
    for x in [2.65, 4.9, 7.3, 9.7, 12.2]:
        arrow(ax, x, 3.0, x + 0.28, 3.0, '#64748b')
    arrow(ax, 12.9, 3.55, 12.9, 4.95, '#2563eb', lw=2)
    text(ax, 13.1, 4.25, '数据库更新', 8, BLUE, 'bold')
    rounded(ax, 5.3, 0.45, 4.8, 0.95, '#0f172a', '#0f172a', lw=1)
    text(ax, 7.7, 0.93, 'MySQL：事务 · 外键 · 预处理 · 状态与历史保留', 8.3, 'white', 'bold', ha='center')
    arrow(ax, 7.55, 2.15, 7.55, 1.45, '#334155')
    fig.savefig(ASSETS / 'business-flow.png', dpi=220, bbox_inches='tight', facecolor='white')
    plt.close(fig)


if __name__ == '__main__':
    draw_er()
    draw_architecture()
    draw_mvc()
    draw_modules()
    draw_business_flow()
    print('diagrams generated')
