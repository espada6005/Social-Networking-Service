document.addEventListener("DOMContentLoaded", async function () {

    const csrfToken = document.querySelector("#csrf-token").dataset.token;

    const params = new URLSearchParams(window.location.search);
    const user = params.get("user" ?? "");

    const formData = new FormData();
    formData.append("user", user);
    formData.append("csrf_token", csrfToken);
    const responseJson = await sendPostRequest("profile/init", formData);

    if (responseJson.status) {
        initprofile(responseJson.user);
    } else {
        alert("error");
    }

    function initprofile(user) {
        const nameEl = document.querySelector("#profile-name");
        const usernameEl = document.querySelector("#profile-username");
        const profileTextEl = document.querySelector("#profile-text-area");
        const profileImageEl = document.querySelector("#profile-image-area");
        const profileImageLinkEl = document.querySelector("#profile-image-link");
        const followeeCountEl = document.querySelector("#followee-count");
        const followerCountEl = document.querySelector("#follower-count");
        const followeeLink = document.querySelector("#followee-link");
        const followerLink = document.querySelector("#follower-link");

        nameEl.innerText = user.name;
        usernameEl.innerText = "@" + user.username;
        profileImageEl.src = user.profileImagePath;
        profileTextEl.innerText = user.profileText ?? "";
        profileImageLinkEl.href = user.profileImagePath;
        followeeCountEl.innerText = user.followeeCount;
        followerCountEl.innerText = user.followerCount;
        followeeLink.href = `/followees?user=${user.username}`;
        followerLink.href = `/followers?user=${user.username}`;

        const nameInput = document.querySelector("#name");
        const usernameInput = document.querySelector("#username");
        const profileTextInput = document.querySelector("#profile-text");
        const defaultRadio = document.querySelector("#profile-image-type-default");
        const customRadio = document.querySelector("#profile-image-type-custom");
        const profileImagePreviewEl = document.querySelector("#profile-image-preview");

        const messageBtn = document.querySelector("#message-btn");
        const editBtn = document.querySelector("#profile-edit-btn");
        const followBtn = document.querySelector("#profile-follow-btn");
        const unfollowBtn = document.querySelector("#profile-unfollow-btn");
        const followerLabel = document.querySelector("#follower-label");

        nameInput.value = user.name;
        usernameInput.value = user.username;
        profileTextInput.value = user.profileText ?? "";
        defaultRadio.checked = user.profileImageType === "default";
        customRadio.checked = user.profileImageType === "custom";
        profileImagePreviewEl.src = user.profileImagePath;


        if (user.isLoggedInUser) {
            editBtn.classList.remove("d-none");
        } else {
            messageBtn.href = `/messages/chat?user=${user.username}`;
            messageBtn.classList.remove("d-none");

            if (user.isFollowee) {
                unfollowBtn.classList.remove("d-none");
            } else {
                followBtn.classList.remove("d-none");
            }

            if (user.isFollower) {
                followerLabel.classList.remove("d-none");
            }
        }

        // インフルエンサーの場合はバッチを表示
        if (user.userType === "INFLUENCER") {
            const influencerIcon = document.createElement("ion-icon");
            influencerIcon.setAttribute("name", "shield-checkmark");
            influencerIcon.style.color = "#dbbf4b";
            influencerIcon.style.height = "25px";
            influencerIcon.style.verticalAlign = "text-top";
            nameEl.appendChild(influencerIcon);
        }

        const profileBlock = document.querySelector("#profile-block");
        profileBlock.classList.remove("d-none");
    }

    /**
 * プロフィール編集モーダル
 * 画像タイプラジオボタン変更時の処理
 */
    const defaultRadio = document.querySelector("#profile-image-type-default");
    const customRadio = document.querySelector("#profile-image-type-custom");
    const uploadBlock = document.querySelector("#profile-image-upload-block");

    function toggleUploadBlock() {
        if (defaultRadio.checked) {
            uploadBlock.classList.add("d-none");
        } else {
            uploadBlock.classList.remove("d-none");
        }
    }

    toggleUploadBlock();
    customRadio.addEventListener("change", toggleUploadBlock);
    defaultRadio.addEventListener("change", toggleUploadBlock);

    /**
   * プロフィール編集モーダル
   * ファイルinput値変更時の処理
   */
    const profileImageInput = document.querySelector("#profile-image");
    profileImageInput.addEventListener("change", function (event) {
        const file = event.target.files[0]; // アップロードされたファイルを取得

        if (file && file.type.startsWith("image/")) { // ファイルが画像の場合のみ処理
            const reader = new FileReader(); // FileReaderオブジェクトを作成

            reader.onload = function (e) {
                const previewImage = document.querySelector("#profile-image-preview");
                previewImage.src = e.target.result; // 読み込んだ画像をプレビューに設定
                previewImage.style.display = "block"; // img要素を表示
                resetFormValidation("profile-image"); // バリデーションエラーがあればクリア
            };

            reader.readAsDataURL(file); // ファイルをデータURLとして読み込む
        }
    });

    /**
     * プロフィール編集モーダル
     * 保存ボタンクリック時の処理
     */
    const form = document.querySelector("#profile-edit-form");
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        const formData = new FormData(form);
        const responseJson = await sendPostRequest("form/profile/update", formData);

        if (responseJson === null) {
            alert("エラーが発生しました。");
        }

        if (responseJson.status === "success") {
            window.location.reload();
            console.log("プロフィール更新成功");
        }

        if (responseJson.status === "fieldErrors") {
            for (const field in responseJson.message) {
                setFormValidation(field, responseJson.message[field]);
            }
        }

        if (responseJson.error) {
            alert(responseJson.error);
        }
    });

