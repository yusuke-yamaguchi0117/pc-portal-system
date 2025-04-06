<?php
require_once '../config/database.php';
require_once '../auth.php';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業日程管理 - プログラ加古川南校</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- RRule Library -->
    <script src='https://cdn.jsdelivr.net/npm/rrule@2.7.2/dist/es5/rrule.min.js'></script>
    <!-- FullCalendar Bundle -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <!-- FullCalendar RRule Plugin -->
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.10/index.global.min.js'></script>
    <style>
        .fc-event {
            cursor: pointer;
        }

        /* 休みイベントのスタイル */
        .holiday-event {
            cursor: pointer;
            text-align: center;
            color: #fff !important;
            padding: 2px !important;
        }

        .holiday-event .fc-event-title {
            color: #fff !important;
            font-weight: bold;
            font-size: 0.9em !important;
        }

        main {
            margin-left: 240px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
        }

        /* 日曜日の背景色設定 */
        .fc-day-sun {
            background-color: #f8f9fa !important;
        }

        /* 日曜日のヘッダー文字色 */
        .fc-col-header-cell.fc-day-sun {
            color: #dc3545;
        }

        /* 生徒の授業スケジュールのスタイル */
        .fc-event.student-lesson {
            margin: 2px 0;
            padding: 2px 4px;
            border: none;
        }

        /* 生徒の授業スケジュールのテキスト色 */
        .fc-lesson .fc-event-main {
            color: #000000 !important;
        }

        /* イベントのテキストを段落ちさせる */
        .fc-h-event .fc-event-main-frame {
            white-space: pre-line !important;
            word-break: break-all !important;
            line-height: 1.2 !important;
            padding: 2px 4px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }

        /* イベントの高さを自動調整 */
        .fc-event {
            height: auto !important;
            min-height: 24px !important;
        }

        /* イベントのテキストを中央揃えに */
        .fc-event-title {
            text-align: center !important;
            font-size: 0.9em !important;
            display: block !important;
            width: 100% !important;
        }

        /* イベントの時間表示のスタイル */
        .fc-event-time {
            display: block !important;
            text-align: center !important;
            font-weight: bold !important;
            font-size: 0.9em !important;
            margin-bottom: 2px !important;
            width: 100% !important;
        }

        /* fc-lessonクラスの特別なスタイル */
        .fc-lesson .fc-event-main-frame {
            display: flex !important;
            flex-direction: column !important;
            gap: 2px !important;
        }
    </style>
</head>

