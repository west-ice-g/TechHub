<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：tests/admin_smoke.php
 * 模块：后台管理集成验证
 * 职责：通过真实 HTTP 会话验证后台 CRUD、RBAC、CSRF、上传、审核、日志与统计接口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/app.php';

use App\Support\ImageUpload;
use Core\Database;

$baseUrl = rtrim((string) (getenv('TECHHUB_TEST_BASE') ?: 'http://127.0.0.1:8090'), '/');
$tmpDir = BASE_PATH . '/tests/tmp';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0775, true);
}

$adminJar = $tmpDir . '/admin_smoke_admin.cookies';
$editorJar = $tmpDir . '/admin_smoke_editor.cookies';
$userJar = $tmpDir . '/admin_smoke_user.cookies';
$guestJar = $tmpDir . '/admin_smoke_guest.cookies';
$disabledJar = $tmpDir . '/admin_smoke_disabled.cookies';
foreach ([$adminJar, $editorJar, $userJar, $guestJar, $disabledJar] as $jar) {
    @unlink($jar);
}

$passed = 0;
$failed = 0;
$suffix = date('His') . '-' . bin2hex(random_bytes(3));
$testUsername = 'test_admin_smoke_' . $suffix;
$testPassword = 'Tmp-Smoke-' . bin2hex(random_bytes(6));
$testRoleCode = 'test_smoke_role_' . str_replace('-', '_', $suffix);
$testCategorySlug = 'test-smoke-category-' . strtolower(substr($suffix, 0, 6));
$testProjectSlug = 'test-smoke-project-' . strtolower(substr($suffix, 0, 6));
$testArticleSlug = 'test-smoke-article-' . strtolower(substr($suffix, 0, 6));
$testAnnouncementTitle = '测试公告 ' . $suffix;
$testFileTitle = '测试文件 ' . $suffix;
$messageMarker = '后台集成测试留言 ' . $suffix;
$originalTeamSlogan = null;
$teamChanged = false;
$createdIds = [
    'user' => null,
    'role' => null,
    'category' => null,
    'project' => null,
    'article' => null,
    'announcement' => null,
    'membership' => null,
    'file' => null,
];

/**
 * @param array<string, mixed> $data
 * @param array<string, mixed> $files
 * @return array{status: int, body: string, location: string}
 */
function httpRequest(
    string $baseUrl,
    string $method,
    string $path,
    string $jar,
    array $data = [],
    array $files = []
): array {
    $ch = curl_init($baseUrl . $path);
    if ($ch === false) {
        return ['status' => 0, 'body' => '', 'location' => ''];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Accept: text/html,application/json'],
    ]);

    if ($method === 'POST') {
        $payload = $files === [] ? http_build_query($data) : array_merge($data, $files);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $location = (string) (curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: '');
    if (is_string($location) && $location !== '') {
        $location = (string) parse_url($location, PHP_URL_PATH);
    }
    curl_close($ch);

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
        'location' => $location,
    ];
}

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

function csrfToken(string $html): ?string
{
    return preg_match('/name="_token" value="([^"]+)"/', $html, $matches) === 1 ? $matches[1] : null;
}

function db(): PDO
{
    return Database::connection();
}

/**
 * @param array<string, mixed> $params
 */
function scalar(string $sql, array $params = []): mixed
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $value = $statement->fetchColumn();

    return $value === false ? null : $value;
}

/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>|null
 */
function row(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $value = $statement->fetch();

    return is_array($value) ? $value : null;
}

function fileExists(string $path): bool
{
    clearstatcache(true, $path);

    return is_file($path);
}

/**
 * @return array{status: int, body: string, location: string}
 */
function getPage(string $path, string $jar): array
{
    global $baseUrl;

    return httpRequest($baseUrl, 'GET', $path, $jar);
}

/**
 * @param array<string, mixed> $data
 * @param array<string, mixed> $files
 * @return array{status: int, body: string, location: string}
 */
function postPage(string $path, string $jar, array $data, array $files = []): array
{
    global $baseUrl;

    return httpRequest($baseUrl, 'POST', $path, $jar, $data, $files);
}

function login(string $baseUrl, string $jar, string $username, string $password): array
{
    $loginPage = httpRequest($baseUrl, 'GET', '/admin/login', $jar);
    $token = csrfToken($loginPage['body']);

    return httpRequest($baseUrl, 'POST', '/admin/login', $jar, [
        '_token' => $token ?? '',
        'username' => $username,
        'password' => $password,
    ]);
}

