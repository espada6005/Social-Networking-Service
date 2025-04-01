async function sendPostRequest(url, body) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: body
        });
        const responseJson = await response.json();
        return responseJson;
    } catch (error) {
        console.error(`Error: ${error}`);
        return null;
    }
}
