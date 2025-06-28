async function sendPostRequest(url, body) {
    try {
        const response = await fetch(url, {
            method: "POST",
            body: body
        });
        const responseJson = await response.json();
        return responseJson;
    } catch (error) {
        console.error(`Error: ${error}`);
        return null;
    }
}

async function resetFormValidations() {
    const invalidInputs = document.querySelectorAll("input.is-invalid, textarea.is-invalid");
    invalidInputs.forEach(function (input) {
        input.classList.remove("is-invalid");
    });
}

async function resetFormValidation(fieldId) {
    const invalidInput = document.getElementById(fieldId);
    invalidInput.classList.remove("is-invalid");
}

async function setFormValidation(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add("is-invalid");
    const errorMsg = document.getElementById(`${fieldId}-error-msg`);
    errorMsg.innerText = message;
}

function createPostEl(post, parent) {
    const cardDiv = document.createElement("div");
    cardDiv.classList.add("card", "p-3", "rounded-0", `post-${post.postId}`);
    cardDiv.style.cursor = "pointer";
    cardDiv.addEventListener("click", () => {
        window.location.href = post.postPath;
    });

    const cardContentDiv = document.createElement("div");
    cardContentDiv.classList.add("d-flex", "gap-2");

    /** 左ブロック */
    // プロフィール画像
    const leftDiv = document.createElement("div");

    const profileLink = document.createElement("a");
    profileLink.href = post.profilePath;

    const profileImg = document.createElement("img");
    profileImg.src = post.profileImagePath;
    profileImg.alt = "プロフィール画像";
    profileImg.width = 40;
    profileImg.height = 40;
    profileImg.classList.add("rounded-circle");

    profileLink.appendChild(profileImg);
    leftDiv.appendChild(profileLink);

    /** 右ブロック */
    const rightDiv = document.createElement("div");
    rightDiv.classList.add("flex-grow-1");

    // 名前, 3点ドットアイコン
    const nameThreeDotsDiv = document.createElement("div");
    nameThreeDotsDiv.classList.add("d-flex", "justify-content-between");

    const nameLink = document.createElement("a");
    nameLink.classList.add("text-black", "fw-semibold", "fs-6", "hover-underline");
    nameLink.href = post.profilePath;
    nameLink.textContent = post.name;

    if (post.userType === "INFLUENCER") {
        // インフルエンサーの場合は名前の後にバッチアイコンをつける
        const influencerIcon = document.createElement("ion-icon");
        influencerIcon.setAttribute("name", "shield-checkmark");
        influencerIcon.style.color = "#dbbf4b";
        influencerIcon.style.height = "18px";
        influencerIcon.style.verticalAlign = "text-top";
        nameLink.appendChild(influencerIcon);
    }

    const dropdownDiv = document.createElement("div");
    dropdownDiv.classList.add("dropdown");
    dropdownDiv.style.height = "22px";
    dropdownDiv.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
    });

    const threeDotsIcon = document.createElement("ion-icon");
    threeDotsIcon.setAttribute("name", "ellipsis-horizontal-outline");
    threeDotsIcon.classList.add("dropdown-toggle", "rounded", "hover-action");
    threeDotsIcon.setAttribute("data-bs-toggle", "dropdown");
    threeDotsIcon.setAttribute("aria-expanded", "false");
    threeDotsIcon.style.cursor = "pointer";

    const dropdownMenu = document.createElement("ul");
    dropdownMenu.classList.add("dropdown-menu");
    dropdownMenu.style.minWidth = 0;

    const deleteItem = document.createElement("li");
    const deleteLink = document.createElement("a");
    deleteLink.classList.add("dropdown-item");
    if (!post.deletable) deleteLink.classList.add("disabled");
    deleteLink.href = "#";
    deleteLink.innerText = "削除";
    deleteLink.addEventListener("click", async (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (confirm("ポストを削除するとこの投稿に紐づくデータ（返信ポスト、いいね）も削除されます。削除しますか。")) {
            await deletePost(post.postId);
        }
    });

    deleteItem.appendChild(deleteLink);
    dropdownMenu.appendChild(deleteItem);
    dropdownDiv.appendChild(threeDotsIcon);
    dropdownDiv.appendChild(dropdownMenu);

    nameThreeDotsDiv.appendChild(nameLink);
    nameThreeDotsDiv.appendChild(dropdownDiv);

    // ユーザー名, 投稿時間
    const usernamePostedAtDiv = document.createElement("div");
    usernamePostedAtDiv.classList.add("w-100", "mb-1");

    const usernamePostedAtLink = document.createElement("a");
    usernamePostedAtLink.classList.add("text-secondary", "hover-underline", "d-flex");
    usernamePostedAtLink.href = post.profilePath;
    usernamePostedAtLink.style.fontSize = "14px";

    const usernameSpan = document.createElement("span");
    usernameSpan.classList.add("d-inline-block");
    usernameSpan.style.overflow = "hidden";
    usernameSpan.style.textOverflow = "ellipsis";
    usernameSpan.style.whiteSpace = "nowrap";
    usernameSpan.innerText = `@${post.username}`;

    const postedAtSpan = document.createElement("span");
    postedAtSpan.classList.add("d-inline-block", "flex-shrink-0");
    postedAtSpan.innerText = `・${post.postedAt}`;

    usernamePostedAtLink.appendChild(usernameSpan);
    usernamePostedAtLink.appendChild(postedAtSpan);
    usernamePostedAtDiv.appendChild(usernamePostedAtLink);

    // 本文
    const textBody = document.createElement("div");
    textBody.innerText = post.content;

    // 画像
    const postImgDiv = document.createElement("div");
    postImgDiv.classList.add("text-center");
    const postImg = document.createElement("img");
    postImg.src = post.thumbnailPath;
    postImg.alt = "ポスト画像";
    postImg.style.width = "100%";
    postImg.style.maxWidth = "300px";
    postImg.classList.add("border");
    const postImgLink = document.createElement("a");
    postImgLink.href = post.imagePath;
    postImgLink.target = "_blank";
    postImgLink.rel = "noopener noreferrer";
    postImgLink.addEventListener("click", (event) => {
        event.stopPropagation();
    });
    postImgLink.appendChild(postImg);
    postImgDiv.appendChild(postImgLink);

    // 返信アイコン・返信数, いいねアイコン・いいね数
    const iconsDiv = document.createElement("div");
    iconsDiv.classList.add("mt-2", "d-flex", "align-items-center");

    const replyDiv = document.createElement("div");
    replyDiv.classList.add("text-dark", "text-decoration-none", "d-flex", "align-items-center", "rounded", "hover-action");
    replyDiv.style.cursor = "pointer";
    replyDiv.setAttribute("data-bs-toggle", "modal");
    replyDiv.setAttribute("data-bs-target", "#createReplyModal");
    replyDiv.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();

        const replyToIdInput = document.getElementById("reply-to-id");
        replyToIdInput.value = post.postId;
    });

    const replyIcon = document.createElement("ion-icon");
    replyIcon.setAttribute("name", "chatbubbles-outline");

    const replyCount = document.createElement("span");
    replyCount.classList.add("ms-1");
    replyCount.innerText = post.replyCount;

    replyDiv.appendChild(replyIcon);
    replyDiv.appendChild(replyCount);

    const heartDiv = document.createElement("div");
    heartDiv.classList.add("ms-3", "text-dark", "text-decoration-none", "d-flex", "align-items-center", "rounded", "hover-action");
    heartDiv.style.cursor = "pointer";
    heartDiv.addEventListener("click", async (event) => {
        event.preventDefault();
        event.stopPropagation();

        const heartIcon = heartDiv.querySelector("[name='heart']");
        if (heartIcon) {
            await unlikePost(post.postId);
        } else {
            await likePost(post.postId);
        }
    });

    const heartIcon = document.createElement("ion-icon");
    if (post.liked) {
        heartIcon.setAttribute("name", "heart");
        heartIcon.style.color = "red";
    } else {
        heartIcon.setAttribute("name", "heart-outline");
    }

    const heartCount = document.createElement("span");
    heartCount.classList.add("ms-1", "heart-count");
    heartCount.innerText = post.likeCount;

    heartDiv.appendChild(heartIcon);
    heartDiv.appendChild(heartCount);

    iconsDiv.appendChild(replyDiv);
    iconsDiv.appendChild(heartDiv);

    rightDiv.appendChild(nameThreeDotsDiv);
    rightDiv.appendChild(usernamePostedAtDiv);
    rightDiv.appendChild(textBody);
    if (post.imagePath && post.thumbnailPath) rightDiv.appendChild(postImgDiv);
    rightDiv.appendChild(iconsDiv);

    /** 左右のブロックをカードのコンテンツブロックの子要素に追加 */
    cardContentDiv.appendChild(leftDiv);
    cardContentDiv.appendChild(rightDiv);
    cardDiv.appendChild(cardContentDiv);

    parent.appendChild(cardDiv);
}

