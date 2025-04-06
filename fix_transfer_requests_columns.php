<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== transfer_requestsテーブルの列チェックと修正 ===\n\n";

    // 必要なカラムのリスト
    $required_columns = [
        'student_id' => 'INT NOT NULL',
        'lesson_slot_id' => 'INT NOT NULL',
        'transfer_date' => 'DATE NOT NULL',
        'transfer_start_time' => 'TIME NOT NULL',
        'transfer_end_time' => 'TIME NOT NULL',
        'reason' => 'TEXT',
        'status' => "ENUM('pending','approved','rejected') DEFAULT 'pending'",
        'reject_reason' => 'TEXT',
        'created_at' => 'DATETIME NOT NULL',
        'updated_at' => 'DATETIME NOT NULL'
    ];

    // 現在のカラム構造を取得
    $stmt = $pdo->query("DESCRIBE transfer_requests");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[$row['Field']] = $row['Type'];
    }

    // テーブルが存在するかどうかを確認
    if (empty($existing_columns)) {
        echo "transfer_requestsテーブルが存在しません。新規作成します。\n";

        $sql = "CREATE TABLE transfer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,";

        foreach ($required_columns as $column => $type) {
            $sql .= "\n            $column $type,";
        }

        // 最後のカンマを削除
        $sql = rtrim($sql, ',');

        $sql .= "\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $pdo->exec($sql);
        echo "テーブルを作成しました。\n";
    } else {
        echo "既存のカラム:\n";
        foreach ($existing_columns as $column => $type) {
            echo "- $column ($type)\n";
        }

        echo "\n不足しているカラムを追加します:\n";
        foreach ($required_columns as $column => $type) {
            if (!isset($existing_columns[$column])) {
                $sql = "ALTER TABLE transfer_requests ADD COLUMN $column $type";
                $pdo->exec($sql);
                echo "- $column ($type) を追加しました\n";
            }
        }
    }

    // 変更後のテーブル構造を確認
    echo "\n=== 更新後のtransfer_requestsテーブル構造 ===\n";
    $stmt = $pdo->query("DESCRIBE transfer_requests");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL可' : '必須') . "\n";
    }

    echo "\n処理が完了しました。\n";

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>