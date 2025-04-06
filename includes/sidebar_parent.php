<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="sidebar">
    <ul class="list-unstyled components">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="/portal/parent/dashboard.php">
                <i class="fas fa-home"></i> ダッシュボード
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>" href="/portal/parent/calendar.php">
                <i class="fas fa-calendar-alt"></i> 授業日程
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'transfer_request.php' ? 'active' : ''; ?>" href="/portal/parent/transfer_request.php">
                <i class="fas fa-exchange-alt"></i> 授業振替申請
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'family.php' ? 'active' : ''; ?>" href="/portal/parent/family.php">
                <i class="fas fa-users"></i> 家族情報
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="/portal/parent/attendance.php">
                <i class="fas fa-calendar-check"></i> 出席管理
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="/portal/parent/profile.php">
                <i class="fas fa-user"></i> プロフィール
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'parent_lesson_posts.php' ? 'active' : ''; ?>" href="/portal/parent/parent_lesson_posts.php">
                <i class="fas fa-book"></i> 授業の様子
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portal/logout.php">
                <i class="fas fa-sign-out-alt"></i> ログアウト
            </a>
        </li>
    </ul>
</nav>