<?php
require_once '../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

// POSTデータを取得
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'IDが指定されていません']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM lesson_calendar WHERE id = ?");
    $stmt->execute([$data['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'レッスンが削除されました'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>