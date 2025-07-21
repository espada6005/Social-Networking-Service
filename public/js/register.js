document.addEventListener("DOMContentLoaded", async function() {
    const registerForm = document.querySelector("#register-form");
    const registerBtn = document.querySelector("#register-btn");
    const registerSpinner = document.querySelector("#register-spinner");

    registerForm.addEventListener("submit", async function(event) {
        event.preventDefault();
        resetFormValidations();

        registerBtn.disabled = true;
        registerSpinner.classList.remove("d-none");

        const formData = new FormData(registerForm);
        const responseJson = await sendPostRequest("/form/user/register", formData);

        registerBtn.disabled = false;
        registerSpinner.classList.add("d-none");

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