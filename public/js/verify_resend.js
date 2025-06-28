document.addEventListener("DOMContentLoaded", async function() {
    const resendForm = document.querySelector("#verify-resend-form");

    resendForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        const formData = new FormData(resendForm);
        const responseJson = await sendPostRequest("/form/verify/resend", formData);

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