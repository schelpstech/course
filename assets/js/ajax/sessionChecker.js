let warningShown = false;
let lastPing = 0;

const PING_INTERVAL = 60000; // 1 min
const WARNING_THRESHOLD = 120; // 2 min

// -----------------------------
// UI - Toast popup
// -----------------------------
function createSessionPopup() {
    const popup = document.createElement("div");
    popup.id = "sessionPopup";
    popup.style.position = "fixed";
    popup.style.bottom = "20px";
    popup.style.right = "20px";
    popup.style.background = "#222";
    popup.style.color = "#fff";
    popup.style.padding = "12px 15px";
    popup.style.borderRadius = "8px";
    popup.style.zIndex = "9999";
    popup.style.display = "none";
    popup.innerHTML = `
        <b>Session Warning</b><br>
        Expiring soon (2 minutes left)<br><br>
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
// Unified session check
// -----------------------------
async function checkSession() {
    try {
        const res = await fetch('../api/keepalive.php', {
            method: 'GET',
            credentials: 'include'
        });

        const data = await res.json();
        console.log("SESSION CHECK:", data);

        // expired
        if (data.status === "expired") {
            logoutUser();
            return;
        }

        if (!data.expires_at) return;

        const now = Math.floor(Date.now() / 1000);
        const remaining = data.expires_at - now;

        // warning
        if (remaining <= WARNING_THRESHOLD && remaining > 0 && !warningShown) {
            showPopup();
            warningShown = true;
        }

        // forced logout
        if (remaining <= 0) {
            logoutUser();
        }

    } catch (err) {
        console.error("Session check failed", err);
    }
}

// -----------------------------
// Throttled activity ping
// -----------------------------
function keepAlive() {
    const now = Date.now();

    if (now - lastPing < PING_INTERVAL) return;
    lastPing = now;

    checkSession();
}

// -----------------------------
// Extend session
// -----------------------------
function extendSession() {
    hidePopup();
    warningShown = false;
    keepAlive();
}

// -----------------------------
// Logout (single source of truth)
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
// Multi-tab sync
// -----------------------------
window.addEventListener("storage", function(e) {
    if (e.key === "forceLogout") {
        window.location.href = "../api/logout.php";
    }
});

// -----------------------------
// Activity tracking (lightweight)
// -----------------------------
['click', 'keypress', 'scroll'].forEach(evt => {
    document.addEventListener(evt, keepAlive);
});

// -----------------------------
// INIT
// -----------------------------
createSessionPopup();
setInterval(checkSession, 30000);
setInterval(keepAlive, 60000);