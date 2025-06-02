let timer = 29; // segundos
const timerBadge = document.getElementById('timer');

const countdown = setInterval(() => {
    if (timer <= 0) {
        clearInterval(countdown);

        // Enviar automáticamente el formulario con "timeout"
        const form = document.getElementById("question-form");
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "es_correcta";
        input.value = "timeout";
        form.appendChild(input);

        form.submit();
    } else {
        const minutes = String(Math.floor(timer / 60)).padStart(2, '0');
        const seconds = String(timer % 60).padStart(2, '0');
        if (timerBadge) {
            timerBadge.textContent = `${minutes}:${seconds}`;
        }
        timer--;
    }
}, 1000);