function cleanupTestData(): void
{
    global $testUsername, $testRoleCode, $testCategorySlug, $testProjectSlug, $testArticleSlug,
        $testAnnouncementTitle, $testFileTitle, $messageMarker, $originalTeamSlogan, $teamChanged;

    try {
        if ($teamChanged && $originalTeamSlogan !== null) {
            $team = row('SELECT * FROM teams ORDER BY id LIMIT 1');
            if ($team !== null) {
                $stmt = db()->prepare('UPDATE teams SET slogan = :slogan WHERE id = :id');
                $stmt->execute(['slogan' => $originalTeamSlogan, 'id' => $team['id']]);
            }
        }

        $stmt = db()->prepare('SELECT id FROM projects WHERE slug = :slug');
        $stmt->execute(['slug' => $testProjectSlug]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $project = row('SELECT cover_path FROM projects WHERE id = :id', ['id' => $id]);
            if ($project !== null) {
                ImageUpload::removeManaged((string) ($project['cover_path'] ?? ''));
            }
            db()->prepare('DELETE FROM projects WHERE id = :id')->execute(['id' => $id]);
        }

        $stmt = db()->prepare('SELECT id, cover_path FROM articles WHERE slug = :slug');
        $stmt->execute(['slug' => $testArticleSlug]);
        foreach ($stmt->fetchAll() as $article) {
            ImageUpload::removeManaged((string) ($article['cover_path'] ?? ''));
            db()->prepare('DELETE FROM articles WHERE id = :id')->execute(['id' => $article['id']]);
        }

        $stmt = db()->prepare('SELECT id FROM announcements WHERE title LIKE :title');
        $stmt->execute(['title' => $testAnnouncementTitle . '%']);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            db()->prepare('DELETE FROM announcements WHERE id = :id')->execute(['id' => $id]);
        }

        $stmt = db()->prepare('SELECT id, category, file_path FROM files WHERE title LIKE :title');
        $stmt->execute(['title' => $testFileTitle . '%']);
        $directories = [
            'team_assignment' => 'data/team',
            'personal_assignment' => 'data/personal',
            'resource' => 'data/resources',
        ];
        foreach ($stmt->fetchAll() as $file) {
            $directory = $directories[(string) $file['category']] ?? null;
            if ($directory !== null) {
                $relative = (string) $file['file_path'];
                if (
                    str_starts_with($relative, $directory . '/')
                    && !str_contains($relative, '..')
                    && fileExists(BASE_PATH . '/' . $relative)
                ) {
                    @unlink(BASE_PATH . '/' . $relative);
                }
            }
            db()->prepare('DELETE FROM files WHERE id = :id')->execute(['id' => $file['id']]);
        }

        $stmt = db()->prepare('SELECT id FROM messages WHERE content LIKE :content');
        $stmt->execute(['content' => '%' . $messageMarker . '%']);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            db()->prepare('DELETE FROM messages WHERE id = :id')->execute(['id' => $id]);
        }

        $user = row('SELECT id FROM users WHERE username = :username', ['username' => $testUsername]);
        if ($user !== null) {
            $userId = (int) $user['id'];
            $profile = row('SELECT avatar_path FROM member_profiles WHERE user_id = :id', ['id' => $userId]);
            if ($profile !== null) {
                ImageUpload::removeManaged((string) ($profile['avatar_path'] ?? ''));
            }
            $memberships = db()->prepare('SELECT id FROM team_members WHERE user_id = :id');
            $memberships->execute(['id' => $userId]);
            foreach ($memberships->fetchAll(PDO::FETCH_COLUMN) as $membershipId) {
                db()->prepare('DELETE FROM project_members WHERE team_member_id = :id')->execute(['id' => $membershipId]);
                db()->prepare('DELETE FROM files WHERE related_member_id = :id')->execute(['id' => $membershipId]);
                db()->prepare('DELETE FROM team_members WHERE id = :id')->execute(['id' => $membershipId]);
            }
            db()->prepare('DELETE FROM member_profiles WHERE user_id = :id')->execute(['id' => $userId]);
            db()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
        }

        db()->prepare('DELETE FROM roles WHERE code = :code')->execute(['code' => $testRoleCode]);
        db()->prepare('DELETE FROM categories WHERE slug = :slug')->execute(['slug' => $testCategorySlug]);
    } catch (Throwable $e) {
        echo "[WARN] 测试数据清理异常：" . $e->getMessage() . "\n";
    }
}

register_shutdown_function(static function (): void {
    cleanupTestData();
});

echo "TechHub admin HTTP integration test\n";
echo "Base URL: {$baseUrl}\n\n";

$loginPage = getPage('/admin/login', $adminJar);
check('开发服务器可达', $loginPage['status'] === 200, 'HTTP ' . $loginPage['status']);
if ($loginPage['status'] !== 200) {
    echo "\nAdmin summary: {$passed} passed, {$failed} failed.\n";
    exit(1);
}

$adminPassword = (string) env('INITIAL_ADMIN_PASSWORD', '');
$adminUsername = (string) env('INITIAL_ADMIN_USERNAME', 'admin');
$editorPassword = (string) env('INITIAL_EDITOR_PASSWORD', '');
$editorUsername = (string) env('INITIAL_EDITOR_USERNAME', 'editor');

$result = login($baseUrl, $adminJar, $adminUsername, $adminPassword);
check('超级管理员登录', $result['status'] === 302 && $result['location'] === '/admin');
check('后台首页可访问', getPage('/admin', $adminJar)['status'] === 200);
check('统计接口返回 JSON', str_contains(getPage('/admin/api/stats', $adminJar)['body'], '"project_status"'));

$stats = json_decode(getPage('/admin/api/stats', $adminJar)['body'], true);
check('统计接口六项汇总', is_array($stats) && isset($stats['summary']['members'], $stats['summary']['projects']));
check('项目状态饼图数据来自数据库', is_array($stats) && count($stats['project_status']['labels']) === count($stats['project_status']['values']));
check('文章趋势为 6 个月', is_array($stats) && count($stats['article_trend']['labels']) === 6);
check('成员参与项目图有数据', is_array($stats) && $stats['member_participation']['labels'] !== []);
check('统计汇总与 users 表一致', is_array($stats) && $stats['summary']['users'] === (int) scalar('SELECT COUNT(*) FROM users'));

$noToken = httpRequest($baseUrl, 'POST', '/admin/projects', $adminJar, ['title' => 'csrf-test']);
check('后台 POST 缺少 CSRF 返回 403', $noToken['status'] === 403, 'status=' . $noToken['status'] . ' location=' . $noToken['location'] . ' body=' . substr(strip_tags($noToken['body']), 0, 120));

