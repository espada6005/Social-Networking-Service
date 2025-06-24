<?php if ($user !== null && $user->getEmailConfirmedAt() !== null): ?>
    <div class="modal fade" id="createReplyModal" tabindex="-1" aria-labelledby="createReplyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createReplyModalLabel">新規返信ポスト</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-1">
                    <form method="post" id="create-reply-form">
                                                <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>">
                        <div id="reply-create-block">
                            <input type="hidden" id="reply-to-id" name="post-reply-to-id" value="">
                            <div class="mb-3">
                                <label for="post-content" class="form-label">コンテンツ</label>
                                <textarea class="form-control" id="reply-content" name="post-content" rows="3" required></textarea>
                                <div id="reply-content-error-msg" class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="post-image" class="form-label">画像</label>
                                <input type="file" class="form-control" id="reply-image" name="post-image" accept=".jpg, .jpeg, .png, .gif">
                                <div id="reply-image-error-msg" class="invalid-feedback"></div>
                                <div id="reply-image-preview-wrapper" class="d-none justify-content-center mt-3">
                                    <div class="text-center">
                                        <p class="p-0 m-0">選択された画像</p>
                                        <img id="reply-image-preview" src="" alt="ポスト画像プレビュー" class="border" style="width: 100%; max-width: 150px;">
                                    </div>
                                    <ion-icon id="reply-image-delete-icon" name="close-outline" class="fs-4" style="cursor: pointer;"></ion-icon>
                                </div>
                            </div>

                            <div class="mt-5 text-end">
                                <button id="reply-create-btn" type="submit" class="btn btn-primary d-none">作成</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>