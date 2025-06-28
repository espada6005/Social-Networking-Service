document.addEventListener("DOMContentLoaded", async function () {
    /**
     * ポスト, リプライ初期化処理
     */
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const postId = urlParams.get("id");
    const csrfToken = document.querySelector("#csrf-token").dataset.token;

    const replyLimit = 10;
    let replyOffset = 0;
    let loadAllReplies = false;

    const postBlock = document.querySelector("#post-block");
    const repliesBlock = document.querySelector("#replies-block");
    const parentPostBlock = document.querySelector("#parent-post-block");
    const parentPost = document.querySelector("#parent-post");
    const spinner = document.querySelector("#spinner");

    async function loadPost() {
        const formData = new FormData();
        formData.append("postId", postId ?? "");
        formData.append("csrf_token", csrfToken);
        const responseJson = await sendPostRequest("/post/detail", formData);

        if (responseJson.status === "success") {

            if (responseJson.post === null) {
                const postNotFound = document.querySelector("#post-not-found");
                postNotFound.classList.remove("d-none");
                return;
            } else {
                createPostEl(responseJson.post, postBlock);

                if (responseJson.parentPost) {
                    createPostEl(responseJson.parentPost, parentPost);
                    parentPostBlock.classList.remove("d-none");
                }
                postBlock.classList.remove("d-none");
            }
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }

    async function loadReplies() {
        let showReplies = true;

        const formData = new FormData();
        formData.append("postId", postId ?? "");
        formData.append("csrf_token", csrfToken);
        formData.append("replyLimit", replyLimit);
        formData.append("replyOffset", replyOffset);
        const responseJson = await sendPostRequest("/post/replies", formData);

        if (responseJson.status === "success") {
            if (responseJson.replies && responseJson.replies.length) {
                const replies = document.querySelector("#replies");
                for (const reply of responseJson.replies) {
                    createPostEl(reply, replies);
                }
                replyOffset += replyLimit;
            } else {
                loadAllReplies = true;
                if (replyOffset === 0) {
                    showReplies = false;
                }
            }

            if (showReplies) {
                repliesBlock.classList.add("d-flex");
                repliesBlock.classList.remove("d-none");
            }

            spinner.classList.add("d-none");
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }

    await loadPost();
    await loadReplies();


    /**
     * replies-wrapperのスクロール時の処理
     */
    document.querySelector("#replies-wrapper").addEventListener("scroll", async function () {
        const content = this;

        // 要素がスクロールの最下部に達したかを確認
        if (content.scrollTop + content.clientHeight >= content.scrollHeight) {
            if (!loadAllReplies) {
                spinner.classList.remove("d-none");
                await loadReplies();
            }
        }
    });


    /**
     * toggle-parent-post-linkのクリック時の処理
     * リンクのテキストとアイコンを変更する
     */
    const toggleLink = document.querySelector("#toggle-parent-post-link");
    const linkText = document.querySelector("#link-text");
    const linkIcon = document.querySelector("#link-icon");
    const targetBlock = document.querySelector("#collapse-block");

    toggleLink.addEventListener("click", function () {
        setTimeout(function () {
            if (targetBlock.classList.contains("show")) {
                linkText.textContent = "返信元ポストを隠す";
                linkIcon.name = "chevron-up-outline";
            } else {
                linkText.textContent = "返信元ポストを見る";
                linkIcon.name = "chevron-down-outline";
            }
        }, 400); // Bootstrap Collapseのデフォルトアニメーション分の待機
    });
});
