<?php
require_once '../config/database.php';
require_once '../auth.php';

// 管理者権限チェック
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /portal/login.php');
    exit;
}

// データベース接続
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );

    // transfer_requestsテーブルにreject_reasonカラムが存在するか確認
    $checkColumnSql = "SHOW COLUMNS FROM transfer_requests LIKE 'reject_reason'";
    $checkColumnStmt = $pdo->prepare($checkColumnSql);
    $checkColumnStmt->execute();

    if ($checkColumnStmt->rowCount() === 0) {
        // reject_reasonカラムが存在しない場合は追加
        $alterTableSql = "ALTER TABLE transfer_requests ADD COLUMN reject_reason TEXT NULL AFTER reason";
        $pdo->exec($alterTableSql);
    }
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}

// フィルタリング処理
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// 振替申請データ取得用のクエリ作成
$sql = "
    SELECT
        tr.id,
        s.name as student_name,
        c.name as course_name,
        ls.date as lesson_date,
        ls.start_time as lesson_start_time,
        ls.end_time as lesson_end_time,
        tr.transfer_date,
        tr.transfer_start_time,
        tr.transfer_end_time,
        tr.status,
        tr.created_at,
        tr.reason,
        IFNULL(tr.reject_reason, '') as reject_reason
    FROM transfer_requests tr
    JOIN students s ON tr.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN lesson_slots ls ON tr.lesson_slot_id = ls.id
    WHERE 1=1
";

// フィルタリング条件を追加
if ($status_filter) {
    $sql .= " AND tr.status = :status";
}

if ($search_query) {
    $sql .= " AND s.name LIKE :search";
}

$sql .= " ORDER BY tr.created_at DESC";

$stmt = $pdo->prepare($sql);

// パラメータをバインド
if ($status_filter) {
    $stmt->bindValue(':status', $status_filter);
}

if ($search_query) {
    $stmt->bindValue(':search', '%' . $search_query . '%');
}

$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ステータスの日本語マッピング
$status_labels = [
    'pending' => '申請中',
    'approved' => '承認済',
    'rejected' => '却下'
];

// 曜日の日本語マッピング
$weekdays = [
    'Sunday' => '日',
    'Monday' => '月',
    'Tuesday' => '火',
    'Wednesday' => '水',
    'Thursday' => '木',
    'Friday' => '金',
    'Saturday' => '土'
];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>振替申請管理 - 管理者ページ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .status-pending {
            color: #ffc107;
        }

        .status-approved {
            color: #198754;
        }

        .status-rejected {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <?php
    // 共通レイアウトのインクルード
    require_once '../includes/header.php';
    require_once '../includes/sidebar_admin.php';
    ?>

    <div class="container-fluid">
        <h1 class="h2 mb-4">振替申請管理</h1>

        <!-- 検索・フィルタリング -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="生徒名で検索" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-outline-primary">検索</button>
                </form>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <div class="btn-group" role="group">
                        <a href="?status=" class="btn <?php echo $status_filter === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">すべて</a>
                        <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">申請中</a>
                        <a href="?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">承認済</a>
                        <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">却下</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 申請一覧テーブル -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>生徒名</th>
                        <th>コース</th>
                        <th>授業予定日</th>
                        <th>振替希望日</th>
                        <th>ステータス</th>
                        <th>備考</th>
                        <th>申請日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="9" class="text-center">振替申請はありません</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            // 日付フォーマット
                            $lesson_date_obj = new DateTime($request['lesson_date']);
                            $lesson_weekday = $weekdays[$lesson_date_obj->format('l')];
                            $lesson_date_formatted = $lesson_date_obj->format('Y-m-d') . '（' . $lesson_weekday . '）';
                            $lesson_time = substr($request['lesson_start_time'], 0, 5);

                            $transfer_date_obj = new DateTime($request['transfer_date']);
                            $transfer_weekday = $weekdays[$transfer_date_obj->format('l')];
                            $transfer_date_formatted = $transfer_date_obj->format('Y-m-d') . '（' . $transfer_weekday . '）';
                            $transfer_time = substr($request['transfer_start_time'], 0, 5);

                            $created_at = (new DateTime($request['created_at']))->format('Y-m-d H:i');

                            // ステータスクラス
                            $status_class = 'status-' . $request['status'];
                            ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                <td><?php echo $lesson_date_formatted . ' ' . $lesson_time; ?></td>
                                <td><?php echo $transfer_date_formatted . ' ' . $transfer_time; ?></td>
                                <td>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo $status_labels[$request['status']]; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($request['reason'] ?? ''); ?></td>
                                <td><?php echo $created_at; ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success approve-btn" data-id="<?php echo $request['id']; ?>" title="承認">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger reject-btn" data-id="<?php echo $request['id']; ?>" title="却下">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $request['id']; ?>">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 申請詳細モーダル -->
    <?php foreach ($requests as $request): ?>
        <div class="modal fade" id="detailModal<?php echo $request['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">申請詳細 #<?php echo $request['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>生徒名:</strong> <?php echo htmlspecialchars($request['student_name']); ?></p>
                        <p><strong>コース:</strong> <?php echo htmlspecialchars($request['course_name']); ?></p>
                        <p><strong>授業予定日:</strong> <?php echo $lesson_date_formatted . ' ' . $lesson_time; ?></p>
                        <p><strong>振替希望日:</strong> <?php echo $transfer_date_formatted . ' ' . $transfer_time; ?></p>
                        <p><strong>ステータス:</strong> <span class="<?php echo $status_class; ?>"><?php echo $status_labels[$request['status']]; ?></span></p>
                        <p><strong>申請日時:</strong> <?php echo $created_at; ?></p>

                        <?php if (!empty($request['reason'])): ?>
                            <p><strong>申請理由:</strong> <?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'rejected' && !empty($request['reject_reason'])): ?>
                            <p><strong>却下理由:</strong> <?php echo nl2br(htmlspecialchars($request['reject_reason'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- 却下理由モーダル -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">却下理由</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm">
                        <input type="hidden" id="reject_request_id" name="id">
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label">却下理由 (任意)</label>
                            <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">却下する</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 承認処理
            const approveButtons = document.querySelectorAll('.approve-btn');
            approveButtons.forEach(button => {
                button.addEventListener('click', function () {
                    if (confirm('この振替申請を承認しますか？')) {
                        const requestId = this.getAttribute('data-id');
                        processTransferRequest(requestId, 'approved');
                    }
                });
            });

            // 却下モーダル表示
            const rejectButtons = document.querySelectorAll('.reject-btn');
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

            rejectButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const requestId = this.getAttribute('data-id');
                    document.getElementById('reject_request_id').value = requestId;
                    rejectModal.show();
                });
            });

            // 却下確定処理
            document.getElementById('confirmReject').addEventListener('click', function () {
                const requestId = document.getElementById('reject_request_id').value;
                const rejectReason = document.getElementById('reject_reason').value;
                processTransferRequest(requestId, 'rejected', rejectReason);
                rejectModal.hide();
            });

            // 申請処理関数
            function processTransferRequest(id, status, rejectReason = '') {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);
                if (rejectReason) {
                    formData.append('reject_reason', rejectReason);
                }

                fetch('/portal/admin/api/save_transfer_request.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('エラー: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('処理中にエラーが発生しました');
                    });
            }
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>

</html>