<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 生徒情報の取得
    $stmt = $pdo->query("
        SELECT
            s.id,
            s.name,
            s.course_id,
            c.name as course_name
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.id
    ");

    echo "=== 生徒情報 ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . "\n";
        echo "名前: " . $row['name'] . "\n";
        echo "コースID: " . ($row['course_id'] ?: 'なし') . "\n";
        echo "コース名: " . ($row['course_name'] ?: 'なし') . "\n";
        echo "-------------------\n";
    }

    // コース情報の取得
    $stmt = $pdo->query("SELECT * FROM courses");

    echo "\n=== 利用可能なコース ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . "\n";
        echo "コース名: " . $row['name'] . "\n";
        echo "-------------------\n";
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>