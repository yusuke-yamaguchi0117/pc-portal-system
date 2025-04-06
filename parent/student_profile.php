<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/ImageProcessor.php';

// 生徒IDの取得と検証
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($student_id <= 0) {
    $_SESSION['error_message'] = '無効な生徒IDです。';
    header('Location: family.php');
    exit;
}

// ログイン中の保護者と生徒の関連を確認
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM parent_student
    WHERE parent_id = ? AND student_id = ?
");
$stmt->execute([$_SESSION['parent_id'], $student_id]);
if ($stmt->fetchColumn() == 0) {
    $_SESSION['error_message'] = 'アクセス権限がありません。';
    header('Location: family.php');
    exit;
}

// POST処理（プロフィール更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $imageProcessor = new ImageProcessor();
        $photo = null;

        // 画像アップロード処理
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $imageProcessor->uploadImage($_FILES['photo']);
        }

        // データベース更新
        $stmt = $pdo->prepare("
            UPDATE students
            SET
                nickname = ?,
                gender = ?,
                blood_type = ?,
                birthday = ?,
                note = ?,
                school_name = ?,
                grade = ?" .
            ($photo ? ", photo = ?" : "") . "
            WHERE id = ?
        ");

        $params = [
            $_POST['nickname'] ?: null,
            $_POST['gender'] ?: null,
            $_POST['blood_type'] ?: null,
            $_POST['birthday'] ?: null,
            $_POST['note'] ?: null,
            $_POST['school_name'] ?: null,
            $_POST['grade'] ?: null
        ];
        if ($photo) {
            $params[] = $photo;
        }
        $params[] = $student_id;

        $stmt->execute($params);
        $_SESSION['success_message'] = 'プロフィールを更新しました。';
        header("Location: student_profile.php?id=" . $student_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// 生徒情報の取得
$stmt = $pdo->prepare("
    SELECT *
    FROM students
    WHERE id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error_message'] = '生徒情報が見つかりません。';
    header('Location: family.php');
    exit;
}

// 年齢計算（birthdayが存在する場合のみ）
$age = null;
if (!empty($student['birthday'])) {
    $birthday = new DateTime($student['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthday)->y;
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
                                        <?php if (!empty($student['photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="生徒写真" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php endif; ?>
                                        <label for="photo" class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow-sm" style="cursor: pointer;">
                                            <i class="fas fa-camera text-primary"></i>
                                        </label>
                                        <input type="file" id="photo" name="photo" class="d-none" accept="image/jpeg,image/png,image/gif">
                                    </div>

                                    <h4 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h4>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($student['furigana']); ?></p>

                                    <div class="mb-3">
                                        <label class="form-label">ニックネーム</label>
                                        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($student['nickname'] ?? ''); ?>" placeholder="ニックネームを入力">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">誕生日</label>
                                        <input type="date" name="birthday" class="form-control" value="<?php echo htmlspecialchars($student['birthday'] ?? ''); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">性別</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" name="gender" value="男" class="form-check-input" <?php echo ($student['gender'] ?? '') === '男' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">男</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" name="gender" value="女" class="form-check-input" <?php echo ($student['gender'] ?? '') === '女' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">女</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" name="gender" value="未回答" class="form-check-input" <?php echo ($student['gender'] ?? '') === '未回答' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">未回答</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">血液型</label>
                                        <select name="blood_type" class="form-select">
                                            <option value="">選択してください</option>
                                            <?php
                                            $blood_types = ['A', 'B', 'O', 'AB', '不明'];
                                            foreach ($blood_types as $type):
                                                ?>
                                                <option value="<?php echo $type; ?>" <?php echo ($student['blood_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                                    <?php echo $type; ?>型
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-school text-primary me-3" style="width: 20px;"></i>
                                            <div class="flex-grow-1">
                                                <div class="small text-muted">学校名</div>
                                                <input type="text" class="form-control" name="school" value="<?php echo htmlspecialchars($student['school'] ?? ''); ?>" placeholder="学校名を入力">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-graduation-cap text-primary me-3" style="width: 20px;"></i>
                                            <div class="flex-grow-1">
                                                <div class="small text-muted">学年</div>
                                                <select class="form-select" name="grade">
                                                    <option value="">選択してください</option>
                                                    <option value="年少" <?php echo ($student['grade'] ?? '') === '年少' ? 'selected' : ''; ?>>年少</option>
                                                    <option value="年中" <?php echo ($student['grade'] ?? '') === '年中' ? 'selected' : ''; ?>>年中</option>
                                                    <option value="年長" <?php echo ($student['grade'] ?? '') === '年長' ? 'selected' : ''; ?>>年長</option>
                                                    <option value="小学1年" <?php echo ($student['grade'] ?? '') === '小学1年' ? 'selected' : ''; ?>>小学1年</option>
                                                    <option value="小学2年" <?php echo ($student['grade'] ?? '') === '小学2年' ? 'selected' : ''; ?>>小学2年</option>
                                                    <option value="小学3年" <?php echo ($student['grade'] ?? '') === '小学3年' ? 'selected' : ''; ?>>小学3年</option>
                                                    <option value="小学4年" <?php echo ($student['grade'] ?? '') === '小学4年' ? 'selected' : ''; ?>>小学4年</option>
                                                    <option value="小学5年" <?php echo ($student['grade'] ?? '') === '小学5年' ? 'selected' : ''; ?>>小学5年</option>
                                                    <option value="小学6年" <?php echo ($student['grade'] ?? '') === '小学6年' ? 'selected' : ''; ?>>小学6年</option>
                                                    <option value="中学1年" <?php echo ($student['grade'] ?? '') === '中学1年' ? 'selected' : ''; ?>>中学1年</option>
                                                    <option value="中学2年" <?php echo ($student['grade'] ?? '') === '中学2年' ? 'selected' : ''; ?>>中学2年</option>
                                                    <option value="中学3年" <?php echo ($student['grade'] ?? '') === '中学3年' ? 'selected' : ''; ?>>中学3年</option>
                                                    <option value="高校1年" <?php echo ($student['grade'] ?? '') === '高校1年' ? 'selected' : ''; ?>>高校1年</option>
                                                    <option value="高校2年" <?php echo ($student['grade'] ?? '') === '高校2年' ? 'selected' : ''; ?>>高校2年</option>
                                                    <option value="高校3年" <?php echo ($student['grade'] ?? '') === '高校3年' ? 'selected' : ''; ?>>高校3年</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">備考</label>
                                        <textarea name="note" class="form-control" rows="4" placeholder="備考を入力"><?php echo htmlspecialchars($student['note'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                                <a href="?id=<?php echo $student_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>キャンセル
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- 表示モード -->
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <?php if (!empty($student['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="生徒写真" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>

                                <h4 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h4>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($student['furigana']); ?></p>
                                <?php if (!empty($student['nickname'])): ?>
                                    <p class="text-muted small">（<?php echo htmlspecialchars($student['nickname']); ?>）</p>
                                <?php else: ?>
                                    <p class="text-muted small">（ニックネーム未設定）</p>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-birthday-cake text-primary me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">お誕生日</div>
                                            <div>
                                                <?php if (!empty($student['birthday'])): ?>
                                                    <?php echo date('Y年m月d日', strtotime($student['birthday'])); ?>
                                                    <?php if ($age !== null): ?>
                                                        <span class="text-muted ms-2">(<?php echo $age; ?>歳)</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tint text-danger me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">血液型</div>
                                            <div>
                                                <?php if (!empty($student['blood_type'])): ?>
                                                    <?php echo htmlspecialchars($student['blood_type']); ?>型
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-info me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">性別</div>
                                            <div>
                                                <?php if (!empty($student['gender'])): ?>
                                                    <?php echo htmlspecialchars($student['gender']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-school text-primary me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">学校名</div>
                                            <div>
                                                <?php if (!empty($student['school'])): ?>
                                                    <?php echo htmlspecialchars($student['school']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-graduation-cap text-primary me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">学年</div>
                                            <div>
                                                <?php if (!empty($student['grade'])): ?>
                                                    <?php echo htmlspecialchars($student['grade']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex">
                                        <i class="fas fa-sticky-note text-warning me-3" style="width: 20px;"></i>
                                        <div>
                                            <div class="small text-muted">備考</div>
                                            <div class="mt-1">
                                                <?php if (!empty($student['note'])): ?>
                                                    <?php echo nl2br(htmlspecialchars($student['note'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent text-center">
                            <a href="?id=<?php echo $student_id; ?>&edit=true" class="btn btn-primary">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 画像プレビュー機能
        const photoInput = document.getElementById('photo');
        if (photoInput) {
            photoInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('画像サイズは2MB以下にしてください。');
                        e.target.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = photoInput.closest('.position-relative').querySelector('img');
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>