<?php
require_once '../../config/database.php';
require_once '../../auth.php';
require_once '../../db_connect.php';

header('Content-Type: application/json');

// エラーログの設定
ini_set('display_errors', 1);
ini_set('error_log', '../../logs/error.log');
error_reporting(E_ALL);

// デバッグ用のログ関数
function debug_log($message)
{
    error_log("[DEBUG] " . $message);
}

try {
    $sql = "SELECT s.*, c.name as course_name
            FROM students s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.status = 'active'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($students as $student) {
        try {
            $weekday = $student['lesson_weekday'];
            if (!$weekday)
                continue;

            $lessonTime = $student['lesson_time'];
            if (!$lessonTime)
                continue;

            $startDate = new DateTime('first day of this month');
            $endDate = new DateTime('last day of next month');

            $event = [
                'title' => $student['name'] . '（' . $student['grade'] . '）',
                'rrule' => [
                    'freq' => 'weekly',
                    'dtstart' => $startDate->format('Y-m-d') . 'T' . $lessonTime,
                    'byweekday' => [strtolower(getWeekdayShort($weekday))]
                ],
                'duration' => '01:00',
                'backgroundColor' => '#0d6efd',
                'borderColor' => '#0d6efd'
            ];

            $events[] = $event;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => '生徒データの処理中にエラーが発生しました']);
            exit;
        }
    }

    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'データベースエラーが発生しました']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'エラーが発生しました']);
    exit;
}

function getWeekdayShort($weekday)
{
    $weekdays = [
        1 => 'mo',
        2 => 'tu',
        3 => 'we',
        4 => 'th',
        5 => 'fr',
        6 => 'sa',
        7 => 'su'
    ];
    return $weekdays[$weekday] ?? 'mo';
}
?>