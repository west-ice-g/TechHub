<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：tests/frontend_smoke.php
 * 模块：前台数据层验证
 * 职责：验证前台 Model、分页、Markdown 转义、下载路径与个人资料查询。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/app.php';

use App\Models\Announcement;
use App\Models\Article;
use App\Models\Category;
use App\Models\FileResource;
use App\Models\Member;
use App\Models\Message;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Support\DownloadPath;
use App\Support\Markdown;
use Core\Paginator;

$passed = 0;
$failed = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] {$label}\n";
        return;
    }

    $failed++;
    echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

$team = (new Team())->primary();
check('Team page data exists', is_array($team) && ($team['name'] ?? '') !== '');
check('Team research areas available', trim((string) ($team['research_areas'] ?? '')) !== '');
check('Team tech stack available', trim((string) ($team['tech_stack'] ?? '')) !== '');
check('Team logo file exists', is_file(BASE_PATH . '/public/' . (string) ($team['logo_path'] ?? '')));

$memberModel = new Member();
$teamId = $team === null ? 0 : (int) $team['id'];
$memberTotal = $teamId > 0 ? $memberModel->countPublic($teamId) : 0;
$memberPage = $teamId > 0 ? $memberModel->publicPage($teamId, 3, 0) : [];
$member = $memberModel->findPublic(1);
check('Member list reads data', $memberTotal >= 1 && $memberPage !== []);
check('Member detail reads data', is_array($member) && ($member['display_name'] ?? '') !== '');
check('Member detail has projects', $member !== null && $memberModel->projectsForMember((int) $member['membership_id']) !== []);
check('Member avatar file exists', is_file(BASE_PATH . '/public/' . (string) ($member['avatar_path'] ?? '')));

$projectModel = new Project();
$project = $projectModel->findPublicBySlug('campus-tech-community');
check('Project list reads data', $projectModel->countPublic() >= 1);
check('Project detail reads outcomes', is_array($project) && trim((string) ($project['outcomes'] ?? '')) !== '');
check('Project cover file exists', is_file(BASE_PATH . '/public/' . (string) ($project['cover_path'] ?? '')));
check('Project members read data', $project !== null && $projectModel->membersForProject((int) $project['id']) !== []);

$categoryModel = new Category();
$categories = $categoryModel->enabledWithPublishedCounts();
$articleModel = new Article();
$article = $articleModel->findPublishedBySlug('database-design-from-requirements');
check('Article categories read data', $categories !== []);
check('Article list reads data', $articleModel->countPublished() >= 1);
check('Article category filter works', $articleModel->countPublished('database-design') >= 1);
check('Article search works', $articleModel->countPublished('', 'MVC') >= 1);
check('Article detail reads content', is_array($article) && str_contains((string) ($article['content'] ?? ''), '##'));

$announcement = (new Announcement())->findActiveById(1);
check('Announcement list/detail reads data', is_array($announcement) && ($announcement['title'] ?? '') !== '');

$thread = (new Message())->findApprovedWithReplies(1);
check('Message thread reads approved message', is_array($thread) && ($thread['message']['visitor_name'] ?? '') !== '');
check('Message thread reads admin reply', is_array($thread) && $thread['replies'] !== []);

$fileModel = new FileResource();
$file = $fileModel->findPublicById(1);
check('Download list reads team files', $fileModel->countPublicByCategory('team_assignment') >= 1);
check('Download list reads personal files', $fileModel->countPublicByCategory('personal_assignment') >= 1);
check('Download file metadata reads', is_array($file) && ($file['title'] ?? '') !== '');

$validPath = DownloadPath::resolve(BASE_PATH, 'data/team/team-sample.txt', 'data/team');
$traversalPath = DownloadPath::resolve(BASE_PATH, 'data/team/../personal/personal-sample.txt', 'data/team');
$outsidePath = DownloadPath::resolve(BASE_PATH, 'data/personal/personal-sample.txt', 'data/team');
$missingPath = DownloadPath::resolve(BASE_PATH, 'data/team/not-found.txt', 'data/team');
check('Download path resolves valid file', $validPath !== null && is_file($validPath));
check('Download path rejects traversal', $traversalPath === null);
check('Download path rejects other category directory', $outsidePath === null);
check('Download path rejects missing file', $missingPath === null);

$profile = (new User())->findFrontProfile(3);
check('Profile reads member account data', is_array($profile) && ($profile['display_name'] ?? '') !== '');
check('Profile resolves team membership', is_array($profile) && !empty($profile['membership_id']));

$page = Paginator::resolve(999, 13, 5);
check('Paginator clamps beyond last page', $page['page'] === 3 && $page['offset'] === 10);
$zero = Paginator::resolve('abc', 0, 5);
check('Paginator handles empty dataset', $zero['page'] === 1 && $zero['from'] === 0 && $zero['to'] === 0);

$markdown = Markdown::render("## 标题\n\n<script>alert(1)</script>\n\n- 列表项");
check('Markdown renders headings', str_contains($markdown, '<h2>标题</h2>'));
check('Markdown escapes script tags', !str_contains($markdown, '<script>') && str_contains($markdown, '&lt;script&gt;'));
check('Markdown renders lists', str_contains($markdown, '<ul><li>列表项</li></ul>'));

echo "\nFrontend summary: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
