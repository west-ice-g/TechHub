# -*- coding: utf-8 -*-
"""Build the 14-slide TechHub course presentation."""
import shutil
from pathlib import Path

from PIL import Image
from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.util import Inches, Pt
from pptx.oxml.ns import qn
from pptx.oxml.xmlchemy import OxmlElement

ROOT = Path(__file__).resolve().parents[1]
PROJECT = ROOT.parents[1]
TEAM = '冰冰冰队'
SUFFIX = '(2412888_2411259_2413238_2412509)'
OUT = PROJECT / 'data' / 'team' / f'{TEAM}_项目展示{SUFFIX}.pptx'
COPY = ROOT / 'pptx' / OUT.name
ASSETS = ROOT / 'assets'

NAVY = RGBColor(0x0F, 0x17, 0x2A)
DARK = RGBColor(0x11, 0x1C, 0x33)
BLUE = RGBColor(0x25, 0x63, 0xEB)
BLUE_L = RGBColor(0xDB, 0xEA, 0xFE)
TEAL = RGBColor(0x0D, 0x94, 0x88)
TEAL_L = RGBColor(0xCC, 0xFB, 0xF1)
GOLD = RGBColor(0xF5, 0x9E, 0x0B)
GOLD_L = RGBColor(0xFE, 0xF3, 0xC7)
ROSE = RGBColor(0xDB, 0x27, 0x77)
ROSE_L = RGBColor(0xFD, 0xE7, 0xF3)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
LIGHT = RGBColor(0xF8, 0xFA, 0xFC)
MUTED = RGBColor(0x64, 0x74, 0x8B)
INK = RGBColor(0x1E, 0x29, 0x3B)
LINE = RGBColor(0xE2, 0xE8, 0xF0)
FONT = 'Microsoft YaHei'

prs = Presentation()
prs.slide_width = Inches(13.333)
prs.slide_height = Inches(7.5)
prs.core_properties.title = 'TechHub 高校技术团队展示与管理平台'
prs.core_properties.author = TEAM
prs.core_properties.subject = '互联网数据库开发课程设计'


def set_east_asian(run, name=FONT):
    rPr = run._r.get_or_add_rPr()
    for tag in ('a:ea', 'a:cs'):
        el = rPr.find(qn(tag))
        if el is None:
            el = OxmlElement(tag)
            rPr.append(el)
        el.set('typeface', name)


def style_run(run, size=16, color=INK, bold=False, font=FONT):
    run.font.name = font
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    set_east_asian(run, font)


def add_text(slide, x, y, w, h, text, size=16, color=INK, bold=False,
             align=PP_ALIGN.LEFT, valign=MSO_ANCHOR.TOP, margin=0, line_spacing=1.15):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.clear()
    tf.word_wrap = True
    tf.margin_left = Inches(margin)
    tf.margin_right = Inches(margin)
    tf.margin_top = Inches(margin)
    tf.margin_bottom = Inches(margin)
    tf.vertical_anchor = valign
    lines = str(text).split('\n')
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.line_spacing = line_spacing
        p.space_after = Pt(0)
        run = p.add_run()
        run.text = line
        style_run(run, size, color, bold)
    return box


def add_rect(slide, x, y, w, h, fill, line=None, radius=False, line_width=1):
    shape_type = MSO_SHAPE.ROUNDED_RECTANGLE if radius else MSO_SHAPE.RECTANGLE
    shp = slide.shapes.add_shape(shape_type, Inches(x), Inches(y), Inches(w), Inches(h))
    shp.fill.solid()
    shp.fill.fore_color.rgb = fill
    if line is None:
        shp.line.fill.background()
    else:
        shp.line.color.rgb = line
        shp.line.width = Pt(line_width)
    shp.shadow.inherit = False
    if radius:
        try:
            shp.adjustments[0] = 0.08
        except Exception:
            pass
    return shp


def add_oval(slide, x, y, w, h, fill, line=None, line_width=1):
    shp = slide.shapes.add_shape(MSO_SHAPE.OVAL, Inches(x), Inches(y), Inches(w), Inches(h))
    shp.fill.solid()
    shp.fill.fore_color.rgb = fill
    if line is None:
        shp.line.fill.background()
    else:
        shp.line.color.rgb = line
        shp.line.width = Pt(line_width)
    return shp


