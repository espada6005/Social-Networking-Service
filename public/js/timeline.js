document.addEventListener("DOMContentLoaded", async function () {
    /**
     * タイムライン初期化処理
     */
    const spinner = document.querySelector("#spinner");
    const limit = 30;
    const tlData = {
        trend: {
            offset: 0,
            loadAll: false,
            tlEl: document.querySelector("#trend-timeline"),
        },
        follow: {
            offset: 0,
            loadAll: false,
            tlEl: document.querySelector("#follow-timeline"),
        },
    }

    async function loadTl(tlType = "trend") {
        const csrfToken = document.querySelector("#csrf-token").dataset.token;
        const formData = new FormData();
        formData.append("limit", limit);
        formData.append("offset", tlData[tlType].offset ?? 0);
        formData.append("csrf_token", csrfToken);
        const responseJson = await sendPostRequest(`timeline/${tlType === "trend" ? "trend" : "follow"}/init`, formData);

        if (responseJson.status === "success") {
            if (responseJson.posts.length) {
                for (const post of responseJson.posts) {
                    createPostEl(post, tlData[tlType].tlEl);
                }
                tlData[tlType].offset += limit;
            } else {
                tlData[tlType].loadAll = true;
            }
            spinner.classList.add("d-none");
        } else {
            if (responseJson.status === "error") {
                alert(responseJson.message);
            }
        }
    }
    await loadTl();


    /**
     * タイムラインタブ切り替え時の処理
     */
    let activeTab = "trend";
    document.querySelectorAll("#timeline-tabs .nav-link").forEach(link => {
        link.addEventListener("click", async function (event) {
            event.preventDefault();

            // 全てのnav-linkからactiveクラスを削除
            document.querySelectorAll("#timeline-tabs .nav-link").forEach(item => {
                item.classList.remove("active");
            });

            // クリックされたnav-linkにactiveクラスを追加
            this.classList.add("active");

            // 全てのセクションを非表示
            document.querySelectorAll("div[id$='-timeline']").forEach(section => {
                section.classList.add("d-none");
            });

            // クリックされたリンクに対応するセクションを表示
            const target = document.querySelector(this.getAttribute("data-target"));
            if (target) {
                target.classList.remove("d-none");
            }

            // タイムラインスクロールをトップに戻す
            const timelineWrapper = document.querySelector("#timeline-wrapper");
            timelineWrapper.scrollTop = 0;
            activeTab = this.id === "trend-nav-link" ? "trend" : "follow";
            if (tlData[activeTab].offset === 0) {
                await loadTl(activeTab);
            }
        });
    });


    /**
     * timeline-wrapperのスクロール時の処理
     */
    document.querySelector("#timeline-wrapper").addEventListener("scroll", async function () {
        const content = this;

        // 要素がスクロールの最下部に達したかを確認
        if (content.scrollTop + content.clientHeight >= content.scrollHeight) {
            if (!tlData[activeTab].loadAll) {
                spinner.classList.remove("d-none");
                await loadTl(activeTab);
            }
        }
    });
});
