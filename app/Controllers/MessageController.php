<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/MessageController.php
 * 模块：留言与回复
 * 职责：显示已审核留言、处理访客留言提交、校验输入并展示管理员回复。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use Core\Controller;
use Core\Paginator;
use Core\Response;
use Core\Session;

final class MessageController extends Controller
{
    private const PER_PAGE = 5;
    private const COOLDOWN_SECONDS = 30;
    private const HOURLY_LIMIT = 3;

    public function index(): Response
    {
        $model = new Message();
        $total = $model->countApproved();
        $pagination = Paginator::resolve($this->request->query('page'), $total, self::PER_PAGE);

        return $this->view('message/index', [
            'title' => '留言板 · TechHub',
            'messages' => $model->approvedPage(self::PER_PAGE, $pagination['offset']),
            'pagination' => $pagination,
            'paginationPath' => '/messages',
            'paginationQuery' => [],
            'old' => Session::pull('_message_old', []),
            'errors' => Session::pull('_message_errors', []),
        ]);
    }

    public function store(): Response
    {
        $honeypot = $this->request->post('website', '');
        if (is_string($honeypot) && trim($honeypot) !== '') {
            Session::flash('success', '留言已提交，审核通过后会显示。');
            return $this->redirect('/messages');
        }

        $limitMessage = $this->checkRateLimit();
        if ($limitMessage !== null) {
            Session::flash('error', $limitMessage);
            return $this->redirect('/messages#message-form');
        }

        $rawName = $this->request->post('visitor_name', '');
        $rawEmail = $this->request->post('visitor_email', '');
        $rawContent = $this->request->post('content', '');

        $name = is_string($rawName) ? trim($rawName) : '';
        $email = is_string($rawEmail) ? trim($rawEmail) : '';
        $content = is_string($rawContent) ? trim($rawContent) : '';

        $errors = [];
        if (!mb_check_encoding($name, 'UTF-8')) {
            $errors['visitor_name'] = '昵称编码不合法。';
            $name = '';
        } elseif (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 50) {
            $errors['visitor_name'] = '昵称长度应为 2～50 个字符。';
        }

        if (!mb_check_encoding($email, 'UTF-8')) {
            $errors['visitor_email'] = '邮箱编码不合法。';
            $email = '';
        } elseif ($email !== '') {
            if (mb_strlen($email, 'UTF-8') > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['visitor_email'] = '请输入有效的邮箱地址。';
            }
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            $errors['content'] = '留言内容编码不合法。';
            $content = '';
        } elseif (mb_strlen($content, 'UTF-8') < 5 || mb_strlen($content, 'UTF-8') > 2000) {
            $errors['content'] = '留言内容长度应为 5～2000 个字符。';
        }

        if ($errors !== []) {
            Session::put('_message_old', [
                'visitor_name' => $name,
                'visitor_email' => $email,
                'content' => $content,
            ]);
            Session::put('_message_errors', $errors);
            Session::flash('error', '留言未提交，请检查表单中的提示。');

            return $this->redirect('/messages#message-form');
        }

        (new Message())->createVisitor(
            $name,
            $email === '' ? null : $email,
            $content,
            $this->request->ip(),
            $this->request->userAgent()
        );

        $this->recordSuccessfulSubmission();
        Session::forget('_message_old');
        Session::forget('_message_errors');
        Session::flash('success', '留言已提交，审核通过后会显示在留言板中。');

        return $this->redirect('/messages');
    }

    public function show(string $id): Response
    {
        $messageId = (int) $id;
        if ($messageId <= 0) {
            abort(404, '留言不存在。');
        }

        $thread = (new Message())->findApprovedWithReplies($messageId);
        if ($thread === null) {
            abort(404, '留言不存在或尚未审核通过。');
        }

        return $this->view('message/show', [
            'title' => '留言详情 · TechHub',
            'message' => $thread['message'],
            'replies' => $thread['replies'],
        ]);
    }

    private function checkRateLimit(): ?string
    {
        $now = time();
        $lastAt = Session::get('_message_last_at');
        if (is_int($lastAt) && $now - $lastAt < self::COOLDOWN_SECONDS) {
            return '提交过于频繁，请稍后再试。';
        }

        $times = Session::get('_message_times', []);
        if (!is_array($times)) {
            $times = [];
        }

        $times = array_values(array_filter(
            $times,
            static fn (mixed $value): bool => is_int($value) && $value > $now - 3600
        ));

        if (count($times) >= self::HOURLY_LIMIT) {
            return '一小时内提交次数过多，请稍后再试。';
        }

        return null;
    }

    private function recordSuccessfulSubmission(): void
    {
        $now = time();
        $times = Session::get('_message_times', []);
        if (!is_array($times)) {
            $times = [];
        }

        $times[] = $now;
        Session::put('_message_times', array_slice($times, -10));
        Session::put('_message_last_at', $now);
    }
}
