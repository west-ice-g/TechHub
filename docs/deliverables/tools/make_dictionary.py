# -*- coding: utf-8 -*-
"""Build a full data dictionary from the real MySQL schema dump."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
schema = json.loads((ROOT / 'data' / 'schema.json').read_text(encoding='utf-8'))
tables = {t['name']: t for t in schema['tables']}
order = [
    'roles', 'permissions', 'role_permissions', 'users', 'member_profiles',
    'teams', 'team_members', 'categories', 'projects', 'project_members',
    'articles', 'announcements', 'messages', 'message_replies', 'files',
    'file_downloads', 'activity_logs',
]
purposes = {
    'roles': '后台角色定义，内置超级管理员、内容编辑、团队成员。',
    'permissions': '权限码定义，由初始化 SQL 提供，代码按权限码校验。',
    'role_permissions': '角色与权限多对多映射。',
    'users': '后台登录账号、角色、状态和登录信息。',
    'member_profiles': '与账号一对一的公开成员资料。',
    'teams': '团队实体、研究方向、技术栈、联系方式和展示状态。',
    'team_members': '团队与用户多对多成员关系及历史状态。',
    'categories': '文章分类，支持父子层级。',
    'projects': '项目资料、状态、发布、封面和成果。',
    'project_members': '项目与团队成员多对多关联及项目角色。',
    'articles': '技术文章、草稿/发布/归档和浏览量。',
    'announcements': '团队公告、类型、置顶和有效期。',
    'messages': '访客留言、审核状态和来源信息。',
    'message_replies': '管理员对留言的回复，可公开或隐藏。',
    'files': '团队作业、个人作业和通用资源文件。',
    'file_downloads': '真实文件下载流水。',
    'activity_logs': '后台操作审计，记录操作结果。',
}

lines = []
lines.append('# 完整数据字典（从实际 MySQL 结构生成）')
lines.append('')
lines.append(f"- 数据库：`{schema['database']}`")
lines.append(f"- MySQL：`{schema['server']}`")
lines.append(f"- 生成时间：{schema['generated_at']}")
lines.append(f"- 表数量：{len(order)}")
lines.append('- 说明：以下字段类型、可空性、默认值、主键/索引和外键均读取自 `information_schema`；行数为生成时实际计数。')
lines.append('- 实际 SQL 未设置列注释，因此字典不生成空“说明”列；字段业务含义由表说明、字段名和上下文表达。')
lines.append('')
lines.append('## 表清单')
lines.append('')
lines.append('| 序号 | 表名 | 业务用途 | 实际行数 |')
lines.append('| --- | --- | --- | --- |')
for i, name in enumerate(order, 1):
    lines.append(f"| {i} | `{name}` | {purposes.get(name, '')} | {tables[name]['rows']} |")
lines.append('')

group_titles = {
    'roles': '账号与权限', 'permissions': '账号与权限', 'role_permissions': '账号与权限',
    'users': '账号与权限', 'member_profiles': '账号与权限',
    'teams': '团队与项目', 'team_members': '团队与项目',
    'projects': '团队与项目', 'project_members': '团队与项目',
    'categories': '内容管理', 'articles': '内容管理', 'announcements': '内容管理',
    'messages': '留言与文件', 'message_replies': '留言与文件',
    'files': '留言与文件', 'file_downloads': '留言与文件',
    'activity_logs': '审计',
}
current_group = ''
for i, name in enumerate(order, 1):
    t = tables[name]
    group = group_titles[name]
    if group != current_group:
        lines.append(f"## {group}")
        lines.append('')
        current_group = group
    lines.append(f"### 4.{i} `{name}`")
    lines.append('')
    lines.append(purposes.get(name, ''))
    lines.append('')
    lines.append('| 字段 | 类型 | 可空 | 默认值 | 键 | 附加 |')
    lines.append('| --- | --- | --- | --- | --- | --- |')
    for c in t['columns']:
        default = c['column_default']
        default = 'NULL' if default is None else str(default).replace('|', '\\|')
        key = c['column_key'] or ''
        extra = c['extra'] or ''
        comment = (c['column_comment'] or '').replace('|', '\\|')
        lines.append(
            f"| `{c['column_name']}` | `{c['column_type']}` | {c['is_nullable']} | {default} | "
            f"{key} | {extra} | {comment} |"
        )
    lines.append('')
    if t['foreign_keys']:
        fk_bits = []
        for fk in t['foreign_keys']:
            fk_bits.append(f"`{fk['column_name']}` → `{fk['referenced_table_name']}.{fk['referenced_column_name']}`")
        lines.append('**外键**：' + '；'.join(fk_bits))
    else:
        lines.append('**外键**：无')
    if t['indexes']:
        idx_bits = []
        for idx in t['indexes']:
            unique = '唯一' if int(idx['non_unique']) == 0 else '普通'
            idx_bits.append(f"`{idx['index_name']}`（{unique}：{idx['cols']}）")
        lines.append('')
        lines.append('**索引**：' + '；'.join(idx_bits))
    lines.append('')
    lines.append(f"**生成时实际行数**：{t['rows']}")
    lines.append('')

(ROOT / 'data' / 'data-dictionary.md').write_text('\n'.join(lines), encoding='utf-8')
print('dictionary rows', sum(len(t['columns']) for t in schema['tables']))
