-- ============================================================
-- TechHub 后台管理阶段数据库迁移
-- 文件：data/migrations/2026-11-25_admin.sql
-- 适用：MySQL 8.0.19，可重复执行
-- 内容：为 activity_logs 增加操作结果字段及查询索引
-- ============================================================

USE `techhub`;

SET @column_exists := (
  SELECT COUNT(*)
  FROM `information_schema`.`columns`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'activity_logs'
    AND `column_name` = 'result'
);

SET @sql := IF(
  @column_exists = 0,
  'ALTER TABLE `activity_logs`
     ADD COLUMN `result` ENUM(''success'',''failure'') NOT NULL DEFAULT ''success'' AFTER `description`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
  SELECT COUNT(*)
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'activity_logs'
    AND `index_name` = 'idx_activity_logs_result'
);

SET @sql := IF(
  @index_exists = 0,
  'ALTER TABLE `activity_logs` ADD KEY `idx_activity_logs_result` (`result`, `created_at`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
