document.addEventListener("DOMContentLoaded", async function () {
    const forgotForm = document.querySelector("#password-forgot-form");
    const forgotBtn = document.querySelector("#password-forgot-btn");
    const forgotSpinner = document.querySelector("#password-forgot-spinner");

    forgotForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        forgotBtn.disabled = true;
        forgotSpinner.classList.remove("d-none");

        const formData = new FormData(forgotForm);
        const responseJson = await sendPostRequest("/form/password/forgot", formData);

        forgotBtn.disabled = false;
        forgotSpinner.classList.add("d-none");

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