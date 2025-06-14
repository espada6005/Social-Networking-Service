<div class="container-fluid pt-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card py-5 px-3 my-3">
                <h2 class="text-center mb-4">メールアドレス検証</h2>
                <form id="verify-resend-form" >
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <p class="text-center mb-0">検証メールからメールアドレス検証を完了させてください。</p>
                    <p class="text-center">検証メールが届いていない、もしくはリンクの有効期限が切れている場合は、下のボタンより再送信することができます。</p>
                    <div class="text-center mt-5">
                        <button id="email-verification-resend-btn" type="submit" class="btn btn-primary w-180">検証用メールを再送信する</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/js/verify_resend.js"></script>