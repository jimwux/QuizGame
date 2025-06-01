
document.addEventListener('DOMContentLoaded', function() {
    const gameContainer = document.getElementById('game-container');
    const mensajeFeedbackDiv = document.getElementById('mensaje-feedback');
    const preguntaNumeroSpan = document.getElementById('pregunta-numero-actual');
    const puntajeAcumuladoSpan = document.getElementById('puntaje-acumulado');

    const preguntaTextoH2 = document.getElementById('pregunta-texto');
    const preguntaImagen = document.getElementById('pregunta-imagen');
    const opcionesPreguntaDiv = document.getElementById('opciones-pregunta');
    const inputPreguntaId = document.getElementById('input-pregunta-id');
    const inputPartidaId = document.getElementById('input-partida-id');

    if (gameContainer) {
        gameContainer.addEventListener('submit', async function(event) {
            if (event.target && event.target.id === 'game-form') {
                event.preventDefault();

                const form = event.target;
                const clickedButton = event.submitter;
                const formData = new FormData(form);
                formData.append('respuestaUsuario', clickedButton.value);

                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = true);
                mostrarMensajeFeedback(true, 'Procesando respuesta...');

                try {
                    const response = await fetch('/QuizGame/index.php?controller=game&method=responderAjax', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Error HTTP:', response.status, response.statusText, errorText);
                        mostrarMensajeFeedback(false, `Error del servidor: ${response.status} ${response.statusText}`);
                        buttons.forEach(btn => btn.disabled = false);
                        return;
                    }

                    const result = await response.json();
                    console.log('Respuesta del servidor (responderAjax):', result);

                    if (result.error) {
                        mostrarMensajeFeedback(false, result.error);
                        if (result.juego_terminado && result.siguiente_url) {
                            setTimeout(() => {
                                window.location.href = result.siguiente_url;
                            }, 1500);
                        } else {
                            buttons.forEach(btn => btn.disabled = false);
                        }
                    } else {
                        mostrarMensajeFeedback(result.fue_correcta, result.mensaje_feedback);
                        actualizarProgreso(result.pregunta_actual_numero, result.puntaje_actual);

                        if (result.juego_terminado) {
                            setTimeout(() => {
                                window.location.href = result.siguiente_url;
                            }, 1500);
                        } else {
                            setTimeout(async () => {
                                const currentPartidaId = inputPartidaId.value;
                                if (currentPartidaId) {
                                    await cargarSiguientePregunta(currentPartidaId);
                                    // Los botones de la nueva pregunta ya estarán habilitados por updateGameContent
                                    mensajeFeedbackDiv.style.display = 'none';
                                } else {
                                    console.error('No se pudo obtener el ID de la partida para cargar la siguiente pregunta.');
                                    mostrarMensajeFeedback(false, 'Error: ID de partida no disponible.');
                                    buttons.forEach(btn => btn.disabled = false);
                                }
                            }, 1000);
                        }
                    }

                } catch (error) {
                    console.error('Error durante la petición o el procesamiento:', error);
                    mostrarMensajeFeedback(false, 'Hubo un error de conexión. Por favor, recarga la página.');
                    buttons.forEach(btn => btn.disabled = false);
                }
            }
        });
    }

    async function cargarSiguientePregunta(partidaId) {
        try {
            console.log('Cargando siguiente pregunta HTML desde:', `/QuizGame/index.php?controller=game&method=show&id=${partidaId}`);
            const response = await fetch(`/QuizGame/index.php?controller=game&method=show&id=${partidaId}`);
            if (!response.ok) {
                 console.error('Error al cargar HTML de la siguiente pregunta:', response.status, response.statusText);
                 mostrarMensajeFeedback(false, 'Error al cargar la siguiente pregunta.');
                 return;
            }
            const htmlContent = await response.text();
            console.log('HTML de la siguiente pregunta cargado. Longitud:', htmlContent.length); // Muestra la longitud para verificar si hay contenido
            // console.log('Contenido HTML completo:', htmlContent); // Ojo: esto puede ser muy largo, solo para debugging intensivo

            updateGameContent(htmlContent);
            mensajeFeedbackDiv.style.display = 'none';

        } catch (error) {
            console.error('Error al cargar la siguiente pregunta:', error);
            mostrarMensajeFeedback(false, 'No se pudo cargar la siguiente pregunta.');
        }
    }

    function updateGameContent(htmlContent) {
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(htmlContent, 'text/html');
        console.log('Documento HTML parseado:', newDoc);

        // Referencias a los elementos del nuevo documento
        const newPreguntaTextoH2 = newDoc.getElementById('pregunta-texto');
        const newPreguntaImagen = newDoc.getElementById('pregunta-imagen');
        const newOpcionesPreguntaDiv = newDoc.getElementById('opciones-pregunta');
        const newPreguntaIdInput = newDoc.getElementById('input-pregunta-id');
        const newPartidaIdInput = newDoc.getElementById('input-partida-id');

        const newPreguntaNumeroSpan = newDoc.getElementById('pregunta-numero-actual');
        const newPuntajeAcumuladoSpan = newDoc.getElementById('puntaje-acumulado');


        // Depuración de los elementos encontrados en el NUEVO documento
        console.log('Elementos del nuevo documento:');
        console.log('  newPreguntaTextoH2:', newPreguntaTextoH2);
        console.log('  newPreguntaImagen:', newPreguntaImagen);
        console.log('  newOpcionesPreguntaDiv:', newOpcionesPreguntaDiv);
        console.log('  newPreguntaIdInput:', newPreguntaIdInput);
        console.log('  newPartidaIdInput:', newPartidaIdInput);
        console.log('  newPreguntaNumeroSpan:', newPreguntaNumeroSpan);
        console.log('  newPuntajeAcumuladoSpan:', newPuntajeAcumuladoSpan);


        // 1. Actualizar el texto de la pregunta
        if (preguntaTextoH2 && newPreguntaTextoH2) {
            preguntaTextoH2.innerHTML = newPreguntaTextoH2.innerHTML;
            console.log('Texto de pregunta actualizado a:', preguntaTextoH2.innerHTML);
        } else {
            console.warn("Elemento 'pregunta-texto' no encontrado para actualizar. Original:", preguntaTextoH2, "Nuevo:", newPreguntaTextoH2);
        }

        // 2. Actualizar la imagen de la pregunta
        if (preguntaImagen) {
            if (newPreguntaImagen && newPreguntaImagen.src && newPreguntaImagen.src !== window.location.href) {
                preguntaImagen.src = newPreguntaImagen.src;
                preguntaImagen.alt = newPreguntaImagen.alt;
                preguntaImagen.style.display = 'block';
                console.log('Imagen de pregunta actualizada a:', preguntaImagen.src);
            } else {
                preguntaImagen.style.display = 'none';
                console.log('Imagen de pregunta oculta (no hay nueva imagen o URL inválida).');
            }
        }

        // 3. Actualizar las opciones de respuesta
        if (opcionesPreguntaDiv && newOpcionesPreguntaDiv) {
            opcionesPreguntaDiv.innerHTML = newOpcionesPreguntaDiv.innerHTML;
            console.log('Opciones de pregunta actualizadas a:', opcionesPreguntaDiv.innerHTML);
        } else {
            console.warn("Elemento 'opciones-pregunta' no encontrado para actualizar. Original:", opcionesPreguntaDiv, "Nuevo:", newOpcionesPreguntaDiv);
        }

        // 4. Actualizar el hidden input con el ID de la nueva pregunta
        if (inputPreguntaId && newPreguntaIdInput) {
            inputPreguntaId.value = newPreguntaIdInput.value;
            console.log('ID de pregunta actualizado a:', inputPreguntaId.value);
        } else {
            console.warn("Elemento 'input-pregunta-id' no encontrado para actualizar. Original:", inputPreguntaId, "Nuevo:", newPreguntaIdInput);
        }

        // 5. Actualizar el hidden input con el ID de la partida (si es necesario cambiarlo, aunque no debería)
        if (inputPartidaId && newPartidaIdInput) {
            inputPartidaId.value = newPartidaIdInput.value;
            console.log('ID de partida actualizado a:', inputPartidaId.value);
        }

        // 6. Actualizar los spans de progreso (pregunta actual y puntaje)
        // OJO: Estas ya se actualizan con actualizarProgreso(result.pregunta_actual_numero, result.puntaje_actual)
        // Puedes quitar estas líneas de aquí para evitar redundancia, pero no deberían causar error si el valor es el mismo.
        /*
        if (preguntaNumeroSpan && newPreguntaNumeroSpan) {
            preguntaNumeroSpan.textContent = newPreguntaNumeroSpan.textContent;
        }
        if (puntajeAcumuladoSpan && newPuntajeAcumuladoSpan) {
            puntajeAcumuladoSpan.textContent = newPuntajeAcumuladoSpan.textContent;
        }
        */
        
        // Re-habilitar los botones de la nueva pregunta
        const currentForm = document.getElementById('game-form');
        if (currentForm) {
            currentForm.querySelectorAll('button').forEach(btn => btn.disabled = false);
            console.log('Botones re-habilitados.');
        }
    }

    function mostrarMensajeFeedback(esCorrecta, mensaje) {
        if (mensajeFeedbackDiv) {
            mensajeFeedbackDiv.textContent = mensaje;
            mensajeFeedbackDiv.style.display = 'block';
            if (esCorrecta) {
                mensajeFeedbackDiv.className = 'alert alert-success';
            } else {
                mensajeFeedbackDiv.className = 'alert alert-danger';
            }
        }
    }

    function actualizarProgreso(preguntaActual, puntaje) {
        if (preguntaNumeroSpan) {
            preguntaNumeroSpan.textContent = preguntaActual;
        }
        if (puntajeAcumuladoSpan) {
            puntajeAcumuladoSpan.textContent = puntaje;
        }
    }
});