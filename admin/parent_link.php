<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parent_id'])) {
    try {
        $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

        // 入力値のバリデーション
        if (!$parent_id) {
            throw new Exception('保護者を選択してください。');
        }

        // トランザクション開始
        $pdo->beginTransaction();

        // 既存の紐づけを削除
        $stmt = $pdo->prepare("DELETE FROM parent_student WHERE parent_id = ?");
        $stmt->execute([$parent_id]);

        // 新しい紐づけを登録
        if (!empty($student_ids)) {
            $stmt = $pdo->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
            foreach ($student_ids as $student_id) {
                if (filter_var($student_id, FILTER_VALIDATE_INT)) {
                    $stmt->execute([$parent_id, $student_id]);
                }
            }
        }

        // トランザクション完了
        $pdo->commit();
        $success_message = '紐づけ情報を更新しました。';

    } catch (Exception $e) {
        // エラー時はロールバック
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// 保護者一覧の取得
$parents = $pdo->query("SELECT id, name, furigana FROM parents ORDER BY furigana")->fetchAll();

// 生徒一覧の取得
$students = $pdo->query("
    SELECT id, name, furigana, grade, school
    FROM students
    WHERE status != '退会済'
    ORDER BY furigana
")->fetchAll();

// 選択された保護者の紐づけ情報を取得
$selected_parent_id = filter_input(INPUT_GET, 'parent_id', FILTER_VALIDATE_INT) ?:
    filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
$linked_students = [];

if ($selected_parent_id) {
    $stmt = $pdo->prepare("SELECT student_id FROM parent_student WHERE parent_id = ?");
    $stmt->execute([$selected_parent_id]);
    $linked_students = array_column($stmt->fetchAll(), 'student_id');
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">保護者・生徒紐づけ管理</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- 保護者選択フォーム -->
                        <form method="get" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="parent_id" class="form-label">保護者を選択</label>
                                    <select name="parent_id" id="parent_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">選択してください</option>
                                        <?php foreach ($parents as $parent): ?>
                                            <option value="<?php echo $parent['id']; ?>" <?php echo $selected_parent_id == $parent['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($parent['name']); ?>
                                                （<?php echo htmlspecialchars($parent['furigana']); ?>）
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <?php if ($selected_parent_id): ?>
                            <!-- 生徒選択フォーム -->
                            <form method="post" id="linkForm">
                                <input type="hidden" name="parent_id" value="<?php echo $selected_parent_id; ?>">

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">選択</th>
                                                <th>名前</th>
                                                <th>ふりがな</th>
                                                <th>学年</th>
                                                <th>学校</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="form-check-input" <?php echo in_array($student['id'], $linked_students) ? 'checked' : ''; ?>>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['furigana']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['grade']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['school']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary">保存</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('linkForm')?.addEventListener('submit', function (e) {
        const checkedBoxes = document.querySelectorAll('input[name="student_ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('生徒を1人以上選択してください。');
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>