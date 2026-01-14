document.addEventListener('DOMContentLoaded', function () {
  // Utilizando as variáveis passadas pelo PHP
  const countDownDate = countdownData.countDownDate;

  // Referência ao elemento de exibição do countdown
  const countdownElement = document.getElementById('demo');
  
  if (!countdownElement) return;

  // Calcular a diferença de tempo apenas uma vez
  const distance = countDownDate - Date.now();

  if (distance < 0) {
    countdownElement.innerHTML = "EXPIRED";
    return;
  }

  // Cálculos para dias, horas, minutos e segundos
  const days = Math.floor(distance / (1000 * 60 * 60 * 24));
  const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Exibir o countdown
  countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
});

document.querySelectorAll('[class*="is-fcn_"]').forEach(badge => {
  badge.addEventListener('mousemove', (e) => {
    const rect = badge.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    badge.style.setProperty('--mouse-x', `${x}px`);
    badge.style.setProperty('--mouse-y', `${y}px`);
  });
});

