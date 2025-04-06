<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 現在のURLパスを取得
$current_path = $_SERVER['REQUEST_URI'];

// 管理者エリアへのアクセスチェック
if (strpos($current_path, '/portal/admin/') === 0) {
    // 管理者としてログインしていない場合は管理者ログイン画面へ
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: /portal/admin/login.php');
        exit;
    }
}

// 保護者エリアへのアクセスチェック
if (strpos($current_path, '/portal/parent/') === 0) {
    // 保護者としてログインしていない場合は共通ログイン画面へ
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parent') {
        header('Location: /portal/index.php');
        exit;
    }
}

// CSRF対策のトークン生成（必要な場合に使用）
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}