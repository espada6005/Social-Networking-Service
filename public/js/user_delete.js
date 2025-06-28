document.addEventListener("DOMContentLoaded", async function() {
    const registerForm = document.querySelector("#account-delete-form");

    registerForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        const formData = new FormData(registerForm);
        const responseJson = await sendPostRequest("/form/user/delete", formData);

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

document.querySelector("#confirmDelete").addEventListener('change', function() {
    document.querySelector("#deleteButton").disabled = !this.checked;
});