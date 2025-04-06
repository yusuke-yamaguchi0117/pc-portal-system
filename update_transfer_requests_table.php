<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // テーブルが存在するか確認
    $stmt = $pdo->query("SHOW TABLES LIKE 'transfer_requests'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        // カラムの存在確認
        $stmt = $pdo->query("SHOW COLUMNS FROM transfer_requests LIKE 'lesson_slot_id'");
        $columnExists = $stmt->rowCount() > 0;

        if (!$columnExists) {
            // lesson_slot_idカラムを追加
            echo "lesson_slot_idカラムが見つかりません。追加します。\n";
            $pdo->exec("ALTER TABLE transfer_requests ADD COLUMN lesson_slot_id INT NOT NULL AFTER student_id");
            echo "lesson_slot_idカラムを追加しました。\n";
        } else {
            echo "lesson_slot_idカラムは既に存在します。\n";
        }
    } else {
        // テーブルの作成
        echo "transfer_requestsテーブルが見つかりません。作成します。\n";
        $sql = "
        CREATE TABLE transfer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            lesson_slot_id INT NOT NULL,
            transfer_date DATE NOT NULL,
            transfer_start_time TIME NOT NULL,
            transfer_end_time TIME NOT NULL,
            reason TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            reject_reason TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($sql);
        echo "transfer_requestsテーブルを作成しました。\n";
    }

    // テーブル構造の確認
    $sql = "DESCRIBE transfer_requests";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== 更新後のtransfer_requestsテーブルの構造 ===\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . ($column['Null'] === 'YES' ? 'NULL可' : '必須') . "\n";
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>