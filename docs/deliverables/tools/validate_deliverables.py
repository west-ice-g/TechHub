# -*- coding: utf-8 -*-
"""Validate generated PDF/PPTX/Markdown deliverables."""
import hashlib
import re
from pathlib import Path

import fitz
from PIL import Image
from pptx import Presentation

ROOT = Path(__file__).resolve().parents[1]
PROJECT = ROOT.parents[1]
SUFFIX = '(2412888_2411259_2413238_2412509)'
TEAM = '冰冰冰队'
DOCS = ['需求文档', '设计文档', '实现文档', '用户手册', '部署文档']
lines = []
ok = True


def check(condition, message):
    global ok
    lines.append(('[PASS] ' if condition else '[FAIL] ') + message)
    if not condition:
        ok = False


for title in DOCS:
    stem = f'{TEAM}_{title}{SUFFIX}'
    pdf = PROJECT / 'data' / 'team' / f'{stem}.pdf'
    copy = ROOT / 'pdf' / f'{stem}.pdf'
    md = ROOT / f'{stem}.md'
    check(pdf.exists() and pdf.stat().st_size > 100000, f'{title} PDF 存在且非空')
    check(copy.exists(), f'{title} PDF 副本存在')
    if pdf.exists() and copy.exists():
        h1 = hashlib.sha256(pdf.read_bytes()).hexdigest()
        h2 = hashlib.sha256(copy.read_bytes()).hexdigest()
        check(h1 == h2, f'{title} PDF 正本与副本一致')
    if pdf.exists():
        doc = fitz.open(pdf)
        check(doc.page_count > 0, f'{title} PDF 可打开，页数 {doc.page_count}')
        pix = doc[0].get_pixmap(dpi=72)
        img = Image.frombytes('RGB', (pix.width, pix.height), pix.samples)
        non_white = sum(1 for p in img.getdata() if sum(p) < 740)
        check(non_white > img.width * img.height * 0.03, f'{title} 封面有实际内容')
        doc.close()
    check(md.exists(), f'{title} Markdown 源文件存在')

# Image links and placeholders
for md in ROOT.glob('*.md'):
    text = md.read_text(encoding='utf-8')
    check('<!-- TABLE_SUMMARY' not in text and '<!-- DATA_DICTIONARY' not in text, f'{md.name} 无未替换占位符')
    for link in re.findall(r'!\[[^\]]*\]\(([^)]+)\)', text):
        check((ROOT / link).exists(), f'{md.name} 图片存在: {link}')

pptx_path = PROJECT / 'data' / 'team' / f'{TEAM}_项目展示{SUFFIX}.pptx'
pptx_copy = ROOT / 'pptx' / pptx_path.name
check(pptx_path.exists() and pptx_path.stat().st_size > 500000, 'PPTX 存在且非空')
check(pptx_copy.exists(), 'PPTX 副本存在')
if pptx_path.exists():
    prs = Presentation(pptx_path)
    check(len(prs.slides) == 15, f'PPTX 可打开，幻灯片数 {len(prs.slides)}')
    texts = '\n'.join(
        shape.text for slide in prs.slides for shape in slide.shapes if hasattr(shape, 'text')
    )
    check(TEAM in texts, 'PPTX 含团队名称')
    check('刚栋冰' in texts and '成钧翔' in texts and '罗清' in texts and '王毅豪' in texts, 'PPTX 含四名真实成员')
    check('30%' in texts and '20%' in texts and '项目分工明细' in texts, 'PPTX 含项目分工明细和工作量比例')

# Credential leakage in deliverable sources.
for f in list(ROOT.glob('*.md')) + [ROOT / 'README.md']:
    text = f.read_text(encoding='utf-8')
    check('DB_PASSWORD=' not in text or 'change-me' in text or '替换' in text, f'{f.name} 未写入真实数据库密码')

lines.append('')
lines.append('RESULT: ' + ('PASS' if ok else 'FAIL'))
(ROOT / 'validation.txt').write_text('\n'.join(lines), encoding='utf-8')
print('\n'.join(lines))
raise SystemExit(0 if ok else 1)
