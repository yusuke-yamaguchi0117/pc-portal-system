<?php
require_once '../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 既存のテーブルを削除
    $pdo->exec("DROP TABLE IF EXISTS `lesson_posts`");
    echo "既存のlesson_postsテーブルを削除しました。\n";

    // lesson_postsテーブルの作成
    $sql = file_get_contents(__DIR__ . '/../sql/create_lesson_posts.sql');
    $pdo->exec($sql);

    echo "lesson_postsテーブルが正常に作成されました。\n";

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage() . "\n");
}