def bg(slide, color=WHITE):
    fill = slide.background.fill
    fill.solid()
    fill.fore_color.rgb = color


def header(slide, kicker, title, accent=BLUE):
    bg(slide, LIGHT)
    add_rect(slide, 0, 0, 13.333, 0.12, accent)
    add_text(slide, 0.7, 0.4, 8.5, 0.3, kicker.upper(), 10, accent, True)
    add_text(slide, 0.7, 0.72, 11.8, 0.6, title, 27, NAVY, True)
    add_rect(slide, 0.72, 1.45, 1.1, 0.06, accent)


def footer(slide, n):
    add_rect(slide, 0.7, 7.08, 11.93, 0.012, LINE)
    add_text(slide, 0.7, 7.13, 8, 0.2, f'{TEAM} · TechHub 课程设计', 8.5, MUTED)
    add_text(slide, 11.95, 7.11, 0.65, 0.25, str(n), 9, MUTED, True, PP_ALIGN.RIGHT)
    add_oval(slide, 12.62, 7.12, 0.08, 0.08, BLUE)


def add_picture_fit(slide, path, x, y, w, h, border=True):
    path = Path(path)
    with Image.open(path) as im:
        iw, ih = im.size
    scale = min(w / iw, h / ih)
    pw, ph = iw * scale, ih * scale
    px = x + (w - pw) / 2
    py = y + (h - ph) / 2
    pic = slide.shapes.add_picture(str(path), Inches(px), Inches(py), Inches(pw), Inches(ph))
    if border:
        pic.line.color.rgb = RGBColor(0xCB, 0xD5, 0xE1)
        pic.line.width = Pt(0.75)
    return pic


def bullet_lines(slide, x, y, w, items, size=15, color=INK, gap=0.46):
    for i, item in enumerate(items):
        add_oval(slide, x, y + i * gap + 0.08, 0.09, 0.09, BLUE)
        add_text(slide, x + 0.2, y + i * gap, w - 0.2, 0.36, item, size, color)
    return y + len(items) * gap


def notes(slide, text):
    slide.notes_slide.notes_text_frame.text = text


def bold_value_line(slide, x, y, label, value, color=BLUE, width=2.7):
    add_text(slide, x, y, width, 0.25, label, 9.5, MUTED, True)
    add_text(slide, x, y + 0.27, width, 0.42, value, 20, color, True)


# Slide 1: title
s = prs.slides.add_slide(prs.slide_layouts[6])
bg(s, NAVY)
add_oval(s, 9.6, -1.8, 5.8, 5.8, RGBColor(0x17, 0x2B, 0x51))
add_oval(s, 10.8, 0.5, 3.6, 3.6, RGBColor(0x1D, 0x4E, 0xD8))
add_rect(s, 0, 0, 0.22, 7.5, BLUE)
add_text(s, 0.9, 0.85, 8.8, 0.35, 'TECHHUB · 高校技术团队展示与管理平台', 11, RGBColor(0x93, 0xC5, 0xFD), True)
add_text(s, 0.9, 2.05, 9.8, 1.3, 'TechHub', 54, WHITE, True)
add_text(s, 0.9, 3.25, 9.8, 0.6, '高校技术团队展示与管理平台', 25, RGBColor(0xDB, 0xEA, 0xFE))
add_text(s, 0.9, 4.15, 8.8, 0.4, '原生 PHP MVC · MySQL 8.0 · Bootstrap 5 · ECharts', 13, RGBColor(0xBF, 0xDB, 0xFE))
add_rect(s, 0.92, 4.95, 1.2, 0.06, GOLD)
add_text(s, 0.9, 5.25, 4.2, 0.45, TEAM, 24, WHITE, True)
add_text(s, 0.9, 5.82, 9.8, 0.7,
         '刚栋冰 2412888（组长）　成钧翔 2411259　罗清 2413238　王毅豪 2412509',
         10.5, RGBColor(0xCB, 0xD5, 0xE1))
add_text(s, 0.9, 6.62, 8, 0.3, '课程设计答辩 · 2026-11-25', 10, RGBColor(0x94, 0xA3, 0xB8))
notes(s, '开场：介绍项目名称、团队和课程设计主题。说明当前系统真实可运行，Git 尚无提交，分工按团队提供资料记录。')

