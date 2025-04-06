<?php
require_once '../../config/database.php';
require_once '../../auth.php';

// 必ずJSONを返すように設定
header('Content-Type: application/json');

// エラー表示をオフにし、エラーをキャッチできるようにする
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );

    // 振替申請情報を含めて授業予定を取得
    $sql = "
        SELECT
            ls.id,
            ls.student_id,
            ls.date,
            ls.start_time,
            ls.end_time,
            ls.type,
            ls.lesson_day,
            s.name as student_name,
            c.name as course_name,
            tr.lesson_slot_id as original_lesson_id,
            tr.lesson_date as original_lesson_date
        FROM lesson_slots ls
        LEFT JOIN students s ON ls.student_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN transfer_requests tr ON ls.id = tr.lesson_slot_id AND tr.status = 'approved'
        WHERE ls.status = 'scheduled'
        ORDER BY ls.date, ls.start_time
    ";

    $stmt = $pdo->query($sql);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 休み情報を取得
    $sql = "
        SELECT
            lesson_date as date,
            lesson_type,
            note
        FROM lesson_calendar
        WHERE lesson_type IN ('休み', '臨時休校')
    ";

    $stmt = $pdo->query($sql);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 月ごとの授業回数をカウント
    $monthly_counts = [];
    foreach ($lessons as $lesson) {
        if ($lesson['student_id']) {  // 生徒の授業のみカウント
            $month = substr($lesson['date'], 0, 7);
            $key = $month . '_' . $lesson['student_id'];

            if (!isset($monthly_counts[$key])) {
                $monthly_counts[$key] = [
                    'month' => $month,
                    'student_id' => $lesson['student_id'],
                    'student_name' => $lesson['student_name'],
                    'count' => 0
                ];
            }
            $monthly_counts[$key]['count']++;
        }
    }

    // 警告メッセージを生成
    $warnings = [];
    foreach ($monthly_counts as $data) {
        if ($data['count'] >= 5) {
            $warnings[] = [
                'student_name' => $data['student_name'],
                'month' => $data['month'],
                'count' => $data['count'],
                'type' => 'too_many'
            ];
        } elseif ($data['count'] <= 3) {
            $warnings[] = [
                'student_name' => $data['student_name'],
                'month' => $data['month'],
                'count' => $data['count'],
                'type' => 'too_few'
            ];
        }
    }

    // イベントデータを生成
    $events = [];

    // 授業予定をイベントに変換
    foreach ($lessons as $lesson) {
        $backgroundColor = '#4CAF50'; // デフォルトの色（通常授業）

        if ($lesson['type'] === 'transfer') {
            $backgroundColor = '#FF9800'; // 振替授業の色
        }

        $event = [
            'id' => $lesson['id'],
            'title' => $lesson['student_name'] . "\n" . $lesson['course_name'],
            'start' => $lesson['date'] . 'T' . $lesson['start_time'],
            'end' => $lesson['date'] . 'T' . $lesson['end_time'],
            'backgroundColor' => $backgroundColor,
            'borderColor' => $backgroundColor,
            'extendedProps' => [
                'student_id' => $lesson['student_id'],
                'student_name' => $lesson['student_name'],
                'course_name' => $lesson['course_name'],
                'lesson_day' => $lesson['lesson_day'],
                'start_time' => substr($lesson['start_time'], 0, 5),
                'type' => $lesson['type']
            ]
        ];

        // 振替予定の場合、元の授業日情報を追加
        if ($lesson['type'] === 'transfer' && $lesson['original_lesson_date']) {
            $event['extendedProps']['original_lesson_date'] = $lesson['original_lesson_date'];
        }

        $events[] = $event;
    }

    // 休みをイベントに変換
    foreach ($holidays as $holiday) {
        $title = $holiday['lesson_type'];
        if (!empty($holiday['note'])) {
            $title .= "\n" . $holiday['note'];
        }

        $events[] = [
            'title' => $title,
            'start' => $holiday['date'],
            'allDay' => true,
            'backgroundColor' => $holiday['lesson_type'] === '臨時休校' ? '#dc3545' : '#ff9800',
            'borderColor' => $holiday['lesson_type'] === '臨時休校' ? '#dc3545' : '#ff9800',
            'classNames' => ['holiday-event'],
            'extendedProps' => [
                'type' => 'holiday',
                'holidayType' => $holiday['lesson_type'],
                'note' => $holiday['note']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'events' => $events,
        'warnings' => $warnings
    ]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラーが発生しました'
    ]);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'エラーが発生しました'
    ]);
}