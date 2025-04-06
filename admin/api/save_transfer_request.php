<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

// 管理者権限チェック
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '権限がありません']);
    exit;
}

// POSTデータチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不正なリクエストメソッドです']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'パラメータが不足しています']);
    exit;
}

$request_id = intval($_POST['id']);
$status = $_POST['status'];
$reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // トランザクション開始
    $pdo->beginTransaction();

    // 申請情報の取得
    $stmt = $pdo->prepare("
        SELECT
            tr.id,
            tr.student_id,
            tr.lesson_slot_id,
            tr.transfer_date,
            tr.transfer_start_time,
            tr.transfer_end_time,
            tr.status,
            ls.date as lesson_date,
            ls.start_time as lesson_start_time,
            ls.end_time as lesson_end_time
        FROM transfer_requests tr
        JOIN lesson_slots ls ON tr.lesson_slot_id = ls.id
        WHERE tr.id = ? AND tr.status = 'pending'
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '処理対象の申請が見つからないか、すでに処理済みです']);
        exit;
    }

    // 申請のステータス更新
    $updateSql = "UPDATE transfer_requests SET status = ?, updated_at = NOW()";
    if ($status === 'rejected' && !empty($reject_reason)) {
        $updateSql .= ", reject_reason = ?";
        $updateParams = [$status, $reject_reason, $request_id];
    } else {
        $updateParams = [$status, $request_id];
    }
    $updateSql .= " WHERE id = ?";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($updateParams);

    // 承認の場合、レッスンスロットの更新処理
    if ($status === 'approved') {
        // 元の予定を振替日に更新
        $stmt = $pdo->prepare("
            UPDATE lesson_slots
            SET
                date = ?,
                start_time = ?,
                end_time = ?,
                type = 'transfer',
                lesson_day = (CASE
                    WHEN DAYOFWEEK(?) = 1 THEN '日曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 2 THEN '月曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 3 THEN '火曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 4 THEN '水曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 5 THEN '木曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 6 THEN '金曜' COLLATE utf8_general_ci
                    WHEN DAYOFWEEK(?) = 7 THEN '土曜' COLLATE utf8_general_ci
                END),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $request['transfer_date'],
            $request['transfer_start_time'],
            $request['transfer_end_time'],
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['transfer_date'],  // lesson_day計算用
            $request['lesson_slot_id']
        ]);
    }

    // トランザクション確定
    $pdo->commit();

    // レスポンス
    $message = $status === 'approved' ? '振替申請を承認しました' : '振替申請を却下しました';
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('General Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'エラーが発生しました']);
}
?>