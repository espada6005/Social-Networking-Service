document.addEventListener("DOMContentLoaded", async function() {
    const loginForm = document.querySelector("#login-form");

    loginForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        const formData = new FormData(loginForm);
        const responseJson = await sendPostRequest("/form/login", formData);

        if (responseJson === null) {
            alert("エラーが発生しました");
            return;
        }

        if (responseJson.status === "fieldErrors") {
            alert("入力に問題があります");
        }

        if (responseJson.status === "success") {
            window.location.href = responseJson.redirectUrl;
        }

        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    });
});