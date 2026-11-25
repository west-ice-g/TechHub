# TechHub 课程设计交付文件

- 团队：冰冰冰队
- 项目：TechHub 高校技术团队展示与管理平台
- 成员：刚栋冰 2412888（组长）、成钧翔 2411259、罗清 2413238、王毅豪 2412509
- 生成日期：2026-11-25

## 1. 正式交付文件

| 交付物 | 文件 | 页数 / 幻灯片 |
| --- | --- | --- |
| 需求文档 | `data/team/冰冰冰队_需求文档(2412888_2411259_2413238_2412509).pdf` | 13 页 |
| 设计文档 | `data/team/冰冰冰队_设计文档(2412888_2411259_2413238_2412509).pdf` | 28 页 |
| 实现文档 | `data/team/冰冰冰队_实现文档(2412888_2411259_2413238_2412509).pdf` | 18 页 |
| 用户手册 | `data/team/冰冰冰队_用户手册(2412888_2411259_2413238_2412509).pdf` | 27 页 |
| 部署文档 | `data/team/冰冰冰队_部署文档(2412888_2411259_2413238_2412509).pdf` | 18 页 |
| 项目展示 | `data/team/冰冰冰队_项目展示(2412888_2411259_2413238_2412509).pptx` | 15 页幻灯片 |

同名 Markdown 源文件保存在本目录；PDF 副本保存在 `pdf/`，PPTX 副本保存在 `pptx/`。

## 2. Markdown 源文件

- `冰冰冰队_需求文档(2412888_2411259_2413238_2412509).md`
- `冰冰冰队_设计文档(2412888_2411259_2413238_2412509).md`
- `冰冰冰队_实现文档(2412888_2411259_2413238_2412509).md`
- `冰冰冰队_用户手册(2412888_2411259_2413238_2412509).md`
- `冰冰冰队_部署文档(2412888_2411259_2413238_2412509).md`
- `冰冰冰队_项目展示(2412888_2411259_2413238_2412509).md`

## 3. 数据与图表来源

| 目录/文件 | 内容 |
| --- | --- |
| `data/schema.json` | 从实际 MySQL `information_schema` 导出的 17 表结构、字段、外键、索引和行数 |
| `data/data-dictionary.md` | 根据真实数据库生成、嵌入设计文档的完整数据字典 |
| `assets/architecture.png` | 系统总体架构图 |
| `assets/modules.png` | 功能模块图 |
| `assets/business-flow.png` | 系统业务流程图 |
| `assets/er-diagram.png` | 根据真实外键关系绘制的 E-R 图 |
| `assets/mvc-flow.png` | MVC 请求生命周期图 |
| `assets/charts-collage.png` | 4 张 ECharts 的真实截图拼图 |
| `assets/screenshots/` | 前台页面、后台页面、登录页等实际运行截图 |

## 4. 生成工具与验证

- Markdown → HTML：Python `markdown`
- HTML → PDF：Microsoft Edge Headless `--print-to-pdf`
- PPTX：Python `python-pptx`
- PPTX 验证：使用本机 Microsoft PowerPoint 打开并导出预览 PDF，15 页成功
- PDF 验证：使用 PyMuPDF 打开并检查页数、图片数量，全部非空
- Docker 验证：`docker compose config`、冷启动、HTTP、数据库、容器内三套测试通过；原始摘要见 `docs/evidence/docker-deploy-2026-11-25.txt`
- 部署文件：项目根目录 `Dockerfile`、`docker-compose.yml`、`.env.example`、`deploy.sh`、`docker/`
- 完整校验结果：`validation.txt`（PDF 正副本一致性、封面内容、图片链接、PPTX 可打开、敏感信息检查）
- 文档中的数据库字典由实际 `techhub` 库生成，不是第一阶段规划表

重新生成：

```bat
python docs\deliverables\tools\dump_schema.php
python docs\deliverables\tools\make_dictionary.py
python docs\deliverables\tools\make_diagrams.py
python docs\deliverables\tools\build_docs.py
python docs\deliverables\tools\build_pptx.py
```

## 5. 文档真实性说明

- 团队成员姓名、学号来自用户提供资料。
- 分工按用户提供的模块负责关系整理，具体代码文件按实际项目路径列示。
- 当前 Git 仓库 `main` 分支没有任何提交；文档没有编造提交哈希、提交信息和逐人 Git 贡献。
- 工作量比例按团队确认的任务包统计：刚栋冰 30%、罗清 20%、成钧翔 30%、王毅豪 20%。
- 网站页面仍使用明确标注的示例团队、示例成员和示例项目；用户手册截图也是当前实际运行页面。
- Docker Compose 的 PHP 8.2 + Apache 容器已实际启动验证；宿主机 Apache 虚拟主机是否使用 PHP 8.2 仍需按目标服务器配置。
- 测试数字来自 `docs/evidence/` 中的实际输出文件。

## 6. 待补充事项

| 事项 | 状态 | 处理建议 |
| --- | --- | --- |
| Git 提交记录 | 无提交 | 按真实分工由成员分别提交本人文件，再补交提交记录截图 |
| 工作量比例 | 已按团队确认填写 30/20/30/20 | 如团队后续调整，需同步 PPT、实现文档和分工页 |
| 真实团队资料 | 网站仍为示例数据 | 提供团队名称、成员照片、项目、文章后替换并重拍用户手册截图 |
| 宿主 Apache PHP 8.2 部署 | 未执行（Docker 容器内 Apache 已验证） | 按部署文档方案 B 配置虚拟主机并回归 |
| 学校文档模板 | 未提供 | 若学校有封面、字号、页眉页脚要求，替换当前统一 A4 样式 |
| 真实移动设备/性能/备份 | 未执行 | 在答辩前补充实体手机、压力测试和恢复演练 |
