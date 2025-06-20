let countdown;       // Controla el setInterval
let timer = 29;      // Tiempo inicial
let timerActivo = true; // Estado actual del temporizador

document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initialTimerValue !== 'undefined' && window.initialTimerValue !== null) {
        timer = window.initialTimerValue - 1;
    } else {
        console.warn("Tiempo restante no válido o no definido. Usando valor por defecto.");
    }

    const timerBadge = document.getElementById('timer');

    function actualizarVisual() {
        const minutes = String(Math.floor(timer / 60)).padStart(2, '0');
        const seconds = String(timer % 60).padStart(2, '0');
        if (timerBadge) {
            timerBadge.textContent = `${minutes}:${seconds}`;
        }
    }

    function tick() {
        if (!timerActivo) return; // Si está pausado, no hace nada
        if (timer < 0) {
            clearInterval(countdown);

            const form = document.getElementById("question-form");
            if (form && !form.dataset.submitted) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "es_correcta";
                input.value = "timeout";
                form.appendChild(input);
                form.dataset.submitted = 'true';
                form.submit();
            }
        } else {
            actualizarVisual();
            timer--;
        }
    }

    countdown = setInterval(tick, 1000);
    actualizarVisual();
});

// Estas funciones pueden ser llamadas desde cualquier lugar
function pausarTimer() {
    timerActivo = false;
}

function reanudarTimer() {
    timerActivo = true;
}
