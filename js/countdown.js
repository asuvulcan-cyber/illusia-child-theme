document.addEventListener('DOMContentLoaded', function () {
  const countdownElement = document.getElementById('countdown');
  const expirationTimestamp = parseInt(countdownElement.dataset.expirationTimestamp, 10);

  if (isNaN(expirationTimestamp)) {
    countdownElement.textContent = "Data de expiração inválida.";
    return;
  }

  function updateCountdown() {
    const now = new Date().getTime();
    const timeLeft = expirationTimestamp - now;

    if (timeLeft <= 0) {
      countdownElement.textContent = "A senha expirou.";
      return;
    }

    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

    countdownElement.textContent = `Expira em ${days}d ${hours}h ${minutes}m ${seconds}s`;
  }

  // Atualizar a cada segundo
  updateCountdown();
  setInterval(updateCountdown, 1000);
});