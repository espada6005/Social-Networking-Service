document.addEventListener("DOMContentLoaded", async function () {
    /**
     * ユーザーデータ初期化処理
     */
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const user = urlParams.get("user");
    const csrfToken = document.querySelector("#csrf-token").dataset.token;

    async function initChatUser() {
        const formData = new FormData();
        formData.append("csrf_token", csrfToken);
        formData.append("user", user);
        const responseJson = await sendPostRequest("/messages/chat/user", formData);

        if (responseJson.status === "success") {
            if (responseJson.userData) {
                const chatUserLinks = document.querySelectorAll(".chat-user-link");
                chatUserLinks.forEach(l => { l.href = responseJson.userData.profilePath });

                const chatUserImage = document.getElementById("chat-user-image");
                chatUserImage.src = responseJson.userData.profileImagePath;

                const chatUserName = document.getElementById("chat-user-name");
                chatUserName.textContent = responseJson.userData.name;

                const chatUserInfo = document.getElementById("chat-user-info");
                chatUserInfo.classList.remove("d-none");
            }
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }
    await initChatUser();


    /**
     * メッセージデータ初期化処理
     */
    const listEl = document.getElementById("messages");
    const spinner = document.getElementById("spinner");
    const limit = 30;
    let offset = 0;
    let loadAll = false;

    async function loadMessages() {
        const formData = new FormData();
        formData.append("user", user);
        formData.append("limit", limit);
        formData.append("offset", offset);
        formData.append("csrf_token", csrfToken);
        const responseJson = await sendPostRequest("/messages/chat/messages", formData);

        if (responseJson.status === "success") {
            if (responseJson.messages.length) {
                for (const message of responseJson.messages) {
                    createMessageEl(message, listEl);
                }
                offset += limit;
            } else {
                loadAll = true;
            }

            spinner.classList.add("d-none");
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }
    await loadMessages();

    function createMessageEl(message, parent, direction = "start") {
        const div = document.createElement("div");
        const innerDiv = document.createElement("div");
        innerDiv.innerText = message.content;
        innerDiv.style.maxWidth = "60%";

        if (message.isMyMessage) {
            div.classList.add("msg", "d-flex", "justify-content-end", "mb-2");
            innerDiv.classList.add("bg-secondary-subtle", "d-inline-block", "p-2", "me-1", "rounded-top-3", "rounded-start-3");
        } else {
            div.classList.add("msg", "d-flex", "justify-content-start", "mb-2");
            innerDiv.classList.add("bg-primary-subtle", "d-inline-block", "p-2", "me-1", "rounded-top-3", "rounded-end-3");
        }

        div.appendChild(innerDiv);
        if (direction === "start") parent.prepend(div);
        else parent.append(div);
    }


    /**
     * messages-wrapperのスクロールバーの初期表示位置設定処理
     * スクロールバーを最下部にする
     */
    function scrollbarToBottom() {
        const messagesWrapper = document.getElementById("messages-wrapper");
        messagesWrapper.scrollTop = messagesWrapper.scrollHeight;
    }
    scrollbarToBottom();


    /**
     * messages-wrapperのスクロール時の処理
     */
    document.getElementById("messages-wrapper").addEventListener("scroll", async function () {
        const content = this;

        // 要素がスクロールの最下部に達したかを確認
        if (content.scrollTop === 0) {
            if (!loadAll) {
                spinner.classList.remove("d-none");
                await loadMessages();
            }
        }
    });


    /**
     * 送信ボタンのdisabled切り替え処理
     */
    const messageInput = document.getElementById("message-input");
    const btn = document.getElementById("send-button");
    messageInput.addEventListener("input", (event) => {
        const value = event.target.value.trim();
        if (value.length === 0 || value.length > 200) {
            btn.classList.add("disabled");
            if (value.length) {
                setFormValidation(messageInput.id, "メッセージは200文字以下で入力してください。");
            }
        } else {
            btn.classList.remove("disabled");
            if (messageInput.classList.contains("is-invalid")) {
                resetFormValidations();
            }
        }
    });


    /**
     * WebSocket関連処理
     */
    async function connectToWsServer() {
        let fun = "";
        let tun = "";
        let token = "";

        // WebSocket用認証トークン取得
        const formData = new FormData();
        formData.append("user", user);
        formData.append("csrf_token", csrfToken);
        const responseJson = await sendPostRequest("/messages/chat/token", formData);
        if (responseJson.status === "success") {
            fun = responseJson.fun ?? "";
            tun = responseJson.tun ?? "";
            token = responseJson.token ?? "";
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }

        if (!fun || !tun || !token) alert("エラーが発生しました。");

        // WebSocketサーバー接続
        const wsUrl = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1"
            ? "ws://localhost:8080"
            : "wss://sns.yua-tech.com/ws";
        const conn = new WebSocket(`${wsUrl}?fun=${fun}&tun=${tun}&t=${token}`);

        conn.addEventListener("open", (event) => { });

        conn.addEventListener("message", (event) => {
            const message = JSON.parse(event.data);
            createMessageEl(message, listEl, "end");
            offset++;
            resetChat();
        });

        conn.addEventListener("error", (error) => {
            console.error(error);
        });

        conn.addEventListener("close", (event) => { });

        return conn;
    }
    // WebSocket接続, 初期化
    const wsConn = await connectToWsServer();

    // メッセージ送信
    btn.addEventListener("click", (event) => {
        const content = messageInput.value;
        wsConn.send(JSON.stringify({ content }));
    });

    // メッセージ送信完了後の処理
    function resetChat() {
        // 入力欄をクリア
        messageInput.value = "";

        // チャットスクロールを最下部に移動
        const wrapper = document.getElementById("messages-wrapper");
        wrapper.scrollTop = wrapper.scrollHeight;
    }
});
