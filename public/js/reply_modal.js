document.addEventListener("DOMContentLoaded", async function () {

    const createBtn = document.querySelector("#reply-create-btn");

    function toggleUploadBlock() {
        createBtn.classList.remove("d-none");
    }

    toggleUploadBlock();

    /**
     * 返信ポスト作成モーダル
     * ファイルinput値変更時の処理
     */
    const replyImageInput = document.querySelector("#reply-image");
    replyImageInput.addEventListener("change", function (event) {
        const file = event.target.files[0]; // アップロードされたファイルを取得

        if (file && file.type.startsWith("image/")) { // ファイルが画像の場合のみ処理
            const reader = new FileReader(); // FileReaderオブジェクトを作成

            reader.onload = function (e) {
                const replyImagePreview = document.querySelector("#reply-image-preview");
                replyImagePreview.src = e.target.result; // 読み込んだ画像をプレビューに設定

                const replyImagePreviewWrapper = document.querySelector("#reply-image-preview-wrapper");
                replyImagePreviewWrapper.classList.add("d-flex");
                replyImagePreviewWrapper.classList.remove("d-none");
            };

            reader.readAsDataURL(file); // ファイルをデータURLとして読み込む
        }
    });


    /**
     * 返信ポスト作成モーダル
     * 選択された画像削除アイコンクリック時の処理
     */
    const replyImageDeleteIcon = document.querySelector("#reply-image-delete-icon");
    replyImageDeleteIcon.addEventListener("click", function (event) {
        replyImageInput.value = "";
        const replyImagePreview = document.querySelector("#reply-image-preview");
        replyImagePreview.src = "";

        const replyImagePreviewWrapper = document.querySelector("#reply-image-preview-wrapper");
        replyImagePreviewWrapper.classList.add("d-none");
        replyImagePreviewWrapper.classList.remove("d-flex");
    });


    /**
     * 返信ポスト作成モーダル
     * 作成ボタンクリック時の処理
     */
    const form = document.querySelector("#create-reply-form");
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        const submitter = event.submitter.id;
        let type = "create";

        const formData = new FormData(form);
        formData.append("type", type);
        const responseJson = await sendPostRequest("/post/create", formData);

        if (responseJson === null) {
            alert("エラーが発生しました。");
        }

        if (responseJson.status === "success") {
            if (responseJson.redirectUrl) {
                window.location.href = responseJson.redirectUrl;
            }
        } else {
            if (responseJson.status === "fieldErrors") {
                for (const field in responseJson.fieldErrors) {
                    setFormValidation(field.replace("post-", "reply-"), responseJson.fieldErrors[field]);
                }
            }
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    });
});
