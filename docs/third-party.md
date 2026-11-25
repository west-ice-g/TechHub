# 第三方前端资源与许可证

TechHub 不使用 CDN 运行时依赖，以下资源均保存在项目 `public/assets/` 中。

| 资源 | 版本 | 许可证 | 本地文件 |
| --- | --- | --- | --- |
| Bootstrap | 5.3.3 | MIT | `public/assets/css/bootstrap.min.css`、`public/assets/js/bootstrap.bundle.min.js`、`public/assets/css/bootstrap.LICENSE.txt` |
| Bootstrap Icons | 1.11.3 | MIT | `public/assets/css/bootstrap-icons.min.css`、`public/assets/fonts/`、`public/assets/css/bootstrap-icons.LICENSE.txt` |
| Apache ECharts | 5.5.1 | Apache-2.0 | `public/assets/js/echarts.min.js`、`public/assets/js/echarts.LICENSE.txt` |

## ECharts 用途

- 后台数据统计首页：项目状态分布、文章发布趋势、近 6 个月项目数量、成员参与项目数量；
- 数据来自受登录和 `stats.view` 权限保护的 `GET /admin/api/stats`，不包含硬编码的业务数据。

## 说明

- 未引入 Vue、React、图表 CDN 或其他需要构建工具的运行时依赖；
- Bootstrap Icons 字体文件随项目本地提供；
- 若升级版本，应同步替换本地文件、许可证文本和版本记录，并重新执行浏览器回归。