async function likePost(postId) {
    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("csrf_token", csrfToken);
    const resData = await sendPostRequest("post/like", formData);

    if (resData.success) {
        const likedPosts = document.querySelectorAll(`.post-${postId}`);
        for (const likedPost of likedPosts) {
            const heartIcon = likedPost.querySelector("[name='heart-outline']");
            heartIcon.name = "heart";
            heartIcon.classList.remove("unlike");
            heartIcon.classList.add("like");
            heartIcon.style.color = "red";

            const heartCount = likedPost.querySelector(".heart-count");
            const currentCount = +heartCount.textContent;
            heartCount.innerText = (currentCount + 1).toString();
        }
    } else {
        if (resData.error) {
            alert(resData.error);
        }
    }
}

async function unlikePost(postId) {
    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("csrf_token", csrfToken);
    const resData = await sendPostRequest("post/unlike", formData);

    if (resData.success) {
        const unlikedPosts = document.querySelectorAll(`.post-${postId}`);
        for (const unlikedPost of unlikedPosts) {
            const heartIcon = unlikedPost.querySelector("[name='heart']");
            heartIcon.name = "heart-outline";
            heartIcon.classList.remove("like");
            heartIcon.classList.add("unlike");
            heartIcon.style.color = "";

            const heartCount = unlikedPost.querySelector(".heart-count");
            const currentCount = +heartCount.textContent;
            heartCount.innerText = (currentCount - 1).toString();
        }
    } else {
        if (resData.error) {
            alert(resData.error);
        }
    }
}

async function deletePost(postId) {
    const csrfToken = document.querySelector("#csrf-token").dataset.token;
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("csrf_token", csrfToken);
    const responseJson = await sendPostRequest("/post/delete", formData);

    if (responseJson.status === "success") {
        const deletedPosts = document.querySelectorAll(`.post-${postId}`);
        for (const deletedPost of deletedPosts) {
            deletedPost.remove();
        }
        window.location.reload();
    } else {
        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    }
}