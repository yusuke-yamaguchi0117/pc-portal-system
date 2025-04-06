<?php
require_once '../config/database.php';
require_once '../auth.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );

    // lesson_typeカラムの定義を変更
    $sql = "ALTER TABLE lesson_calendar MODIFY COLUMN lesson_type VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci";
    $pdo->exec($sql);

    echo "lesson_calendarテーブルのlesson_typeカラムを更新しました。";

} catch (PDOException $e) {
    echo "エラーが発生しました: " . $e->getMessage();
    error_log('Database Error: ' . $e->getMessage());
}