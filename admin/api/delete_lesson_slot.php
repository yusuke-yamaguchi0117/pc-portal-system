<?php
require_once '../../config/database.php';
require_once '../../auth.php';

// 必ずJSONを返すように設定
header('Content-Type: application/json');

// エラー表示をオフにし、エラーをキャッチできるようにする
error_reporting(0);
ini_set('display_errors', 0);

try {
    // POSTデータを取得
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        throw new Exception('必要なパラメータが不足しています');
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // lesson_slotsテーブルから削除を試みる
    $stmt = $pdo->prepare("DELETE FROM lesson_slots WHERE id = ?");
    $result = $stmt->execute([$data['id']]);

    if ($stmt->rowCount() === 0) {
        // lesson_slotsで削除できなかった場合、lesson_calendarから削除を試みる
        $stmt = $pdo->prepare("DELETE FROM lesson_calendar WHERE id = ?");
        $result = $stmt->execute([$data['id']]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('指定された予定が見つかりません');
        }
    }

    echo json_encode([
        'success' => true,
        'message' => '予定を削除しました'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}