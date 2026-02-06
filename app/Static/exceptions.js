const MAX_REPORTED_ERRORS = 5;
let errorCount = 0;
let isReporting = false;

window.addEventListener("error", errorListener, true);
window.addEventListener('unhandledrejection', errorListener, true);

function errorListener(event) {
    if (isReporting) return;
    if (errorCount >= MAX_REPORTED_ERRORS) {
        window.removeEventListener("error", errorListener, true);
        window.removeEventListener('unhandledrejection', errorListener, true);
        return;
    }

    errorCount++;
    isReporting = true;

    try {
        event.preventDefault();

        let errorData = {};
        if (event.error) {
            errorData = {
                error: true,
                message: event.error.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno
            };
        } else {
            errorData = {
                error: false,
                target: event.target.src || event.target.href
            };
        }

        errorData.language = window.navigator.language;
        errorData.platform = window.navigator.platform;
        errorData.userAgent = window.navigator.userAgent;
        try {
            errorData.timezone = Temporal.Now.timeZoneId();
        } catch (e) {
            errorData.timezone = "N/A";
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/exception.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(JSON.stringify(errorData));
    } finally {
        isReporting = false;
    }
}