# Slide 2: background
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '01 / BACKGROUND', '项目背景：把分散的信息收拢到一个系统')
add_text(s, 0.7, 1.75, 4.2, 0.5, '痛点', 18, BLUE, True)
issues = [
    ('信息分散', '团队资料、项目成果、文章和作业散落在聊天群、网盘与个人仓库。'),
    ('缺少权限', '公开页面容易做，真正难的是后台身份、角色和管理边界。'),
    ('缺少审计', '文件谁上传、留言谁审核、内容谁修改，需要可追溯。'),
]
for i, (title, desc) in enumerate(issues):
    x = 0.7 + i * 4.15
    add_rect(s, x, 2.3, 3.85, 2.45, WHITE, LINE, radius=True)
    add_rect(s, x + 0.25, 2.55, 0.44, 0.44, BLUE_L, None, radius=True)
    add_text(s, x + 0.38, 2.63, 0.2, 0.25, str(i + 1), 12, BLUE, True)
    add_text(s, x + 0.25, 3.15, 3.3, 0.4, title, 16, NAVY, True)
    add_text(s, x + 0.25, 3.7, 3.3, 0.8, desc, 10.5, MUTED)
add_rect(s, 0.7, 5.35, 11.93, 1.05, NAVY, None, radius=True)
add_text(s, 1.0, 5.62, 11.3, 0.55, '目标：把团队展示、内容维护和课程资料管理放进一个真实 MySQL 系统。', 16, WHITE, True)
notes(s, '说明选题原因：不是为了做静态展示页，而是做一个需要登录、权限、数据库和审计的完整平台。')

# Slide 3: requirements
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '02 / REQUIREMENTS', '需求分析：四类角色，十一类后台任务')
cards = [
    ('访客', '浏览团队、成员、项目、文章、公告；提交留言；下载公开资料。', BLUE, BLUE_L),
    ('团队成员', '登录后台；查看本人资料；只访问被授权页面。', TEAL, TEAL_L),
    ('内容编辑', '维护项目、文章、分类、公告、留言和文件；查看统计。', GOLD, GOLD_L),
    ('超级管理员', '管理团队、成员、账号、角色权限和操作日志。', ROSE, ROSE_L),
]
for i, (role, desc, color, light) in enumerate(cards):
    col, row = i % 2, i // 2
    x, y = 0.7 + col * 6.1, 1.75 + row * 2.15
    add_rect(s, x, y, 5.75, 1.8, WHITE, LINE, radius=True)
    add_rect(s, x, y, 0.10, 1.8, color)
    add_text(s, x + 0.35, y + 0.25, 2.4, 0.35, role, 16, color, True)
    add_text(s, x + 0.35, y + 0.78, 4.95, 0.8, desc, 10.5, MUTED)
add_text(s, 0.7, 6.25, 11.8, 0.45,
         '约束：不开放注册 · 服务端 RBAC · CSRF · PDO 预处理 · 上传白名单 · 作业文件位于 Web 根之外',
         10.5, MUTED)
notes(s, '强调后台权限和审计是需求的一部分，不是附加功能。')

# Slide 4: architecture
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '03 / ARCHITECTURE', '系统架构：原生 MVC，统一入口')
add_picture_fit(s, ASSETS / 'architecture.png', 0.65, 1.62, 12.05, 5.1)
notes(s, '讲解请求链路：public/index.php → Router → Middleware → Controller → Model → MySQL / View。强调不依赖框架和 CDN。')

# Slide 5: modules
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '04 / MODULES', '功能模块：前台展示 + 11 个后台业务模块')
add_picture_fit(s, ASSETS / 'modules.png', 0.55, 1.62, 12.25, 5.15)
notes(s, '模块与数据表对应关系见设计文档第 3 节。每个模块都有真实后台页面和数据库写入。')

