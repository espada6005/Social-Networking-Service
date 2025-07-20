<div class="container-fluid bg-light pt-5" style="min-height: 100vh;">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card py-5 px-3 my-3">
                <h2 class="text-center mb-4">パスワードリセット</h2>
                <form id="password-reset-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">新しいパスワード</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div id="password-error-msg" class="invalid-feedback"></div>
                        <span class="form-text m-0 ms-2">(8〜30文字 + 大文字/小文字/数字/記号 必須)</span>
                    </div>
                    <div class="mb-3">
                        <label for="confirm-password" class="form-label">パスワード（確認）</label>
                        <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
                        <div id="confirm-password-error-msg" class="invalid-feedback"></div>
                    </div>
                    <div class="text-center mt-5">
                        <button id="password-reset-btn" type="submit" class="btn btn-primary w-100">
                            パスワードリセット
                            <div id="btn-spinner" class="spinner-border spinner-border-sm text-light d-none" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/js/password_reset.js"></script>