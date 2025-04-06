<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // transfer_requestsテーブルの構造を確認
    $sql = "DESCRIBE transfer_requests";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== transfer_requestsテーブルの構造 ===\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . ($column['Null'] === 'YES' ? 'NULL可' : '必須') . "\n";
    }

    // テーブル内のデータをサンプル確認
    $sql = "SELECT * FROM transfer_requests LIMIT 5";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== transfer_requestsテーブルのサンプルデータ ===\n";
    if (empty($data)) {
        echo "データはありません\n";
    } else {
        foreach ($data as $row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>