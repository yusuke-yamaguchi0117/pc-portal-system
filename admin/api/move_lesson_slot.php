<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

// 管理者権限チェック
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '権限がありません']);
    exit;
}

// POSTデータの取得
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['date']) || !isset($data['start_time'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'パラメータが不足しています']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // レッスンスロットの更新
    $stmt = $pdo->prepare("
        UPDATE lesson_slots
        SET
            date = ?,
            start_time = ?,
            end_time = ?,
            lesson_day = CASE
                WHEN DAYOFWEEK(?) = 1 THEN '日曜'
                WHEN DAYOFWEEK(?) = 2 THEN '月曜'
                WHEN DAYOFWEEK(?) = 3 THEN '火曜'
                WHEN DAYOFWEEK(?) = 4 THEN '水曜'
                WHEN DAYOFWEEK(?) = 5 THEN '木曜'
                WHEN DAYOFWEEK(?) = 6 THEN '金曜'
                WHEN DAYOFWEEK(?) = 7 THEN '土曜'
            END,
            type = CASE
                WHEN EXISTS (
                    SELECT 1 FROM students s
                    WHERE s.id = lesson_slots.student_id
                    AND BINARY s.lesson_day = BINARY CASE
                        WHEN DAYOFWEEK(?) = 1 THEN '日曜'
                        WHEN DAYOFWEEK(?) = 2 THEN '月曜'
                        WHEN DAYOFWEEK(?) = 3 THEN '火曜'
                        WHEN DAYOFWEEK(?) = 4 THEN '水曜'
                        WHEN DAYOFWEEK(?) = 5 THEN '木曜'
                        WHEN DAYOFWEEK(?) = 6 THEN '金曜'
                        WHEN DAYOFWEEK(?) = 7 THEN '土曜'
                    END
                    AND TIME_FORMAT(s.lesson_time, '%H:%i') = TIME_FORMAT(?, '%H:%i')
                ) THEN 'regular'
                ELSE 'transfer'
            END,
            updated_at = NOW()
        WHERE id = ?
    ");

    $params = [
        $data['date'],                // date
        $data['start_time'],          // start_time
        $data['end_time'],            // end_time
        $data['date'],                // lesson_day CASE 1
        $data['date'],                // lesson_day CASE 2
        $data['date'],                // lesson_day CASE 3
        $data['date'],                // lesson_day CASE 4
        $data['date'],                // lesson_day CASE 5
        $data['date'],                // lesson_day CASE 6
        $data['date'],                // lesson_day CASE 7
        $data['date'],                // type CASE 1
        $data['date'],                // type CASE 2
        $data['date'],                // type CASE 3
        $data['date'],                // type CASE 4
        $data['date'],                // type CASE 5
        $data['date'],                // type CASE 6
        $data['date'],                // type CASE 7
        $data['start_time'],          // lesson_time comparison
        $data['id']                   // WHERE id
    ];

    $stmt->execute($params);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました']);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'エラーが発生しました']);
}