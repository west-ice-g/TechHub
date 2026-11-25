# -*- coding: utf-8 -*-
"""Assemble Markdown documents, render HTML, and print PDFs with Edge."""
import json
import re
import shutil
import subprocess
import sys
import time
from pathlib import Path

import markdown

ROOT = Path(__file__).resolve().parents[1]
PROJECT = ROOT.parents[1]
TEAM = '冰冰冰队'
MEMBERS = [
    ('刚栋冰', '2412888', '组长'),
    ('成钧翔', '2411259', '项目与内容负责人'),
    ('罗清', '2413238', '前台业务负责人'),
    ('王毅豪', '2412509', '互动与后台负责人'),
]
SUFFIX = '(2412888_2411259_2413238_2412509)'
EDGE = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'

DOCS = [
    ('需求文档', f'{TEAM}_需求文档{SUFFIX}'),
    ('设计文档', f'{TEAM}_设计文档{SUFFIX}'),
    ('实现文档', f'{TEAM}_实现文档{SUFFIX}'),
    ('用户手册', f'{TEAM}_用户手册{SUFFIX}'),
    ('部署文档', f'{TEAM}_部署文档{SUFFIX}'),
]

PURPOSES = {
    'roles': '后台角色', 'permissions': '权限码', 'role_permissions': '角色权限映射',
    'users': '后台账号', 'member_profiles': '公开成员资料', 'teams': '团队',
    'team_members': '团队成员关系', 'categories': '文章分类', 'projects': '项目',
    'project_members': '项目成员', 'articles': '技术文章', 'announcements': '公告',
    'messages': '访客留言', 'message_replies': '留言回复', 'files': '文件资源',
    'file_downloads': '下载流水', 'activity_logs': '操作日志',
}


def table_summary():
    schema = json.loads((ROOT / 'data' / 'schema.json').read_text(encoding='utf-8'))
    order = list(PURPOSES)
    by_name = {t['name']: t for t in schema['tables']}
    lines = [
        '| 序号 | 表名 | 业务用途 | 字段数 | 主键 | 外键数 | 实际行数 |',
        '| --- | --- | --- | --- | --- | --- | --- |',
    ]
    for i, name in enumerate(order, 1):
        t = by_name[name]
        pk = ', '.join(c['column_name'] for c in t['columns'] if c['column_key'] == 'PRI') or '—'
        lines.append(
            f"| {i} | `{name}` | {PURPOSES[name]} | {len(t['columns'])} | `{pk}` | "
            f"{len(t['foreign_keys'])} | {t['rows']} |"
        )
    return '\n'.join(lines)


def dictionary_block():
    source = (ROOT / 'data' / 'data-dictionary.md').read_text(encoding='utf-8')
    lines = source.splitlines()
    if lines and lines[0].startswith('# '):
        lines = lines[1:]
    converted = []
    for line in lines:
        if line.startswith('### '):
            line = '#### ' + line[4:]
        elif line.startswith('## '):
            line = '### ' + line[3:]
        line = re.sub(r'^#### 4\.\d+ ', '#### ', line)
        converted.append(line)
    return '\n'.join(converted).strip()


def assemble_design():
    source = ROOT / f'{TEAM}_设计文档{SUFFIX}.md'
    text = source.read_text(encoding='utf-8')
    text = text.replace('<!-- TABLE_SUMMARY -->', table_summary())
    text = text.replace('<!-- DATA_DICTIONARY -->', dictionary_block())
    source.write_text(text, encoding='utf-8')
    return source


def image_uri(match):
    rel = match.group(1)
    p = ROOT / rel
    if not p.exists():
        return match.group(0)
    return 'src="' + p.as_uri() + '"'


