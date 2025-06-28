document.addEventListener("DOMContentLoaded", async function() {
    const registerForm = document.querySelector("#register-form");

    registerForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        const formData = new FormData(registerForm);
        const responseJson = await sendPostRequest("/form/user/register", formData);

        if (responseJson === null) {
            alert("エラーが発生しました");
            return;
        }

        if (responseJson.status === "fieldErrors") {
            alert("入力に問題があります");
            console.log(JSON.stringify(responseJson, null, 2));
        }

        if (responseJson.status === "success") {
            window.location.href = responseJson.redirectUrl;
        } 

        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    });
});