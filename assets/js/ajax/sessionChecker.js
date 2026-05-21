
let warningShown = false;
let lastActivityPing = 0;
const PING_INTERVAL = 60000; // 1 minute

// -----------------------------
// Create popup
// -----------------------------
function createSessionPopup() {
    const popup = document.createElement("div");
    popup.id = "sessionPopup";
    popup.style.position = "fixed";
    popup.style.bottom = "20px";
    popup.style.right = "20px";
    popup.style.background = "#222";
    popup.style.color = "#fff";
    popup.style.padding = "15px";
    popup.style.borderRadius = "8px";
    popup.style.zIndex = "9999";
    popup.style.display = "none";
    popup.innerHTML = `
        <b>Session Warning</b><br>
        Session will expire in 2 minutes.<br><br>
        <button onclick="extendSession()">Stay Logged In</button>
    `;
    document.body.appendChild(popup);
}

function showPopup() {
    document.getElementById("sessionPopup").style.display = "block";
}

function hidePopup() {
    document.getElementById("sessionPopup").style.display = "none";
}

// -----------------------------
// Proper logout (POST)
// -----------------------------
function logoutUser() {
    localStorage.setItem("forceLogout", Date.now());

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "../api/logout.php";

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "csrf_token";
    input.value = "logout";

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// -----------------------------
// Keep session alive (throttled)
// -----------------------------
function keepAlive() {
    const now = Date.now();

    if (now - lastActivityPing < PING_INTERVAL) return;
    lastActivityPing = now;

    fetch('../api/keepalive.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "expired") {
            logoutUser();
        }
    });
}

// -----------------------------
// Extend session manually
// -----------------------------
function extendSession() {
    keepAlive();
    hidePopup();
    warningShown = false;
}

// -----------------------------
// Monitor session expiry
// -----------------------------
function monitorSession() {
    fetch('../api/keepalive.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (!data.expires_at) return;

            const now = Math.floor(Date.now() / 1000);
            const remaining = data.expires_at - now;

            if (remaining <= 120 && remaining > 0 && !warningShown) {
                showPopup();
                warningShown = true;
            }

            if (remaining <= 0) {
                logoutUser();
            }
        });
}

// -----------------------------
// Multi-tab logout sync
// -----------------------------
window.addEventListener("storage", function(e) {
    if (e.key === "forceLogout") {
        window.location.href = "../api/logout.php";
    }
});

// -----------------------------
// Activity listeners (safe)
// -----------------------------
['click', 'keypress', 'scroll'].forEach(evt => {
    document.addEventListener(evt, keepAlive);
});

// -----------------------------
// Init
// -----------------------------
createSessionPopup();
setInterval(monitorSession, 30000);
setInterval(keepAlive, 60000);