/**
* フォローボタンクリック時の処理
*/
const followBtn = document.querySelector("#profile-follow-btn");
followBtn.addEventListener("click", async function (event) {
    event.preventDefault();

    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const formData = new FormData();
    formData.append("user", user ?? "");
    formData.append("csrf_token", csrfToken);
    const responseJson = await sendPostRequest("follow", formData);

    if (responseJson === null) {
        alert("エラーが発生しました。");
    }

    if (responseJson.status === "success") {
        window.location.reload();
    } else {
        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    }
});

/**
 * アンフォローボタンクリック時の処理
 */
const unfollowBtn = document.querySelector("#profile-unfollow-btn");
unfollowBtn.addEventListener("click", async function (event) {
    event.preventDefault();

    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const formData = new FormData();
    formData.append("user", user ?? "");
    formData.append("csrf_token", csrfToken);
    const responseJson = await sendPostRequest("unfollow", formData);

    if (responseJson === null) {
        alert("エラーが発生しました。");
    }

    if (responseJson.status === "success") {
        window.location.reload();
    } else {
        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    }
});

/**
* ポスト一覧初期化処理
*/
const postBlock = document.querySelector("#post-block");
const spinner = document.querySelector("#spinner");
const limit = 30;
const listData = {
    posts: {
        offset: 0,
        loadAll: false,
        listEl: document.querySelector("#posts-list"),
    },
    replies: {
        offset: 0,
        loadAll: false,
        listEl: document.querySelector("#replies-list")
    },
    likes: {
        offset: 0,
        loadAll: false,
        listEl: document.querySelector("#likes-list"),
    },
}

async function loadList(listType = "posts") {
    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const params = new URLSearchParams(window.location.search);
    const user = params.get("user");
    const formData = new FormData();
    formData.append("user", user ?? "");
    formData.append("limit", limit);
    formData.append("offset", listData[listType].offset ?? 0);
    formData.append("csrf_token", csrfToken);
    const responseJson = await sendPostRequest(`/${listType}/init`, formData);

    if (responseJson.status === "success") {
        if (responseJson.posts.length) {
            for (const post of responseJson.posts) {
                createPostEl(post, listData[listType].listEl);
            }
            listData[listType].offset += limit;
        } else {
            listData[listType].loadAll = true;

            if (listData[listType].offset === 0) {
                document.querySelector("#post-not-found").classList.remove("d-none");
                document.querySelector("#list-wrapper").classList.add("d-none");
            }
        }
        spinner.classList.add("d-none");
        postBlock.classList.remove("d-none");
        postBlock.classList.add("d-flex", "flex-column");
    } else {
        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    }
}
await loadList();

/**
 * ポスト種類タブ切り替え時の処理
 */
let activeTab = "posts";
document.querySelectorAll("#post-type-tabs .nav-link").forEach(link => {
    link.addEventListener("click", async function (event) {
        event.preventDefault();

        // 全てのnav-linkからactiveクラスを削除
        document.querySelectorAll("#post-type-tabs .nav-link").forEach(item => {
            item.classList.remove("active");
        });

        // クリックされたnav-linkにactiveクラスを追加
        this.classList.add("active");

        // 全てのセクションを非表示
        document.querySelectorAll("div[id$='-list']").forEach(section => {
            section.classList.add("d-none");
        });

        // クリックされたリンクに対応するセクションを表示
        const target = document.querySelector(this.getAttribute("data-target"));
        if (target) {
            target.classList.remove("d-none");
        }

        // 表示を戻す
        document.querySelector("#post-not-found").classList.add("d-none");
        document.querySelector("#list-wrapper").classList.remove("d-none");

        // リストのスクロールをトップに戻す
        const listWrapper = document.querySelector("#list-wrapper");
        listWrapper.scrollTop = 0;
        activeTab = this.id.replace("-nav-link", "");
        if (listData[activeTab].offset === 0) {
            await loadList(activeTab);
        }
    });
});


/**
 * list-wrapperのスクロール時の処理
 */
document.querySelector("#list-wrapper").addEventListener("scroll", async function () {
    const content = this;

    // 要素がスクロールの最下部に達したかを確認
    if (content.scrollTop + content.clientHeight >= content.scrollHeight) {
        if (!listData[activeTab].loadAll) {
            spinner.classList.remove("d-none");
            await loadList(activeTab);
        }
    }
});

});