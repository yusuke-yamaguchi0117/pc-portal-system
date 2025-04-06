<?php
// タイムゾーンを設定
date_default_timezone_set('Asia/Tokyo');

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'pc_kakogawa_sys');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// 暗号化キー（32バイトのランダムな文字列）
define('ENCRYPTION_KEY', '1234567890abcdef1234567890abcdef');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die('データベース接続に失敗しました。管理者にお問い合わせください。');
}

// エラー表示設定（開発時のみ）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);