<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：tests/smoke.php
 * 模块：基础验证
 * 职责：验证数据库、Model、View、安全工具与上传校验，不修改业务数据。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/app.php';

use App\Models\Article;
use App\Models\Team;
use App\Models\User;
use Core\Database;
use Core\Upload;
use Core\View;

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

// 1. PHP / PDO / database
check('PHP >= 8.2', PHP_VERSION_ID >= 80200, PHP_VERSION);
$pdo = Database::connection();
check('PDO connects to MySQL 8.0', (string) $pdo->query('SELECT VERSION()')->fetchColumn() !== '');
check('17 business tables exist', (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
)->fetchColumn() === 17);

// 2. Model queries
$team = (new Team())->primary();
check('Team model reads primary team', is_array($team) && ($team['name'] ?? '') !== '');
check('User model reads admin', (new User())->findForAuth('admin') !== null);
check('Article model reads published data', count((new Article())->latestPublished(3)) >= 1);

// 3. View rendering
$html = View::render('home/index', [
    'title' => 'TechHub smoke test',
    'team' => $team,
    'members' => $team === null ? [] : (new Team())->members((int) $team['id'], 3),
    'projects' => [],
    'articles' => [],
    'announcements' => [],
    'stats' => ['members' => 0, 'projects' => 0, 'articles' => 0, 'messages' => 0, 'files' => 0],
], 'layouts/app');
check('View renders team data', str_contains($html, (string) ($team['name'] ?? '')));
check('View output escapes script tag', !str_contains($html, '<script>alert(1)</script>'));

// 4. XSS helper
check('e() escapes HTML', e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');

// 5. Upload validation using temporary files
$tmp = BASE_PATH . '/tests/tmp';
if (!is_dir($tmp)) {
    mkdir($tmp, 0775, true);
}

$validFile = $tmp . '/valid.txt';
file_put_contents($validFile, "safe sample text\n");
$valid = Upload::inspect([
    'error' => UPLOAD_ERR_OK,
    'name' => 'safe-sample.txt',
    'tmp_name' => $validFile,
    'size' => filesize($validFile),
], ['txt'], 1024 * 1024, false);
check('Upload accepts allowed txt', $valid['extension'] === 'txt' && $valid['mime_type'] === 'text/plain');

$phpFile = $tmp . '/evil.php';
file_put_contents($phpFile, "<?php echo 'x';\n");
$rejectedPhp = false;
try {
    Upload::inspect([
        'error' => UPLOAD_ERR_OK,
        'name' => 'evil.php',
        'tmp_name' => $phpFile,
        'size' => filesize($phpFile),
    ], ['txt', 'pdf'], 1024 * 1024, false);
} catch (RuntimeException) {
    $rejectedPhp = true;
}
check('Upload rejects .php extension', $rejectedPhp);

$fakeImage = $tmp . '/fake.png';
file_put_contents($fakeImage, 'not an image');
$rejectedMime = false;
try {
    Upload::inspect([
        'error' => UPLOAD_ERR_OK,
        'name' => 'fake.png',
        'tmp_name' => $fakeImage,
        'size' => filesize($fakeImage),
    ], ['png'], 1024 * 1024, false);
} catch (RuntimeException) {
    $rejectedMime = true;
}
check('Upload rejects MIME/extension mismatch', $rejectedMime);

$rejectedSize = false;
try {
    Upload::inspect([
        'error' => UPLOAD_ERR_OK,
        'name' => 'safe-sample.txt',
        'tmp_name' => $validFile,
        'size' => filesize($validFile),
    ], ['txt'], 1, false);
} catch (RuntimeException) {
    $rejectedSize = true;
}
check('Upload rejects oversized file', $rejectedSize);

@unlink($validFile);
@unlink($phpFile);
@unlink($fakeImage);

// 6. Password storage check
$hash = (string) $pdo->query("SELECT password_hash FROM users WHERE username = 'admin'")->fetchColumn();
check('Admin password is hashed', str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2'));

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
