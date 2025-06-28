document.addEventListener("DOMContentLoaded", async function () {
    const forgotForm = document.querySelector("#password-forgot-form");

    forgotForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const formData = new FormData(forgotForm);
        const responseJson = await sendPostRequest("/form/password/forgot", formData);

        if (responseJson === null) {
            alert("エラーが発生しました");
            return;
        }

        if (responseJson.status === "fieldErrors") {
            console.log(JSON.stringify(responseJson, null, 2));
        }

        if (responseJson.status === "success") {
            console.log(responseJson.message);
        }

        if (responseJson.status === "error") {
            console.error(responseJson.message);
        }
    });
});