document.addEventListener("DOMContentLoaded", async function () {
    /**
     * チャットユーザー初期化処理
     */
    const listEl = document.getElementById("chat-usres-list");
    const spinner = document.getElementById("spinner");
    const limit = 30;
    let offset = 0;
    let loadAll = false;
    const csrfToken = document.querySelector("#csrf-token").dataset.token;

    async function loadChatUsers() {
        const formData = new FormData();
        formData.append("csrf_token", csrfToken);
        formData.append("limit", limit);
        formData.append("offset", offset);
        const responseJson = await sendPostRequest("messages/users", formData);

        if (responseJson.status === "success") {
            if (responseJson.chatUsers.length) {
                for (const chatUser of responseJson.chatUsers) {
                    createChatUserEl(chatUser, listEl);
                }
                offset += limit;
            } else {
                loadAll = true;

                if (offset === 0) {
                    const notExistsLabel = document.getElementById("chat-users-not-exists");
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
    await loadChatUsers();

    function createChatUserEl(chatUser, parent) {
        // 親要素のdiv
        const container = document.createElement("div");
        container.classList.add("d-flex", "align-items-center", "p-1");
        container.addEventListener("mouseover", function () {
            container.style.cursor = "pointer";
            container.style.backgroundColor = "rgba(248, 249, 250, 1)";
        });
        container.addEventListener("mouseout", function () {
            container.style.backgroundColor = "";
        });
        container.addEventListener("click", function () {
            window.location.href = chatUser.chatPath;
        });

        // プロフィール画像のimg
        const img = document.createElement("img");
        img.src = chatUser.profileImagePath;
        img.alt = "プロフィール画像";
        img.width = 50;
        img.height = 50;
        img.classList.add("rounded-circle", "border", "flex-shrink-0");

        // 名前とユーザー名を含むdiv
        const textContainer = document.createElement("div");
        textContainer.classList.add("ms-3");
        textContainer.style.minWidth = "0";

        // 名前のdiv, h6
        const nameDiv = document.createElement("div");
        nameDiv.classList.add("d-flex", "align-items-center");

        const nameEl = document.createElement("h6");
        nameEl.classList.add("m-0");
        nameEl.textContent = chatUser.name;
        nameEl.style.overflow = "hidden";
        nameEl.style.textOverflow = "ellipsis";
        nameEl.style.whiteSpace = "nowrap";
        nameDiv.appendChild(nameEl);

        if (chatUser.userType === "INFLUENCER") {
            const influencerIcon = document.createElement("ion-icon");
            influencerIcon.setAttribute("name", "shield-checkmark");
            influencerIcon.classList.add("flex-shrink-0");
            influencerIcon.style.color = "#dbbf4b";
            nameDiv.appendChild(influencerIcon);
        }

        // ユーザー名のp
        const usernameEl = document.createElement("p");
        usernameEl.id = "profile-username";
        usernameEl.classList.add("m-0", "text-secondary", "fw-light");
        usernameEl.textContent = "@" + chatUser.username;
        usernameEl.style.overflow = "hidden";
        usernameEl.style.textOverflow = "ellipsis";
        usernameEl.style.whiteSpace = "nowrap";

        // h6とpをdivの子要素に追加
        textContainer.appendChild(nameDiv);
        textContainer.appendChild(usernameEl);

        // imgとdivを親要素のdivに追加
        container.appendChild(img);
        container.appendChild(textContainer);

        // 親要素のdivを引数で受け取った親要素に追加
        parent.appendChild(container);
    }


    /**
     * list-wrapperのスクロール時の処理
     */
    document.getElementById("list-wrapper").addEventListener("scroll", async function () {
        const content = this;

        // 要素がスクロールの最下部に達したかを確認
        if (content.scrollTop + content.clientHeight >= content.scrollHeight) {
            if (!loadAll) {
                spinner.classList.remove("d-none");
                await loadFollowee();
            }
        }
    });
});
