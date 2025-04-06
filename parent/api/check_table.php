<?php
require_once '../../config/database.php';

try {
    // student_parentテーブルの内容を確認
    $stmt = $pdo->query("SELECT sp.*, u.username as parent_name, s.name as student_name
                        FROM student_parent sp
                        JOIN users u ON sp.parent_id = u.id
                        JOIN students s ON sp.student_id = s.id");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "student_parentテーブルの内容:\n";
    print_r($results);

    // テーブル構造も確認
    $stmt = $pdo->query("DESCRIBE student_parent");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n\nテーブル構造:\n";
    print_r($structure);

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}