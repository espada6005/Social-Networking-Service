<?php if ($user !== null && $user->getEmailConfirmedAt() !== null): ?>
    <div id="sidebar" class="col-auto col-sm-4 col-md-3 col-xl-2 px-sm-1 px-0">
        <div class="d-flex flex-column align-items-center align-items-sm-start px-2 pt-2 min-vh-100" style="display:inline-block;">
            <span class="fs-5 d-none d-sm-inline text-light">SNS</span>
            <ul class="nav nav-pills flex-column mb-sm-auto mt-0 mt-sm-3 align-items-center align-items-sm-start" id="menu">
                <li class="nav-item mb-2">
                    <a href="/timeline" class="nav-link p-1 d-flex align-items-center text-light">
                        <ion-icon name="home-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">ホーム</span>
                    </a>
                </li>
                <li class="nav-item mb-2 position-relative">
                    <a href="#" class="nav-link p-1 d-flex align-items-center text-light">
                        <ion-icon name="notifications-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">通知</span>
                    </a>
                    <?php if ($notificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $notificationCount > 99 ? "99+" : $notificationCount ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="nav-item mb-2">
                    <a href="/#" class="nav-link p-1 d-flex align-items-center text-light">
                        <ion-icon name="mail-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">メッセージ</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/profile<?= '?user=' . $user->getUsername() ?>" class="nav-link p-1 d-flex align-items-center text-light">
                        <ion-icon name="person-circle-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">プロフィール</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="" class="nav-link p-1 d-flex align-items-center text-light" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <ion-icon name="create-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">投稿</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/logout" class="nav-link p-1 d-flex align-items-center text-light">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span class="fs-5 ms-2 d-none d-sm-inline">ログアウト</span>
                    </a>
                </li>
            </ul>
            <div id="profile-dropdown" class="dropdown mb-4">
                <a href="#" class="d-flex align-items-center text-light text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="text-light d-none d-sm-inline mx-1">@<?= $user->getUsername() ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-light shadow">
                    <li><a class="dropdown-item" href="/user/delete">アカウント削除</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div id="main-content" class="col bg-light text-dark" style="max-height: 100vh; overflow-y: hidden;">
        <!-- 下のコメントアウト部分を各ページファイルで作成する -->
        <!-- Content area... -->
    <?php endif; ?>