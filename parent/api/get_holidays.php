<?php
require_once '../../config/database.php';
require_once '../../auth.php';

// 必ずJSONを返すように設定
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // lesson_calendarテーブルから休みの予定を取得
    $stmt = $pdo->prepare("
        SELECT
            id,
            lesson_date,
            lesson_type,
            note
        FROM lesson_calendar
        WHERE lesson_type IN ('休み', '臨時休校')
        AND lesson_date >= CURDATE()
        ORDER BY lesson_date
    ");
    $stmt->execute();

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['lesson_type'],
            'start' => $row['lesson_date'],
            'backgroundColor' => $row['lesson_type'] === '休み' ? '#ff9800' : '#dc3545', // 休み：オレンジ色、臨時休校：赤色
            'borderColor' => $row['lesson_type'] === '休み' ? '#ff9800' : '#dc3545',
            'textColor' => '#ffffff',
            'extendedProps' => [
                'type' => 'holiday',
                'status' => $row['lesson_type'],
                'note' => $row['note']
            ]
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([]);
}