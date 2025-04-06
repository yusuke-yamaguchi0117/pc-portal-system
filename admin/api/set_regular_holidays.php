<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode(file_get_contents('php://input'), true);
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    $weekdays = $data['weekdays'] ?? [];

    if (!$start_date || !$end_date || empty($weekdays)) {
        throw new Exception('必要なパラメータが不足しています');
    }

    // 開始日と終了日のバリデーション
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    if ($start > $end) {
        throw new Exception('開始日は終了日より前の日付を指定してください');
    }

    $pdo->beginTransaction();

    // 指定された期間内の日付をループ
    $current = clone $start;
    while ($current <= $end) {
        // 現在の曜日が選択された曜日に含まれているかチェック
        if (in_array($current->format('w'), $weekdays)) {
            $date = $current->format('Y-m-d');

            // 既存のレコードを確認
            $stmt = $pdo->prepare("SELECT lesson_date FROM lesson_calendar WHERE lesson_date = ?");
            $stmt->execute([$date]);

            if ($stmt->fetch()) {
                // 既存のレコードを更新
                $stmt = $pdo->prepare("
                    UPDATE lesson_calendar
                    SET lesson_type = '休み',
                        note = '定休日',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE lesson_date = ?
                ");
            } else {
                // 新規レコードを挿入
                $stmt = $pdo->prepare("
                    INSERT INTO lesson_calendar
                    (lesson_date, lesson_type, note, created_at, updated_at)
                    VALUES (?, '休み', '定休日', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
            }

            $stmt->execute([$date]);
        }

        $current->modify('+1 day');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '定休日を設定しました'
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }

    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}