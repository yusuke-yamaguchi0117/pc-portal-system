<?php
ob_start();
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

// 編集モードの場合、生徒情報を取得
$student = null;
if (isset($_GET['id'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT s.*, c.id as course_id, c.name as course_name
            FROM students s
            LEFT JOIN courses c ON s.course = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            header('Location: students.php');
            exit;
        }
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

// 授業時間帯の取得
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT
            s.id,
            CAST(s.course_id AS CHAR) as course_id,
            s.day_of_week,
            s.start_time,
            c.name as course_name,
            CASE s.day_of_week
                WHEN 0 THEN '日'
                WHEN 1 THEN '月'
                WHEN 2 THEN '火'
                WHEN 3 THEN '水'
                WHEN 4 THEN '木'
                WHEN 5 THEN '金'
                WHEN 6 THEN '土'
            END as weekday
        FROM schedules s
        JOIN courses c ON s.course_id = c.id
        ORDER BY s.day_of_week, s.start_time
    ");
    $lesson_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}

// コース一覧を取得
try {
    $stmt = $pdo->query("SELECT id, name FROM courses ORDER BY id");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("コースデータの取得に失敗しました: " . $e->getMessage());
}

// 曜日の配列を作成（表示用）
$weekday_display = [
    '月' => '月曜',
    '火' => '火曜',
    '水' => '水曜',
    '木' => '木曜',
    '金' => '金曜',
    '土' => '土曜'
];

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 授業時間帯の処理
        $lesson_slot = json_decode($_POST['lesson_slot'], true);
        $lesson_day = $lesson_slot['day'];
        $lesson_time = $lesson_slot['time'];

        // 生徒情報の保存
        if (isset($_GET['id'])) {
            // 更新処理
            $stmt = $pdo->prepare("UPDATE students SET
                name = ?,
                furigana = ?,
                school = ?,
                grade = ?,
                gender = ?,
                course = ?,
                lesson_day = ?,
                lesson_time = ?,
                join_date = ?,
                me_id = ?,
                me_password = ?,
                device_number = ?,
                status = ?
                WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['furigana'],
                $_POST['school'],
                $_POST['grade'],
                $_POST['gender'],
                $_POST['course'],
                $lesson_day,
                $lesson_time,
                $_POST['join_date'],
                $_POST['me_id'],
                $_POST['me_password'],
                $_POST['device_number'],
                $_POST['status'],
                $_GET['id']
            ]);
            $student_id = $_GET['id'];

            // 更新開始日が指定されている場合、その日以降の予定を更新
            if (isset($_POST['update_start_date'])) {
                $update_start_date = $_POST['update_start_date'];

                // 過去6日間の日付を計算
                $start_date = new DateTime($update_start_date);
                $start_date->modify('-6 days');
                $start_date_str = $start_date->format('Y-m-d');

                // 既存の予定を削除（typeが'transfer'のものは除く）
                $stmt = $pdo->prepare("DELETE FROM lesson_slots
                    WHERE student_id = ?
                    AND date >= ?
                    AND type != 'transfer'");
                $stmt->execute([$student_id, $start_date_str]);

                // 新しい予定を生成（選択された日から）
                generateLessonSlots($pdo, $student_id, $lesson_day, $lesson_time, $update_start_date);
            }
        } else {
            // 新規登録処理
            $stmt = $pdo->prepare("INSERT INTO students (
                name, furigana, school, grade, gender, course, lesson_day, lesson_time,
                join_date, me_id, me_password, device_number, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['furigana'],
                $_POST['school'],
                $_POST['grade'],
                $_POST['gender'],
                $_POST['course'],
                $lesson_day,
                $lesson_time,
                $_POST['join_date'],
                $_POST['me_id'],
                $_POST['me_password'],
                $_POST['device_number'],
                $_POST['status']
            ]);
            $student_id = $pdo->lastInsertId();

            // 新規登録の場合のみ授業予定を生成
            generateLessonSlots($pdo, $student_id, $lesson_day, $lesson_time);
        }

        header('Location: students.php');
        exit;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

/**
 * 授業予定を生成する関数
 * @param PDO $pdo データベース接続
 * @param int $student_id 生徒ID
 * @param string $lesson_day 授業曜日（例：月曜）
 * @param string $lesson_time 授業時間（例：17:15）
 * @param string $start_date 開始日（オプション）
 */
