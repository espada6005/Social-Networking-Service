document.addEventListener("DOMContentLoaded", async function () {
    const forgotForm = document.querySelector("#password-reset-form");

    forgotForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        const formData = new FormData(forgotForm);
        const responseJson = await sendPostRequest("/form/password/reset", formData);

        if (responseJson === null) {
            alert("エラーが発生しました");
            return;
        }

        if (responseJson.status === "fieldErrors") {
            for (let field in responseJson.message) {
                setFormValidation(field, responseJson.message[field]);
            }
        }

        if (responseJson.status === "success") {
            window.location.href = responseJson.redirectUrl;
        }

        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    });
});