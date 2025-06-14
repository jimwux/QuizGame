document.addEventListener('DOMContentLoaded', () => {
    let timer = 29; // Valor por defecto (30 segundos menos 1)

    // Si existe el valor de tiempo_restante, úsalo, de lo contrario, usa el valor por defecto
    if (typeof window.initialTimerValue !== 'undefined' && window.initialTimerValue !== null) {
        timer = window.initialTimerValue - 1; // Ajusta el temporizador en base al valor pasado desde PHP
    } else {
        console.warn("Tiempo restante no válido o no definido. Usando valor por defecto.");
    }

    const timerBadge = document.getElementById('timer');
    if (timerBadge) {
        const initialMinutes = String(Math.floor((timer + 1) / 60)).padStart(2, '0');
        const initialSeconds = String((timer + 1) % 60).padStart(2, '0');
        timerBadge.textContent = `${initialMinutes}:${initialSeconds}`;
    }

    const countdown = setInterval(() => {
        if (timer < 0) { // Cuando el tiempo se acaba, evita que se vuelva a contar
            clearInterval(countdown);

            // Enviar automáticamente el formulario con "timeout"
            const form = document.getElementById("question-form");
            if (form && !form.dataset.submitted) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "es_correcta"; // Esto es lo que el backend buscará para el timeout
                input.value = "timeout";
                form.appendChild(input);
                form.dataset.submitted = 'true';
                form.submit();
            }
        } else {
            const minutes = String(Math.floor(timer / 60)).padStart(2, '0');
            const seconds = String(timer % 60).padStart(2, '0');
            if (timerBadge) {
                timerBadge.textContent = `${minutes}:${seconds}`;
            }
            timer--;
        }
    }, 1000);
});
