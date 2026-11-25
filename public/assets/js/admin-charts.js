/*
 * TechHub - 高校技术团队展示与管理平台
 * 文件：public/assets/js/admin-charts.js
 * 模块：后台统计图表
 * 职责：从受权限保护的后台 API 获取 MySQL 统计并用 ECharts 渲染。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

'use strict';

(function () {
    const app = document.getElementById('adminChartsApp');
    if (!app || typeof window.echarts === 'undefined') {
        return;
    }

    const endpoint = app.getAttribute('data-stats-url');
    if (!endpoint) {
        return;
    }

    const palette = ['#2563eb', '#0d9488', '#f59e0b', '#7c3aed', '#e11d48', '#64748b'];
    const charts = [];
    const textColor = '#334155';
    const mutedColor = '#94a3b8';

    function showError(message) {
        document.querySelectorAll('[data-chart]').forEach(function (node) {
            node.innerHTML = '<div class="admin-chart-loading">' + message + '</div>';
        });
    }

    function baseOption() {
        return {
            color: palette,
            textStyle: {
                color: textColor,
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", "Microsoft YaHei", sans-serif'
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.94)',
                borderWidth: 0,
                textStyle: { color: '#f8fafc', fontSize: 12 }
            }
        };
    }

    function renderPie(id, data) {
        const node = document.getElementById(id);
        if (!node) {
            return;
        }

        const chart = window.echarts.init(node);
        const total = data.values.reduce(function (sum, value) { return sum + value; }, 0);

        chart.setOption(Object.assign(baseOption(), {
            tooltip: Object.assign(baseOption().tooltip, { trigger: 'item' }),
            legend: {
                bottom: 0,
                icon: 'circle',
                itemHeight: 8,
                itemWidth: 8,
                textStyle: { color: mutedColor, fontSize: 11 }
            },
            series: [{
                name: '项目状态',
                type: 'pie',
                radius: ['44%', '68%'],
                center: ['50%', '43%'],
                avoidLabelOverlap: true,
                itemStyle: {
                    borderColor: '#fff',
                    borderRadius: 4,
                    borderWidth: 2
                },
                label: {
                    color: textColor,
                    fontSize: 11,
                    formatter: '{b}\n{c} 个'
                },
                labelLine: { length: 10, length2: 8 },
                data: data.labels.map(function (label, index) {
                    return { name: label, value: data.values[index] || 0 };
                })
            }],
            graphic: total === 0 ? [{
                type: 'text',
                left: 'center',
                top: '38%',
                style: { text: '暂无项目', fill: mutedColor, fontSize: 12 }
            }] : []
        }));
        charts.push(chart);
    }

    function renderLine(id, data) {
        const node = document.getElementById(id);
        if (!node) {
            return;
        }

        const chart = window.echarts.init(node);
        chart.setOption(Object.assign(baseOption(), {
            grid: { left: 12, right: 42, top: 34, bottom: 10, containLabel: true },
            tooltip: Object.assign(baseOption().tooltip, { trigger: 'axis' }),
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: data.labels,
                axisLine: { lineStyle: { color: '#e2e8f0' } },
                axisLabel: { color: mutedColor, fontSize: 10 },
                axisTick: { show: false }
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                axisLabel: { color: mutedColor, fontSize: 10 },
                splitLine: { lineStyle: { color: '#f1f5f9' } }
            },
            series: [{
                name: '发布文章',
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 7,
                data: data.values,
                lineStyle: { width: 3, color: '#2563eb' },
                itemStyle: { color: '#2563eb', borderColor: '#fff', borderWidth: 2 },
                areaStyle: {
                    color: new window.echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: 'rgba(37, 99, 235, 0.2)' },
                        { offset: 1, color: 'rgba(37, 99, 235, 0.01)' }
                    ])
                }
            }]
        }));
        charts.push(chart);
    }

    function renderBar(id, data, horizontal) {
        const node = document.getElementById(id);
        if (!node) {
            return;
        }

        const chart = window.echarts.init(node);
        const categoryAxis = {
            type: 'category',
            data: data.labels,
            axisLine: { lineStyle: { color: '#e2e8f0' } },
            axisLabel: { color: mutedColor, fontSize: 10, interval: 0 },
            axisTick: { show: false }
        };
        const valueAxis = {
            type: 'value',
            minInterval: 1,
            axisLabel: { color: mutedColor, fontSize: 10 },
            splitLine: { lineStyle: { color: '#f1f5f9' } }
        };

        chart.setOption(Object.assign(baseOption(), {
            grid: { left: 12, right: 34, top: 24, bottom: 10, containLabel: true },
            tooltip: Object.assign(baseOption().tooltip, { trigger: 'axis', axisPointer: { type: 'shadow' } }),
            xAxis: horizontal ? valueAxis : categoryAxis,
            yAxis: horizontal ? Object.assign({}, categoryAxis, { inverse: true }) : valueAxis,
            series: [{
                name: horizontal ? '参与项目数' : '新增项目',
                type: 'bar',
                barMaxWidth: horizontal ? 16 : 28,
                data: data.values,
                itemStyle: {
                    borderRadius: horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0],
                    color: horizontal ? '#0d9488' : '#2563eb'
                },
                label: {
                    show: true,
                    position: horizontal ? 'right' : 'top',
                    color: mutedColor,
                    fontSize: 10
                }
            }]
        }));
        charts.push(chart);
    }

    fetch(endpoint, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function (payload) {
            if (!payload || payload.success !== true) {
                throw new Error('统计数据格式错误');
            }
            renderPie('chartProjectStatus', payload.project_status);
            renderLine('chartArticleTrend', payload.article_trend);
            renderBar('chartProjectTrend', payload.project_trend, false);
            renderBar('chartMemberParticipation', payload.member_participation, true);
        })
        .catch(function () {
            showError('统计数据加载失败，请刷新页面重试。');
        });

    let resizeTimer = null;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            charts.forEach(function (chart) {
                chart.resize();
            });
        }, 120);
    });
})();
