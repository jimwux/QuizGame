window.addEventListener('load', () => {
    const [entry] = performance.getEntriesByType("navigation");
    const timer1 = document.getElementById('timer');
    const timer2 = document.getElementById('timer2');


    if (entry) {
        if (entry.type === "reload") {
            console.log("Tipo de navegación: Recarga de página");
            // Lógica específica para recarga
            window.location.href = "/QuizGame/game/getNextQuestion?badRequest=1"
            /*
            fetch("http://localhost/QuizGame/game/getNextQuestion", {
                method: 'POST', // Especifica el método POST
                headers: {
                    'Content-Type': 'application/json' // Indica al servidor que estamos enviando JSON
                },
                body: JSON.stringify({ myValue: 1 })
            }).then(response => {
                // Verifica si la solicitud fue exitosa
                if (!response.ok) {
                    throw new Error('La respuesta de red no fue correcta ' + response.statusText);
                }
                return response.json(); // Parsea la respuesta JSON del servidor
            })
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    // Puedes manejar la respuesta del servidor aquí, por ejemplo, actualizar la interfaz de usuario
                })
                .catch(error => {
                    console.error('Hubo un problema con la operación fetch:', error);
                });
            */
        } else if (entry.type === "back_forward") {
            console.log("Tipo de navegación: Botón de atrás/adelante");
            // Lógica específica para navegación de historial
        } else if (entry.type === "navigate") {
            console.log("Tipo de navegación: Navegación normal (primer acceso, clic en enlace, URL directa)");
            // Lógica para navegación normal
        } else {
            console.log("Tipo de navegación desconocido:", entry.type);
        }
    } else {
        console.log("No se pudo obtener la entrada de navegación. Posiblemente un navegador antiguo o un escenario específico.");
    }

});


/*
ES UNA API PARA SABER LA INTERACCION AL HACERLE ALGO A LA PAGINA
 entry.type:
    reload -> para verificar si recarga la pagina
    back_forward -> para verificar si va hacia atras o delante
    navigate -> pagina actual sin aplicar ninguna intencion mala de romper el juego/codigo

* */


