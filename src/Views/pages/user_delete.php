<div class="container-fluid bg-light pt-5" style="min-height: 100vh;">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card py-5 px-3 my-3">
                <h2 class="text-center mb-4">アカウント削除</h2>
                <p class="text-center mb-0">アカウントを削除するには、以下のチェックボックスにチェックを入れてください。</p>
                <form id="account-delete-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <div class="d-flex justify-content-center align-items-center mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete">
                        <label class="form-check-label ms-2" for="confirmDelete">
                            アカウントを削除します
                        </label>
                    </div>
                    <div class="text-center">
                        <button id="deleteButton" class="btn btn-danger mt-3" disabled>アカウント削除</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/js/user_delete.js"></script>