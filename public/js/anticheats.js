window.addEventListener('load', () => {
    const [entry] = performance.getEntriesByType("navigation");
    const timer1 = document.getElementById('timer');
    const timer2 = document.getElementById('timer2');


    if (entry) {
        if (entry.type === "reload") {
            console.log("Tipo de navegación: Recarga de página");
            // Lógica específica para recarga
            window.location.href = "/QuizGame/game/getNextQuestion?badRequest=1"
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


