document.addEventListener("DOMContentLoaded", async function () {
    const resetForm = document.querySelector("#password-reset-form");
    const resetBtn = document.querySelector("#password-reset-btn");
    const resetSpinner = document.querySelector("#password-reset-spinner");

    resetForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        resetFormValidations();

        resetBtn.disabled = true;
        resetSpinner.classList.remove("d-none");

        const formData = new FormData(resetForm);
        const responseJson = await sendPostRequest("/form/password/reset", formData);

        resetBtn.disabled = false;
        resetSpinner.classList.add("d-none");

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