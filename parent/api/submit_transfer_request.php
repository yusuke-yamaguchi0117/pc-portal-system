<?php
require_once '../../config/database.php';
require_once '../../auth.php';

header('Content-Type: application/json');

// デバッグ情報
error_log('Debug - SESSION: ' . json_encode($_SESSION));
error_log('Debug - POST: ' . json_encode($_POST));

// 認証チェック（緩和）
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '認証が必要です。再ログインしてください。']);
    exit;
}

// POSTデータチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不正なリクエストメソッドです']);
    exit;
}

// 必須パラメータチェック
$required_params = ['student_id', 'transfer_date'];
foreach ($required_params as $param) {
    if (!isset($_POST[$param]) || empty($_POST[$param])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'パラメータが不足しています: ' . $param]);
        exit;
    }
}

$parent_id = $_SESSION['user_id'];
$student_id = intval($_POST['student_id']);
$lesson_date = $_POST['lesson_date'];
$transfer_date = $_POST['transfer_date'];
$transfer_start_time = isset($_POST['transfer_start_time']) ? $_POST['transfer_start_time'] : '16:00:00';
$transfer_end_time = isset($_POST['transfer_end_time']) ? $_POST['transfer_end_time'] : '17:00:00';
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';

error_log("Debug - 処理パラメータ: parent_id=$parent_id, student_id=$student_id, lesson_date=$lesson_date, transfer_date=$transfer_date");

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // トランザクション開始
    $pdo->beginTransaction();

    // lesson_slot_idの有無をチェック
    $lesson_slot_id = isset($_POST['lesson_slot_id']) ? intval($_POST['lesson_slot_id']) : null;

    // lesson_slot_idがない場合は、日付から授業情報を取得
    if (!$lesson_slot_id && isset($_POST['lesson_date'])) {
        // 日付から時間を除去
        $lesson_date_clean = preg_replace('/\s*\(.+\).*$/', '', $_POST['lesson_date']);

        error_log("Debug - lesson_date_clean: $lesson_date_clean");

        // lesson_slotsからIDを取得
        $stmt = $pdo->prepare("
            SELECT id
            FROM lesson_slots
            WHERE student_id = ?
            AND date = ?
            AND status = 'scheduled'
            LIMIT 1
        ");

        $stmt->execute([$student_id, $lesson_date_clean]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Debug - lesson_slots 検索結果: " . json_encode($result));

        if ($result) {
            $lesson_slot_id = $result['id'];
        }
    }

    // lesson_slot_idがない場合はエラー
    if (!$lesson_slot_id) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '授業情報が取得できません。別の日程を選択してください。']);
        exit;
    }

    error_log("Debug - 確定したlesson_slot_id: $lesson_slot_id");

    // 予約スロットの確認
    $stmt = $pdo->prepare("
        SELECT id
        FROM lesson_slots
        WHERE id = ? AND student_id = ? AND status = 'scheduled'
    ");
    $stmt->execute([$lesson_slot_id, $student_id]);

    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '指定された授業予定が見つかりません']);
        exit;
    }

    // 振替日に空きがあるか確認
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as booked_count
        FROM lesson_slots
        WHERE date = ? AND start_time = ? AND status = 'scheduled'
    ");
    $stmt->execute([$transfer_date, $transfer_start_time]);
    $booked_count = intval($stmt->fetchColumn());

    // コースに基づく定員を取得
    $stmt = $pdo->prepare("
        SELECT s.day_of_week, s.capacity
        FROM students st
        JOIN schedules s ON st.course_id = s.course_id
        WHERE st.id = ? AND s.day_of_week = DAYOFWEEK(?)
        AND s.start_time = ?
    ");
    $stmt->execute([$student_id, $transfer_date, $transfer_start_time]);
    $schedule_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule_info) {
        error_log("Debug - コース時間割が見つかりません: student_id=$student_id, transfer_date=$transfer_date, transfer_start_time=$transfer_start_time");
        // 定員を仮設定
        $capacity = 4;
    } else {
        $capacity = intval($schedule_info['capacity']);
    }

    if ($booked_count >= $capacity) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '指定された振替日時はすでに定員に達しています']);
        exit;
    }

    // 重複申請チェック
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM transfer_requests
            WHERE student_id = ? AND lesson_slot_id = ? AND status = 'pending'
        ");
        $stmt->execute([$student_id, $lesson_slot_id]);

        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'この授業予定の振替申請はすでに提出されています']);
            exit;
        }
    } catch (PDOException $e) {
        // lesson_slot_idカラムが存在しない場合
        if (strpos($e->getMessage(), "Unknown column 'lesson_slot_id'") !== false) {
            error_log("Warning: lesson_slot_idカラムが存在しません。中間対応を適用します。");
            // 代替チェック - student_idと日付だけでチェック
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM transfer_requests
                WHERE student_id = ? AND transfer_date = ? AND status = 'pending'
            ");
            $stmt->execute([$student_id, $transfer_date]);

            if ($stmt->fetchColumn() > 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'この生徒の振替申請はすでに提出されています']);
                exit;
            }
        } else {
            // その他のエラーは再スロー
            throw $e;
        }
    }

    // 振替申請を登録（テーブル構造に合わせた処理）
    try {
        // テーブル構造を確認
        $stmt = $pdo->query("DESCRIBE transfer_requests");
        $existing_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $row['Field'];
        }

        error_log("Debug - 既存のカラム: " . implode(", ", $existing_columns));

        // INSERT文を動的に構築
        $columns = [];
        $placeholders = [];
        $values = [];

        // 基本項目（必ず入れる）
        if (in_array('student_id', $existing_columns)) {
            $columns[] = 'student_id';
            $placeholders[] = '?';
            $values[] = $student_id;
        }

        // 親IDがあれば設定
        if (in_array('parent_id', $existing_columns)) {
            $columns[] = 'parent_id';
            $placeholders[] = '?';
            $values[] = $parent_id;
        }

        // レッスンスロットID
        if (in_array('lesson_slot_id', $existing_columns)) {
            $columns[] = 'lesson_slot_id';
            $placeholders[] = '?';
            $values[] = $lesson_slot_id;
        }

        // 振替元日付
        if (in_array('lesson_date', $existing_columns)) {
            $columns[] = 'lesson_date';
            $placeholders[] = '?';
            $values[] = $lesson_date_clean; // 元のレッスン日
        } else if (in_array('original_date', $existing_columns)) {
            $columns[] = 'original_date';
            $placeholders[] = '?';
            $values[] = $lesson_date_clean; // 元のレッスン日
        }

        // 振替希望日
        if (in_array('transfer_date', $existing_columns)) {
            $columns[] = 'transfer_date';
            $placeholders[] = '?';
            $values[] = $transfer_date;
        } else if (in_array('requested_date', $existing_columns)) {
            $columns[] = 'requested_date';
            $placeholders[] = '?';
            $values[] = $transfer_date;
        }

        // 振替希望時間
        if (in_array('transfer_start_time', $existing_columns)) {
            $columns[] = 'transfer_start_time';
            $placeholders[] = '?';
            $values[] = $transfer_start_time;
        } else if (in_array('requested_time', $existing_columns)) {
            $columns[] = 'requested_time';
            $placeholders[] = '?';
            $values[] = $transfer_start_time;
        }

        // transfer_end_timeはrequested_timeに含まれないため、存在する場合のみ追加
        if (in_array('transfer_end_time', $existing_columns)) {
            $columns[] = 'transfer_end_time';
            $placeholders[] = '?';
            $values[] = $transfer_end_time;
        }

        // 理由
        if (in_array('reason', $existing_columns) && !empty($reason)) {
            $columns[] = 'reason';
            $placeholders[] = '?';
            $values[] = $reason;
        }

        // ステータス
        if (in_array('status', $existing_columns)) {
            $columns[] = 'status';
            $placeholders[] = '?';
            $values[] = 'pending';
        }

        // タイムスタンプ
        if (in_array('created_at', $existing_columns)) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        if (in_array('updated_at', $existing_columns)) {
            $columns[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }

        if (empty($columns)) {
            throw new Exception('挿入可能なカラムがありません');
        }

        // SQL文を構築
        $sql = "INSERT INTO transfer_requests (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        error_log("Debug - 実行するSQL: " . $sql);
        error_log("Debug - バインド値: " . json_encode($values));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $request_id = $pdo->lastInsertId();
        error_log("Debug - 挿入成功: ID=" . $request_id);

    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        // 既存のテーブル構造に直接合わせた緊急対応
        try {
            // テーブルのカラム確認
            $stmt = $pdo->query("SHOW COLUMNS FROM transfer_requests");
            $table_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $table_columns[] = $row['Field'];
            }

            error_log("Emergency - テーブルカラム: " . implode(", ", $table_columns));

            // 基本的な挿入SQL
            $sql = "INSERT INTO transfer_requests (";
            $values_part = " VALUES (";
            $params = [];

            // student_id（必須）
            $sql .= "student_id";
            $values_part .= "?";
            $params[] = $student_id;

            // lesson_slot_id（あれば）
            if (in_array('lesson_slot_id', $table_columns)) {
                $sql .= ", lesson_slot_id";
                $values_part .= ", ?";
                $params[] = $lesson_slot_id;
            }

            // parent_id（あれば）
            if (in_array('parent_id', $table_columns)) {
                $sql .= ", parent_id";
                $values_part .= ", ?";
                $params[] = $parent_id;
            }

            // lesson_date（あれば）
            if (in_array('lesson_date', $table_columns)) {
                $sql .= ", lesson_date";
                $values_part .= ", ?";
                $params[] = $lesson_date_clean;
            }
            // original_date（あれば）
            else if (in_array('original_date', $table_columns)) {
                $sql .= ", original_date";
                $values_part .= ", ?";
                $params[] = $lesson_date_clean;
            }

            // transfer_date（あれば）
            if (in_array('transfer_date', $table_columns)) {
                $sql .= ", transfer_date";
                $values_part .= ", ?";
                $params[] = $transfer_date;
            }
            // requested_date（あれば）
            else if (in_array('requested_date', $table_columns)) {
                $sql .= ", requested_date";
                $values_part .= ", ?";
                $params[] = $transfer_date;
            }

            // transfer_start_time（あれば）
            if (in_array('transfer_start_time', $table_columns)) {
                $sql .= ", transfer_start_time";
                $values_part .= ", ?";
                $params[] = $transfer_start_time;
            }
            // requested_time（あれば）
            else if (in_array('requested_time', $table_columns)) {
                $sql .= ", requested_time";
                $values_part .= ", ?";
                $params[] = $transfer_start_time;
            }

            // transfer_end_time（あれば）
            if (in_array('transfer_end_time', $table_columns)) {
                $sql .= ", transfer_end_time";
                $values_part .= ", ?";
                $params[] = $transfer_end_time;
            }

            // status（あれば）
            if (in_array('status', $table_columns)) {
                $sql .= ", status";
                $values_part .= ", ?";
                $params[] = 'pending';
            }

            // reason（あれば）
            if (in_array('reason', $table_columns) && !empty($reason)) {
                $sql .= ", reason";
                $values_part .= ", ?";
                $params[] = $reason;
            }

            // created_at（あれば）
            if (in_array('created_at', $table_columns)) {
                $sql .= ", created_at";
                $values_part .= ", NOW()";
            }

            // updated_at（あれば）
            if (in_array('updated_at', $table_columns)) {
                $sql .= ", updated_at";
                $values_part .= ", NOW()";
            }

            $sql .= ")" . $values_part . ")";

            error_log("Emergency - SQL: " . $sql);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $request_id = $pdo->lastInsertId();
            error_log("Emergency - 挿入成功: ID=" . $request_id);
        } catch (Exception $e2) {
            error_log("Emergency - 最終エラー: " . $e2->getMessage());
            throw $e2; // 元のエラーを再スロー
        }
    }

    // トランザクション確定
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '振替申請を受け付けました',
        'request_id' => $request_id
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('General Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'エラーが発生しました']);
}
?>