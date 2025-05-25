document.addEventListener('DOMContentLoaded', function () {
    const mapDiv = document.getElementById('map');
    if (!mapDiv) return;

    const ubicacion = mapDiv.dataset.ubicacion;
    if (!ubicacion) {
        console.error('Ubicación no encontrada en el dataset');
        return;
    }

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ubicacion)}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                console.error('No se encontró la ubicación:', ubicacion);
                return;
            }

            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);

            const map = L.map('map', {
                center: [lat, lng],
                zoom: 13,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                zoomControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.circleMarker([lat, lng], {
                radius: 6,
                fillColor: "#2b8fd6",
                color: "#2b8fd6",
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map).bindPopup(ubicacion).openPopup();
        })
        .catch(error => console.error('Error al geocodificar ubicación:', error));
});