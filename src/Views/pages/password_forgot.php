<div class="container-fluid bg-light pt-5" style="min-height: 100vh;">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card py-5 px-3 my-3">
                <h2 class="text-center mb-4">パスワードをお忘れの場合</h2>
                <p class="text-center mb-0">登録したメールアドレスを入力してください。</p>
                <p class="text-center">このメールアドレス宛にパスワード変更用リンクを送信します。</p>
                <form id="password-forgot-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div id="email-error-msg" class="invalid-feedback"></div>
                    </div>
                    <div class="text-center mt-5">
                        <button id="password-forgot-btn" type="submit" class="btn btn-primary w-100">
                            <span>送信</span>
                            <div id="password-forgot-spinner" class="spinner-border text-light spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/js/password_forget.js"></script>