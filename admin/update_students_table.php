<?php
require_once '../config/database.php';

try {
    // gradeカラムをNULLを許可するように変更
    $sql = "ALTER TABLE students MODIFY COLUMN grade VARCHAR(10) DEFAULT NULL COMMENT '学年'";
    $pdo->exec($sql);
    echo "gradeカラムの変更が完了しました。";
} catch (PDOException $e) {
    echo "エラーが発生しました: " . $e->getMessage();
}
?>