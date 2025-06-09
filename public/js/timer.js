document.addEventListener('DOMContentLoaded', () => {
    let timer; // Declara la variable sin inicializarla aquí

    // Intenta usar el valor pasado desde el backend, si no existe, usa 29 (para 30 segundos de cuenta atrás)
    if (typeof window.initialTimerValue !== 'undefined' && window.initialTimerValue !== null) {
        timer = window.initialTimerValue - 1; // Resta 1 porque el setInterval se ejecuta después de 1 segundo
    } else {
        timer = 29; // Por defecto, si no se pasa un valor (debería ser raro si todo funciona bien)
    }

    const timerBadge = document.getElementById('timer');

    // Muestra el tiempo inicial inmediatamente, ya que el setInterval esperará 1 segundo.
    if (timerBadge) {
        const initialMinutes = String(Math.floor((timer + 1) / 60)).padStart(2, '0');
        const initialSeconds = String((timer + 1) % 60).padStart(2, '0');
        timerBadge.textContent = `${initialMinutes}:${initialSeconds}`;
    }


    const countdown = setInterval(() => {
        if (timer < 0) { // Usamos < 0 para asegurarnos de que el tiempo no baje de 00:00 y evitar envíos múltiples.
            clearInterval(countdown);

            // Enviar automáticamente el formulario con "timeout"
            const form = document.getElementById("question-form");
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "es_correcta"; // Esto es lo que el backend buscará para el timeout
            input.value = "timeout";
            form.appendChild(input);

            // Asegúrate de que el formulario solo se envíe una vez
            if (!form.dataset.submitted) {
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