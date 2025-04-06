<?php
session_start();

// すでにログインしている場合は適切なダッシュボードにリダイレクト
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: /portal/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['user_type'] === 'parent') {
        header('Location: /portal/parent/dashboard.php');
        exit;
    }
}

require_once 'config/database.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // まず管理者テーブルで確認
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // 管理者としてログイン
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: /portal/admin/dashboard.php');
            exit;
        }

        // 管理者で見つからない場合は保護者テーブルで確認
        $stmt = $pdo->prepare("SELECT * FROM parents WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // 保護者としてログイン
            $_SESSION['user_type'] = 'parent';
            $_SESSION['parent_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['family_id'] = $user['family_id'];

            // 最終ログイン日時を更新
            $stmt = $pdo->prepare("UPDATE parents SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id']]);

            header('Location: /portal/parent/dashboard.php');
            exit;
        }

        $error_message = 'メールアドレスまたはパスワードが正しくありません。';

    } catch (PDOException $e) {
        $error_message = 'ログイン処理中にエラーが発生しました。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - プログラ加古川南校</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 24px;
            color: #0d6efd;
            margin: 0;
        }

        .copyright {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>プログラ加古川南校</h1>
                <p class="text-muted">保護者・スタッフポータル</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">パスワード</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">ログイン</button>
                </div>
            </form>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> プログラ加古川南校
        </div>
    </div>
</body>

</html>