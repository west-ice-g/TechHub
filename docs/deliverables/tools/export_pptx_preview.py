# -*- coding: utf-8 -*-
import os, time
from pathlib import Path
import win32com.client
from pptx import Presentation

ROOT=Path.cwd()
pptx=next((ROOT/'data/team').glob('冰冰冰队_项目展示*.pptx')).resolve()
out=ROOT/'docs/deliverables/pptx'
out.mkdir(parents=True, exist_ok=True)
pdf=out/'projects-preview.pdf'
ppt=win32com.client.DispatchEx('PowerPoint.Application')
try:
    pres=ppt.Presentations.Open(str(pptx), WithWindow=False)
    pres.SaveAs(str(pdf), 32)
    pres.Close()
    print('exported', pdf, pdf.stat().st_size)
finally:
    ppt.Quit()
# Reopen via python-pptx as an integrity check.
p=Presentation(pptx)
print('reopen slides', len(p.slides))
