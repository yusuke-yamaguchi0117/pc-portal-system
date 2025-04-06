<?php
require_once '../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT * FROM lesson_calendar ORDER BY lesson_date");
    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = array_map(function ($lesson) {
        // ステータスに応じて色を設定
        $color = '';
        switch ($lesson['lesson_type']) {
            case '授業あり':
                $color = '#28a745'; // 緑
                break;
            case '休み':
                $color = '#dc3545'; // 赤
                break;
            case '振替対応日':
                $color = '#ffc107'; // 黄
                break;
            default:
                $color = '#6c757d'; // グレー
        }

        return [
            'id' => $lesson['id'],
            'title' => $lesson['lesson_type'],
            'start' => $lesson['lesson_date'],
            'color' => $color,
            'status' => $lesson['lesson_type'],
            'note' => $lesson['note']
        ];
    }, $lessons);

    echo json_encode($events);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>