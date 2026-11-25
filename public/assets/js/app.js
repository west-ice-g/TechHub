/*
 * TechHub - 高校技术团队展示与管理平台
 * 文件：public/assets/js/app.js
 * 职责：提供不依赖前端框架的轻量交互；危险操作二次确认。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

'use strict';

document.addEventListener('submit', function (event) {
    const form = event.target instanceof Element
        ? event.target.closest('form[data-confirm]')
        : null;

    if (!form) {
        return;
    }

    const message = form.getAttribute('data-confirm') || '确认执行该操作吗？';
    if (!window.confirm(message)) {
        event.preventDefault();
    }
});
