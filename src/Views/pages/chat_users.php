<div id="csrf-token" style="display: none;" data-token="<?= Helpers\CrossSiteForgeryProtection::getToken(); ?>"></div>
<div class="bg-white p-3 my-0 mx-auto d-flex flex-column" style="max-width: 600px; height: 100%;">
    <div id="chat-users-not-exists" class="py-3 text-center d-none">
        <h6>現在チャットしているユーザーはいません。</h6>
    </div>

    <div id="list-wrapper" class="flex-grow-1" style="overflow-y: scroll;">
        <div id="chat-usres-list">
        </div>

        <div id="spinner" class="text-center d-none my-2">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script src="/js/chat_user.js"></script>