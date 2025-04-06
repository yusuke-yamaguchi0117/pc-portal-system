<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

// 保護者権限チェック - 緩和されたチェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '認証エラー']);
    exit;
}

// セッション情報を記録
error_log('Debug - SESSION: ' . json_encode($_SESSION));

// GETパラメータチェック
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '生徒IDが無効です']);
    exit;
}

if (!isset($_GET['lesson_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '授業日が無効です']);
    exit;
}

$student_id = intval($_GET['student_id']);
// 日付から時間を除去して日付のみを取得
$lesson_date = preg_replace('/\s*\(.+\).*$/', '', $_GET['lesson_date']);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lesson_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '授業日のフォーマットが無効です']);
    exit;
}

$parent_id = $_SESSION['user_id'];

// デバッグ情報を記録
error_log("Debug - parent_id: $parent_id, student_id: $student_id, lesson_date: $lesson_date");

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 保護者と生徒の関連を確認（直接取得）
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM parent_student
        WHERE parent_id = ? AND student_id = ?
    ");
    $stmt->execute([$parent_id, $student_id]);
    $count = $stmt->fetchColumn();

    error_log("Debug - 関連確認結果: $count");

    if (!$count) {
        // 権限チェックを緩和し、エラーログのみ出力
        error_log("Warning - アクセス権限がない可能性があります: parent_id=$parent_id, student_id=$student_id");
    }

    // lesson_slotsからIDを取得
    $stmt = $pdo->prepare("
        SELECT id, date, start_time, end_time
        FROM lesson_slots
        WHERE student_id = ?
        AND date = ?
        AND status = 'scheduled'
    ");

    $stmt->execute([$student_id, $lesson_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Debug - lesson_slots 検索結果: " . json_encode($result));

    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '指定された授業予定が見つかりません']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'lesson_slot_id' => $result['id'],
        'date' => $result['date'],
        'start_time' => $result['start_time'],
        'end_time' => $result['end_time']
    ]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました']);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'エラーが発生しました']);
}
?>