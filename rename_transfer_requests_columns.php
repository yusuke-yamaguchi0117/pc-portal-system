<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== transfer_requestsテーブルのカラム名標準化 ===\n\n";

    // カラム名のマッピング
    $column_mappings = [
        'original_date' => 'lesson_date',
        'requested_date' => 'transfer_date',
        'requested_time' => 'transfer_start_time'
    ];

    // 現在のカラム構造を取得
    $stmt = $pdo->query("DESCRIBE transfer_requests");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }

    echo "現在のカラム: " . implode(", ", $existing_columns) . "\n\n";

    // バックアップテーブルの作成
    echo "バックアップテーブルを作成します...\n";

    // バックアップテーブルが既に存在する場合は削除
    $pdo->exec("DROP TABLE IF EXISTS transfer_requests_backup");

    // 現在のテーブル構造を使用してバックアップテーブルを作成
    $pdo->exec("CREATE TABLE transfer_requests_backup LIKE transfer_requests");

    // 明示的にカラム名を指定してデータをコピー
    $columns = implode(", ", $existing_columns);
    $pdo->exec("INSERT INTO transfer_requests_backup ($columns) SELECT $columns FROM transfer_requests");

    echo "バックアップが完了しました。\n\n";

    // カラム名の変更
    echo "カラム名を標準化します...\n";
    foreach ($column_mappings as $old_name => $new_name) {
        if (in_array($old_name, $existing_columns) && !in_array($new_name, $existing_columns)) {
            // カラムのデータ型を取得
            $stmt = $pdo->prepare("SHOW COLUMNS FROM transfer_requests WHERE Field = ?");
            $stmt->execute([$old_name]);
            $column_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $type = $column_info['Type'];
            $nullable = $column_info['Null'] === 'YES' ? '' : 'NOT NULL';
            $default = $column_info['Default'] ? "DEFAULT '" . $column_info['Default'] . "'" : '';

            // カラム名変更
            $sql = "ALTER TABLE transfer_requests CHANGE COLUMN `$old_name` `$new_name` $type $nullable $default";
            $pdo->exec($sql);
            echo "  カラム $old_name を $new_name に変更しました\n";
        } else if (in_array($old_name, $existing_columns) && in_array($new_name, $existing_columns)) {
            echo "  カラム $old_name と $new_name の両方が存在するため、変更しませんでした\n";
        } else if (!in_array($old_name, $existing_columns)) {
            echo "  カラム $old_name が存在しないためスキップしました\n";
        }
    }

    // transfer_end_timeカラムの追加
    if (!in_array('transfer_end_time', $existing_columns)) {
        echo "\ntransfer_end_timeカラムを追加します...\n";

        // 最新のカラム名を再取得（カラム名が変更されている可能性があるため）
        $stmt = $pdo->query("DESCRIBE transfer_requests");
        $updated_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $updated_columns[] = $row['Field'];
        }

        // transfer_start_timeの1時間後の値を設定
        $pdo->exec("ALTER TABLE transfer_requests ADD COLUMN transfer_end_time TIME AFTER transfer_start_time");

        // 更新されたカラム名を使用
        if (in_array('transfer_start_time', $updated_columns)) {
            $pdo->exec("UPDATE transfer_requests SET transfer_end_time = ADDTIME(transfer_start_time, '01:00:00')");
            echo "transfer_end_timeカラムを追加し、transfer_start_timeの1時間後の値を設定しました\n";
        } else {
            echo "transfer_end_timeカラムを追加しましたが、時間データが設定できませんでした\n";
        }
    }

    // 変更後のテーブル構造を確認
    $stmt = $pdo->query("DESCRIBE transfer_requests");
    $new_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $new_columns[] = $row['Field'];
    }

    echo "\n変更後のカラム: " . implode(", ", $new_columns) . "\n";
    echo "\n処理が完了しました。\n";

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>