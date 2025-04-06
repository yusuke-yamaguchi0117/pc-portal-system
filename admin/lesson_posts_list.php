<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

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

    // フィルター条件の取得
    $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
    $theme = isset($_GET['theme']) ? $_GET['theme'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // 生徒一覧の取得（フィルター用）
    $stmt = $pdo->query("SELECT id, name, furigana FROM students ORDER BY furigana");
    $students = $stmt->fetchAll();

    // 授業テーマ一覧の取得（フィルター用）
    $stmt = $pdo->query("SELECT DISTINCT theme FROM lesson_posts ORDER BY theme");
    $themes = $stmt->fetchAll();

    // 投稿一覧の取得
    $where_conditions = [];
    $params = [];

    if ($student_id) {
        $where_conditions[] = "lp.student_id = ?";
        $params[] = $student_id;
    }
    if ($theme) {
        $where_conditions[] = "lp.theme = ?";
        $params[] = $theme;
    }
    if ($date_from) {
        $where_conditions[] = "ls.date >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $where_conditions[] = "ls.date <= ?";
        $params[] = $date_to;
    }

    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $sql = "
        SELECT
            lp.*,
            s.name as student_name,
            ls.date as lesson_date,
            ls.start_time as lesson_time
        FROM lesson_posts lp
        JOIN students s ON lp.student_id = s.id
        LEFT JOIN lesson_slots ls ON lp.lesson_slot_id = ls.id
        {$where_clause}
        ORDER BY lp.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}
?>

<!-- メインコンテンツ -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">授業様子一覧</h1>
            </div>
            <div class="col-sm-6 text-end">
                <a href="lesson_posts_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規投稿作成
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- フィルター -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="student_id" class="form-label">生徒名</label>
                        <select class="form-select" id="student_id" name="student_id">
                            <option value="">すべて</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo $student_id == $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['furigana'] . ' - ' . $student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="theme" class="form-label">授業テーマ</label>
                        <select class="form-select" id="theme" name="theme">
                            <option value="">すべて</option>
                            <?php foreach ($themes as $t): ?>
                                <option value="<?php echo $t['theme']; ?>" <?php echo $theme == $t['theme'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['theme']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">授業日（From）</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">授業日（To）</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">検索</button>
                        <a href="lesson_posts_list.php" class="btn btn-secondary ms-2">リセット</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 投稿一覧 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>生徒名</th>
                                <th>授業日</th>
                                <th>授業時間帯</th>
                                <th>授業テーマ</th>
                                <th>コメント</th>
                                <th>投稿日時</th>
                                <th>写真</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['student_name']); ?></td>
                                    <td><?php echo $post['lesson_date'] ? date('Y-m-d', strtotime($post['lesson_date'])) : '-'; ?></td>
                                    <td><?php echo $post['lesson_time'] ? date('H:i', strtotime($post['lesson_time'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($post['theme']); ?></td>
                                    <td>
                                        <?php
                                        $comment = htmlspecialchars($post['comment']);
                                        echo mb_strlen($comment) > 50 ? mb_substr($comment, 0, 50) . '...' : $comment;
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($post['photo_path']): ?>
                                            <img src="<?php echo htmlspecialchars($post['photo_path']); ?>" class="img-thumbnail" style="max-width: 50px; cursor: pointer;" onclick="showImageModal('<?php echo htmlspecialchars($post['photo_path']); ?>')">
                                        <?php else: ?>
                                            <span class="text-muted">写真なし</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="showDetailModal(<?php echo htmlspecialchars(json_encode($post)); ?>)">
                                            詳細を見る
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                <img src="" id="modalImage" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- 詳細モーダル -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">投稿詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>生徒名</dt>
                            <dd id="modalStudentName"></dd>
                            <dt>授業日</dt>
                            <dd id="modalLessonDate"></dd>
                            <dt>授業時間帯</dt>
                            <dd id="modalLessonTime"></dd>
                            <dt>授業テーマ</dt>
                            <dd id="modalTheme"></dd>
                            <dt>投稿日時</dt>
                            <dd id="modalCreatedAt"></dd>
                        </dl>
                    </div>
                    <div class="col-md-6" id="modalPhotoContainer">
                        <!-- 写真がある場合のみ表示 -->
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <dt>コメント</dt>
                        <dd id="modalComment"></dd>
                    </div>
                </div>
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

    function showDetailModal(post) {
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));

        // 基本情報の設定
        document.getElementById('modalStudentName').textContent = post.student_name;
        document.getElementById('modalLessonDate').textContent = post.lesson_date ? new Date(post.lesson_date).toLocaleDateString('ja-JP') : '-';
        document.getElementById('modalLessonTime').textContent = post.lesson_time ? new Date('1970-01-01T' + post.lesson_time).toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' }) : '-';
        document.getElementById('modalTheme').textContent = post.theme;
        document.getElementById('modalCreatedAt').textContent = new Date(post.created_at).toLocaleString('ja-JP');
        document.getElementById('modalComment').textContent = post.comment;

        // 写真の設定
        const photoContainer = document.getElementById('modalPhotoContainer');
        if (post.photo_path) {
            photoContainer.innerHTML = `
            <img src="${post.photo_path}" class="img-fluid" style="max-height: 300px;">
        `;
        } else {
            photoContainer.innerHTML = '<p class="text-muted">写真なし</p>';
        }

        modal.show();
    }

    // 日付範囲の相関チェック
    document.querySelector('form').addEventListener('submit', function (e) {
        const dateFrom = document.getElementById('date_from').value;
        const dateTo = document.getElementById('date_to').value;

        if (dateFrom && dateTo && dateFrom > dateTo) {
            e.preventDefault();
            alert('授業日の範囲が正しくありません。');
        }
    });
</script>