# Slide 6: database
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '05 / DATABASE', '数据库设计：17 张表，21 个外键')
add_picture_fit(s, ASSETS / 'er-diagram.png', 0.35, 1.55, 10.15, 5.25)
add_rect(s, 10.65, 1.72, 2.2, 1.15, BLUE_L, None, radius=True)
add_text(s, 10.82, 1.9, 1.8, 0.25, '业务表', 9, BLUE, True)
add_text(s, 10.82, 2.16, 1.8, 0.5, '17', 28, NAVY, True)
add_rect(s, 10.65, 3.05, 2.2, 1.15, TEAL_L, None, radius=True)
add_text(s, 10.82, 3.23, 1.8, 0.25, '外键', 9, TEAL, True)
add_text(s, 10.82, 3.49, 1.8, 0.5, '21', 28, NAVY, True)
add_rect(s, 10.65, 4.38, 2.2, 1.15, GOLD_L, None, radius=True)
add_text(s, 10.82, 4.56, 1.8, 0.25, '多对多桥接', 9, RGBColor(0xB4, 0x53, 0x09), True)
add_text(s, 10.82, 4.82, 1.8, 0.5, '3', 28, NAVY, True)
add_text(s, 10.65, 5.78, 2.2, 0.7, 'role_permissions\nteam_members\nproject_members', 8.5, MUTED)
notes(s, 'ER 图只展示主要关系，完整字段和外键见设计文档第 7 节数据字典。')

# Slide 7: frontend
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '06 / FRONTEND', '前台功能：团队、项目、文章与互动')
add_picture_fit(s, ASSETS / 'screenshots' / 'front-home.png', 0.65, 1.65, 8.05, 4.7)
add_text(s, 9.0, 1.82, 3.55, 0.4, '真实页面', 16, BLUE, True)
bullet_lines(s, 9.0, 2.35, 3.5, [
    '团队介绍与核心数据',
    '成员列表/详情',
    '项目筛选与详情',
    '文章分类、搜索、浏览量',
    '公告、留言、下载',
], 12.5, INK, 0.59)
add_text(s, 0.7, 6.55, 12, 0.3, '所有内容读取 MySQL；当前截图使用明确标注的示例数据。', 9.5, MUTED)
notes(s, '演示时打开首页、项目详情和下载页，说明内容不是静态写死。')

# Slide 8: backend
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '07 / BACKEND', '后台功能：权限菜单 + 真实 CRUD')
add_picture_fit(s, ASSETS / 'screenshots' / 'admin-users.png', 0.7, 1.65, 8.1, 4.6)
add_text(s, 9.15, 1.82, 3.3, 0.4, '核心设计', 16, TEAL, True)
bullet_lines(s, 9.15, 2.3, 3.3, [
    '侧边栏按权限显示',
    '路由逐请求校验',
    '用户/角色双重保护',
    '删除 POST + CSRF',
    '操作日志记录结果',
], 12.2, INK, 0.57)
add_text(s, 0.7, 6.55, 12, 0.3, '普通成员访问项目/统计接口返回 403，编辑账号访问用户管理返回 403。', 9.5, MUTED)
notes(s, '现场可切换普通成员账号展示菜单差异和 403，说明不是隐藏按钮。')

# Slide 9: ECharts
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '08 / VISUALIZATION', '数据可视化：4 张 ECharts，全部来自 SQL 聚合')
add_picture_fit(s, ASSETS / 'charts-collage.png', 0.65, 1.68, 8.4, 4.8)
add_text(s, 9.35, 1.88, 3.2, 0.35, '图表', 16, BLUE, True)
bullet_lines(s, 9.35, 2.35, 3.15, [
    '项目状态饼图',
    '文章发布趋势',
    '项目新增柱状图',
    '成员参与项目',
], 12, INK, 0.52)
add_text(s, 9.35, 4.72, 3.2, 0.6, '接口\n/admin/api/stats', 10.5, MUTED)
add_rect(s, 9.35, 5.45, 3.05, 0.68, BLUE_L, None, radius=True)
add_text(s, 9.55, 5.62, 2.7, 0.3, '权限：stats.view', 10, BLUE, True)
notes(s, '说明数据来自 MySQL，不是前端写死的数组；新增数据后刷新图表会变化。')

