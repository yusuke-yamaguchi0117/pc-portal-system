<?php
require_once '../config/database.php';
require_once '../auth.php';

try {
    if (!isset($_GET['id'])) {
        die("IDが指定されていません。");
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 保護者情報の削除
    $stmt = $pdo->prepare("DELETE FROM parents WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // 一覧画面にリダイレクト
    header('Location: parents.php');
    exit;

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}