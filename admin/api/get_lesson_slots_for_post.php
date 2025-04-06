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

    if (!isset($_GET['student_id'])) {
        throw new Exception('生徒IDが指定されていません');
    }

    $student_id = intval($_GET['student_id']);

    // 過去1週間の授業を取得
    $stmt = $pdo->prepare("
        SELECT id, date, start_time
        FROM lesson_slots
        WHERE student_id = :student_id
        AND status = 'scheduled'
        AND date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
        ORDER BY date DESC, start_time DESC
    ");

    $stmt->execute(['student_id' => $student_id]);
    $slots = $stmt->fetchAll();

    // 日付をフォーマット
    $formatted_slots = array_map(function ($slot) {
        $date = new DateTime($slot['date']);
        $time = new DateTime($slot['start_time']);
        $slot['formatted_date'] = $date->format('Y年m月d日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$date->format('w')] . ') ' . $time->format('H:i');
        return $slot;
    }, $slots);

    echo json_encode($formatted_slots);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>