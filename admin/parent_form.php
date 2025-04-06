<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

// データベース接続
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $parent = null;
    $mode = 'create';

    // 編集モードの場合、保護者情報を取得
    if (isset($_GET['id'])) {
        $mode = 'edit';
        $stmt = $pdo->prepare("SELECT * FROM parents WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {
            die("指定された保護者が見つかりません。");
        }
    }

    // フォーム送信時の処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $furigana = $_POST['furigana'];
        $email = $_POST['email'];
        $tel = $_POST['tel'];
        $address = $_POST['address'];
        $password = $_POST['password'];

        if ($mode === 'create') {
            // 新規登録
            if (empty($password)) {
                die("パスワードは必須です。");
            }

            $stmt = $pdo->prepare("INSERT INTO parents (
                name, furigana, email, tel, address, password, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name,
                $furigana,
                $email,
                $tel,
                $address,
                password_hash($password, PASSWORD_BCRYPT),
                $_POST['note']
            ]);
        } else {
            // 更新
            if (empty($password)) {
                // パスワード変更なし
                $stmt = $pdo->prepare("UPDATE parents SET
                    name = ?,
                    furigana = ?,
                    email = ?,
                    tel = ?,
                    address = ?,
                    note = ?
                    WHERE id = ?");
                $stmt->execute([
                    $name,
                    $furigana,
                    $email,
                    $tel,
                    $address,
                    $_POST['note'],
                    $_GET['id']
                ]);
            } else {
                // パスワード変更あり
                $stmt = $pdo->prepare("UPDATE parents SET
                    name = ?,
                    furigana = ?,
                    email = ?,
                    tel = ?,
                    address = ?,
                    password = ?,
                    note = ?
                    WHERE id = ?");
                $stmt->execute([
                    $name,
                    $furigana,
                    $email,
                    $tel,
                    $address,
                    password_hash($password, PASSWORD_BCRYPT),
                    $_POST['note'],
                    $_GET['id']
                ]);
            }
        }

        // 一覧画面にリダイレクト
        header('Location: parents.php');
        exit;
    }
} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $mode === 'create' ? '保護者新規登録' : '保護者情報編集'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">氏名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($parent) ? htmlspecialchars($parent['name']) : ''; ?>">
                                <div class="invalid-feedback">氏名を入力してください</div>
                            </div>

                            <div class="mb-3">
                                <label for="furigana" class="form-label">ふりがな <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="furigana" name="furigana" required value="<?php echo isset($parent) ? htmlspecialchars($parent['furigana']) : ''; ?>">
                                <div class="invalid-feedback">ふりがなを入力してください</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($parent) ? htmlspecialchars($parent['email']) : ''; ?>">
                                <div class="invalid-feedback">有効なメールアドレスを入力してください</div>
                            </div>

                            <div class="mb-3">
                                <label for="tel" class="form-label">電話番号 <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="tel" name="tel" required value="<?php echo isset($parent) ? htmlspecialchars($parent['tel']) : ''; ?>">
                                <div class="invalid-feedback">電話番号を入力してください</div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">住所</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($parent) ? htmlspecialchars($parent['address']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">備考</label>
                                <textarea class="form-control" id="note" name="note" rows="3" placeholder="管理用メモ（兄弟情報など）"><?php echo isset($parent) ? htmlspecialchars($parent['note']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    パスワード
                                    <?php if ($mode === 'create'): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo $mode === 'create' ? 'required' : ''; ?>>
                                <?php if ($mode === 'edit'): ?>
                                    <div class="form-text">変更する場合のみ入力してください</div>
                                <?php endif; ?>
                                <div class="invalid-feedback">パスワードを入力してください</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $mode === 'create' ? '登録' : '更新'; ?>
                                </button>
                                <a href="parents.php" class="btn btn-secondary">キャンセル</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Bootstrap フォームバリデーションの有効化
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>

<?php require_once '../includes/footer.php'; ?>