if ($editorPassword !== '') {
    $editorLogin = login($baseUrl, $editorJar, $editorUsername, $editorPassword);
    check('内容编辑账号登录', $editorLogin['status'] === 302);
    check('编辑可访问项目模块', getPage('/admin/projects', $editorJar)['status'] === 200);
    check('编辑访问用户管理被拒绝', getPage('/admin/users', $editorJar)['status'] === 403);
    check('编辑访问角色权限被拒绝', getPage('/admin/roles', $editorJar)['status'] === 403);
    $editorPage = getPage('/admin/projects', $editorJar);
    $editorPost = postPage('/admin/users', $editorJar, [
        '_token' => csrfToken($editorPage['body']) ?? '',
        'username' => 'should-not-create',
        'display_name' => '越权测试',
        'role_id' => 3,
        'status' => 'active',
        'password' => 'NotAllowed-123',
        'password_confirm' => 'NotAllowed-123',
    ]);
    check('编辑即使持有效 CSRF 也不能新增用户', $editorPost['status'] === 403);
} else {
    check('编辑账号凭据存在', false, '缺少 INITIAL_EDITOR_PASSWORD');
}

// ------------------------------------------------------------
// 用户管理
// ------------------------------------------------------------
$userCreatePage = getPage('/admin/users/create', $adminJar);
$userCreateToken = csrfToken($userCreatePage['body']);
$userCreate = postPage('/admin/users', $adminJar, [
    '_token' => $userCreateToken ?? '',
    'username' => $testUsername,
    'display_name' => '后台集成测试账号',
    'email' => $testUsername . '@example.com',
    'role_id' => 3,
    'status' => 'active',
    'password' => $testPassword,
    'password_confirm' => $testPassword,
]);
$testUser = row('SELECT id, password_hash, status FROM users WHERE username = :username', ['username' => $testUsername]);
$createdIds['user'] = $testUser === null ? null : (int) $testUser['id'];
check('新增用户写入数据库', $userCreate['status'] === 302 && $testUser !== null);
check('用户密码以哈希保存', $testUser !== null && password_verify($testPassword, (string) $testUser['password_hash']));

$userLogin = login($baseUrl, $userJar, $testUsername, $testPassword);
check('新用户可登录后台', $userLogin['status'] === 302 && $userLogin['location'] === '/admin');
$memberDashboard = getPage('/admin', $userJar);
check('普通成员只能看到授权菜单', $memberDashboard['status'] === 200
    && !str_contains($memberDashboard['body'], '项目管理')
    && !str_contains($memberDashboard['body'], '用户管理'));
check('普通成员访问项目被 403', getPage('/admin/projects', $userJar)['status'] === 403);
check('普通成员访问统计接口被 403', getPage('/admin/api/stats', $userJar)['status'] === 403);

$userId = (int) $createdIds['user'];
$userEditPage = getPage('/admin/users/' . $userId . '/edit', $adminJar);
$userEdit = postPage('/admin/users/' . $userId . '/edit', $adminJar, [
    '_token' => csrfToken($userEditPage['body']) ?? '',
    'display_name' => '后台集成测试账号改名',
    'email' => $testUsername . '@example.com',
    'role_id' => 3,
    'status' => 'active',
    'password' => '',
    'password_confirm' => '',
]);
$renamed = row('SELECT display_name FROM users WHERE id = :id', ['id' => $userId]);
check('编辑用户显示名称', $userEdit['status'] === 302 && ($renamed['display_name'] ?? '') === '后台集成测试账号改名');

$adminId = (int) scalar('SELECT id FROM users WHERE username = :username', ['username' => $adminUsername]);
$adminSelfPage = getPage('/admin/users/' . $adminId . '/edit', $adminJar);
$selfDemote = postPage('/admin/users/' . $adminId . '/edit', $adminJar, [
    '_token' => csrfToken($adminSelfPage['body']) ?? '',
    'display_name' => (string) scalar('SELECT display_name FROM users WHERE id = :id', ['id' => $adminId]),
    'email' => (string) scalar('SELECT email FROM users WHERE id = :id', ['id' => $adminId]),
    'role_id' => 2,
    'status' => 'active',
    'password' => '',
    'password_confirm' => '',
]);
check('超级管理员不能把自己降级', $selfDemote['status'] === 302
    && $selfDemote['location'] === '/admin/users/' . $adminId . '/edit'
    && (int) scalar('SELECT role_id FROM users WHERE id = :id', ['id' => $adminId]) === (int) scalar("SELECT id FROM roles WHERE code = 'super_admin'"));
getPage('/admin/users/' . $adminId . '/edit', $adminJar);

