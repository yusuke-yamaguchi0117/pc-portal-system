<?php
session_start();
require_once '../config/database.php';

// セッションチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 管理者情報の取得
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// 最近の申請の取得
$stmt = $pdo->prepare("
    SELECT
        'transfer' as type,
        tr.id,
        tr.student_id,
        tr.parent_id,
        tr.lesson_date as original_date,
        tr.transfer_date as requested_date,
        tr.transfer_start_time as requested_time,
        tr.reason,
        tr.status,
        tr.created_at,
        s.name as student_name
    FROM transfer_requests tr
    JOIN students s ON tr.student_id = s.id
    WHERE tr.status = 'pending'
    UNION ALL
    SELECT
        'timechange' as type,
        tcr.id,
        tcr.student_id,
        tcr.parent_id,
        CONCAT(sc.day_of_week, ' ', sc.start_time) as original_date,
        tcr.requested_date,
        tcr.requested_time,
        tcr.reason,
        tcr.status,
        tcr.created_at,
        s.name as student_name
    FROM timechange_requests tcr
    JOIN students s ON tcr.student_id = s.id
    JOIN schedules sc ON tcr.current_schedule_id = sc.id
    WHERE tcr.status = 'pending'
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_requests = $stmt->fetchAll();

// 未対応のお問い合わせの取得
$stmt = $pdo->prepare("
    SELECT i.*, p.name as parent_name
    FROM inquiries i
    JOIN parents p ON i.parent_id = p.id
    WHERE i.status = 'new'
    ORDER BY i.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_inquiries = $stmt->fetchAll();

// 共通レイアウトのインクルード
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="container-fluid">
    <h1 class="h2 mb-4">ダッシュボード</h1>

    <!-- 最近の申請 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">最近の申請</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_requests)): ?>
                <p class="text-muted">新しい申請はありません。</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>種類</th>
                                <th>生徒名</th>
                                <th>変更前</th>
                                <th>変更後</th>
                                <th>申請日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['type'] === 'transfer' ? '振替' : '時間変更'; ?></td>
                                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['original_date']); ?></td>
                                    <td><?php echo htmlspecialchars($request['requested_date'] . ' ' . $request['requested_time']); ?></td>
                                    <td><?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo $request['type'] === 'transfer' ? 'transfer_requests.php' : 'timechange_requests.php'; ?>?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">詳細</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 未対応のお問い合わせ -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">未対応のお問い合わせ</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_inquiries)): ?>
                <p class="text-muted">未対応のお問い合わせはありません。</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>保護者名</th>
                                <th>件名</th>
                                <th>受信日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_inquiries as $inquiry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inquiry['parent_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['subject']); ?></td>
                                    <td><?php echo date('Y/m/d H:i', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <a href="inquiries.php?id=<?php echo $inquiry['id']; ?>" class="btn btn-sm btn-primary">詳細</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>