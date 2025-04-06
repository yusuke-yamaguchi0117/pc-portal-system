<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">プログラ加古川南校</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- ダッシュボード -->
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>ダッシュボード</p>
                    </a>
                </li>

                <!-- 生徒管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>生徒管理</p>
                    </a>
                </li>

                <!-- 保護者管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.parents.index') }}" class="nav-link {{ request()->routeIs('admin.parents.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>保護者管理</p>
                    </a>
                </li>

                <!-- 授業の様子 -->
                <li class="nav-item">
                    <a href="{{ route('admin.lesson-reports.index') }}" class="nav-link {{ request()->routeIs('admin.lesson-reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book-reader"></i>
                        <p>授業の様子</p>
                    </a>
                </li>

                <!-- 授業日程管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>授業日程管理</p>
                    </a>
                </li>

                <!-- 振替申請管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.transfers.index') }}" class="nav-link {{ request()->routeIs('admin.transfers.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <p>振替申請管理</p>
                    </a>
                </li>

                <!-- 時間変更申請管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.time-changes.index') }}" class="nav-link {{ request()->routeIs('admin.time-changes.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clock"></i>
                        <p>時間変更申請管理</p>
                    </a>
                </li>

                <!-- お問い合わせ管理 -->
                <li class="nav-item">
                    <a href="{{ route('admin.inquiries.index') }}" class="nav-link {{ request()->routeIs('admin.inquiries.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>お問い合わせ管理</p>
                    </a>
                </li>

                <!-- 授業時間帯設定 -->
                <li class="nav-item">
                    <a href="{{ route('admin.time-slots.index') }}" class="nav-link {{ request()->routeIs('admin.time-slots.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-business-time"></i>
                        <p>授業時間帯設定</p>
                    </a>
                </li>

                <!-- メール設定 -->
                <li class="nav-item">
                    <a href="{{ route('admin.email-settings.index') }}" class="nav-link {{ request()->routeIs('admin.email-settings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>メール設定</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
