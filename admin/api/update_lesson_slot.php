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

    if (!$data) {
        throw new Exception('無効なリクエストデータです');
    }

    // 必須パラメータのチェック
    if (!isset($data['date']) || !isset($data['status'])) {
        throw new Exception('日付とステータスは必須です');
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // テーブル構造の確認と必要なカラムの追加
    try {
        // カラムの存在確認と追加のための関数
        function columnExists($pdo, $table, $column)
        {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([DB_NAME, $table, $column]);
            return (bool) $stmt->fetchColumn();
        }

        // student_idカラムをNULL許容に変更
        $pdo->exec("ALTER TABLE lesson_slots MODIFY student_id INT NULL");

        // start_timeカラムをNULL許容に変更
        $pdo->exec("ALTER TABLE lesson_slots MODIFY start_time TIME NULL");

        // end_timeカラムをNULL許容に変更
        $pdo->exec("ALTER TABLE lesson_slots MODIFY end_time TIME NULL");

        // created_atカラムの追加（存在しない場合のみ）
        if (!columnExists($pdo, 'lesson_slots', 'created_at')) {
            $pdo->exec("ALTER TABLE lesson_slots ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }

        // updated_atカラムの追加（存在しない場合のみ）
        if (!columnExists($pdo, 'lesson_slots', 'updated_at')) {
            $pdo->exec("ALTER TABLE lesson_slots ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    } catch (PDOException $e) {
        // テーブル構造の変更に失敗した場合はログに記録
        error_log('Table structure modification failed: ' . $e->getMessage());
    }

    // テーブル構造を確認
    $stmt = $pdo->query("DESCRIBE lesson_calendar");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log('Lesson Calendar columns: ' . print_r($columns, true));

    // トランザクション開始
    $pdo->beginTransaction();

    // 生徒に紐づく予定の場合はlesson_slotsを更新
    if (!empty($data['student_id'])) {
        if (empty($data['id'])) {
            // 新規作成の場合
            $stmt = $pdo->prepare("
                INSERT INTO lesson_slots
                (date, status, student_id, start_time, end_time)
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['date'],
                $data['status'],
                $data['student_id'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            ]);
        } else {
            // 更新の場合
            $stmt = $pdo->prepare("
                UPDATE lesson_slots
                SET
                    status = ?,
                    start_time = ?,
                    end_time = ?
                WHERE id = ? AND student_id = ?
            ");
            $result = $stmt->execute([
                $data['status'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null,
                $data['id'],
                $data['student_id']
            ]);
        }
    }
    // 生徒に紐づかない予定（休みなど）の場合はlesson_calendarを更新
    else {
        if (empty($data['id'])) {
            // 新規作成の場合
            $stmt = $pdo->prepare("
                INSERT INTO lesson_calendar
                (lesson_date, lesson_type, note)
                VALUES (?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['date'],
                $data['status'],
                $data['note'] ?? null
            ]);
        } else {
            // 更新の場合
            $stmt = $pdo->prepare("
                UPDATE lesson_calendar
                SET
                    lesson_type = ?,
                    note = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['status'],
                $data['note'] ?? null,
                $data['id']
            ]);
        }
    }

    if ($result) {
        $pdo->commit();
        // 成功時は200ステータスコードを明示的に設定
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => empty($data['id']) ? '新規予定を作成しました' : '予定を更新しました'
        ]);
    } else {
        throw new Exception('データの保存に失敗しました');
    }

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラーが発生しました: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('General Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}