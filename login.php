<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
} catch (Exception $e) {
    die('データベース接続設定ファイルの読み込みに失敗しました: ' . $e->getMessage());
}

// ログイン済みの場合はダッシュボードにリダイレクト
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: /portal/admin/dashboard.php');
    } else {
        header('Location: /portal/parent/dashboard.php');
    }
    exit;
}

// POSTデータのデバッグ出力（開発時のみ）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data received: ' . print_r($_POST, true));

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // まず管理者テーブルをチェック
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $userType = 'admin';

        // デバッグ出力
        error_log("Admin check for email {$email}: " . ($user ? 'found' : 'not found'));
        if ($user) {
            error_log("Stored password hash: " . $user['password']);
            error_log("Input password: " . $password);
        }

        // 管理者で見つからない場合、保護者テーブルをチェック
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM parents WHERE email = ?");
            $stmt->execute([$email]);
            $parent = $stmt->fetch();
            $userType = 'parent';

            // デバッグ出力
            error_log("Parent check for email {$email}: " . ($parent ? 'found' : 'not found'));
            if ($parent) {
                error_log("Stored password hash: " . $parent['password']);
                error_log("Input password: " . $password);

                // パスワード検証（保護者）
                if (password_verify($password, $parent['password'])) {
                    $_SESSION['user_id'] = $parent['id'];
                    $_SESSION['user_type'] = 'parent';
                    $_SESSION['user_name'] = $parent['name'];

                    // 最終ログイン日時を更新
                    $now = date('Y-m-d H:i:s');
                    $stmt = $pdo->prepare("UPDATE parents SET last_login = ? WHERE id = ?");
                    $stmt->execute([$now, $parent['id']]);

                    error_log("Login successful for {$email} as parent");
                    header('Location: /portal/parent/dashboard.php');
                    exit;
                }
            }
        } else {
            // パスワード検証（管理者）
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_name'] = $user['name'];

                error_log("Login successful for {$email} as admin");
                header('Location: /portal/admin/dashboard.php');
                exit;
            }
        }

        $error = 'メールアドレスまたはパスワードが正しくありません。';
        error_log("Login failed for {$email}: invalid credentials");

    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $error = 'システムエラーが発生しました。しばらく時間をおいて再度お試しください。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - プロクラ保護者ポータル</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-box {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }

        .system-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .system-title h1 {
            font-size: 1.75rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }

        .system-title p {
            font-size: 1.25rem;
            color: #6c757d;
            margin: 0;
        }

        .copyright {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
            font-size: 0.875rem;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="system-title">
                <h1>プロクラ保護者ポータル</h1>
                <p>ログイン</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email" autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">パスワード</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">ログイン</button>
                </div>
            </form>
        </div>
    </div>
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> プロクラ加古川南校
    </div>
</body>

</html>