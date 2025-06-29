document.addEventListener("DOMContentLoaded", function () {
    const mapDiv = document.getElementById("map-profile");
    const lat = parseFloat(mapDiv.dataset.lat);
    const lng = parseFloat(mapDiv.dataset.lng);

    if (!isNaN(lat) && !isNaN(lng)) {
        const map = L.map('map-profile').setView([lat, lng], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup("Tu ubicación")
            .openPopup();
    } else {
        mapDiv.innerHTML = "<p class='text-danger text-center mt-4'>Ubicación no disponible.</p>";
    }
});
