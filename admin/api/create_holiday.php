<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['date'])) {
        throw new Exception('日付が指定されていません');
    }

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );

    // 既存のレコードを確認
    $stmt = $pdo->prepare("SELECT id FROM lesson_calendar WHERE lesson_date = ?");
    $stmt->execute([$data['date']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 既存のレコードを更新
        $stmt = $pdo->prepare("
            UPDATE lesson_calendar
            SET lesson_type = ?,
                note = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE lesson_date = ?
        ");
        $stmt->execute([
            $data['type'] ?? '休み',
            $data['note'] ?? null,
            $data['date']
        ]);
    } else {
        // 新規レコードを挿入
        $stmt = $pdo->prepare("
            INSERT INTO lesson_calendar
            (lesson_date, lesson_type, note, created_at, updated_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $data['date'],
            $data['type'] ?? '休み',
            $data['note'] ?? null
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => '休みを登録しました'
    ]);

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}