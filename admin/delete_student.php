<?php
require_once '../config/database.php';
require_once '../auth.php';

if (!isset($_GET['id'])) {
    header('Location: students.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    header('Location: students.php');
    exit;
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}