<?php
session_start();
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_parent.php';

// セッションチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit;
}

// ログイン中の保護者情報を取得
$parent_id = $_SESSION['parent_id'];

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

// 最近の授業記録を取得
$stmt = $pdo->prepare("
    SELECT a.*, s.name as student_name, s.grade
    FROM attendances a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN parent_student ps ON s.id = ps.student_id
    WHERE ps.parent_id = ?
    ORDER BY a.date DESC, a.created_at DESC
    LIMIT 5
");
$stmt->execute([$parent_id]);
$recent_attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h1 class="mb-4">ようこそ、<?php echo htmlspecialchars($_SESSION['user_name']); ?>様</h1>

        <div class="row">
            <!-- 登録生徒一覧 -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">登録生徒一覧</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <p>登録されている生徒はいません。</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($students as $student): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($student['grade']); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo htmlspecialchars($student['course']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 最近の授業記録 -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">最近の授業記録</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attendances)): ?>
                            <p>最近の授業記録はありません。</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_attendances as $attendance): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($attendance['student_name']); ?>
                                                （<?php echo htmlspecialchars($attendance['grade']); ?>）
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('Y/m/d', strtotime($attendance['date'])); ?>
                                            </small>
                                        </div>
                                        <?php if (!empty($attendance['comment'])): ?>
                                            <p class="mb-1">
                                                <?php echo nl2br(htmlspecialchars($attendance['comment'])); ?>
                                            </p>
                                        <?php endif; ?>
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