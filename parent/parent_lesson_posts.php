<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_parent.php';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=pc_kakogawa_sys;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // セッションから保護者IDを取得
    $parent_id = $_SESSION['parent_id'];

    // 保護者の有効性チェック
    $stmt = $pdo->prepare("SELECT id FROM parents WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();

    if (!$parent) {
        header('Location: /portal/parent/login.php');
        exit;
    }

    // 保護者に紐づく生徒一覧を取得
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.furigana
        FROM students s
        INNER JOIN parent_student ps ON s.id = ps.student_id
        WHERE ps.parent_id = ?
        ORDER BY s.furigana
    ");
    $stmt->execute([$parent_id]);
    $students = $stmt->fetchAll();

    // 選択された生徒IDを取得（デフォルトは最初の生徒）
    $selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : ($students ? $students[0]['id'] : null);

    // 選択された生徒の投稿一覧を取得
    $posts = [];
    if ($selected_student_id) {
        $stmt = $pdo->prepare("
            SELECT
                lp.*,
                ls.date as lesson_date,
                ls.start_time as lesson_time
            FROM lesson_posts lp
            LEFT JOIN lesson_slots ls ON lp.lesson_slot_id = ls.id
            WHERE lp.student_id = ?
            ORDER BY lp.created_at DESC
        ");
        $stmt->execute([$selected_student_id]);
        $posts = $stmt->fetchAll();
    }

    // 各生徒の投稿を取得
    $student_posts = [];
    foreach ($students as $student) {
        $stmt = $pdo->prepare("
            SELECT
                lp.*,
                ls.date as lesson_date,
                ls.start_time as lesson_time
            FROM lesson_posts lp
            LEFT JOIN lesson_slots ls ON lp.lesson_slot_id = ls.id
            WHERE lp.student_id = ?
            ORDER BY lp.created_at DESC
        ");
        $stmt->execute([$student['id']]);
        $student_posts[$student['id']] = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}
?>

<!-- メインコンテンツ -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">授業の様子</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    お子様の情報が見つかりませんでした。
                </div>
            <?php else: ?>
                <!-- 生徒選択タブ -->
                <div class="card mb-4">
                    <div class="card-body">
                        <ul class="nav nav-tabs lesson-post-tabs" id="studentTabs" role="tablist">
                            <?php foreach ($students as $index => $student): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $student['id'] == $selected_student_id ? 'active' : ''; ?>" id="student-<?php echo $student['id']; ?>-tab" data-bs-toggle="tab" data-bs-target="#student-<?php echo $student['id']; ?>" type="button" role="tab" aria-controls="student-<?php echo $student['id']; ?>" aria-selected="<?php echo $student['id'] == $selected_student_id ? 'true' : 'false'; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- 投稿一覧 -->
                <div class="tab-content" id="studentTabsContent">
                    <?php foreach ($students as $student): ?>
                        <div class="tab-pane fade <?php echo $student['id'] == $selected_student_id ? 'show active' : ''; ?>" id="student-<?php echo $student['id']; ?>" role="tabpanel" aria-labelledby="student-<?php echo $student['id']; ?>-tab">

                            <?php if (empty($student_posts[$student['id']])): ?>
                                <div class="alert alert-info">
                                    授業の様子はまだありません。
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($student_posts[$student['id']] as $post): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card lesson-post-card">
                                                <?php if ($post['photo_path']): ?>
                                                    <img src="<?php echo htmlspecialchars($post['photo_path']); ?>" class="card-img-top lesson-post-image" onclick="showImageModal('<?php echo htmlspecialchars($post['photo_path']); ?>')">
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($post['theme']); ?></h5>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            授業日: <?php echo $post['lesson_date'] ? date('Y-m-d', strtotime($post['lesson_date'])) : '-'; ?><br>
                                                            時間: <?php echo $post['lesson_time'] ? date('H:i', strtotime($post['lesson_time'])) : '-'; ?>
                                                        </small>
                                                    </p>
                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($post['comment'])); ?></p>
                                                </div>
                                                <div class="card-footer">
                                                    <small class="text-muted">
                                                        投稿日時: <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 画像モーダル -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">写真</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="modal-image">
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    function showImageModal(imagePath) {
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        document.getElementById('modalImage').src = imagePath;
        modal.show();
    }
</script>