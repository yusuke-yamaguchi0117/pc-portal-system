<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

try {
    // パラメータのバリデーション
    if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
        throw new Exception('生徒IDが無効です');
    }
    if (!isset($_GET['lesson_date'])) {
        throw new Exception('授業日が無効です');
    }

    $student_id = intval($_GET['student_id']);
    // 日付から時間を除去して日付のみを取得
    $lesson_date = preg_replace('/\s*\(.+\).*$/', '', $_GET['lesson_date']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lesson_date)) {
        throw new Exception('授業日のフォーマットが無効です');
    }

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

    // 生徒のコース情報を取得
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.course_id,
            s.name,
            c.name as course_name
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_info) {
        http_response_code(404);
        echo json_encode(['error' => '生徒情報が見つかりません']);
        exit;
    }
    if (empty($student_info['course_id'])) {
        throw new Exception('生徒のコース情報が設定されていません');
    }

    // コースの時間割を取得
    $sql = "SELECT * FROM schedules WHERE course_id = :course_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['course_id' => $student_info['course_id']]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $schedule_count = count($schedules);

    if ($schedule_count === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'コースの時間割が見つかりません']);
        exit;
    }

    // 日付範囲の設定
    $start_date = date('Y-m-d', strtotime($lesson_date . ' -6 days'));
    $end_date = date('Y-m-d', strtotime($lesson_date . ' +6 days'));

    // 休みの日を取得
    $sql = "SELECT lesson_date FROM lesson_calendar WHERE lesson_date BETWEEN :start_date AND :end_date AND lesson_type = '休み'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $holidays = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 既存の予約を取得
    $sql = "SELECT date as lesson_date FROM lesson_slots
            WHERE student_id = :student_id
            AND date BETWEEN :start_date AND :end_date
            AND status = 'scheduled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'student_id' => $student_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 振替候補日を取得
    $stmt = $pdo->prepare("
        WITH RECURSIVE date_range AS (
            -- 日付範囲を生成（前後6日）
            SELECT DATE(?) as check_date
            UNION ALL
            SELECT DATE_ADD(check_date, INTERVAL 1 DAY)
            FROM date_range
            WHERE check_date < DATE(?)
        ),
        course_schedules AS (
            -- コースの全時間帯を取得
            SELECT
                day_of_week,
                start_time,
                ADDTIME(start_time, '01:00:00') as end_time,
                capacity
            FROM schedules
            WHERE course_id = ?
        ),
        available_slots AS (
            -- 日付と時間帯のすべての組み合わせを生成
            SELECT
                dr.check_date,
                cs.start_time,
                cs.end_time,
                cs.capacity,
                DAYOFWEEK(dr.check_date) as day_of_week
            FROM date_range dr
            CROSS JOIN course_schedules cs
            WHERE cs.day_of_week = DAYOFWEEK(dr.check_date)
        ),
        current_bookings AS (
            -- 既存の予約数を集計
            SELECT
                ls.date,
                ls.start_time,
                COUNT(*) as booked_count
            FROM lesson_slots ls
            WHERE ls.date BETWEEN ? AND ?
            AND ls.status = 'scheduled'
            GROUP BY ls.date, ls.start_time
        )
        SELECT
            avs.check_date as date,
            DATE_FORMAT(avs.check_date, '%Y-%m-%d') as formatted_date,
            avs.start_time,
            avs.end_time,
            DAYNAME(avs.check_date) as day_name,
            CASE
                WHEN lc.lesson_type = '休み' THEN 0
                ELSE avs.capacity - COALESCE(cb.booked_count, 0)
            END as remaining_slots
        FROM available_slots avs
        LEFT JOIN current_bookings cb ON
            avs.check_date = cb.date
            AND avs.start_time = cb.start_time
        LEFT JOIN lesson_calendar lc ON avs.check_date = lc.lesson_date
        WHERE (lc.lesson_type IS NULL OR lc.lesson_type != '休み')
        AND avs.check_date != ?
        ORDER BY avs.check_date ASC, avs.start_time ASC
    ");

    $stmt->execute([
        $start_date,
        $end_date,
        $student_info['course_id'],
        $start_date,
        $end_date,
        $lesson_date
    ]);

    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 日本語の曜日を設定
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    foreach ($candidates as &$candidate) {
        $date = new DateTime($candidate['date']);
        $jp_day = $weekdays[$date->format('w')];
        $start_time = substr($candidate['start_time'], 0, 5);
        $end_time = substr($candidate['end_time'], 0, 5);
        $candidate['formatted_date'] = $candidate['formatted_date'] . "({$jp_day}) " . $start_time . '～' . $end_time;
    }

    echo json_encode([
        'success' => true,
        'candidates' => $candidates,
        'message' => empty($candidates) ? '振替可能な日程が見つかりません' : null
    ]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラーが発生しました'
    ]);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>