# Slide 10: core technology
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '09 / CORE TECH', '核心技术：安全与数据一致性')
techs = [
    ('PDO 预处理', '禁用模拟预处理，搜索参数分开命名，避免 SQL 注入和重复占位符错误。', BLUE, BLUE_L),
    ('认证与 RBAC', '密码哈希、Session 重建、登录限流、权限码服务端校验。', TEAL, TEAL_L),
    ('事务', '项目成员同步、角色权限同步使用数据库事务。', GOLD, GOLD_L),
    ('上传校验', '扩展名 + 真实 MIME + 大小 + 图片内容，随机存储名。', ROSE, ROSE_L),
    ('路径边界', 'realpath 检查下载/删除路径，拒绝目录遍历。', RGBColor(0x7C, 0x3A, 0xED), RGBColor(0xF5, 0xF3, 0xFF)),
    ('操作审计', '记录模块、动作、目标、成功/失败、IP 和 UA。', RGBColor(0x47, 0x55, 0x69), RGBColor(0xF1, 0xF5, 0xF9)),
]
for i, (title, desc, color, light) in enumerate(techs):
    col, row = i % 3, i // 3
    x, y = 0.7 + col * 4.1, 1.7 + row * 2.15
    add_rect(s, x, y, 3.82, 1.8, WHITE, LINE, radius=True)
    add_rect(s, x, y, 3.82, 0.10, color)
    add_text(s, x + 0.25, y + 0.28, 3.2, 0.35, title, 15, color, True)
    add_text(s, x + 0.25, y + 0.8, 3.3, 0.75, desc, 9.8, MUTED)
notes(s, '代码示例见实现文档第 9 节，包括 PDO、事务、下载路径和日志。')

# Slide 11: features
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '10 / HIGHLIGHTS', '项目特色：真实、可测、可审计')
features = [
    ('01', '不是静态页面', '前台展示和后台 CRUD 都真实读写 MySQL。'),
    ('02', '图谱式数据关系', '17 张表覆盖账号、团队、项目、内容、文件与日志。'),
    ('03', '安全边界完整', 'CSRF、XSS、RBAC、上传白名单、路径校验。'),
    ('04', '可重复部署', 'install.sql 与迁移脚本可重复执行并验证。'),
    ('05', '可视化真实', '4 张 ECharts 全部读取聚合接口。'),
    ('06', '测试有证据', '127 项自动化测试 + 浏览器/安装证据归档。'),
]
for i, (num, title, desc) in enumerate(features):
    col, row = i % 2, i // 2
    x, y = 0.7 + col * 6.15, 1.7 + row * 1.6
    add_text(s, x, y, 0.7, 0.5, num, 24, BLUE, True)
    add_text(s, x + 0.75, y - 0.02, 4.9, 0.35, title, 15, NAVY, True)
    add_text(s, x + 0.75, y + 0.42, 4.95, 0.45, desc, 10.2, MUTED)
notes(s, '突出真实数据库、真实权限、真实测试，不夸大未完成部署和 Git。')

# Slide 12: tests
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '11 / TESTS', '测试情况：127 项自动化测试通过')
rows = [
    ('PHP 语法检查', '123 个文件', '0 失败'),
    ('基础测试', 'tests/smoke.php', '14 / 14'),
    ('前台数据测试', 'tests/frontend_smoke.php', '34 / 34'),
    ('后台 HTTP 集成测试', 'tests/admin_smoke.php', '79 / 79'),
    ('后台页面/接口', '30 个 GET', '全部 200'),
    ('ECharts / 移动端', '4 个 canvas / 390px', '正常 / 无横向溢出'),
]
for i, (name, scope, result) in enumerate(rows):
    y = 1.72 + i * 0.62
    add_rect(s, 0.7, y, 11.9, 0.52, WHITE if i % 2 == 0 else RGBColor(0xF1, 0xF5, 0xF9), LINE, radius=False)
    add_text(s, 0.9, y + 0.12, 4.1, 0.3, name, 11, NAVY, True)
    add_text(s, 5.1, y + 0.12, 4.1, 0.3, scope, 10, MUTED)
    add_text(s, 9.6, y + 0.12, 2.8, 0.3, result, 11, TEAL, True, PP_ALIGN.RIGHT)
add_text(s, 0.7, 5.75, 11.8, 0.55,
         '未完成：Apache PHP 8.2 部署、真实移动设备、性能压测、备份恢复演练。', 10.5, RGBColor(0xB4, 0x53, 0x09))
add_text(s, 0.7, 6.35, 11.8, 0.3, '原始输出：docs/evidence/ 下的测试与浏览器证据文件。', 9, MUTED)
notes(s, '说明测试数字来自实际执行，未完成项主动说明，不把计划写成通过。')

