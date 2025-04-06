<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

try {
    // POSTデータを取得
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['student_id'], $data['date'], $data['start_time'], $data['end_time'], $data['type'])) {
        throw new Exception('必要なパラメータが不足しています');
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 既存の予定をチェック
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM lesson_slots
        WHERE student_id = ?
        AND date = ?
        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
    ");
    $stmt->execute([
        $data['student_id'],
        $data['date'],
        $data['end_time'],
        $data['start_time'],
        $data['end_time'],
        $data['start_time']
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        throw new Exception('指定した時間帯に既に予定が存在します');
    }

    // 予定を登録
    $stmt = $pdo->prepare("
        INSERT INTO lesson_slots (student_id, date, start_time, end_time, type, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'scheduled', NOW(), NOW())
    ");
    $stmt->execute([
        $data['student_id'],
        $data['date'],
        $data['start_time'],
        $data['end_time'],
        $data['type']
    ]);

    echo json_encode([
        'success' => true,
        'message' => '予定を登録しました'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}