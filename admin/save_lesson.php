<?php
// エラーレポートを設定
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 出力バッファリングを開始
ob_start();

try {
    // 設定ファイルの読み込み
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception('データベース設定ファイルが見つかりません: ' . $configPath);
    }
    require_once $configPath;

    // リクエストボディの取得
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('リクエストボディの取得に失敗しました');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSONの解析に失敗しました: ' . json_last_error_msg());
    }

    // バリデーション
    if (empty($data['date'])) {
        throw new Exception('日付が指定されていません');
    }

    if (empty($data['status'])) {
        throw new Exception('ステータスが指定されていません');
    }

    // 日付の形式を確認
    $date = DateTime::createFromFormat('Y-m-d', $data['date']);
    if (!$date || $date->format('Y-m-d') !== $data['date']) {
        throw new Exception('日付の形式が不正です');
    }

    // データベース接続
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+09:00'"
            ]
        );
    } catch (PDOException $e) {
        throw new Exception('データベース接続に失敗しました: ' . $e->getMessage());
    }

    // テーブルの存在確認
    $tableExists = $pdo->query("SHOW TABLES LIKE 'lesson_calendar'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception('テーブル lesson_calendar が存在しません');
    }

    if (!empty($data['id'])) {
        // 更新
        try {
            $stmt = $pdo->prepare("UPDATE lesson_calendar SET lesson_date = :date, lesson_type = :status, note = :note, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':id' => $data['id'],
                ':date' => $data['date'],
                ':status' => $data['status'],
                ':note' => $data['note'] ?? null
            ]);
        } catch (PDOException $e) {
            throw new Exception('更新に失敗しました: ' . $e->getMessage());
        }
    } else {
        // 新規作成
        try {
            $stmt = $pdo->prepare("INSERT INTO lesson_calendar (lesson_date, lesson_type, note, created_at, updated_at) VALUES (:date, :status, :note, NOW(), NOW())");
            $stmt->execute([
                ':date' => $data['date'],
                ':status' => $data['status'],
                ':note' => $data['note'] ?? null
            ]);
            $data['id'] = $pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('新規作成に失敗しました: ' . $e->getMessage());
        }
    }

    // 出力バッファをクリア
    ob_clean();

    // ヘッダーを設定
    header('Content-Type: application/json; charset=utf-8');

    // 成功レスポンスを返す
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $data['id'],
            'date' => $data['date'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null
        ]
    ]);

} catch (Exception $e) {
    // 出力バッファをクリア
    ob_clean();

    // エラーログを記録
    error_log('Error in save_lesson.php: ' . $e->getMessage());

    // ヘッダーを設定
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);

    // エラーレスポンスを返す
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// 出力バッファをフラッシュ
ob_end_flush();
?>