# Slide 13: team
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '12 / TEAM', '团队分工：真实成员与任务包')
cols = [
    ('成员', 0.7, 2.55), ('负责模块', 3.25, 3.0), ('数据库表', 6.3, 1.55),
    ('核心代码文件', 7.9, 2.85), ('工作量占比', 10.8, 1.8),
]
for title, x, w in cols:
    add_text(s, x, 1.68, w, 0.3, title, 9, MUTED, True)
members = [
    ('刚栋冰\n2412888 · 组长', '架构、核心框架、用户权限、SQL 统筹', '5 张', 'User.php / Admin/UserController.php / admin/users', '5 表 · 3 MVC · 30%'),
    ('罗清\n2413238', '前台、团队与成员、响应式 UI', '2 张', 'Member.php / MemberController.php / member/index', '2 表 · 3 MVC · 20%'),
    ('成钧翔\n2411259', '项目、文章、分类、公告', '5 张', 'Project.php / ProjectController.php / project/index', '5 表 · 3 MVC · 30%'),
    ('王毅豪\n2412509', '留言、文件、统计、测试', '4 张', 'Message.php / MessageController.php / ECharts', '4 表 · 3 MVC · 20%'),
]
for i, row in enumerate(members):
    y = 2.1 + i * 1.05
    add_rect(s, 0.7, y, 11.9, 0.9, WHITE, LINE, radius=True)
    add_text(s, 0.85, y + 0.12, 2.25, 0.62, row[0], 9.3, NAVY, True)
    add_text(s, 3.25, y + 0.13, 2.95, 0.55, row[1], 8.4, MUTED)
    add_text(s, 6.3, y + 0.3, 1.45, 0.3, row[2], 10, TEAL, True)
    add_text(s, 7.9, y + 0.13, 2.8, 0.55, row[3], 7.9, MUTED)
    add_text(s, 10.8, y + 0.3, 1.65, 0.3, row[4], 8.2, NAVY, True)
add_text(s, 0.7, 6.30, 11.9, 0.25,
         '文档任务：刚栋冰=需求/设计；罗清=用户手册；成钧翔=设计/实现；王毅豪=实现/手册/部署',
         8.2, MUTED)
add_text(s, 0.7, 6.62, 11.9, 0.35,
         '工作量比例按团队任务包统计：刚栋冰 30% · 罗清 20% · 成钧翔 30% · 王毅豪 20%。', 8.5, MUTED)
notes(s, '介绍四位成员的任务包和工作量比例；不把 Git 提交记录作为本次工作量依据。')

# Slide 14: detailed project division
s = prs.slides.add_slide(prs.slide_layouts[6])
header(s, '13 / DIVISION', '项目分工明细：职责、数据表与必做 MVC')
cards = [
    {
        'name': '成员 A · 组长 刚栋冰',
        'role': '系统架构与用户权限 · 2412888',
        'color': BLUE, 'light': BLUE_L,
        'work': '项目统筹/技术选型 · PHP MVC 框架\n路由/数据库/认证 · 用户/角色权限 · SQL 整合',
        'tables': 'users · roles · permissions\nrole_permissions · teams',
        'mvc': 'User.php / user/list.php\nUserController.php',
        'percent': '30%',
    },
    {
        'name': '成员 B · 罗清',
        'role': '前台业务负责人 · 2413238',
        'color': TEAL, 'light': TEAL_L,
        'work': '首页 · 团队介绍 · 成员列表/详情\n成员与团队编辑 · 前台 UI/响应式',
        'tables': 'team_members · files（头像协作）',
        'mvc': 'TeamMember.php / team/member-list.php\nTeamMemberController.php',
        'percent': '20%',
    },
    {
        'name': '成员 C · 成钧翔',
        'role': '项目与内容负责人 · 2411259',
        'color': GOLD, 'light': GOLD_L,
        'work': '项目列表/详情 · 项目 CRUD/成员关联\n文章发布 · 分类管理 · 公告',
        'tables': 'projects · project_members · articles\ncategories · announcements',
        'mvc': 'Project.php / project/list.php\nProjectController.php',
        'percent': '30%',
    },
    {
        'name': '成员 D · 王毅豪',
        'role': '互动与后台负责人 · 2412509',
        'color': ROSE, 'light': ROSE_L,
        'work': '留言板 · 回复/审核 · 文件上传下载\n作业管理 · ECharts · 统计 · 测试/手册',
        'tables': 'messages · message_replies\nactivity_logs · files（与 B 协作）',
        'mvc': 'Message.php / message/list.php\nMessageController.php',
        'percent': '20%',
    },
]
for i, card in enumerate(cards):
    col, row = i % 2, i // 2
    x, y = 0.7 + col * 6.1, 1.58 + row * 2.55
    add_rect(s, x, y, 5.85, 2.32, WHITE, LINE, radius=True)
    add_rect(s, x, y, 0.08, 2.32, card['color'])
    add_text(s, x + 0.25, y + 0.13, 4.5, 0.28, card['name'], 10.2, NAVY, True)
    add_text(s, x + 4.75, y + 0.12, 0.9, 0.3, card['percent'], 14, card['color'], True, PP_ALIGN.RIGHT)
    add_text(s, x + 0.25, y + 0.43, 5.2, 0.22, card['role'], 7.7, card['color'], True)
    add_text(s, x + 0.25, y + 0.72, 5.3, 0.18, '主要工作', 6.8, MUTED, True)
    add_text(s, x + 0.25, y + 0.91, 5.3, 0.45, card['work'], 7.25, INK)
    add_text(s, x + 0.25, y + 1.38, 5.3, 0.18, '负责数据库表', 6.8, MUTED, True)
    add_text(s, x + 0.25, y + 1.55, 5.3, 0.36, card['tables'], 6.9, INK)
    add_text(s, x + 0.25, y + 1.92, 5.3, 0.18, '必做 MVC', 6.8, MUTED, True)
    add_text(s, x + 0.25, y + 2.07, 5.3, 0.28, card['mvc'], 6.35, INK)