CSS = r"""
@page { size: A4; margin: 16mm 15mm 17mm 15mm; }
* { box-sizing: border-box; }
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
body {
  width: 100%; overflow-wrap: anywhere;
  font-family: "Microsoft YaHei", "SimSun", sans-serif;
  color: #1e293b; font-size: 10.5pt; line-height: 1.75; margin: 0;
}
.cover {
  height: 255mm; display: flex; flex-direction: column; justify-content: center; overflow: hidden;
  padding: 12mm 12mm 10mm 12mm; color: white; page-break-after: always;
  background: linear-gradient(145deg, #0f172a 0%, #14274d 55%, #1d4ed8 100%);
  border-radius: 0;
}
.cover-brand { font-size: 15pt; font-weight: 700; color: #93c5fd; letter-spacing: .08em; }
.cover-kicker { margin-top: 25mm; color: #bfdbfe; font-size: 10pt; letter-spacing: .16em; text-transform: uppercase; }
.cover h1 { color: white; font-size: 34pt; line-height: 1.3; margin: 4mm 0 3mm; border: none; padding: 0; page-break-before: avoid; }
.cover-sub { color: #dbeafe; font-size: 15pt; margin-bottom: 13mm; }
.cover-team { font-size: 20pt; font-weight: 700; color: #fff; margin-top: 6mm; }
.cover ul { list-style: none; padding: 0; color: #e2e8f0; font-size: 11pt; margin: 4mm 0; }
.cover li { margin: 1.2mm 0; }
.cover-foot { margin-top: 20mm; border-top: 1px solid rgba(255,255,255,.3); padding-top: 4mm; color: #cbd5e1; font-size: 9pt; }
h1, h2, h3, h4 { color: #0f172a; font-weight: 700; page-break-after: avoid; }
h2 { font-size: 18pt; color: #1d4ed8; border-bottom: 2px solid #dbeafe; padding-bottom: 2mm; margin: 0 0 5mm; page-break-before: always; }
h3 { font-size: 12.5pt; color: #0f172a; margin-top: 6mm; margin-bottom: 2mm; }
h4 { font-size: 10.5pt; color: #0f766e; margin-top: 4mm; margin-bottom: 1.5mm; }
p { margin: 2mm 0; text-align: justify; }
ul, ol { margin: 2mm 0 3mm; padding-left: 7mm; }
li { margin: 1mm 0; }
a { color: #1d4ed8; text-decoration: none; }
strong { color: #0f172a; }
code { font-family: Consolas, "Courier New", monospace; background: #f1f5f9; color: #0f766e; padding: .2mm 1mm; border-radius: 2px; font-size: 9pt; overflow-wrap: anywhere; word-break: break-word; }
pre { background: #0f172a; color: #e2e8f0; padding: 3.5mm 4mm; border-radius: 3mm; font-size: 8.4pt; line-height: 1.55; overflow-wrap: anywhere; white-space: pre-wrap; page-break-inside: avoid; }
pre code { background: transparent; color: inherit; padding: 0; }
blockquote { background: #eff6ff; border-left: 3px solid #3b82f6; margin: 3mm 0; padding: 2.5mm 4mm; color: #334155; }
table { border-collapse: collapse; width: 100%; margin: 3mm 0 4mm; font-size: 8.4pt; line-height: 1.5; }
thead { display: table-header-group; }
.dict-table { table-layout: fixed; font-size: 8pt; }
.dict-table th:nth-child(1), .dict-table td:nth-child(1) { width: 18%; white-space: nowrap; }
.dict-table th:nth-child(2), .dict-table td:nth-child(2) { width: 18%; }
.dict-table th:nth-child(3), .dict-table td:nth-child(3) { width: 7%; }
.dict-table th:nth-child(4), .dict-table td:nth-child(4) { width: 19%; }
.dict-table th:nth-child(5), .dict-table td:nth-child(5) { width: 8%; }
.dict-table th:nth-child(6), .dict-table td:nth-child(6) { width: 30%; }
tr { page-break-inside: avoid; }
th { background: #1e3a8a; color: white; font-weight: 700; text-align: left; padding: 1.8mm 2mm; border: .2mm solid #cbd5e1; overflow-wrap: anywhere; }
td { padding: 1.6mm 2mm; border: .2mm solid #cbd5e1; vertical-align: top; overflow-wrap: anywhere; word-break: normal; }
tbody tr:nth-child(even) td { background: #f8fafc; }
img { display: block; max-width: 100%; max-height: 170mm; margin: 4mm auto; border: .2mm solid #e2e8f0; }
h2 + p img, p:has(> img) { page-break-inside: avoid; }
hr { border: none; border-top: .2mm solid #e2e8f0; margin: 6mm 0; }
.small-note { color: #64748b; font-size: 8.5pt; }
"""


