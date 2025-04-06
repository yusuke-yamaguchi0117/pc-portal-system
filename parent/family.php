<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_parent.php';

// ログイン中の保護者情報を取得
$parent_id = $_SESSION['parent_id'];

// 同じ生徒に紐づく保護者一覧の取得
$stmt = $pdo->prepare("
    SELECT DISTINCT p.*
    FROM parents p
    JOIN parent_student ps ON p.id = ps.parent_id
    WHERE ps.student_id IN (
        SELECT student_id
        FROM parent_student
        WHERE parent_id = ?
    )
    ORDER BY
        CASE WHEN p.id = ? THEN 0 ELSE 1 END,
        p.name
");
$stmt->execute([$parent_id, $parent_id]);
$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 紐づけられた生徒一覧の取得
$stmt = $pdo->prepare("
    SELECT s.*
    FROM students s
    INNER JOIN parent_student ps ON s.id = ps.student_id
    WHERE ps.parent_id = ?
    ORDER BY s.furigana
");
$stmt->execute([$parent_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="container-fluid">
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">家族情報</h1>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">保護者一覧</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($parents)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p class="mb-0">登録された保護者はいません</p>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-sm-2 g-3">
                                <?php foreach ($parents as $parent): ?>
                                    <div class="col">
                                        <div class="card h-100 border shadow-sm">
                                            <div class="text-center pt-3">
                                                <?php if (!empty($parent['profile_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($parent['profile_image']); ?>" alt="プロフィール画像" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body text-center">
                                                <h6 class="card-title mb-1">
                                                    <?php echo htmlspecialchars($parent['name']); ?>
                                                    <?php if ($parent['id'] == $parent_id): ?>
                                                        <span class="badge bg-success ms-1">あなた</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (!empty($parent['nickname'])): ?>
                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($parent['nickname']); ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted small mb-2">-</p>
                                                <?php endif; ?>
                                                <?php if ($parent['id'] == $parent_id): ?>
                                                    <div class="mt-3">
                                                        <a href="parent_profile.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-info w-100">
                                                            詳細
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">子ども一覧</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-child fa-3x mb-3"></i>
                                <p class="mb-0">登録された子どもはいません</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($students as $student): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="text-center pt-3">
                                                <?php if (!empty($student['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="生徒写真" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="/portal/assets/images/default_student_avatar.png" alt="デフォルト画像" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($student['name']); ?></h6>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($student['furigana']); ?></p>
                                                <div class="mb-2">
                                                    <?php if (!empty($student['grade'])): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($student['grade']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($student['school'])): ?>
                                                        <div class="small text-muted mt-1"><?php echo htmlspecialchars($student['school']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info w-100">
                                                    詳細
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>