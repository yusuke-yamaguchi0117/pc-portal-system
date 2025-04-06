<?php
require_once '../config/database.php';

try {
    // 保護者と生徒の紐づけを挿入
    $stmt = $pdo->prepare("INSERT INTO student_parent (parent_id, student_id) VALUES (?, ?)");
    $stmt->execute([5, 1]); // parent_id: 5, student_id: 1

    echo "データの挿入が完了しました。";

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
}