def build_html(md_path, title):
    text = md_path.read_text(encoding='utf-8')
    # Strip the source title; the PDF cover carries it.
    text = re.sub(r'^#\s+.*\n+', '', text, count=1)
    html_body = markdown.markdown(
        text,
        extensions=['tables', 'fenced_code', 'sane_lists', 'toc'],
        output_format='html5',
    )
    html_body = re.sub(r'src="(assets/[^"]+)"', image_uri, html_body)
    def mark_dictionary_tables(match):
        block = match.group(0)
        if '<th>字段</th>' in block:
            return block.replace('<table>', '<table class="dict-table">', 1)
        return block
    html_body = re.sub(r'<table>.*?</table>', mark_dictionary_tables, html_body, flags=re.S)
    members = ''.join(f'<li>{n} {sid} · {role}</li>' for n, sid, role in MEMBERS)
    cover = f"""
    <section class="cover">
      <div class="cover-brand">TECHHUB · 高校技术团队展示与管理平台</div>
      <div class="cover-kicker">课程设计交付文档</div>
      <h1>{title}</h1>
      <div class="cover-sub">原生 PHP MVC · MySQL 8.0 · Bootstrap 5 · ECharts</div>
      <div class="cover-team">{TEAM}</div>
      <ul>{members}</ul>
      <div class="cover-foot">文档日期：2026-11-25　|　项目版本：TechHub 课程设计版　|　本文件内容以实际项目与测试记录为依据</div>
    </section>
    """
    return f"""<!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><title>{title} · {TEAM} · TechHub</title>
<style>{CSS}</style></head><body>{cover}{html_body}</body></html>"""


def print_pdf(html_path, pdf_path):
    profile = ROOT / 'assets' / '.edge-pdf-profile'
    if profile.exists():
        shutil.rmtree(profile, ignore_errors=True)
    args = [
        EDGE, '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        f'--user-data-dir={profile}', '--no-pdf-header-footer', '--virtual-time-budget=3500',
        f'--print-to-pdf={pdf_path}', html_path.as_uri(),
    ]
    result = subprocess.run(args, capture_output=True, text=True, encoding='utf-8', errors='replace', timeout=180)
    if not pdf_path.exists() or pdf_path.stat().st_size < 10000:
        raise RuntimeError(f'PDF 生成失败: {pdf_path}\n{result.stdout}\n{result.stderr}')


def verify_pdf(pdf_path):
    import fitz
    doc = fitz.open(pdf_path)
    pages = doc.page_count
    images = sum(len(p.get_images(full=True)) for p in doc)
    first = doc[0]
    # Render the cover for visual confirmation.
    if pages > 0:
        pix = first.get_pixmap(dpi=100)
        pix.save(pdf_path.with_suffix('.cover.png'))
    doc.close()
    return pages, images


def main():
    assemble_design()
    html_dir = ROOT / 'html'
    pdf_dir = ROOT / 'pdf'
    html_dir.mkdir(exist_ok=True)
    pdf_dir.mkdir(exist_ok=True)
    out_dir = PROJECT / 'data' / 'team'
    out_dir.mkdir(parents=True, exist_ok=True)
    requested = set(sys.argv[1:])
    for title, stem in DOCS:
        if requested and title not in requested:
            continue
        md_path = ROOT / f'{stem}.md'
        if not md_path.exists():
            raise FileNotFoundError(md_path)
        html = build_html(md_path, title)
        html_path = html_dir / f'{stem}.html'
        html_path.write_text(html, encoding='utf-8')
        pdf_path = pdf_dir / f'{stem}.pdf'
        print('PDF', title, '...', flush=True)
        print_pdf(html_path, pdf_path)
        pages, images = verify_pdf(pdf_path)
        shutil.copy2(pdf_path, out_dir / pdf_path.name)
        print(f'  OK pages={pages} images={images} size={pdf_path.stat().st_size}', flush=True)


if __name__ == '__main__':
    main()
