<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // `course`カラムの値を`course_id`に設定
    $stmt = $pdo->prepare("
        UPDATE students
        SET course_id = course
        WHERE course_id IS NULL
    ");

    $stmt->execute();

    echo "生徒のコースIDを更新しました。\n";

    // 更新後の情報を確認
    $stmt = $pdo->query("
        SELECT
            s.id,
            s.name,
            s.course_id,
            c.name as course_name
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.id
    ");

    echo "\n=== 更新後の生徒情報 ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . "\n";
        echo "名前: " . $row['name'] . "\n";
        echo "コースID: " . $row['course_id'] . "\n";
        echo "コース名: " . $row['course_name'] . "\n";
        echo "-------------------\n";
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>