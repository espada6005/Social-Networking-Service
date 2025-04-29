<?php if ($user !== null && $user->getEmailConfirmedAt() !== null): ?>
    <style>

    </style>
    <!-- ハンバーガーメニュー（スマホ向け） -->
    <button class="btn btn-dark d-md-none menu-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- サイドバー -->
    <div class="sidebar d-none d-md-flex flex-column p-3">
        <ul class="nav flex-column">
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-home me-2"></i> ホーム</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-bell me-2"></i> 通知</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-envelope me-2"></i> メッセージ</a></li>
            <li class="nav-item"><a href="user/profile" class="nav-link"><i class="fas fa-user me-2"></i> プロフィール</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-edit me-2"></i> 作成</a></li>
            <li class="nav-item"><a href="/logout" class="nav-link"><i class="fas fa-sign-out-alt me-2"></i> ログアウト</a></li>
        </ul>
    </div>

    <!-- スマホ用オフキャンバスメニュー -->
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-body mt-5">
            <ul class="nav flex-column">
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-home me-2"></i> ホーム</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-bell me-2"></i> 通知</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-envelope me-2"></i> メッセージ</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-user me-2"></i> プロフィール</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-edit me-2"></i> 作成</a></li>
                <li class="nav-item"><a href="/logout" class="nav-link"><i class="fas fa-sign-out-alt me-2"></i> ログアウト</a></li>
            </ul>
        </div>
    </div>
<?php endif; ?>