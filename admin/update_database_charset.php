<?php
require_once '../config/database.php';

try {
    // まず現在のデータベースの文字セットを確認
    $stmt = $pdo->query("SELECT @@character_set_database, @@collation_database");
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "現在の設定:<br>";
    echo "文字セット: " . $current['@@character_set_database'] . "<br>";
    echo "照合順序: " . $current['@@collation_database'] . "<br><br>";

    // データベースをutf8に変更
    $pdo->exec("ALTER DATABASE " . DB_NAME . " CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "データベースの文字コードをutf8に変更しました。<br><br>";

    // 既存のテーブルの文字セットを変更
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $pdo->exec("ALTER TABLE `" . $table . "` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
        echo "テーブル " . $table . " の文字コードを変更しました。<br>";
    }

    echo "<br>すべてのテーブルの文字コードの更新が完了しました。<br>";

    // 最終的な設定を確認
    $stmt = $pdo->query("SELECT @@character_set_database, @@collation_database");
    $final = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<br>更新後の設定:<br>";
    echo "文字セット: " . $final['@@character_set_database'] . "<br>";
    echo "照合順序: " . $final['@@collation_database'] . "<br>";

} catch (PDOException $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "<br>";
    echo "エラーコード: " . $e->getCode() . "<br>";
    error_log("Database charset update error: " . $e->getMessage());
}