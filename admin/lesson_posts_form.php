<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=pc_kakogawa_sys;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // 在籍中の生徒を取得
    $stmt = $pdo->query("SELECT id, name, furigana FROM students WHERE status = '在籍中' ORDER BY furigana");
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit;
}
?>

<!-- メインコンテンツ -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">授業様子投稿</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="lessonPostForm" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">生徒名 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">選択してください</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo htmlspecialchars($student['id']); ?>">
                                                <?php echo htmlspecialchars($student['furigana'] . ' - ' . $student['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="lesson_slot_id" class="form-label">授業日 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="lesson_slot_id" name="lesson_slot_id" required disabled>
                                        <option value="">生徒を選択してください</option>
                                    </select>
                                    <div id="existingPostWarning" class="text-warning mt-2" style="display: none;"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="theme" class="form-label">授業テーマ <span class="text-danger">*</span></label>
                                    <select class="form-select" id="theme" name="theme" required>
                                        <option value="">選択してください</option>
                                        <option value="【1週目】順次処理">【1週目】順次処理</option>
                                        <option value="【2週目】繰り返し">【2週目】繰り返し</option>
                                        <option value="【3週目】条件分岐">【3週目】条件分岐</option>
                                        <option value="【4週目】制作発表">【4週目】制作発表</option>
                                        <option value="基礎">基礎</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">コメント <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="photo" class="form-label">写真</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                </div>

                                <button type="submit" class="btn btn-primary">投稿する</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 完了モーダル -->
<div class="modal fade" id="completionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">完了</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                投稿が完了しました！
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function () {
        // 生徒選択時の処理
        $('#student_id').change(function () {
            const studentId = $(this).val();
            const lessonSlotSelect = $('#lesson_slot_id');
            const warningDiv = $('#existingPostWarning');

            if (studentId) {
                // 授業日の取得
                $.ajax({
                    url: 'api/get_lesson_slots_for_post.php',
                    type: 'GET',
                    data: { student_id: studentId },
                    success: function (response) {
                        lessonSlotSelect.prop('disabled', false);
                        lessonSlotSelect.empty();
                        lessonSlotSelect.append('<option value="">選択してください</option>');

                        response.forEach(function (slot) {
                            lessonSlotSelect.append(
                                `<option value="${slot.id}">${slot.formatted_date}</option>`
                            );
                        });
                        warningDiv.hide();
                    },
                    error: function () {
                        alert('授業日の取得に失敗しました');
                    }
                });
            } else {
                lessonSlotSelect.prop('disabled', true);
                lessonSlotSelect.empty();
                lessonSlotSelect.append('<option value="">生徒を選択してください</option>');
                warningDiv.hide();
            }
        });

        // 授業日選択時の処理
        $('#lesson_slot_id').change(function () {
            const lessonSlotId = $(this).val();
            const studentId = $('#student_id').val();
            const warningDiv = $('#existingPostWarning');

            if (lessonSlotId && studentId) {
                // 過去の投稿チェック
                $.ajax({
                    url: 'api/check_existing_post.php',
                    type: 'GET',
                    data: {
                        student_id: studentId,
                        lesson_slot_id: lessonSlotId
                    },
                    success: function (response) {
                        if (response.exists) {
                            warningDiv.text(response.message).show();
                        } else {
                            warningDiv.hide();
                        }
                    },
                    error: function () {
                        warningDiv.hide();
                    }
                });
            } else {
                warningDiv.hide();
            }
        });

        // フォーム送信時の処理
        $('#lessonPostForm').submit(function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            $.ajax({
                url: 'api/save_lesson_post.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        $('#completionModal').modal('show');
                        $('#lessonPostForm')[0].reset();
                        $('#lesson_slot_id').prop('disabled', true);
                        $('#existingPostWarning').hide();
                    } else {
                        alert('エラー: ' + response.message);
                    }
                },
                error: function () {
                    alert('投稿に失敗しました');
                }
            });
        });
    });
</script>