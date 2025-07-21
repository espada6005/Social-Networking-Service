document.addEventListener("DOMContentLoaded", async function () {
    const resendForm = document.querySelector("#verify-resend-form");
    const resendBtn = document.querySelector("#verify-resend-btn");
    const resendSpinner = document.querySelector("#verify-resend-spinner");

    resendForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        resendBtn.disabled = true;
        resendSpinner.classList.remove("d-none");

        const formData = new FormData(resendForm);
        const responseJson = await sendPostRequest("/form/verify/resend", formData);

        resendBtn.disabled = false;
        resendSpinner.classList.add("d-none");

        if (responseJson === null) {
            alert("エラーが発生しました");
            return;
        }

        if (responseJson.status === "success") {
            window.location.href = responseJson.redirectUrl;
        }

        if (responseJson.status === "error") {
            alert(responseJson.message);
        }
    });
});