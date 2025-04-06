<?php
require_once '../../config/database.php';
require_once '../../auth.php';

// 必ずJSONを返すように設定
header('Content-Type: application/json');

try {
    // student_idのバリデーション
    if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
        throw new Exception('生徒IDが無効です');
    }

    $student_id = intval($_GET['student_id']);

    // 保護者IDを取得
    $parent_id = $_SESSION['user_id'];

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 保護者と生徒の関連を確認
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM parent_student
        WHERE parent_id = ? AND student_id = ?
    ");
    $stmt->execute([$parent_id, $student_id]);

    if (!$stmt->fetchColumn()) {
        throw new Exception('アクセス権限がありません');
    }

    // 生徒の今後の授業予定日を取得（当日を除く）
    $stmt = $pdo->prepare("
        SELECT
            ls.date,
            DATE_FORMAT(ls.date, '%Y-%m-%d') as formatted_date,
            ls.start_time,
            ls.end_time
        FROM lesson_slots ls
        WHERE ls.student_id = ?
        AND ls.date > CURDATE()  -- CURDATEと等号を外して当日を除外
        AND ls.status = 'scheduled'
        ORDER BY ls.date ASC, ls.start_time ASC
    ");
    $stmt->execute([$student_id]);

    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 日付と時間を結合して表示用のフォーマットを作成
    foreach ($dates as &$date) {
        $start_time = substr($date['start_time'], 0, 5);
        $end_time = substr($date['end_time'], 0, 5);
        $date['formatted_date'] = $date['formatted_date'] . ' (' . $start_time . '～' . $end_time . ')';
    }

    echo json_encode($dates);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'データベースエラーが発生しました'
    ]);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>