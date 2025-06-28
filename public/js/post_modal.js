document.addEventListener("DOMContentLoaded", async function () {
    /**
     * ポスト作成モーダル
     * datetimepicker
     */
    new tempusDominus.TempusDominus(document.querySelector("#post-datetimepicker"), {
        localization: {
            format: "yyyy/MM/dd HH:mm",
        },
    });


    /**
     * ポスト作成モーダル
     * 予約投稿チェックボックス変更時の処理
     */
    const scheduleSwicher = document.querySelector("#post-schedule");
    const datetimepicker = document.querySelector("#post-datetimepicker");
    // const draftBtn = document.querySelector("#post-draft-btn");
    const createBtn = document.querySelector("#post-create-btn");
    const scheduleBtn = document.querySelector("#post-schedule-btn");

    function toggleUploadBlock() {
        if (scheduleSwicher.checked) {
            datetimepicker.classList.remove("d-none");
            // draftBtn.classList.add("d-none");
            createBtn.classList.add("d-none");
            scheduleBtn.classList.remove("d-none");
        } else {
            datetimepicker.classList.add("d-none");
            // draftBtn.classList.remove("d-none");
            createBtn.classList.remove("d-none");
            scheduleBtn.classList.add("d-none");
        }
    }

    toggleUploadBlock();
    scheduleSwicher.addEventListener("change", toggleUploadBlock);


    /**
     * ポスト作成モーダル
     * ファイルinput値変更時の処理
     */
    const postImageInput = document.querySelector("#post-image");
    postImageInput.addEventListener("change", function (event) {
        const file = event.target.files[0]; // アップロードされたファイルを取得

        if (file && file.type.startsWith("image/")) { // ファイルが画像の場合のみ処理
            const reader = new FileReader(); // FileReaderオブジェクトを作成

            reader.onload = function (e) {
                const postImagePreview = document.querySelector("#post-image-preview");
                postImagePreview.src = e.target.result; // 読み込んだ画像をプレビューに設定

                const postImagePreviewWrapper = document.querySelector("#post-image-preview-wrapper");
                postImagePreviewWrapper.classList.add("d-flex");
                postImagePreviewWrapper.classList.remove("d-none");
            };

            reader.readAsDataURL(file); // ファイルをデータURLとして読み込む
        }
    });


    /**
     * ポスト作成モーダル
     * 選択された画像削除アイコンクリック時の処理
     */
    const postImageDeleteIcon = document.querySelector("#post-image-delete-icon");
    postImageDeleteIcon.addEventListener("click", function (event) {
        postImageInput.value = "";
        const postImagePreview = document.querySelector("#post-image-preview");
        postImagePreview.src = "";

        const postImagePreviewWrapper = document.querySelector("#post-image-preview-wrapper");
        postImagePreviewWrapper.classList.add("d-none");
        postImagePreviewWrapper.classList.remove("d-flex");
    });


    /**
     * ポスト作成モーダル
     * 作成ボタンクリック時の処理
     */
    const form = document.querySelector("#create-post-form");
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        const submitter = event.submitter.id;
        let type = "create";
        if (submitter === "post-schedule-btn") type = "schedule";

        const formData = new FormData(form);
        formData.append("type", type);
        const responseJson = await sendPostRequest("/post/create", formData);

        if (responseJson === null) {
            alert("エラーが発生しました。");
        }

        if (responseJson.status === "success") {
            window.location.reload();
        } else {
            if (responseJson.status === "fieldErrors") {
                for (const field in responseJson.message) {
                    setFormValidation(field, responseJson.fieldErrors[field]);
                }
            }
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    });


    /**
     * ポスト作成モーダル - 予約タブ
     * 予約ポスト一覧取得処理
     */
    const listEl = document.querySelector("#scheduled-post-list");
    const detailEl = document.querySelector("#scheduled-post-detail");
    const notExistsLabel = document.querySelector("#scheduled-post-not-exists");
    const spinner = document.querySelector("#spinner");
    const limit = 30;
    let offset = 0;
    let loadAll = false;

    async function loadScheduledPosts() {
        const csrfToken = document.querySelector("#csrf-token").dataset.token;
        const formData = new FormData();
        formData.append("limit", limit);
        formData.append("offset", offset);
        formData.append("csrf_token", csrfToken);
        const responseJson = await sendPostRequest("/post/scheduled_posts/init", formData);

        if (responseJson.status === "success") {
            if (responseJson.scheduledPosts.length) {
                for (const scheduledPost of responseJson.scheduledPosts) {
                    createScheduledPostEl(scheduledPost, listEl);
                }
                offset += limit;
            } else {
                loadAll = true;

                if (offset === 0) {
                    notExistsLabel.classList.remove("d-none");
                    return;
                }
            }

            spinner.classList.add("d-none");
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }

    function createScheduledPostEl(scheduledPost, parent) {
        // 親要素のdiv
        const container = document.createElement("div");
        container.id = `post-${scheduledPost.postId}`;
        container.classList.add("p-1", `post-${scheduledPost.postId}`);
        container.addEventListener("mouseover", function () {
            container.style.cursor = "pointer";
            container.style.backgroundColor = "rgba(248, 249, 250, 1)";
        });
        container.addEventListener("mouseout", function () {
            container.style.backgroundColor = "";
        });
        container.addEventListener("click", function () {
            // ポストデータをコピー
            const detailScheduledAt = document.querySelector("#detail-scheduled-at");
            const detailContent = document.querySelector("#detail-content");
            const detailImageLink = document.querySelector("#detail-image-link");
            const detailImage = document.querySelector("#detail-image");

            const divs = this.querySelectorAll("div");
            detailScheduledAt.innerText = this.querySelector("small").textContent;
            detailContent.innerText = divs[1].textContent;
            if (divs[2].textContent && divs[3].textContent) {
                detailImageLink.href = divs[2].textContent;
                detailImage.src = divs[3].textContent;
                detailImageLink.classList.remove("d-none");
            } else {
                detailImageLink.href = "#";
                detailImage.src = "#";
                detailImageLink.classList.add("d-none");
            }

            // 削除ボタンのクリックfunction設定
            const deleteBtn = document.querySelector("#scheduled-post-delete-btn");
            const postId = parseInt(this.id.replace("post-", ""));
            deleteBtn.onclick = async function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (confirm("ポストを削除するとこの投稿に紐づくデータ（返信ポスト、いいね）も削除されます。削除しますか。")) {
                    await deletePost(postId);
                    returnList();
                }
            }

            // 表示切り替え
            listEl.classList.add("d-none");
            detailEl.classList.remove("d-none");
        });

        // 投稿日時
        const dateDiv = document.createElement("div");
        dateDiv.classList.add("d-flex", "align-items-center", "gap-1", "text-secondary");

        const icon = document.createElement("ion-icon");
        icon.name = "calendar-outline";

        const small = document.createElement("small");
        small.innerText = `${scheduledPost.scheduledAt}に投稿されます。`;

        dateDiv.appendChild(icon);
        dateDiv.appendChild(small);

        // 投稿内容
        const contentDiv = document.createElement("div");
        contentDiv.style.overflow = "hidden";
        contentDiv.style.textOverflow = "ellipsis";
        contentDiv.style.whiteSpace = "nowrap";
        contentDiv.textContent = scheduledPost.content;

        const imagePathDiv = document.createElement("div");
        imagePathDiv.textContent = scheduledPost.imagePath;
        imagePathDiv.classList.add("d-none");

        const thumbnailPathDiv = document.createElement("div");
        thumbnailPathDiv.textContent = scheduledPost.thumbnailPath;
        thumbnailPathDiv.classList.add("d-none");

        // containerにそれぞれのdivを追加
        container.appendChild(dateDiv);
        container.appendChild(contentDiv);
        container.appendChild(imagePathDiv);
        container.appendChild(thumbnailPathDiv);

        // 親要素のdivを引数で受け取った親要素に追加
        parent.appendChild(container);
    }


    /**
     * ポスト作成モーダル - 予約タブ
     * scheduled-post-list-wrapperのスクロール時の処理
     */
    document.querySelector("#scheduled-post-list-wrapper").addEventListener("scroll", async function () {
        const content = this;

        // 要素がスクロールの最下部に達したかを確認
        if (content.scrollTop + content.clientHeight >= content.scrollHeight) {
            if (!loadAll) {
                spinner.classList.remove("d-none");
                await loadScheduledPosts();
            }
        }
    });


    /**
     * ポスト作成モーダル - 予約タブ
     * 戻るアイコンクリック時の処理
     */
    function returnList() {
        detailEl.classList.add("d-none");
        listEl.classList.remove("d-none");

        if (listEl.children.length === 0) {
            notExistsLabel.classList.remove("d-none");
        }
    }
    document.querySelector("#return-icon").addEventListener("click", returnList);


    /**
     * ポスト作成モーダル
     * タブ切り替え時の処理
     */
    document.querySelectorAll("#createPostModal .nav-link").forEach(link => {
        link.addEventListener("click", async function (event) {
            event.preventDefault();

            // 全てのnav-linkからactiveクラスを削除
            document.querySelectorAll("#createPostModal .nav-link").forEach(item => {
                item.classList.remove("active");
            });

            // クリックされたnav-linkにactiveクラスを追加
            this.classList.add("active");

            // 全てのセクションを非表示
            document.querySelectorAll("#createPostModal div[id$='-block']").forEach(section => {
                section.classList.add("d-none");
            });

            // クリックされたリンクに対応するセクションを表示
            const target = document.querySelector(this.getAttribute("data-target"));
            if (target) {
                target.classList.remove("d-none");
                if (target.id === "post-schedule-block" && offset === 0) {
                    await loadScheduledPosts();
                }
            }
        });
    });
});
