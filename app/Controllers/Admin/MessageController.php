<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/MessageController.php
 * 模块：后台留言管理
 * 职责：留言搜索、审核、回复、查看详情和删除。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Message;
use Core\Auth;
use Core\Paginator;
use Core\Response;

final class MessageController extends AdminController
{
    private const PER_PAGE = 15;

    public function index(): Response
    {
        $status = (string) $this->request->query('status', '');
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : '';
        $query = $this->request->query('q', '');
        $query = is_string($query) ? trim($query) : '';
        $model = new Message();
        $total = $model->countAdmin($status, $query);
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->page('admin/messages/index', '留言管理', 'messages', [
            'messages' => $model->adminPage(self::PER_PAGE, $pagination['offset'], $status, $query),
            'counts' => $model->statusCounts(),
            'query' => $query,
            'status' => $status,
            'pagination' => $pagination,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '互动与资源'],
            ['label' => '留言管理'],
        ]);
    }

    public function show(string $id): Response
    {
        $thread = (new Message())->findAdminWithReplies((int) $id);
        if ($thread === null) {
            abort(404, '留言不存在。');
        }

        return $this->page('admin/messages/show', '留言详情', 'messages', [
            'message' => $thread['message'],
            'replies' => $thread['replies'],
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '留言管理', 'url' => '/admin/messages'],
            ['label' => '留言 #' . (int) $id],
        ]);
    }

    public function status(string $id): Response
    {
        $messageId = (int) $id;
        $model = new Message();
        $thread = $model->findAdminWithReplies($messageId);
        if ($thread === null) {
            abort(404, '留言不存在。');
        }

        $status = $this->text('status', 20);
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $this->error('留言状态不合法。');
            return $this->redirect('/admin/messages/' . $messageId);
        }

        $model->setStatus($messageId, $status);
        $this->logAction('messages', 'status', '留言状态改为 ' . $status . '：#' . $messageId, 'message', $messageId);
        $this->success('留言审核状态已更新。');

        return $this->redirect('/admin/messages/' . $messageId);
    }

    public function reply(string $id): Response
    {
        $messageId = (int) $id;
        $model = new Message();
        $thread = $model->findAdminWithReplies($messageId);
        if ($thread === null) {
            abort(404, '留言不存在。');
        }

        $content = $this->text('content', 2000);
        $status = $this->text('status', 20);
        $status = $status === 'hidden' ? 'hidden' : 'visible';
        if (mb_strlen($content) < 2) {
            $this->error('回复内容至少 2 个字符。');
            return $this->redirect('/admin/messages/' . $messageId);
        }

        $replyId = $model->addReply($messageId, (int) Auth::id(), $content, $status);
        $this->logAction('messages', 'reply', '回复留言 #' . $messageId, 'message_reply', $replyId);
        $this->success('回复已发布。');

        return $this->redirect('/admin/messages/' . $messageId);
    }

    public function destroy(string $id): Response
    {
        $messageId = (int) $id;
        $model = new Message();
        $thread = $model->findAdminWithReplies($messageId);
        if ($thread === null) {
            abort(404, '留言不存在。');
        }

        $model->deleteMessage($messageId);
        $this->logAction('messages', 'delete', '删除留言 #' . $messageId, 'message', $messageId);
        $this->success('留言及其回复已删除。');

        return $this->redirect('/admin/messages');
    }
}
