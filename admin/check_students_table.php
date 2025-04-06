<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=pc_kakogawa_sys;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->query("SHOW COLUMNS FROM students");
    $columns = $stmt->fetchAll();

    echo "<pre>";
    foreach ($columns as $column) {
        print_r($column);
    }
    echo "</pre>";

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
}
?>