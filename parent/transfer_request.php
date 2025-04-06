<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';

// 保護者IDを取得
$parent_id = $_SESSION['user_id'];

// 保護者に紐づく生徒一覧を取得
$stmt = $pdo->prepare("
    SELECT s.id, s.name, s.course_id
    FROM students s
    JOIN parent_student ps ON s.id = ps.student_id
    WHERE ps.parent_id = ?
    ORDER BY s.name
");
$stmt->execute([$parent_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 申請済み一覧を取得
$stmt = $pdo->prepare("
    SELECT
        tr.*,
        s.name as student_name,
        DATE_FORMAT(tr.created_at, '%Y-%m-%d %H:%i') as formatted_created_at
    FROM transfer_requests tr
    JOIN students s ON tr.student_id = s.id
    JOIN parent_student ps ON s.id = ps.student_id
    WHERE ps.parent_id = ?
    ORDER BY tr.created_at DESC
");
$stmt->execute([$parent_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="max-width: 960px;">
    <div class="my-4">
        <h2 class="text-center mb-4">授業振替申請</h2>

        <!-- 振替必須メッセージ -->
        <div id="requiredTransferMessage" class="alert alert-warning mb-4" style="display: none;">
            <div class="fw-bold mb-2">振替が必須な日程があります</div>
            <div id="requiredTransferDates"></div>
        </div>
        <div id="noRequiredTransferMessage" class="alert alert-info mb-4" style="display: none;">
            振替が必須な日程はありません
        </div>

        <!-- 申請フォーム -->
        <form id="transferRequestForm" class="mb-5">
            <div class="mb-3">
                <label for="student_id" class="form-label">生徒</label>
                <select class="form-select" id="student_id" name="student_id" required>
                    <option value="">選択してください</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo htmlspecialchars($student['id']); ?>">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="lesson_date" class="form-label">授業予定日</label>
                <select class="form-select" id="lesson_date" name="lesson_date" required disabled>
                    <option value="">生徒を選択してください</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="transfer_date" class="form-label">振替希望日</label>
                <select class="form-select" id="transfer_date" name="transfer_date" required disabled>
                    <option value="">授業予定日を選択してください</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">備考</label>
                <textarea class="form-control" id="note" name="note" rows="3"></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">申請する</button>
            </div>
        </form>

        <!-- 申請一覧 -->
        <h3 class="mb-3">申請一覧</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>生徒名</th>
                        <th>授業予定日</th>
                        <th>振替希望日</th>
                        <th>申請日時</th>
                        <th>備考</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center">申請履歴はありません</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            // 日付フォーマット
                            $lesson_date = !empty($request['lesson_date']) ? $request['lesson_date'] : '';
                            $lesson_time = !empty($request['lesson_time']) ? $request['lesson_time'] : '';

                            $transfer_date = !empty($request['transfer_date']) ? date('Y-m-d', strtotime($request['transfer_date'])) : '';
                            $transfer_time = '';
                            if (!empty($request['transfer_start_time']) && !empty($request['transfer_end_time'])) {
                                $transfer_time = substr($request['transfer_start_time'], 0, 5) . '～' . substr($request['transfer_end_time'], 0, 5);
                            }
                            $transfer_display = $transfer_date;
                            if ($transfer_time) {
                                $transfer_display .= ' ' . $transfer_time;
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($lesson_date); ?></td>
                                <td><?php echo htmlspecialchars($transfer_display); ?></td>
                                <td><?php echo htmlspecialchars($request['formatted_created_at']); ?></td>
                                <td><?php echo htmlspecialchars($request['reason'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($request['status']) {
                                        case 'pending':
                                            $status_class = 'text-warning';
                                            $status_text = '申請中';
                                            break;
                                        case 'approved':
                                            $status_class = 'text-success';
                                            $status_text = '承認済';
                                            break;
                                        case 'rejected':
                                            $status_class = 'text-danger';
                                            $status_text = '却下';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php if ($request['status'] === 'rejected' && !empty($request['reject_reason'])): ?>
                                        <a href="#" class="text-danger ms-2" onclick="showRejectReason('<?php echo htmlspecialchars(str_replace("'", "\\'", $request['reject_reason']), ENT_QUOTES); ?>'); return false;">却下理由</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 完了モーダル -->
<div class="modal fade" id="completionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">申請完了</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>申請が完了しました！</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 却下理由モーダル -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">却下理由</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="rejectReasonText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<script>
    // グローバルスコープで変数と関数を定義
    let rejectReasonModal;

    function showRejectReason(reason) {
        document.getElementById('rejectReasonText').textContent = reason;
        rejectReasonModal.show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('transferRequestForm');
        const studentSelect = document.getElementById('student_id');
        const lessonDateSelect = document.getElementById('lesson_date');
        const transferDateSelect = document.getElementById('transfer_date');
        const completionModal = new bootstrap.Modal(document.getElementById('completionModal'));
        rejectReasonModal = new bootstrap.Modal(document.getElementById('rejectReasonModal'));

        // ツールチップの初期化
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // 生徒選択時の処理
        studentSelect.addEventListener('change', function () {
            const studentId = this.value;
            lessonDateSelect.disabled = !studentId;
            transferDateSelect.disabled = true;

            // メッセージ要素を取得
            const requiredTransferMessage = document.getElementById('requiredTransferMessage');
            const requiredTransferDates = document.getElementById('requiredTransferDates');
            const noRequiredTransferMessage = document.getElementById('noRequiredTransferMessage');

            if (studentId) {
                // 振替必須の日程を取得
                fetch(`/portal/parent/api/get_required_transfers.php?student_id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.has_required_transfers) {
                                // 振替必須の日程がある場合
                                requiredTransferDates.innerHTML = data.dates.map(date =>
                                    `・${date.formatted_date}`
                                ).join('<br>');
                                requiredTransferMessage.style.display = 'block';
                                noRequiredTransferMessage.style.display = 'none';
                            } else {
                                // 振替必須の日程がない場合
                                requiredTransferMessage.style.display = 'none';
                                noRequiredTransferMessage.style.display = 'block';
                            }
                        } else {
                            // エラーの場合は両方のメッセージを非表示
                            requiredTransferMessage.style.display = 'none';
                            noRequiredTransferMessage.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        requiredTransferMessage.style.display = 'none';
                        noRequiredTransferMessage.style.display = 'none';
                    });

                // 授業予定日を取得
                fetch(`/portal/parent/api/get_lesson_dates.php?student_id=${studentId}`)
                    .then(response => response.json())
                    .then(dates => {
                        lessonDateSelect.innerHTML = '<option value="">選択してください</option>';
                        if (Array.isArray(dates)) {
                            dates.forEach(date => {
                                const option = document.createElement('option');
                                option.value = date.formatted_date;
                                option.textContent = date.formatted_date;
                                lessonDateSelect.appendChild(option);
                            });
                            if (dates.length === 0) {
                                const option = document.createElement('option');
                                option.value = "";
                                option.textContent = "予定されている授業はありません";
                                option.disabled = true;
                                lessonDateSelect.appendChild(option);
                            }
                        } else if (dates.error) {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = dates.error;
                            option.disabled = true;
                            lessonDateSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        lessonDateSelect.innerHTML = '<option value="">エラーが発生しました</option>';
                    });
            } else {
                lessonDateSelect.innerHTML = '<option value="">生徒を選択してください</option>';
                transferDateSelect.innerHTML = '<option value="">授業予定日を選択してください</option>';
            }
        });

        // 授業予定日選択時の処理
        lessonDateSelect.addEventListener('change', function () {
            const lessonDate = this.value;
            const studentId = studentSelect.value;
            transferDateSelect.disabled = !lessonDate;

            if (lessonDate && studentId) {
                // 振替候補日を取得
                const encodedLessonDate = encodeURIComponent(lessonDate);
                fetch(`/portal/parent/api/get_transfer_candidates.php?student_id=${studentId}&lesson_date=${encodedLessonDate}`)
                    .then(response => response.json())
                    .then(data => {
                        transferDateSelect.innerHTML = '<option value="">選択してください</option>';

                        if (data.success) {
                            if (data.candidates && data.candidates.length > 0) {
                                data.candidates.forEach(candidate => {
                                    const option = document.createElement('option');
                                    option.value = candidate.date;
                                    option.textContent = `${candidate.formatted_date} (残り${candidate.remaining_slots}枠)`;
                                    if (candidate.remaining_slots <= 0) {
                                        option.disabled = true;
                                    }
                                    transferDateSelect.appendChild(option);
                                });
                            } else {
                                const option = document.createElement('option');
                                option.value = "";
                                option.textContent = data.message || "振替可能な日程が見つかりません";
                                option.disabled = true;
                                transferDateSelect.appendChild(option);
                            }
                        } else {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = data.error || "エラーが発生しました";
                            option.disabled = true;
                            transferDateSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        transferDateSelect.innerHTML = '<option value="">エラーが発生しました</option>';
                    });
            } else {
                transferDateSelect.innerHTML = '<option value="">授業予定日を選択してください</option>';
            }
        });

        // フォーム送信時の処理
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // バリデーション
            if (!studentSelect.value || !lessonDateSelect.value || !transferDateSelect.value) {
                alert('すべての項目を選択してください。');
                return;
            }

            // フォームデータの作成
            const formData = new FormData(this);

            // 選択された振替日から時間情報を取得
            const selectedTransferDate = transferDateSelect.value;
            const selectedOption = [...transferDateSelect.options].find(option => option.value === selectedTransferDate);
            const transferDateText = selectedOption.textContent;

            // 振替日のデータを追加
            const transferTimeMatch = transferDateText.match(/(\d{2}:\d{2})～(\d{2}:\d{2})/);
            if (transferTimeMatch) {
                formData.append('transfer_start_time', transferTimeMatch[1] + ':00');
                formData.append('transfer_end_time', transferTimeMatch[2] + ':00');
            }

            // noteフィールドの名前をreasonに変更
            const note = formData.get('note');
            formData.delete('note');
            formData.append('reason', note);

            // デバッグ用にデータを表示
            console.log('送信データ:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // 申請処理
            fetch('/portal/parent/api/submit_transfer_request.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('レスポンスステータス:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('レスポンスデータ:', data);
                    if (data.success) {
                        // 完了モーダルを表示
                        completionModal.show();
                        // フォームをリセット
                        form.reset();
                        lessonDateSelect.disabled = true;
                        transferDateSelect.disabled = true;
                        // ページをリロード（申請一覧を更新）
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.error || '申請処理中にエラーが発生しました。');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('申請処理中にエラーが発生しました。');
                });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>