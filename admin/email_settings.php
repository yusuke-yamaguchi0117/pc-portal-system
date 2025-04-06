<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 暗号化関数
function encrypt($data)
{
    $method = "AES-256-CBC";
    $key = ENCRYPTION_KEY;
    $ivlen = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// 復号化関数
function decrypt($data)
{
    $method = "AES-256-CBC";
    $key = ENCRYPTION_KEY;
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

$message = '';
$error = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // 設定を保存
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM email_settings');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $data = [
            'from_name' => $_POST['from_name'],
            'from_email' => $_POST['from_email'],
            'reply_to' => $_POST['reply_to'],
            'smtp_host' => $_POST['smtp_host'],
            'smtp_port' => $_POST['smtp_port'],
            'smtp_user' => $_POST['smtp_user'],
            'smtp_pass' => encrypt($_POST['smtp_pass']),
            'encryption' => $_POST['encryption']
        ];

        if ($count > 0) {
            $sql = 'UPDATE email_settings SET
                from_name = :from_name,
                from_email = :from_email,
                reply_to = :reply_to,
                smtp_host = :smtp_host,
                smtp_port = :smtp_port,
                smtp_user = :smtp_user,
                smtp_pass = :smtp_pass,
                encryption = :encryption,
                updated_at = NOW()
                WHERE id = 1';
        } else {
            $sql = 'INSERT INTO email_settings
                (from_name, from_email, reply_to, smtp_host, smtp_port, smtp_user, smtp_pass, encryption, created_at, updated_at)
                VALUES
                (:from_name, :from_email, :reply_to, :smtp_host, :smtp_port, :smtp_user, :smtp_pass, :encryption, NOW(), NOW())';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $message = '設定を保存しました。';
    }

    // テストメール送信
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test'])) {
        $stmt = $pdo->query('SELECT * FROM email_settings WHERE id = 1');
        $settings = $stmt->fetch();

        if ($settings) {
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $settings['smtp_user'];
                $mail->Password = decrypt($settings['smtp_pass']);
                $mail->SMTPSecure = $settings['encryption'] !== 'none' ? $settings['encryption'] : '';
                $mail->Port = $settings['smtp_port'];

                $mail->setFrom($settings['from_email'], $settings['from_name']);
                $mail->addAddress($_POST['test_email']);
                $mail->addReplyTo($settings['reply_to']);

                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'テストメール';
                $mail->Body = 'これはテストメールです。メール設定が正常に機能していることを確認します。';

                $mail->send();
                $message = 'テストメールを送信しました。';
            } catch (Exception $e) {
                $error = 'テストメール送信に失敗しました: ' . $mail->ErrorInfo;
            }
        } else {
            $error = 'メール設定が保存されていません。';
        }
    }

    // 設定を取得
    $stmt = $pdo->query('SELECT * FROM email_settings WHERE id = 1');
    $settings = $stmt->fetch();

} catch (PDOException $e) {
    $error = 'データベースエラー: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール設定 - プログラ加古川南校</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php require_once '../includes/header.php'; ?>
    <?php require_once '../includes/sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">メール設定</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="mb-4">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label">差出人名</label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo isset($settings['from_name']) ? htmlspecialchars($settings['from_name']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="from_email" class="form-label">送信元メールアドレス</label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo isset($settings['from_email']) ? htmlspecialchars($settings['from_email']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="reply_to" class="form-label">返信先メールアドレス</label>
                                    <input type="email" class="form-control" id="reply_to" name="reply_to" value="<?php echo isset($settings['reply_to']) ? htmlspecialchars($settings['reply_to']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTPホスト</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo isset($settings['smtp_host']) ? htmlspecialchars($settings['smtp_host']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTPポート</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo isset($settings['smtp_port']) ? htmlspecialchars($settings['smtp_port']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_user" class="form-label">SMTPユーザー名</label>
                                    <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo isset($settings['smtp_user']) ? htmlspecialchars($settings['smtp_user']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_pass" class="form-label">SMTPパスワード</label>
                                    <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" value="<?php echo isset($settings['smtp_pass']) ? htmlspecialchars(decrypt($settings['smtp_pass'])) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="encryption" class="form-label">暗号化方式</label>
                                    <select class="form-control" id="encryption" name="encryption" required>
                                        <option value="ssl" <?php echo (isset($settings['encryption']) && $settings['encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo (isset($settings['encryption']) && $settings['encryption'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                                        <option value="none" <?php echo (isset($settings['encryption']) && $settings['encryption'] === 'none') ? 'selected' : ''; ?>>なし</option>
                                    </select>
                                </div>

                                <button type="submit" name="save" class="btn btn-primary">設定を保存</button>
                            </form>

                            <h3>テストメール送信</h3>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">テスト送信先メールアドレス</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email" required>
                                </div>
                                <button type="submit" name="test" class="btn btn-secondary">テストメール送信</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>