function generateLessonSlots($pdo, $student_id, $lesson_day, $lesson_time, $start_date = null)
{
    // 曜日を数値に変換
    $weekday_map = [
        '日曜' => 0,
        '月曜' => 1,
        '火曜' => 2,
        '水曜' => 3,
        '木曜' => 4,
        '金曜' => 5,
        '土曜' => 6
    ];
    $target_weekday = $weekday_map[$lesson_day];

    // 開始日と終了日を設定
    $start_date_obj = $start_date ? new DateTime($start_date) : new DateTime();
    $end_date = new DateTime();
    $end_date->modify('+90 days');

    // 授業予定を生成
    $current_date = clone $start_date_obj;
    $stmt = $pdo->prepare("
        INSERT INTO lesson_slots (student_id, date, start_time, end_time, status, lesson_day, type)
        VALUES (?, ?, ?, ?, 'scheduled', ?, 'regular')
    ");

    while ($current_date <= $end_date) {
        // 指定された曜日の場合のみ予定を生成
        if ($current_date->format('w') == $target_weekday) {
            $date = $current_date->format('Y-m-d');
            $start_time = $lesson_time;

            // 終了時間を計算（開始時間から1時間後）
            $end_time = date('H:i', strtotime($lesson_time . ' +1 hour'));

            // 同じ日付に既存の予定（type='transfer'）があるかチェック
            $check_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM lesson_slots
                WHERE student_id = ?
                AND date = ?
                AND type = 'transfer'
            ");
            $check_stmt->execute([$student_id, $date]);
            $existing_transfer = $check_stmt->fetchColumn();

            // 振替予定がない場合のみ新規予定を追加
            if (!$existing_transfer) {
                try {
                    $stmt->execute([
                        $student_id,
                        $date,
                        $start_time,
                        $end_time,
                        $lesson_day
                    ]);
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        throw new Exception("{$date}の予定は既に登録されています。");
                    }
                    throw $e;
                }
            }
        }
        $current_date->modify('+1 day');
    }
}

// 授業時間のパース処理を修正
$lesson_time = '';
$lesson_hour = '';
$lesson_minute = '';

