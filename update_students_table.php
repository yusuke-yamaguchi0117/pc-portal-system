<?php
require_once 'config/database.php';

try {
    // トランザクションを開始
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // 各カラムの追加（存在しない場合のみ）
    $columns = [
        'nickname' => "VARCHAR(50) DEFAULT NULL COMMENT 'ニックネーム'",
        'gender' => "VARCHAR(10) DEFAULT NULL COMMENT '性別'",
        'blood_type' => "VARCHAR(10) DEFAULT NULL COMMENT '血液型'",
        'birthday' => "DATE DEFAULT NULL COMMENT '誕生日'",
        'note' => "TEXT DEFAULT NULL COMMENT '備考'",
        'photo' => "VARCHAR(255) DEFAULT NULL COMMENT 'プロフィール画像'",
        'school_name' => "VARCHAR(100) DEFAULT NULL COMMENT '学校名'",
        'grade' => "VARCHAR(10) DEFAULT NULL COMMENT '学年'"
    ];

    $success = true;
    foreach ($columns as $column => $definition) {
        try {
            // カラムが存在するかチェック
            $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE students ADD COLUMN $column $definition");
                echo "カラム '$column' を追加しました。<br>";
            } else {
                echo "カラム '$column' は既に存在します。<br>";
            }
        } catch (PDOException $e) {
            $success = false;
            echo "カラム '$column' の追加中にエラーが発生しました: " . $e->getMessage() . "<br>";
        }
    }

    // トランザクションの状態に応じて処理
    if ($pdo->inTransaction()) {
        if ($success) {
            $pdo->commit();
            echo "テーブルの更新が完了しました。";
        } else {
            $pdo->rollBack();
            echo "エラーが発生したため、変更をロールバックしました。";
        }
    }

} catch (Exception $e) {
    // トランザクションがアクティブな場合のみロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "エラーが発生しました: " . $e->getMessage();
}