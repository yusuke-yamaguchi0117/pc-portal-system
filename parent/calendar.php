<?php
require_once '../config/database.php';
require_once '../auth.php';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業日程確認 - プログラ加古川南校</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar Bundle -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        .fc-event {
            cursor: pointer;
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

        /* イベントの中央寄せ */
        .fc-daygrid-dot-event {
            justify-content: center !important;
        }

        /* 生徒の授業スケジュールのテキスト色 */
        .fc-lesson .fc-event-main {
            color: #000000 !important;
            width: 100% !important;
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

        .fc-lesson .fc-event-title {
            white-space: pre-wrap !important;
        }
    </style>
</head>

<body>
    <?php require_once '../includes/header.php'; ?>
    <?php require_once '../includes/sidebar_parent.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">授業日程確認</h5>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
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
                // 読み取り専用設定
                editable: false,
                selectable: false,
                eventStartEditable: false,
                eventDurationEditable: false,
                eventClick: function (info) {
                    // イベントクリックを無効化
                    info.jsEvent.preventDefault();
                },
                // イベントソース
                eventSources: [
                    // 生徒の授業予定（lesson_slotsから取得）
                    {
                        url: '/portal/parent/api/get_lesson_slots.php',
                        method: 'GET',
                        failure: function (error) {
                            console.error('授業予定の取得に失敗しました:', error);
                        }
                    },
                    // 休みなどの特別な予定
                    {
                        url: '/portal/parent/api/get_holidays.php',
                        method: 'GET',
                        failure: function (error) {
                            console.error('休校日の取得に失敗しました:', error);
                        }
                    }
                ],
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
                    // 休みなどの特別な予定の場合
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
                            alertBadge.style.fontSize = '20px';
                            alertBadge.style.fontWeight = 'bold';
                            mainFrame.appendChild(alertBadge);
                        }
                    }
                },
                firstDay: 0, // 週の開始日を日曜日に設定
                displayEventTime: false, // イベントの時間表示を無効化
                eventTimeFormat: { // 時間表示のフォーマットを設定
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
                }
            });
            calendar.render();
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>

</html>