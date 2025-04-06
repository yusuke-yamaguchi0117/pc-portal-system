<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/ImageProcessor.php';

// 保護者IDの取得と検証
$parent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($parent_id <= 0 || $parent_id != $_SESSION['parent_id']) {
    $_SESSION['error_message'] = 'アクセス権限がありません。';
    header('Location: family.php');
    exit;
}

// 保護者情報の取得
$stmt = $pdo->prepare("
    SELECT *
    FROM parents
    WHERE id = ?
");
$stmt->execute([$parent_id]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    $_SESSION['error_message'] = '保護者情報が見つかりません。';
    header('Location: family.php');
    exit;
}

// POST処理（プロフィール更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $imageProcessor = new ImageProcessor();
        $profile_image = null;

        // 画像アップロード処理
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $profile_image = $imageProcessor->uploadImage($_FILES['profile_image']);
        }

        // データベース更新
        $stmt = $pdo->prepare("
            UPDATE parents
            SET
                name = ?,
                furigana = ?,
                nickname = ?,
                email = ?,
                tel = ?,
                address = ?" .
            ($profile_image ? ", profile_image = ?" : "") . "
            WHERE id = ?
        ");

        $params = [
            $_POST['name'],
            $_POST['furigana'],
            $_POST['nickname'] ?: null,
            $_POST['email'],
            $_POST['tel'] ?: null,
            $_POST['address'] ?: null
        ];
        if ($profile_image) {
            $params[] = $profile_image;
        }
        $params[] = $parent_id;

        $stmt->execute($params);
        $_SESSION['success_message'] = 'プロフィールを更新しました。';
        header("Location: parent_profile.php?id=" . $parent_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// 編集モード判定
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';

require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <?php if ($is_edit_mode): ?>
                        <!-- 編集フォーム -->
                        <form method="post" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="position-relative d-inline-block">
                                        <?php if (!empty($parent['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($parent['profile_image']); ?>" alt="プロフィール画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php endif; ?>
                                        <label for="profile_image" class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow-sm" style="cursor: pointer;">
                                            <i class="fas fa-camera text-primary"></i>
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" class="d-none" accept="image/jpeg,image/png,image/gif">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">氏名 <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($parent['name']); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">フリガナ <span class="text-danger">*</span></label>
                                        <input type="text" name="furigana" class="form-control" value="<?php echo htmlspecialchars($parent['furigana']); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">ニックネーム</label>
                                        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($parent['nickname'] ?? ''); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">メールアドレス <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($parent['email']); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">電話番号</label>
                                        <input type="tel" name="tel" class="form-control" value="<?php echo htmlspecialchars($parent['tel'] ?? ''); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">住所</label>
                                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($parent['address'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                                <a href="?id=<?php echo $parent_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>キャンセル
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- 表示モード -->
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <?php if (!empty($parent['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($parent['profile_image']); ?>" alt="プロフィール画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>
                                <h4 class="mb-1"><?php echo htmlspecialchars($parent['name']); ?></h4>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($parent['furigana']); ?></p>
                            </div>

                            <div class="row g-3">
                                <?php if (!empty($parent['nickname'])): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user text-primary me-3" style="width: 20px;"></i>
                                            <div>
                                                <div class="small text-muted">ニックネーム</div>
                                                <div><?php echo htmlspecialchars($parent['nickname']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-envelope text-primary me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">メールアドレス</div>
                                            <div><?php echo htmlspecialchars($parent['email']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($parent['tel'])): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone text-primary me-3" style="width: 20px;"></i>
                                            <div>
                                                <div class="small text-muted">電話番号</div>
                                                <div><?php echo htmlspecialchars($parent['tel']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($parent['address'])): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-home text-primary me-3" style="width: 20px;"></i>
                                            <div>
                                                <div class="small text-muted">住所</div>
                                                <div><?php echo htmlspecialchars($parent['address']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent text-center">
                            <a href="?id=<?php echo $parent_id; ?>&edit=true" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>編集
                            </a>
                            <a href="family.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>戻る
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>