<body>
    <?php require_once '../includes/header.php'; ?>
    <?php require_once '../includes/sidebar_admin.php'; ?>


    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">授業日程管理</h5>
                    </div>
                    <div class="card-body">
                        <!-- 定休日設定フォーム -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">定休日設定</h6>
                                </div>
                                <div class="card-body">
                                    <form id="regularHolidayForm" class="mb-3">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">期間</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control" id="startDate" name="start_date" required>
                                                    <span class="input-group-text">～</span>
                                                    <input type="date" class="form-control" id="endDate" name="end_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">定休曜日</label>
                                                <div class="btn-group" role="group">
                                                    <input type="checkbox" class="btn-check" id="sun" name="weekdays[]" value="0">
                                                    <label class="btn btn-outline-danger" for="sun">日</label>
                                                    <input type="checkbox" class="btn-check" id="mon" name="weekdays[]" value="1">
                                                    <label class="btn btn-outline-primary" for="mon">月</label>
                                                    <input type="checkbox" class="btn-check" id="tue" name="weekdays[]" value="2">
                                                    <label class="btn btn-outline-primary" for="tue">火</label>
                                                    <input type="checkbox" class="btn-check" id="wed" name="weekdays[]" value="3">
                                                    <label class="btn btn-outline-primary" for="wed">水</label>
                                                    <input type="checkbox" class="btn-check" id="thu" name="weekdays[]" value="4">
                                                    <label class="btn btn-outline-primary" for="thu">木</label>
                                                    <input type="checkbox" class="btn-check" id="fri" name="weekdays[]" value="5">
                                                    <label class="btn btn-outline-primary" for="fri">金</label>
                                                    <input type="checkbox" class="btn-check" id="sat" name="weekdays[]" value="6">
                                                    <label class="btn btn-outline-primary" for="sat">土</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">一括設定</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- 警告メッセージ表示エリア -->
                        <div id="warningMessages" class="alert alert-warning" style="display: none;"></div>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- イベント編集モーダル -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">予定の編集</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <!-- 振替警告メッセージ -->
                    <div id="transferWarning" class="alert alert-danger mb-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>振替が必要です
                    </div>

                    <!-- 生徒情報表示セクション -->
                    <div id="studentInfo" class="mb-3" style="display: none;">
                        <h6>生徒情報</h6>
                        <div class="row">
                            <div class="col-12">
                                <p><strong>名前:</strong> <span id="studentName"></span></p>
                                <p><strong>授業曜日:</strong> <span id="studentDay"></span></p>
                                <p><strong>授業時間:</strong> <span id="studentTime"></span></p>
                                <p id="transferInfo" style="display: none;"><strong>振替元:</strong> <span id="originalLessonDate"></span></p>
                            </div>
                        </div>
                    </div>

                    <form id="eventForm">
                        <input type="hidden" id="eventId" name="id">
                        <div class="mb-3">
                            <label for="eventDate" class="form-label">日付</label>
                            <input type="date" class="form-control" id="eventDate" name="date" readonly>
                        </div>
                        <!-- 予定種類の選択 -->
                        <div id="eventTypeGroup" class="mb-3">
                            <label class="form-label">予定の種類</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="eventType" id="eventTypeLesson" value="lesson" checked>
                                <label class="btn btn-outline-primary" for="eventTypeLesson">生徒の授業</label>
                                <input type="radio" class="btn-check" name="eventType" id="eventTypeHoliday" value="holiday">
                                <label class="btn btn-outline-primary" for="eventTypeHoliday">休み</label>
                            </div>
                        </div>
                        <!-- 休みの種類選択（休み選択時のみ表示） -->
                        <div id="holidayTypeGroup" class="mb-3" style="display: none;">
                            <label class="form-label">休みの種類</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="holidayType" id="holidayTypeNormal" value="休み" checked>
                                <label class="btn btn-outline-danger" for="holidayTypeNormal">通常休み</label>
                                <input type="radio" class="btn-check" name="holidayType" id="holidayTypeSpecial" value="臨時休校">
                                <label class="btn btn-outline-danger" for="holidayTypeSpecial">臨時休校</label>
                            </div>
                        </div>
                        <!-- 休みのメッセージ（休み選択時のみ表示） -->
                        <div id="holidayMessageGroup" class="mb-3" style="display: none;">
                            <label for="holidayMessage" class="form-label">メッセージ（任意）</label>
                            <textarea class="form-control" id="holidayMessage" name="note" rows="3" placeholder="例：台風接近のため"></textarea>
                        </div>
                        <!-- 生徒選択（新規登録時のみ表示） -->
                        <div id="studentSelectGroup" class="mb-3" style="display: none;">
                            <label for="studentSelect" class="form-label">生徒</label>
                            <select class="form-select" id="studentSelect" name="student_id" required>
                                <option value="">選択してください</option>
                            </select>
                        </div>
                        <!-- 時間設定（新規登録時のみ表示） -->
                        <div id="timeSettingGroup" class="mb-3" style="display: none;">
                            <label for="startTime" class="form-label">開始時間</label>
                            <input type="time" class="form-control" id="startTime" name="start_time" required>

                            <label for="endTime" class="form-label mt-2">終了時間</label>
                            <input type="time" class="form-control" id="endTime" name="end_time" required>

                            <label for="lessonType" class="form-label mt-2">種別</label>
                            <select class="form-select" id="lessonType" name="type" required>
                                <option value="regular">通常</option>
                                <option value="transfer">振替</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="deleteEventBtn" style="display: none;">削除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-primary" id="saveEventBtn">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const warningMessagesEl = document.getElementById('warningMessages');
            const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            const eventForm = document.getElementById('eventForm');
            const deleteEventBtn = document.getElementById('deleteEventBtn');
            let currentEvent = null;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: '今日',
                    month: '月',
                    week: '週',
                    day: '日'
                },
                editable: true, // ドラッグ&ドロップを有効化
                eventSources: [
                    // 通常の授業予定と休み（lesson_slotsから取得）
                    {
                        url: '/portal/admin/api/get_lesson_slots.php',
                        method: 'GET',
                        failure: function (error) {
                            console.error('授業予定の取得に失敗しました:', error);
                        },
                        success: function (response) {
                            // 警告メッセージを表示
                            if (response.warnings && response.warnings.length > 0) {
                                let tooManyHtml = '<h6>予定数が多い警告</h6><ul class="mb-0">';
                                let tooFewHtml = '<h6>予定数が少ない警告</h6><ul class="mb-0">';

                                response.warnings.forEach(warning => {
                                    const month = warning.month.replace('-', '年') + '月';
                                    if (warning.type === 'too_many') {
                                        tooManyHtml += `<li>${month}に${warning.student_name}の授業が${warning.count}回設定されています</li>`;
                                    } else if (warning.type === 'too_few') {
                                        tooFewHtml += `<li>${month}に${warning.student_name}の授業が${warning.count}回しか設定されていません</li>`;
                                    }
                                });

                                tooManyHtml += '</ul>';
                                tooFewHtml += '</ul>';

                                let warningHtml = '';
                                if (response.warnings.some(w => w.type === 'too_many')) {
                                    warningHtml += tooManyHtml;
                                }
                                if (response.warnings.some(w => w.type === 'too_few')) {
                                    warningHtml += tooFewHtml;
                                }

                                warningMessagesEl.innerHTML = warningHtml;
                                warningMessagesEl.style.display = '';
                            } else {
                                warningMessagesEl.style.display = 'none';
                            }
                            return response.events;
                        }
                    }
                ],
                eventDrop: function (info) {
                    // 特別な予定（休みなど）は移動不可
                    if (!info.event.extendedProps.student_id) {
                        info.revert();
                        alert('特別な予定は移動できません');
                        return;
                    }

                    // イベントがドロップされたときの処理
                    const event = info.event;
                    const newStart = event.start;
                    const newEnd = event.end;

                    // 確認ダイアログを表示
                    if (!confirm(`${event.extendedProps.student_name}さんの授業予定を\n${formatDate(newStart)}に移動しますか？`)) {
                        info.revert();
                        return;
                    }

                    // データベースの更新
                    const updateData = {
                        id: event.id,
                        student_id: event.extendedProps.student_id,
                        date: formatDate(newStart),
                        start_time: formatTime(newStart),
                        end_time: formatTime(new Date(newStart.getTime() + 60 * 60 * 1000)) // 1時間後
                    };

                    fetch('/portal/admin/api/move_lesson_slot.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(updateData)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                info.revert();
                                alert('予定の移動に失敗しました: ' + (data.error || '不明なエラー'));
                            }
                        })
                        .catch(error => {
                            info.revert();
                            console.error('Error:', error);
                            alert('エラーが発生しました');
                        });
                },
                eventContent: function (arg) {
                    // 生徒の授業スケジュールの場合
                    if (arg.event.extendedProps.student_id) {
                        const studentName = arg.event.extendedProps.student_name;
                        const courseName = arg.event.extendedProps.course_name;
                        const time = arg.event.extendedProps.start_time;

                        return {
                            html: `<div class="fc-event-main">
                                <div class="fc-event-main-frame">
                                    <div class="fc-event-time">${time}</div>
                                    <div class="fc-event-title">${studentName}（${courseName}）</div>
                                </div>
                            </div>`
                        };
                    }
                    // 休みの場合
                    else if (arg.event.extendedProps.type === 'holiday') {
                        const title = arg.event.title.split('\n');
                        const holidayType = title[0];

                        return {
                            html: `<div class="fc-event-main">
                                <div class="fc-event-main-frame">
                                    <div class="fc-event-title">
                                        ${holidayType}
                                    </div>
                                </div>
                            </div>`
                        };
                    }
                    // その他の特別な予定の場合
                    else {
                        return {
                            html: `<div class="fc-event-main">
                                <div class="fc-event-main-frame">
                                    <div class="fc-event-title">${arg.event.title}</div>
                                </div>
                            </div>`
                        };
                    }
                },
                eventDidMount: function (info) {
                    const event = info.event;
                    const el = info.el;

                    // イベントのスタイルをカスタマイズ
                    el.style.borderColor = event.backgroundColor;
                    el.style.backgroundColor = event.backgroundColor;

                    // 同じ日付のイベントをチェック
                    const events = info.view.calendar.getEvents();
                    const isHoliday = events.some(e => {
                        const eventDate = new Date(event.startStr).toDateString();
                        const compareDate = new Date(e.startStr).toDateString();
                        return eventDate === compareDate && e.title === '休み';
                    });

                    // 休み以外のイベントで、同じ日に休みがある場合は「！」マークを表示
                    if (isHoliday && event.title !== '休み') {
                        const mainFrame = el.querySelector('.fc-event-main-frame');
                        if (mainFrame) {
                            const alertBadge = document.createElement('div');
                            alertBadge.innerText = '！';
                            alertBadge.style.position = 'absolute';
                            alertBadge.style.top = '2px';
                            alertBadge.style.left = '4px';
                            alertBadge.style.color = 'red';
                            alertBadge.style.fontSize = '14px';
                            alertBadge.style.fontWeight = 'bold';
                            mainFrame.appendChild(alertBadge);
                        }
                    }
                },
                selectable: true,
                selectMirror: true,
                firstDay: 0, // 週の開始日を日曜日に設定
                displayEventTime: false, // イベントの時間表示を無効化
                eventTimeFormat: { // 時間表示のフォーマットを空に
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                dayCellClassNames: function (arg) {
                    // 日曜日の場合、追加のクラスを返す
                    if (arg.date.getDay() === 0) {
                        return ['sunday'];
                    }
                    return [];
                },
                eventClassNames: function (arg) {
                    // 生徒の授業スケジュールの場合、専用のクラスを追加
                    if (arg.event.title && arg.event.title.includes('\n')) {
                        return ['student-lesson', 'fc-lesson'];
                    }
                    return [];
                },
                eventChange: function (info) {
                    // 休みのイベントが変更された場合
                    if (info.event.title === '休み') {
                        const events = info.view.calendar.getEvents();
                        const sameDateEvents = events.filter(e =>
                            e.startStr === info.event.startStr && e.title !== '休み'
                        );

                        // 同じ日付の全てのイベントを更新
                        sameDateEvents.forEach(event => {
                            const el = event.el;
                            const mainFrame = el.querySelector('.fc-event-main-frame');

                            // 既存の「！」マークを削除
                            const existingBadge = mainFrame.querySelector('div[style*="color: red"]');
                            if (existingBadge) {
                                existingBadge.remove();
                            }

                            // 休みの場合は「！」マークを追加
                            if (info.event.title === '休み') {
                                const alertBadge = document.createElement('div');
                                alertBadge.innerText = '！';
                                alertBadge.style.position = 'absolute';
                                alertBadge.style.top = '2px';
                                alertBadge.style.left = '4px';
                                alertBadge.style.color = 'red';
                                alertBadge.style.fontSize = '14px';
                                alertBadge.style.fontWeight = 'bold';

                                mainFrame?.appendChild(alertBadge);
                            }
                        });
                    }
                },
                dateClick: function (info) {
                    const clickedDate = new Date(info.dateStr);
                    const dayOfWeek = clickedDate.getDay();
                    // 日曜日の場合は自動的に休みとして登録
                    if (dayOfWeek === 0) {
                        if (confirm('日曜日は自動的に休みとして登録されます。よろしいですか？')) {
                            return fetch('/portal/admin/api/create_holiday.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    date: info.dateStr
                                })
                            })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        calendar.refetchEvents();
                                    } else {
                                        alert('登録に失敗しました: ' + (result.error || '不明なエラー'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('エラーが発生しました');
                                });
                        }
                        return;
                    }

                    // 祝日かどうかを確認
                    const year = clickedDate.getFullYear();
                    return fetch(`https://holidays-jp.github.io/api/v1/${year}/date.json`)
                        .then(response => response.json())
                        .then(holidays => {
                            const dateStr = info.dateStr;
                            if (holidays[dateStr]) {
                                if (confirm('祝日は自動的に休みとして登録されます。よろしいですか？')) {
                                    return fetch('/portal/admin/api/create_holiday.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            date: dateStr
                                        })
                                    })
                                        .then(response => response.json())
                                        .then(result => {
                                            if (result.success) {
                                                calendar.refetchEvents();
                                            } else {
                                                alert('登録に失敗しました: ' + (result.error || '不明なエラー'));
                                            }
                                        });
                                }
                            } else {
                                // 通常の日付クリック処理
                                currentEvent = null;
                                document.getElementById('eventId').value = '';
                                document.getElementById('eventDate').value = dateStr;
                                document.getElementById('studentInfo').style.display = 'none';
                                document.getElementById('eventTypeGroup').style.display = '';
                                document.getElementById('eventTypeLesson').checked = true;
                                document.getElementById('studentSelectGroup').style.display = '';
                                document.getElementById('timeSettingGroup').style.display = '';
                                document.getElementById('deleteEventBtn').style.display = 'none';
                                eventModal.show();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('エラーが発生しました');
                        });
                },
                eventClick: function (info) {
                    // イベント編集モード
                    currentEvent = info.event;
                    document.getElementById('eventId').value = info.event.id || '';
                    document.getElementById('eventDate').value = info.event.startStr.split('T')[0];
                    document.getElementById('deleteEventBtn').style.display = '';
                    document.getElementById('eventTypeGroup').style.display = 'none';  // 予定種類を非表示
                    document.getElementById('eventDate').parentElement.style.display = 'none';  // 日付入力を非表示

                    // 休みまたは臨時休校の場合
                    if (info.event.extendedProps.type === 'holiday') {
                        document.getElementById('studentInfo').style.display = 'none';
                        document.getElementById('holidayTypeGroup').style.display = '';
                        document.getElementById('holidayMessageGroup').style.display = '';
                        document.getElementById('studentSelectGroup').style.display = 'none';
                        document.getElementById('timeSettingGroup').style.display = 'none';

                        // 休みの種類を設定
                        const holidayType = info.event.title.split('\n')[0];
                        if (holidayType === '休み') {
                            document.getElementById('holidayTypeNormal').checked = true;
                        } else if (holidayType === '臨時休校') {
                            document.getElementById('holidayTypeSpecial').checked = true;
                        }

                        // メッセージを設定
                        const note = info.event.extendedProps.note || '';
                        document.getElementById('holidayMessage').value = note;
                    }
                    // 生徒情報の表示
                    else if (info.event.extendedProps.student_id) {
                        document.getElementById('studentInfo').style.display = 'block';
                        document.getElementById('studentName').textContent = info.event.extendedProps.student_name;
                        document.getElementById('studentDay').textContent = info.event.extendedProps.lesson_day;
                        document.getElementById('studentTime').textContent = info.event.extendedProps.start_time;

                        // 振替情報の表示
                        const transferInfo = document.getElementById('transferInfo');
                        const originalLessonDate = document.getElementById('originalLessonDate');

                        if (info.event.extendedProps.type === 'transfer' && info.event.extendedProps.original_lesson_date) {
                            const originalDate = new Date(info.event.extendedProps.original_lesson_date);
                            const formattedDate = `${originalDate.getFullYear()}年${originalDate.getMonth() + 1}月${originalDate.getDate()}日（${['日', '月', '火', '水', '木', '金', '土'][originalDate.getDay()]}）`;
                            originalLessonDate.textContent = formattedDate;
                            transferInfo.style.display = 'block';
                        } else {
                            transferInfo.style.display = 'none';
                        }
                    } else {
                        document.getElementById('studentInfo').style.display = 'none';
                    }

                    eventModal.show();
                },
                unselect: function () {
                    // 範囲選択のクリア
                    if (!eventModal._isShown) {
                        currentEvent = null;
                    }
                }
            });

            // 日付をYYYY-MM-DD形式にフォーマットする関数
            function formatDate(date) {
                const d = new Date(date);
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // 時間をHH:MM形式にフォーマットする関数
            function formatTime(date) {
                const d = new Date(date);
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                return `${hours}:${minutes}`;
            }

            // 生徒一覧を取得する関数
            function loadStudents() {
                fetch('/portal/admin/api/get_students.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const select = document.getElementById('studentSelect');
                            select.innerHTML = '<option value="">選択してください</option>';
                            data.students.forEach(student => {
                                const option = document.createElement('option');
                                option.value = student.id;
                                option.textContent = `${student.name}（${student.course_name} ${student.lesson_day} ${student.lesson_time}）`;
                                select.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 初期読み込み時に生徒一覧を取得
            loadStudents();

            // 生徒が選択されたときの処理
            document.getElementById('studentSelect').addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    // 生徒の通常の授業時間を終了時間の初期値として設定
                    const timeMatch = selectedOption.text.match(/(\d{2}:\d{2})/);
                    if (timeMatch) {
                        document.getElementById('startTime').value = timeMatch[1];
                        // 1時間後を終了時間に設定
                        const [hours, minutes] = timeMatch[1].split(':');
                        const endTime = new Date();
                        endTime.setHours(parseInt(hours) + 1);
                        endTime.setMinutes(minutes);
                        document.getElementById('endTime').value =
                            `${String(endTime.getHours()).padStart(2, '0')}:${String(endTime.getMinutes()).padStart(2, '0')}`;
                    }
                }
            });

            // 予定種類の選択による表示切り替え
            document.querySelectorAll('input[name="eventType"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    const isLesson = this.value === 'lesson';
                    document.getElementById('studentSelectGroup').style.display = isLesson ? '' : 'none';
                    document.getElementById('timeSettingGroup').style.display = isLesson ? '' : 'none';
                    document.getElementById('holidayTypeGroup').style.display = isLesson ? 'none' : '';
                    document.getElementById('holidayMessageGroup').style.display = isLesson ? 'none' : '';
                });
            });

            calendar.render();

            // モーダルのアクセシビリティ対応
            const modalEl = document.getElementById('eventModal');
            modalEl.addEventListener('shown.bs.modal', function () {
                // モーダルが表示されたときの処理
                const focusableElements = modalEl.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                // モーダルが非表示になる前にフォーカスを移動
                const activeElement = document.activeElement;
                if (modalEl.contains(activeElement)) {
                    calendarEl.focus();
                }
                // aria-hiddenの設定は自動的に行われるため、ここでは設定しない
            });

            // モーダルが開く前のイベント
            modalEl.addEventListener('show.bs.modal', function () {
                // モーダル表示前にaria-hiddenを削除
                modalEl.removeAttribute('aria-hidden');
            });

            // イベント保存ボタンのクリックハンドラを修正
            document.getElementById('saveEventBtn').addEventListener('click', function () {
                const formData = new FormData(document.getElementById('eventForm'));
                const data = Object.fromEntries(formData.entries());
                const eventType = document.querySelector('input[name="eventType"]:checked').value;

                // 新規登録の場合
                if (!currentEvent) {
                    if (eventType === 'lesson') {
                        // 生徒の授業を登録
                        if (!data.student_id || !data.start_time || !data.end_time || !data.type) {
                            alert('必須項目を入力してください');
                            return;
                        }

                        fetch('/portal/admin/api/create_lesson_slot.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    calendar.refetchEvents();
                                    eventModal.hide();
                                } else {
                                    alert('保存に失敗しました: ' + (result.error || '不明なエラー'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('エラーが発生しました');
                            });
                    } else {
                        // 休みを登録
                        const holidayType = document.querySelector('input[name="holidayType"]:checked').value;
                        const holidayMessage = document.getElementById('holidayMessage').value;

                        fetch('/portal/admin/api/create_holiday.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                date: data.date,
                                type: holidayType,
                                note: holidayMessage
                            })
                        })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    calendar.refetchEvents();
                                    eventModal.hide();
                                } else {
                                    alert('保存に失敗しました: ' + (result.error || '不明なエラー'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('エラーが発生しました');
                            });
                    }
                } else {
                    // 既存の予定を更新
                    if (!data.status) {
                        alert('ステータスを選択してください');
                        return;
                    }

                    // student_idが存在する場合のみ追加
                    if (currentEvent?.extendedProps?.student_id) {
                        data.student_id = currentEvent.extendedProps.student_id;
                    }

                    fetch('/portal/admin/api/update_lesson_slot.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                calendar.refetchEvents();
                                eventModal.hide();
                            } else {
                                alert('保存に失敗しました: ' + (result.error || '不明なエラー'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('エラーが発生しました');
                        });
                }
            });

            // イベント削除
            deleteEventBtn.addEventListener('click', function () {
                if (!confirm('この予定を削除してもよろしいですか？')) {
                    return;
                }

                const eventId = document.getElementById('eventId').value;
                if (!eventId) {
                    alert('削除する予定が選択されていません');
                    return;
                }

                fetch('/portal/admin/api/delete_lesson_slot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: eventId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            calendar.refetchEvents();
                            eventModal.hide();
                        } else {
                            alert('削除に失敗しました: ' + (data.error || '不明なエラー'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました');
                    });
            });

            // 定休日設定フォームの送信処理
            const regularHolidayForm = document.getElementById('regularHolidayForm');
            regularHolidayForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const weekdays = Array.from(formData.getAll('weekdays[]'));

                if (weekdays.length === 0) {
                    alert('曜日を選択してください');
                    return;
                }

                const data = {
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    weekdays: weekdays
                };

                fetch('/portal/admin/api/set_regular_holidays.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('定休日を設定しました');
                            calendar.refetchEvents();
                        } else {
                            alert('設定に失敗しました: ' + (result.error || '不明なエラー'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました');
                    });
            });
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>

</html>