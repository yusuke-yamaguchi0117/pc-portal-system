<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ログイン中の保護者に紐づく生徒の授業予定を取得
    $stmt = $pdo->prepare("
        SELECT
            ls.*,
            s.name as student_name,
            c.name as course_name
        FROM lesson_slots ls
        JOIN students s ON ls.student_id = s.id
        JOIN courses c ON s.course = c.id
        JOIN parent_student ps ON s.id = ps.student_id
        WHERE ps.parent_id = ? AND ls.status = 'scheduled'
        ORDER BY ls.date, ls.start_time
    ");
    $stmt->execute([$_SESSION['user_id']]);

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'title' => substr($row['start_time'], 0, 5) . "\n" . $row['student_name'] . '（' . $row['course_name'] . '）',
            'start' => $row['date'] . 'T' . $row['start_time'],
            'end' => $row['date'] . 'T' . $row['end_time'],
            'backgroundColor' => '#4CAF50', // 緑色（管理画面と同じ）
            'borderColor' => '#4CAF50',
            'textColor' => '#ffffff',
            'classNames' => ['student-lesson', 'fc-lesson']
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'データベースエラーが発生しました']);
    error_log($e->getMessage());
}