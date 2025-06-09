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
                <form action="post" id="login-form">
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <div class="mb-3">
                        <label for="login-email" class="form-label">メールアドレス</label>
                        <input type="text" class="form-control" id="login-email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="login-password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="login-password" name="password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="login-btn">ログイン</button>
                    <div class="text-center mt-2">
                        <a href="/password/forgot">パスワードを忘れた方はこちら</a>
                    </div>
                </form>
                <hr>
                <a id="guest-login" class="btn btn-secondary w-100 mt-3">ゲストログイン</a>
            </div>
            <!-- 新規登録フォーム -->
            <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                <form action="post" id="register-form">
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <div class="mb-3">
                        <label for="register-name" class="form-label">名前</label>
                        <input type="text" class="form-control" id="register-name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="register-username" class="form-label">ユーザー名</label>
                        <input type="text" class="form-control" id="register-username" name="username">
                    </div>
                    <div class="mb-3">
                        <label for="register-email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" id="register-email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="register-password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="register-password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="register-confirm-password" class="form-label">パスワード確認用</label>
                        <input type="password" class="form-control" id="register-confirm-password" name="confirm-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="register-btn">登録</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="js/common.js"></script>
<script src="js/login.js"></script>
<script src="js/guest-login.js"></script>
<script src="js/register.js"></script>