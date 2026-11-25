<?php
/**
 * TechHub 交付文档数据导出
 * 从实际 MySQL 数据库读取表结构、外键、索引与行数，输出 JSON。
 */
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/bootstrap/app.php';

$db = Core\Database::connection();
$tables = $db->query("SELECT table_name AS table_name, table_comment AS table_comment, table_rows AS table_rows FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll();
$out = ['database' => (string) $db->query('SELECT DATABASE()')->fetchColumn(), 'server' => (string) $db->query('SELECT VERSION()')->fetchColumn(), 'generated_at' => date('c'), 'tables' => []];
foreach ($tables as $table) {
    $name = (string) $table['table_name'];
    $cols = $db->prepare("SELECT column_name AS column_name, column_type AS column_type, is_nullable AS is_nullable, column_default AS column_default, column_key AS column_key, extra AS extra, column_comment AS column_comment FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t ORDER BY ordinal_position");
    $cols->execute(['t' => $name]);
    $fks = $db->prepare("SELECT constraint_name AS constraint_name, column_name AS column_name, referenced_table_name AS referenced_table_name, referenced_column_name AS referenced_column_name FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = :t AND referenced_table_name IS NOT NULL ORDER BY constraint_name");
    $fks->execute(['t' => $name]);
    $idx = $db->prepare("SELECT index_name AS index_name, non_unique AS non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :t GROUP BY index_name, non_unique ORDER BY index_name");
    $idx->execute(['t' => $name]);
    $count = (int) $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $name) . '`')->fetchColumn();
    $out['tables'][] = [
        'name' => $name,
        'comment' => (string) $table['table_comment'],
        'rows' => $count,
        'columns' => $cols->fetchAll(),
        'foreign_keys' => $fks->fetchAll(),
        'indexes' => $idx->fetchAll(),
    ];
}
file_put_contents(__DIR__ . '/../data/schema.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo 'tables=' . count($out['tables']) . PHP_EOL;