if (!empty($student['lesson_time'])) {
    // 授業時間をパース（例：16:30）
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $student['lesson_time'], $matches)) {
        $lesson_hour = $matches[1];
        $lesson_minute = $matches[2];
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-md-8 col-lg-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo isset($_GET['id']) ? '生徒情報編集' : '生徒新規登録'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">名前</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($student) ? htmlspecialchars($student['name']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="furigana" class="form-label">ふりがな</label>
                                <input type="text" class="form-control" id="furigana" name="furigana" required value="<?php echo isset($student) ? htmlspecialchars($student['furigana']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="school" class="form-label">学校名</label>
                                <input type="text" class="form-control" id="school" name="school" required value="<?php echo isset($student) ? htmlspecialchars($student['school']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="grade" class="form-label">学年</label>
                                <select class="form-select" id="grade" name="grade" required>
                                    <option value="">選択してください</option>
                                    <option value="年少" <?php echo ($student['grade'] ?? '') === '年少' ? 'selected' : ''; ?>>年少</option>
                                    <option value="年中" <?php echo ($student['grade'] ?? '') === '年中' ? 'selected' : ''; ?>>年中</option>
                                    <option value="年長" <?php echo ($student['grade'] ?? '') === '年長' ? 'selected' : ''; ?>>年長</option>
                                    <option value="小学1年" <?php echo ($student['grade'] ?? '') === '小学1年' ? 'selected' : ''; ?>>小学1年</option>
                                    <option value="小学2年" <?php echo ($student['grade'] ?? '') === '小学2年' ? 'selected' : ''; ?>>小学2年</option>
                                    <option value="小学3年" <?php echo ($student['grade'] ?? '') === '小学3年' ? 'selected' : ''; ?>>小学3年</option>
                                    <option value="小学4年" <?php echo ($student['grade'] ?? '') === '小学4年' ? 'selected' : ''; ?>>小学4年</option>
                                    <option value="小学5年" <?php echo ($student['grade'] ?? '') === '小学5年' ? 'selected' : ''; ?>>小学5年</option>
                                    <option value="小学6年" <?php echo ($student['grade'] ?? '') === '小学6年' ? 'selected' : ''; ?>>小学6年</option>
                                    <option value="中学1年" <?php echo ($student['grade'] ?? '') === '中学1年' ? 'selected' : ''; ?>>中学1年</option>
                                    <option value="中学2年" <?php echo ($student['grade'] ?? '') === '中学2年' ? 'selected' : ''; ?>>中学2年</option>
                                    <option value="中学3年" <?php echo ($student['grade'] ?? '') === '中学3年' ? 'selected' : ''; ?>>中学3年</option>
                                    <option value="高校1年" <?php echo ($student['grade'] ?? '') === '高校1年' ? 'selected' : ''; ?>>高校1年</option>
                                    <option value="高校2年" <?php echo ($student['grade'] ?? '') === '高校2年' ? 'selected' : ''; ?>>高校2年</option>
                                    <option value="高校3年" <?php echo ($student['grade'] ?? '') === '高校3年' ? 'selected' : ''; ?>>高校3年</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label">性別</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">選択してください</option>
                                    <option value="男" <?php echo isset($student) && $student['gender'] === '男' ? 'selected' : ''; ?>>男</option>
                                    <option value="女" <?php echo isset($student) && $student['gender'] === '女' ? 'selected' : ''; ?>>女</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="course" class="form-label">コース</label>
                                <select class="form-select" id="course" name="course" required>
                                    <option value="">選択してください</option>
                                    <?php foreach ($courses as $course): ?>
                                        <?php
                                        $selected = isset($student) && $student['course_id'] == $course['id'] ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo htmlspecialchars($course['id']); ?>" data-name="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">授業時間帯</label>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-primary me-3" style="width: 20px;"></i>
                                            <div class="flex-grow-1">
                                                <select class="form-select" id="lesson_slot" name="lesson_slot" required>
                                                    <option value="">コースを選択してください</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="join_date" class="form-label">入会月</label>
                                <input type="month" class="form-control" id="join_date" name="join_date" required value="<?php echo isset($student) ? htmlspecialchars($student['join_date']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="me_id" class="form-label">ME情報（ID）</label>
                                <input type="text" class="form-control" id="me_id" name="me_id" required value="<?php echo isset($student) ? htmlspecialchars($student['me_id']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="me_password" class="form-label">ME情報（PASS）</label>
                                <input type="text" class="form-control" id="me_password" name="me_password" required value="<?php echo isset($student) ? htmlspecialchars($student['me_password']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="device_number" class="form-label">使用端末</label>
                                <select class="form-select" id="device_number" name="device_number" required>
                                    <option value="">選択してください</option>
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        $selected = isset($student) && $student['device_number'] == $i ? 'selected' : '';
                                        echo "<option value=\"{$i}\" {$selected}>{$i}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">在籍ステータス</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">選択してください</option>
                                    <?php
                                    $statuses = ['在籍中', '休会中', '退会済'];
                                    foreach ($statuses as $statusOption) {
                                        $selected = isset($student) && $student['status'] === $statusOption ? 'selected' : '';
                                        echo "<option value=\"{$statusOption}\" {$selected}>{$statusOption}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo isset($_GET['id']) ? '更新' : '登録'; ?>
                                </button>
                                <a href="students.php" class="btn btn-secondary">戻る</a>
                            </div>

                            <!-- 授業予定更新確認モーダル -->
                            <div class="modal fade" id="updateScheduleModal" tabindex="-1" aria-labelledby="updateScheduleModalLabel" aria-modal="true" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="updateScheduleModalLabel">授業予定の更新確認</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>コースまたは授業時間帯が変更されました。授業予定を更新しますか？</p>
                                            <div class="mb-3">
                                                <label for="updateStartDate" class="form-label">更新開始日</label>
                                                <input type="date" class="form-control" id="updateStartDate" name="updateStartDate">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                                            <button type="button" class="btn btn-primary" id="confirmUpdate">更新する</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    // 全ての授業時間帯データ
                                    const allSlots = <?php echo json_encode($lesson_slots); ?>;
                                    console.log('All slots:', allSlots);

                                    // 現在選択されている授業時間帯（編集時）
                                    const currentSlot = <?php echo isset($student) ? json_encode([
                                        'day' => $student['lesson_day'],
                                        'time' => $student['lesson_time']
                                    ]) : 'null'; ?>;
                                    console.log('Current slot:', currentSlot);

                                    const courseSelect = document.getElementById('course');
                                    const lessonSlotSelect = document.getElementById('lesson_slot');
                                    const form = document.querySelector('form');
                                    const updateScheduleModal = new bootstrap.Modal(document.getElementById('updateScheduleModal'));
                                    const updateStartDate = document.getElementById('updateStartDate');
                                    const confirmUpdateBtn = document.getElementById('confirmUpdate');

                                    // 初期値を保存（編集モードの場合のみ）
                                    let originalCourse = null;
                                    let originalLessonSlot = null;
                                    <?php if (isset($student)): ?>
                                        originalCourse = '<?php echo $student['course']; ?>';
                                        originalLessonSlot = JSON.stringify({
                                            day: '<?php echo $student['lesson_day']; ?>',
                                            time: '<?php echo $student['lesson_time']; ?>'
                                        });
                                    <?php endif; ?>

                                    // コース選択時の処理
                                    courseSelect.addEventListener('change', function () {
                                        updateLessonSlots();
                                    });

                                    // 授業時間帯の更新
                                    function updateLessonSlots() {
                                        const selectedCourseId = courseSelect.value;
                                        console.log('Selected course ID:', selectedCourseId);

                                        // セレクトボックスをリセット
                                        lessonSlotSelect.innerHTML = '<option value="">選択してください</option>';

                                        if (!selectedCourseId) {
                                            lessonSlotSelect.disabled = true;
                                            return;
                                        }

                                        // 選択されたコースの授業時間帯のみをフィルタリング
                                        const filteredSlots = allSlots.filter(slot => {
                                            console.log('Comparing:', {
                                                slotCourseId: slot.course_id,
                                                selectedCourseId: selectedCourseId,
                                                slotType: typeof slot.course_id,
                                                selectedType: typeof selectedCourseId
                                            });
                                            return String(slot.course_id) === String(selectedCourseId);
                                        });
                                        console.log('Filtered slots:', filteredSlots);

                                        // 授業時間帯オプションを追加
                                        filteredSlots.forEach(slot => {
                                            const option = document.createElement('option');
                                            const slotValue = JSON.stringify({
                                                day: slot.weekday + '曜',
                                                time: slot.start_time
                                            });

                                            option.value = slotValue;
                                            option.textContent = `${slot.weekday}曜 ${slot.start_time.substring(0, 5)}～`;

                                            // 編集時の選択状態を復元
                                            if (currentSlot &&
                                                currentSlot.day === (slot.weekday + '曜') &&
                                                currentSlot.time === slot.start_time) {
                                                option.selected = true;
                                            }

                                            lessonSlotSelect.appendChild(option);
                                        });

                                        lessonSlotSelect.disabled = false;
                                    }

                                    // フォーム送信時の処理
                                    form.addEventListener('submit', function (e) {
                                        if (<?php echo isset($_GET['id']) ? 'true' : 'false'; ?>) {
                                            const currentCourse = courseSelect.value;
                                            const currentLessonSlot = lessonSlotSelect.value;

                                            // 実際に値が変更された場合のみモーダルを表示
                                            if (currentCourse !== originalCourse || currentLessonSlot !== originalLessonSlot) {
                                                e.preventDefault();
                                                updateScheduleModal.show();
                                            }
                                        }
                                    });

                                    // 更新確認ボタンの処理
                                    confirmUpdateBtn.addEventListener('click', function () {
                                        if (!updateStartDate.value) {
                                            alert('更新開始日を選択してください');
                                            return;
                                        }

                                        // 更新開始日をフォームに追加
                                        const startDateInput = document.createElement('input');
                                        startDateInput.type = 'hidden';
                                        startDateInput.name = 'update_start_date';
                                        startDateInput.value = updateStartDate.value;
                                        form.appendChild(startDateInput);

                                        // フォームを送信
                                        form.submit();
                                    });

                                    // 初期表示時に実行
                                    if (courseSelect.value) {
                                        updateLessonSlots();
                                    }

                                    // モーダル表示時の処理
                                    updateScheduleModal._element.addEventListener('shown.bs.modal', function () {
                                        // モーダルが表示されたら日付入力フィールドにフォーカス
                                        updateStartDate.focus();
                                    });

                                    // モーダル非表示時の処理
                                    updateScheduleModal._element.addEventListener('hidden.bs.modal', function () {
                                        // モーダルが非表示になったらフォームの送信ボタンにフォーカス
                                        form.querySelector('button[type="submit"]').focus();
                                    });
                                });
                            </script>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>