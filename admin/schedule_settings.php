<?php
require_once '../config/database.php';
require_once '../auth.php';

// エラーメッセージ用変数
$error = '';
$success = '';

// 新規登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $lesson_day = $_POST['lesson_day'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $lesson_time = $_POST['lesson_time'] ?? '';

    // バリデーション
    if (empty($lesson_day) || empty($course_id) || empty($lesson_time)) {
        $error = '全ての項目を入力してください。';
    } else {
        try {
            // studentsテーブルに授業設定を追加
            $stmt = $pdo->prepare("
                INSERT INTO schedules (
                    day_of_week,
                    course_id,
                    time,
                    capacity,
                    created_at,
                    updated_at
                ) VALUES (
                    CASE ?
                        WHEN '月曜' THEN 1
                        WHEN '火曜' THEN 2
                        WHEN '水曜' THEN 3
                        WHEN '木曜' THEN 4
                        WHEN '金曜' THEN 5
                        WHEN '土曜' THEN 6
                        WHEN '日曜' THEN 0
                    END,
                    ?,
                    ?,
                    4,
                    NOW(),
                    NOW()
                )
            ");
            $stmt->execute([$lesson_day, $course_id, $lesson_time]);
            $success = '授業時間帯を登録しました。';
        } catch (PDOException $e) {
            $error = '登録に失敗しました。';
            error_log($e->getMessage());
        }
    }
}

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? '';
    if (!empty($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$id]);
            $success = '授業時間帯を削除しました。';
        } catch (PDOException $e) {
            $error = '削除に失敗しました。';
            error_log($e->getMessage());
        }
    }
}

// 既存の授業時間帯を取得
$schedules = []; // 初期化
try {
    $stmt = $pdo->query("
        SELECT
            s.*,
            c.name as course_name,
            CASE s.day_of_week
                WHEN 0 THEN '日曜'
                WHEN 1 THEN '月曜'
                WHEN 2 THEN '火曜'
                WHEN 3 THEN '水曜'
                WHEN 4 THEN '木曜'
                WHEN 5 THEN '金曜'
                WHEN 6 THEN '土曜'
            END as weekday_name,
            TIME_FORMAT(s.start_time, '%H:%i') as formatted_time
        FROM schedules s
        JOIN courses c ON s.course_id = c.id
        ORDER BY s.day_of_week, s.start_time
    ");
    if ($stmt) {
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'データの取得に失敗しました。';
    error_log($e->getMessage());
}

// コース一覧を取得
$courses = []; // 初期化
try {
    $stmt = $pdo->query("SELECT id, name FROM courses ORDER BY id");
    if ($stmt) {
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'コースデータの取得に失敗しました。';
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業時間帯設定 - プログラ加古川南校</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            margin-left: 240px;
            padding: 20px;
        }

        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

        .card {
            margin: 0;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php require_once '../includes/header.php'; ?>
    <?php require_once '../includes/sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">授業時間帯設定</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>

                            <!-- 登録済みの授業時間帯一覧 -->
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>曜日</th>
                                            <th>コース</th>
                                            <th>時間</th>
                                            <th>定員</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $schedule): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($schedule['weekday_name']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['formatted_time']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['capacity']); ?>名</td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('本当に削除しますか？')">削除</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 新規追加フォーム -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">新規授業時間帯の追加</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="lesson_day" class="form-label">曜日</label>
                                                <select class="form-select" id="lesson_day" name="lesson_day" required>
                                                    <option value="">選択してください</option>
                                                    <option value="月曜">月曜日</option>
                                                    <option value="火曜">火曜日</option>
                                                    <option value="水曜">水曜日</option>
                                                    <option value="木曜">木曜日</option>
                                                    <option value="金曜">金曜日</option>
                                                    <option value="土曜">土曜日</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="course_id" class="form-label">コース</label>
                                                <select class="form-select" id="course_id" name="course_id" required>
                                                    <option value="">選択してください</option>
                                                    <?php foreach ($courses as $course): ?>
                                                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                                                            <?php echo htmlspecialchars($course['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="lesson_time" class="form-label">時間</label>
                                                <select class="form-select" id="lesson_time" name="lesson_time" required>
                                                    <option value="">選択してください</option>
                                                    <option value="09:30:00">09:30</option>
                                                    <option value="10:45:00">10:45</option>
                                                    <option value="13:00:00">13:00</option>
                                                    <option value="16:00:00">16:00</option>
                                                    <option value="17:15:00">17:15</option>
                                                    <option value="18:30:00">18:30</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary">登録</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>