$currentUserList = getPage('/admin/users', $adminJar);
$selfDelete = postPage('/admin/users/' . $adminId . '/delete', $adminJar, [
    '_token' => csrfToken($currentUserList['body']) ?? '',
]);
check('超级管理员不能删除自己', $selfDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM users WHERE id = :id', ['id' => $adminId]) === 1);

$superRoleId = (int) scalar("SELECT id FROM roles WHERE code = 'super_admin'");
$superRolePage = getPage('/admin/roles/' . $superRoleId . '/edit', $adminJar);
$superPermissionPost = postPage('/admin/roles/' . $superRoleId . '/permissions', $adminJar, [
    '_token' => csrfToken($superRolePage['body']) ?? '',
    'permissions' => [1],
]);
check('超级管理员角色不能被界面降权', $superPermissionPost['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM role_permissions WHERE role_id = :id', ['id' => $superRoleId]) === 14);

$statusPage = getPage('/admin/users', $adminJar);
$statusResult = postPage('/admin/users/' . $userId . '/status', $adminJar, [
    '_token' => csrfToken($statusPage['body']) ?? '',
]);
check('停用用户', $statusResult['status'] === 302 && scalar('SELECT status FROM users WHERE id = :id', ['id' => $userId]) === 'disabled');
$disabledLogin = login($baseUrl, $disabledJar, $testUsername, $testPassword);
check('停用账号不能登录', $disabledLogin['status'] === 302 && $disabledLogin['location'] === '/admin/login');
$statusPage = getPage('/admin/users', $adminJar);
postPage('/admin/users/' . $userId . '/status', $adminJar, ['_token' => csrfToken($statusPage['body']) ?? '']);
check('重新启用用户', scalar('SELECT status FROM users WHERE id = :id', ['id' => $userId]) === 'active');

// ------------------------------------------------------------
// 角色权限
// ------------------------------------------------------------
$roleCreatePage = getPage('/admin/roles/create', $adminJar);
$roleCreate = postPage('/admin/roles', $adminJar, [
    '_token' => csrfToken($roleCreatePage['body']) ?? '',
    'name' => '集成测试角色',
    'code' => $testRoleCode,
    'description' => '由自动化测试创建',
    'sort_order' => 88,
]);
$testRole = row('SELECT id FROM roles WHERE code = :code', ['code' => $testRoleCode]);
$createdIds['role'] = $testRole === null ? null : (int) $testRole['id'];
check('新增角色', $roleCreate['status'] === 302 && $testRole !== null);

$roleId = (int) $createdIds['role'];
$roleEditPage = getPage('/admin/roles/' . $roleId . '/edit', $adminJar);
$permissionResult = postPage('/admin/roles/' . $roleId . '/permissions', $adminJar, [
    '_token' => csrfToken($roleEditPage['body']) ?? '',
    'permissions' => [1, 3],
]);
check('保存角色权限矩阵', $permissionResult['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM role_permissions WHERE role_id = :id', ['id' => $roleId]) === 2);

$roleEditPage = getPage('/admin/roles/' . $roleId . '/edit', $adminJar);
$roleEdit = postPage('/admin/roles/' . $roleId . '/edit', $adminJar, [
    '_token' => csrfToken($roleEditPage['body']) ?? '',
    'name' => '集成测试角色改名',
    'description' => '由自动化测试改名',
    'sort_order' => 89,
]);
check('编辑角色', $roleEdit['status'] === 302
    && scalar('SELECT name FROM roles WHERE id = :id', ['id' => $roleId]) === '集成测试角色改名');

$roleList = getPage('/admin/roles', $adminJar);
$roleDelete = postPage('/admin/roles/' . $roleId . '/delete', $adminJar, [
    '_token' => csrfToken($roleList['body']) ?? '',
]);
check('删除空角色', $roleDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM roles WHERE id = :id', ['id' => $roleId]) === 0);

// ------------------------------------------------------------
// 分类管理
// ------------------------------------------------------------
$categoryCreatePage = getPage('/admin/categories/create', $adminJar);
$categoryCreate = postPage('/admin/categories', $adminJar, [
    '_token' => csrfToken($categoryCreatePage['body']) ?? '',
    'name' => '集成测试分类',
    'slug' => $testCategorySlug,
    'description' => '由自动化测试创建',
    'parent_id' => 0,
    'sort_order' => 70,
    'status' => 'enabled',
]);
$testCategory = row('SELECT id FROM categories WHERE slug = :slug', ['slug' => $testCategorySlug]);
$createdIds['category'] = $testCategory === null ? null : (int) $testCategory['id'];
check('新增文章分类', $categoryCreate['status'] === 302 && $testCategory !== null);

$categoryId = (int) $createdIds['category'];
$categoryEditPage = getPage('/admin/categories/' . $categoryId . '/edit', $adminJar);
$categoryEdit = postPage('/admin/categories/' . $categoryId . '/edit', $adminJar, [
    '_token' => csrfToken($categoryEditPage['body']) ?? '',
    'name' => '集成测试分类改名',
    'slug' => $testCategorySlug,
    'description' => '由自动化测试改名',
    'parent_id' => 0,
    'sort_order' => 71,
    'status' => 'enabled',
]);
check('编辑文章分类', $categoryEdit['status'] === 302
    && scalar('SELECT name FROM categories WHERE id = :id', ['id' => $categoryId]) === '集成测试分类改名');

$categoryList = getPage('/admin/categories', $adminJar);
postPage('/admin/categories/' . $categoryId . '/status', $adminJar, ['_token' => csrfToken($categoryList['body']) ?? '']);
check('停用文章分类', scalar('SELECT status FROM categories WHERE id = :id', ['id' => $categoryId]) === 'disabled');
$categoryList = getPage('/admin/categories', $adminJar);
$categoryDelete = postPage('/admin/categories/' . $categoryId . '/delete', $adminJar, ['_token' => csrfToken($categoryList['body']) ?? '']);
check('删除文章分类', $categoryDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM categories WHERE id = :id', ['id' => $categoryId]) === 0);

// ------------------------------------------------------------
// 项目管理
// ------------------------------------------------------------
$projectCreatePage = getPage('/admin/projects/create', $adminJar);
$projectCreate = postPage('/admin/projects', $adminJar, [
    '_token' => csrfToken($projectCreatePage['body']) ?? '',
    'title' => '集成测试项目',
    'slug' => $testProjectSlug,
    'summary' => '后台集成测试项目摘要',
    'description' => '后台集成测试项目详细介绍，用于验证项目创建、成员关联、发布和删除。',
    'outcomes' => '集成测试项目成果',
    'tech_stack' => 'PHP / MySQL',
    'repo_url' => '',
    'demo_url' => '',
    'status' => 'ongoing',
    'is_public' => 1,
    'is_featured' => 0,
    'sort_order' => 77,
    'start_date' => '2026-11-01',
    'end_date' => '',
    'members' => [1, 3],
    'member_roles' => [1 => '测试后端', 3 => '测试设计'],
]);
$testProject = row('SELECT id, is_public FROM projects WHERE slug = :slug', ['slug' => $testProjectSlug]);
$createdIds['project'] = $testProject === null ? null : (int) $testProject['id'];
check('新增项目', $projectCreate['status'] === 302 && $testProject !== null);
check('项目成员关联写入', $testProject !== null
    && (int) scalar('SELECT COUNT(*) FROM project_members WHERE project_id = :id', ['id' => $testProject['id']]) === 2);
check('项目详情前台可访问', getPage('/projects/' . $testProjectSlug, $guestJar)['status'] === 200);

$projectId = (int) $createdIds['project'];
$projectList = getPage('/admin/projects', $adminJar);
postPage('/admin/projects/' . $projectId . '/visibility', $adminJar, ['_token' => csrfToken($projectList['body']) ?? '']);
check('项目下架后前台 404', (int) scalar('SELECT is_public FROM projects WHERE id = :id', ['id' => $projectId]) === 0
    && getPage('/projects/' . $testProjectSlug, $guestJar)['status'] === 404);

$projectEditPage = getPage('/admin/projects/' . $projectId . '/edit', $adminJar);
$projectEdit = postPage('/admin/projects/' . $projectId . '/edit', $adminJar, [
    '_token' => csrfToken($projectEditPage['body']) ?? '',
    'title' => '集成测试项目改名',
    'slug' => $testProjectSlug,
    'summary' => '后台集成测试项目摘要改名',
    'description' => '后台集成测试项目详细介绍，用于验证项目编辑、成员重设、发布和删除。',
    'outcomes' => '集成测试项目成果改名',
    'tech_stack' => 'PHP / MySQL / Bootstrap',
    'repo_url' => '',
    'demo_url' => '',
    'status' => 'completed',
    'is_public' => 1,
    'is_featured' => 1,
    'sort_order' => 78,
    'start_date' => '2026-11-02',
    'end_date' => '2026-11-20',
    'members' => [2],
    'member_roles' => [2 => '测试前端'],
]);
check('编辑项目并重设成员', $projectEdit['status'] === 302
    && scalar('SELECT title FROM projects WHERE id = :id', ['id' => $projectId]) === '集成测试项目改名'
    && (int) scalar('SELECT COUNT(*) FROM project_members WHERE project_id = :id', ['id' => $projectId]) === 1);

$projectList = getPage('/admin/projects', $adminJar);
$projectDelete = postPage('/admin/projects/' . $projectId . '/delete', $adminJar, ['_token' => csrfToken($projectList['body']) ?? '']);
check('删除项目并级联成员', $projectDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM projects WHERE id = :id', ['id' => $projectId]) === 0
    && (int) scalar('SELECT COUNT(*) FROM project_members WHERE project_id = :id', ['id' => $projectId]) === 0);

// ------------------------------------------------------------
// 文章管理
// ------------------------------------------------------------
$articleCreatePage = getPage('/admin/articles/create', $adminJar);
$articleCreate = postPage('/admin/articles', $adminJar, [
    '_token' => csrfToken($articleCreatePage['body']) ?? '',
    'title' => '集成测试文章',
    'slug' => $testArticleSlug,
    'summary' => '后台集成测试文章摘要',
    'content' => "## 集成测试\n\n这是后台集成测试文章正文，用于验证草稿、发布和删除。",
    'category_id' => 1,
    'status' => 'draft',
    'is_featured' => 0,
]);
$testArticle = row('SELECT id, status FROM articles WHERE slug = :slug', ['slug' => $testArticleSlug]);
$createdIds['article'] = $testArticle === null ? null : (int) $testArticle['id'];
check('新增文章为草稿', $articleCreate['status'] === 302 && ($testArticle['status'] ?? '') === 'draft');
check('草稿前台不可访问', getPage('/articles/' . $testArticleSlug, $guestJar)['status'] === 404);

$articleId = (int) $createdIds['article'];
$articleList = getPage('/admin/articles', $adminJar);
postPage('/admin/articles/' . $articleId . '/status', $adminJar, [
    '_token' => csrfToken($articleList['body']) ?? '',
    'status' => 'published',
]);
$published = row('SELECT status, published_at FROM articles WHERE id = :id', ['id' => $articleId]);
check('文章发布后前台可访问', ($published['status'] ?? '') === 'published'
    && ($published['published_at'] ?? null) !== null
    && getPage('/articles/' . $testArticleSlug, $guestJar)['status'] === 200);

$articleEditPage = getPage('/admin/articles/' . $articleId . '/edit', $adminJar);
$articleEdit = postPage('/admin/articles/' . $articleId . '/edit', $adminJar, [
    '_token' => csrfToken($articleEditPage['body']) ?? '',
    'title' => '集成测试文章改名',
    'slug' => $testArticleSlug,
    'summary' => '后台集成测试文章摘要改名',
    'content' => "## 集成测试改名\n\n这是修改后的后台集成测试文章正文，长度足够。",
    'category_id' => 3,
    'status' => 'published',
    'is_featured' => 1,
]);
check('编辑文章', $articleEdit['status'] === 302
    && scalar('SELECT title FROM articles WHERE id = :id', ['id' => $articleId]) === '集成测试文章改名');

$articleList = getPage('/admin/articles', $adminJar);
$articleDelete = postPage('/admin/articles/' . $articleId . '/delete', $adminJar, ['_token' => csrfToken($articleList['body']) ?? '']);
check('删除文章', $articleDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM articles WHERE id = :id', ['id' => $articleId]) === 0);

// ------------------------------------------------------------
// 公告管理
// ------------------------------------------------------------
$announcementCreatePage = getPage('/admin/announcements/create', $adminJar);
$announcementCreate = postPage('/admin/announcements', $adminJar, [
    '_token' => csrfToken($announcementCreatePage['body']) ?? '',
    'title' => $testAnnouncementTitle,
    'summary' => '后台集成测试公告摘要',
    'content' => '这是后台集成测试公告正文，用于验证公告发布与删除。',
    'type' => 'event',
    'status' => 'published',
    'is_pinned' => 1,
    'expires_at' => '2026-12-31',
]);
$testAnnouncement = row('SELECT id, status FROM announcements WHERE title = :title', ['title' => $testAnnouncementTitle]);
$createdIds['announcement'] = $testAnnouncement === null ? null : (int) $testAnnouncement['id'];
check('新增并发布公告', $announcementCreate['status'] === 302 && ($testAnnouncement['status'] ?? '') === 'published');
check('公告前台可访问', getPage('/announcements/' . (int) $createdIds['announcement'], $guestJar)['status'] === 200);

$announcementList = getPage('/admin/announcements', $adminJar);
$announcementDelete = postPage('/admin/announcements/' . (int) $createdIds['announcement'] . '/delete', $adminJar, [
    '_token' => csrfToken($announcementList['body']) ?? '',
]);
check('删除公告', $announcementDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM announcements WHERE id = :id', ['id' => (int) $createdIds['announcement']]) === 0);

// ------------------------------------------------------------
// 团队成员与头像上传
// ------------------------------------------------------------
$memberCreatePage = getPage('/admin/members/create', $adminJar);
check('添加成员表单可选测试账号', str_contains($memberCreatePage['body'], $testUsername));

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
$avatarFile = $tmpDir . '/admin_smoke_avatar.png';
file_put_contents($avatarFile, $png === false ? '' : $png);

$memberCreate = postPage('/admin/members', $adminJar, [
    '_token' => csrfToken($memberCreatePage['body']) ?? '',
    'user_id' => $userId,
    'position_title' => '集成测试职位',
    'responsibilities' => '集成测试职责',
    'joined_at' => '2026-11-01',
    'left_at' => '',
    'status' => 'active',
    'sort_order' => 80,
    'bio' => '集成测试成员介绍',
    'skills' => 'PHP, MySQL',
    'public_email' => $testUsername . '@example.com',
    'github_url' => '',
], [
    'avatar' => new CURLFile($avatarFile, 'image/png', 'avatar.png'),
]);
$testMember = row('SELECT id FROM team_members WHERE user_id = :user_id', ['user_id' => $userId]);
$createdIds['membership'] = $testMember === null ? null : (int) $testMember['id'];
$profile = row('SELECT avatar_path, bio FROM member_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
$savedAvatar = (string) ($profile['avatar_path'] ?? '');
check('新增团队成员并写入公开资料', $memberCreate['status'] === 302 && $testMember !== null
    && ($profile['bio'] ?? '') === '集成测试成员介绍');
check('成员头像保存到受控上传目录', str_starts_with($savedAvatar, 'uploads/avatars/')
    && fileExists(BASE_PATH . '/public/' . $savedAvatar));

$membershipId = (int) $createdIds['membership'];
$memberEditPage = getPage('/admin/members/' . $membershipId . '/edit', $adminJar);
$memberEdit = postPage('/admin/members/' . $membershipId . '/edit', $adminJar, [
    '_token' => csrfToken($memberEditPage['body']) ?? '',
    'position_title' => '集成测试职位改',
    'responsibilities' => '集成测试职责改',
    'joined_at' => '2026-11-01',
    'left_at' => '',
    'status' => 'active',
    'sort_order' => 81,
    'bio' => '集成测试成员介绍改',
    'skills' => 'PHP, MySQL, Docker',
    'public_email' => $testUsername . '@example.com',
    'github_url' => '',
    'remove_avatar' => 1,
]);
$profile = row('SELECT avatar_path, bio FROM member_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
check('编辑成员并移除头像', $memberEdit['status'] === 302
    && ($profile['avatar_path'] ?? null) === null
    && ($profile['bio'] ?? '') === '集成测试成员介绍改');
check('移除头像时物理文件被清理', $savedAvatar !== '' && !fileExists(BASE_PATH . '/public/' . $savedAvatar), 'avatar=' . $savedAvatar . ' exists=' . (fileExists(BASE_PATH . '/public/' . $savedAvatar) ? '1' : '0') . ' edit_status=' . $memberEdit['status'] . ' location=' . $memberEdit['location']);

$memberList = getPage('/admin/members', $adminJar);
postPage('/admin/members/' . $membershipId . '/remove', $adminJar, ['_token' => csrfToken($memberList['body']) ?? '']);
$removed = row('SELECT status, left_at FROM team_members WHERE id = :id', ['id' => $membershipId]);
check('成员标记离队保留历史', ($removed['status'] ?? '') === 'removed' && ($removed['left_at'] ?? null) !== null);

$memberList = getPage('/admin/members', $adminJar);
$memberDelete = postPage('/admin/members/' . $membershipId . '/delete', $adminJar, ['_token' => csrfToken($memberList['body']) ?? '']);
check('删除无项目引用的成员关系', $memberDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM team_members WHERE id = :id', ['id' => $membershipId]) === 0);

// 用户删除（成员关系已不存在）
$userList = getPage('/admin/users', $adminJar);
$userDelete = postPage('/admin/users/' . $userId . '/delete', $adminJar, ['_token' => csrfToken($userList['body']) ?? '']);
check('删除无业务依赖账号', $userDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM users WHERE id = :id', ['id' => $userId]) === 0);

// ------------------------------------------------------------
// 文件管理
// ------------------------------------------------------------
$uploadPage = getPage('/admin/files/upload', $adminJar);
$uploadFile = $tmpDir . '/admin_smoke_upload.txt';
file_put_contents($uploadFile, "TechHub admin smoke upload\n");
$fileUpload = postPage('/admin/files/upload', $adminJar, [
    '_token' => csrfToken($uploadPage['body']) ?? '',
    'title' => $testFileTitle,
    'category' => 'team_assignment',
    'description' => '后台集成测试上传文件',
    'related_member_id' => 1,
    'is_public' => 1,
    'status' => 'active',
], [
    'file' => new CURLFile($uploadFile, 'text/plain', 'admin-smoke.txt'),
]);
$testFile = row('SELECT id, file_path, size_bytes FROM files WHERE title = :title', ['title' => $testFileTitle]);
$createdIds['file'] = $testFile === null ? null : (int) $testFile['id'];
$uploadedPath = $testFile === null ? '' : (string) $testFile['file_path'];
check('上传文件并写入数据库', $fileUpload['status'] === 302 && $testFile !== null);
check('文件保存到 data/team', str_starts_with($uploadedPath, 'data/team/') && fileExists(BASE_PATH . '/' . $uploadedPath));

$fileId = (int) $createdIds['file'];
$adminDownload = getPage('/admin/files/' . $fileId . '/download', $adminJar);
check('后台文件下载返回内容', $adminDownload['status'] === 200 && $adminDownload['body'] === "TechHub admin smoke upload\n");
check('前台下载列表显示文件', str_contains(getPage('/downloads?type=team', $guestJar)['body'], $testFileTitle));
check('前台受控下载可用', getPage('/downloads/' . $fileId . '/file', $guestJar)['status'] === 200);

$fileEditPage = getPage('/admin/files/' . $fileId . '/edit', $adminJar);
$fileEdit = postPage('/admin/files/' . $fileId . '/edit', $adminJar, [
    '_token' => csrfToken($fileEditPage['body']) ?? '',
    'title' => $testFileTitle . '改名',
    'description' => '后台集成测试上传文件改名',
    'related_member_id' => 2,
    'is_public' => 1,
    'status' => 'active',
]);
check('编辑文件信息', $fileEdit['status'] === 302
    && scalar('SELECT title FROM files WHERE id = :id', ['id' => $fileId]) === $testFileTitle . '改名');

$fileList = getPage('/admin/files', $adminJar);
postPage('/admin/files/' . $fileId . '/status', $adminJar, ['_token' => csrfToken($fileList['body']) ?? '']);
check('文件隐藏后前台下载 404', scalar('SELECT status FROM files WHERE id = :id', ['id' => $fileId]) === 'hidden'
    && getPage('/downloads/' . $fileId . '/file', $guestJar)['status'] === 404);
$fileList = getPage('/admin/files', $adminJar);
postPage('/admin/files/' . $fileId . '/status', $adminJar, ['_token' => csrfToken($fileList['body']) ?? '']);

$phpFile = $tmpDir . '/admin_smoke_bad.php';
file_put_contents($phpFile, '<?php echo "bad";');
$uploadPage = getPage('/admin/files/upload', $adminJar);
$badUpload = postPage('/admin/files/upload', $adminJar, [
    '_token' => csrfToken($uploadPage['body']) ?? '',
    'title' => $testFileTitle . '非法',
    'category' => 'team_assignment',
    'description' => '',
    'related_member_id' => 0,
    'is_public' => 1,
    'status' => 'active',
], [
    'file' => new CURLFile($phpFile, 'application/x-php', 'bad.php'),
]);
check('拒绝 PHP 文件上传', $badUpload['status'] === 302
    && $badUpload['location'] === '/admin/files/upload'
    && (int) scalar('SELECT COUNT(*) FROM files WHERE title = :title', ['title' => $testFileTitle . '非法']) === 0);

$fileList = getPage('/admin/files', $adminJar);
$fileDelete = postPage('/admin/files/' . $fileId . '/delete', $adminJar, ['_token' => csrfToken($fileList['body']) ?? '']);
check('删除文件记录和物理文件', $fileDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM files WHERE id = :id', ['id' => $fileId]) === 0
    && !fileExists(BASE_PATH . '/' . $uploadedPath), 'status=' . $fileDelete['status'] . ' loc=' . $fileDelete['location'] . ' db=' . (string) scalar('SELECT COUNT(*) FROM files WHERE id = :id', ['id' => $fileId]) . ' file=' . $uploadedPath . ' exists=' . (fileExists(BASE_PATH . '/' . $uploadedPath) ? '1' : '0'));

// ------------------------------------------------------------
// 留言审核与回复
// ------------------------------------------------------------
$messagePage = getPage('/messages', $guestJar);
$messageSubmit = postPage('/messages', $guestJar, [
    '_token' => csrfToken($messagePage['body']) ?? '',
    'visitor_name' => '集成测试访客',
    'visitor_email' => 'smoke-message@example.com',
    'content' => $messageMarker . '，用于验证后台审核、回复与删除。',
    'website' => '',
]);
$testMessage = row('SELECT id, status FROM messages WHERE content LIKE :content ORDER BY id DESC LIMIT 1', [
    'content' => '%' . $messageMarker . '%',
]);
check('前台留言写入待审核状态', $messageSubmit['status'] === 302 && ($testMessage['status'] ?? '') === 'pending');
$messageId = $testMessage === null ? 0 : (int) $testMessage['id'];
check('待审核留言不在前台显示', !str_contains(getPage('/messages', $guestJar)['body'], $messageMarker));

$messageAdminPage = getPage('/admin/messages/' . $messageId, $adminJar);
$approveResult = postPage('/admin/messages/' . $messageId . '/status', $adminJar, [
    '_token' => csrfToken($messageAdminPage['body']) ?? '',
    'status' => 'approved',
]);
check('后台审核通过留言', $approveResult['status'] === 302
    && scalar('SELECT status FROM messages WHERE id = :id', ['id' => $messageId]) === 'approved');
$publicMessage = getPage('/messages/' . $messageId, $guestJar);
check('审核后留言前台可见且不泄露邮箱', $publicMessage['status'] === 200
    && str_contains($publicMessage['body'], $messageMarker)
    && !str_contains($publicMessage['body'], 'smoke-message@example.com'));

$messageAdminPage = getPage('/admin/messages/' . $messageId, $adminJar);
$replyResult = postPage('/admin/messages/' . $messageId . '/reply', $adminJar, [
    '_token' => csrfToken($messageAdminPage['body']) ?? '',
    'content' => '这是后台集成测试回复。',
    'status' => 'visible',
]);
check('管理员回复写入数据库', $replyResult['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM message_replies WHERE message_id = :id AND status = \'visible\'', ['id' => $messageId]) >= 1);
check('回复展示在前台详情', str_contains(getPage('/messages/' . $messageId, $guestJar)['body'], '这是后台集成测试回复。'));

$messageAdminPage = getPage('/admin/messages/' . $messageId, $adminJar);
$messageDelete = postPage('/admin/messages/' . $messageId . '/delete', $adminJar, [
    '_token' => csrfToken($messageAdminPage['body']) ?? '',
]);
check('删除留言并级联回复', $messageDelete['status'] === 302
    && (int) scalar('SELECT COUNT(*) FROM messages WHERE id = :id', ['id' => $messageId]) === 0
    && (int) scalar('SELECT COUNT(*) FROM message_replies WHERE message_id = :id', ['id' => $messageId]) === 0);

// ------------------------------------------------------------
// 团队编辑与恢复
// ------------------------------------------------------------
$team = row('SELECT * FROM teams ORDER BY id LIMIT 1');
if ($team !== null) {
    $originalTeamSlogan = (string) ($team['slogan'] ?? '');
    $teamForm = getPage('/admin/team', $adminJar);
    $teamChanged = true;
    $teamUpdate = postPage('/admin/team', $adminJar, [
        '_token' => csrfToken($teamForm['body']) ?? '',
        'name' => (string) $team['name'],
        'slug' => (string) $team['slug'],
        'short_name' => (string) ($team['short_name'] ?? ''),
        'slogan' => '集成测试团队标语',
        'description' => (string) ($team['description'] ?? ''),
        'research_areas' => (string) ($team['research_areas'] ?? ''),
        'tech_stack' => (string) ($team['tech_stack'] ?? ''),
        'founded_at' => (string) ($team['founded_at'] ?? ''),
        'email' => (string) ($team['email'] ?? ''),
        'location' => (string) ($team['location'] ?? ''),
        'github_url' => (string) ($team['github_url'] ?? ''),
        'website_url' => (string) ($team['website_url'] ?? ''),
        'status' => (string) $team['status'],
    ]);
    check('编辑团队信息并同步前台', $teamUpdate['status'] === 302
        && str_contains(getPage('/team', $guestJar)['body'], '集成测试团队标语'));

    $teamForm = getPage('/admin/team', $adminJar);
    $restore = postPage('/admin/team', $adminJar, [
        '_token' => csrfToken($teamForm['body']) ?? '',
        'name' => (string) $team['name'],
        'slug' => (string) $team['slug'],
        'short_name' => (string) ($team['short_name'] ?? ''),
        'slogan' => $originalTeamSlogan,
        'description' => (string) ($team['description'] ?? ''),
        'research_areas' => (string) ($team['research_areas'] ?? ''),
        'tech_stack' => (string) ($team['tech_stack'] ?? ''),
        'founded_at' => (string) ($team['founded_at'] ?? ''),
        'email' => (string) ($team['email'] ?? ''),
        'location' => (string) ($team['location'] ?? ''),
        'github_url' => (string) ($team['github_url'] ?? ''),
        'website_url' => (string) ($team['website_url'] ?? ''),
        'status' => (string) $team['status'],
    ]);
    $teamChanged = false;
    check('恢复团队原始标语', $restore['status'] === 302
        && scalar('SELECT slogan FROM teams WHERE id = :id', ['id' => $team['id']]) === $originalTeamSlogan);
} else {
    check('团队记录存在', false);
}

// ------------------------------------------------------------
// 操作日志与最终检查
// ------------------------------------------------------------
check('操作日志页面可访问', getPage('/admin/activity', $adminJar)['status'] === 200);
check('操作日志包含成功结果', (int) scalar(
    "SELECT COUNT(*) FROM activity_logs WHERE module = 'projects' AND action = 'create' AND result = 'success'"
) > 0);
check('操作日志包含目标类型', (int) scalar(
    "SELECT COUNT(*) FROM activity_logs WHERE target_type = 'team_member' AND target_id IS NOT NULL"
) > 0);

echo "\nAdmin summary: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
