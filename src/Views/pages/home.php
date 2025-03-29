<header class="text-center pt-5">
    <h1>Welcome to SNS Service</h1>
    <p class="tagline">Connect, Share, and Explore</p>
</header>
<div class="container my-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header d-flex">
            <ul class="nav nav-underline" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="ture">ログイン</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="false">新規登録</button>
                </li>
            </ul>
        </div>
        <div class="tab-content card-body">
            <!-- ログインフォーム -->
            <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                <div class="mb-3">
                    <label for="login-username" class="form-label">ユーザー名</label>
                    <input type="text" class="form-control" id="login-username">
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">パスワード</label>
                    <input type="password" class="form-control" id="login-password">
                </div>
                <button type="submit" id="login-btn" class="btn btn-primary w-100">ログイン</button>
                <div class="text-center mt-2">
                    <a href="#forgot">パスワードを忘れた方はこちら</a>
                </div>
                <hr>
                <button type="button" class="btn btn-outline-primary w-100 mt-3">ゲストログイン</button>
            </div>
            <!-- 新規登録フォーム -->
            <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                <div class="mb-3">
                    <label for="register-name" class="form-label">名前</label>
                    <input type="text" class="form-control" id="register-name">
                </div>
                <div class="mb-3">
                    <label for="register-username" class="form-label">ユーザー名</label>
                    <input type="text" class="form-control" id="register-username">
                </div>
                <div class="mb-3">
                    <label for="register-email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control" id="register-email">
                </div>
                <div class="mb-3">
                    <label for="register-password" class="form-label">パスワード</label>
                    <input type="password" class="form-control" id="register-password">
                </div>
                <div class="mb-3">
                    <label for="register-confirm-password" class="form-label">パスワード確認用</label>
                    <input type="password" class="form-control" id="register-confirm-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">登録</button>
            </div>
        </div>
    </div>
</div>