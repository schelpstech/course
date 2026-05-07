function startCountdown() {
  const deadline = new Date("May 17, 2026 23:59:00").getTime();

  const interval = setInterval(function () {
    const now = new Date().getTime();
    const distance = deadline - now;

    if (distance < 0) {
      clearInterval(interval);
      document.getElementById("countdown").innerHTML = " | Registration Closed";
      return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance / (1000 * 60 * 60)) % 24);
    const minutes = Math.floor((distance / (1000 * 60)) % 60);
    const seconds = Math.floor((distance / 1000) % 60);

    document.getElementById("countdown").innerHTML =
      ` | ⏳ ${days}d ${hours}h ${minutes}m ${seconds}s`;
  }, 1000);
}

startCountdown();

document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const overlay = document.getElementById("loadingOverlay");
  const btn = document.getElementById("loginBtn");

  form.addEventListener("submit", function () {
    // Disable button
    btn.disabled = true;

    // Show overlay
    overlay.style.display = "flex";
  });
});
