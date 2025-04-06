<?php
header('Content-Type: application/json');

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

    if (!isset($_GET['student_id']) || !isset($_GET['lesson_slot_id'])) {
        throw new Exception('必要なパラメータが不足しています');
    }

    $student_id = intval($_GET['student_id']);
    $lesson_slot_id = intval($_GET['lesson_slot_id']);

    // 選択された授業の曜日を取得
    $stmt = $pdo->prepare("
        SELECT DAYOFWEEK(date) as weekday
        FROM lesson_slots
        WHERE id = ?
    ");
    $stmt->execute([$lesson_slot_id]);
    $current_slot = $stmt->fetch();

    if (!$current_slot) {
        throw new Exception('授業が見つかりません');
    }

    // 同じ生徒の同じ曜日の過去の投稿を検索
    $stmt = $pdo->prepare("
        SELECT lp.*, ls.date
        FROM lesson_posts lp
        JOIN lesson_slots ls ON lp.lesson_slot_id = ls.id
        WHERE lp.student_id = ?
        AND DAYOFWEEK(ls.date) = ?
        ORDER BY ls.date DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $current_slot['weekday']]);
    $existing_post = $stmt->fetch();

    if ($existing_post) {
        $date = new DateTime($existing_post['date']);
        echo json_encode([
            'exists' => true,
            'message' => $date->format('Y年m月d日') . 'に投稿があります。'
        ]);
    } else {
        echo json_encode([
            'exists' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>