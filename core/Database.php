<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Database.php
 * 模块：基础设施
 * 职责：创建并复用 PDO MySQL 连接，统一错误模式、字符集和预处理设置。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = config('database', []);
        if (!is_array($config)) {
            throw new \RuntimeException('数据库配置缺失');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($config['host'] ?? '127.0.0.1'),
            (int) ($config['port'] ?? 3306),
            (string) ($config['database'] ?? 'techhub'),
            (string) ($config['charset'] ?? 'utf8mb4')
        );

        try {
            self::$connection = new PDO(
                $dsn,
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
            self::$connection->exec("SET time_zone = " . self::$connection->quote(date('P')));
        } catch (\PDOException $e) {
            throw new \RuntimeException('数据库连接失败，请检查本地配置和数据库服务。', 0, $e);
        }

        return self::$connection;
    }

    /**
     * 仅用于测试或重建连接，不参与常规业务。
     */
    public static function reset(): void
    {
        self::$connection = null;
    }
}