add_text(s, 0.7, 6.63, 11.9, 0.22,
         '注：以上为课程任务要求的 MVC 命名；项目实际落地文件对应关系见实现文档。', 7.3, MUTED)
add_text(s, 0.7, 6.87, 11.9, 0.22,
         '工作量比例按团队任务包统计：刚栋冰 30% · 罗清 20% · 成钧翔 30% · 王毅豪 20%。', 7.3, MUTED)
notes(s, '按团队确认的分工和工作量比例，逐项展示每位成员的模块、数据库表、必做 MVC 文件；实际落地文件名与课程要求可能不同，以实现文档映射为准。')

# Slide 15: summary
s = prs.slides.add_slide(prs.slide_layouts[6])
bg(s, NAVY)
add_rect(s, 0, 0, 0.18, 7.5, BLUE)
add_text(s, 0.9, 0.9, 8, 0.4, '14 / SUMMARY', 10, RGBColor(0x93, 0xC5, 0xFD), True)
add_text(s, 0.9, 1.4, 10, 0.7, '已完成一个真实可运行的前后台系统', 30, WHITE, True)
add_text(s, 0.9, 2.35, 5.1, 0.4, '已完成', 15, RGBColor(0x5E, 0xEA, 0xD4), True)
bullet_lines(s, 0.9, 2.85, 5.1, [
    '17 张表 / 21 个外键',
    '前台展示、搜索、分页、下载',
    '后台 11 个模块真实 CRUD',
    'RBAC、CSRF、上传与路径校验',
    '4 张 ECharts + 操作日志',
], 12, RGBColor(0xE2, 0xE8, 0xF0), 0.55)
add_text(s, 6.55, 2.35, 5.1, 0.4, '待完成', 15, RGBColor(0xFD, 0xBA, 0x74), True)
bullet_lines(s, 6.55, 2.85, 5.1, [
    'Apache PHP 8.2 正式部署',
    '真实项目与文章内容补充',
    '真实团队资料替换',
    '修改本人资料/密码',
    '课程材料模板适配与彩排',
], 12, RGBColor(0xE2, 0xE8, 0xF0), 0.55)
add_text(s, 0.9, 6.45, 11.5, 0.5, '谢谢老师，欢迎提问。', 22, WHITE, True)
notes(s, '总结时主动说明未完成项，避免把最终部署和 Git 记录说成已完成。')

OUT.parent.mkdir(parents=True, exist_ok=True)
COPY.parent.mkdir(parents=True, exist_ok=True)
prs.save(OUT)
shutil.copy2(OUT, COPY)
print('saved', OUT, OUT.stat().st_size)
print('slides', len(prs.slides))
