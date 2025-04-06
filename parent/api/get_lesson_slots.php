<?php
require_once '../../config/database.php';
require_once '../../auth.php';

// 必ずJSONを返すように設定
header('Content-Type: application/json');

// エラー表示をオフにし、エラーをキャッチできるようにする
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ログイン中の保護者に紐づく生徒の授業予定を取得
    $stmt = $pdo->prepare("
        SELECT
            ls.*,
            s.name as student_name,
            c.name as course_name,
            CASE DAYOFWEEK(ls.date)
                WHEN 1 THEN '日'
                WHEN 2 THEN '月'
                WHEN 3 THEN '火'
                WHEN 4 THEN '水'
                WHEN 5 THEN '木'
                WHEN 6 THEN '金'
                WHEN 7 THEN '土'
            END as lesson_day
        FROM lesson_slots ls
        JOIN students s ON ls.student_id = s.id
        JOIN courses c ON s.course = c.id
        JOIN parent_student ps ON s.id = ps.student_id
        WHERE ps.parent_id = ?
        AND ls.date >= CURDATE()
        ORDER BY ls.date, ls.start_time
    ");
    $stmt->execute([$_SESSION['user_id']]);

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['student_name'] . '（' . $row['course_name'] . '）',
            'start' => $row['date'] . ($row['start_time'] ? 'T' . $row['start_time'] : ''),
            'end' => $row['date'] . ($row['end_time'] ? 'T' . $row['end_time'] : ''),
            'backgroundColor' => '#4CAF50', // 緑色
            'borderColor' => '#4CAF50',
            'textColor' => '#ffffff',
            'extendedProps' => [
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name'],
                'course_name' => $row['course_name'],
                'start_time' => $row['start_time'] ? substr($row['start_time'], 0, 5) : '',
                'end_time' => $row['end_time'] ? substr($row['end_time'], 0, 5) : '',
                'lesson_day' => $row['lesson_day'],
                'status' => $row['status']
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