# -*- coding: utf-8 -*-
from pathlib import Path
import sys
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'tools'))
from build_docs import dictionary_block
p=ROOT/'冰冰冰队_设计文档(2412888_2411259_2413238_2412509).md'
s=p.read_text(encoding='utf-8')
start=s.index('## 7. 完整数据字典')
end=s.index('## 8. MVC 架构设计', start)
new='''## 7. 完整数据字典

以下数据字典由实际 MySQL 8.0.19 `information_schema` 生成，包含字段类型、可空性、默认值、键、额外属性、外键、索引和生成时实际行数。实际 SQL 未设置列注释，因此不生成空的“说明”列。

'''+dictionary_block()+'\n\n'
p.write_text(s[:start]+new+s[end:],encoding='utf-8')
print('design dictionary refreshed',len(new))
