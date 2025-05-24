const birthYearSelect = document.getElementById('birthYear');
const currentYear = new Date().getFullYear();
for (let y = currentYear; y >= 1900; y--) {
    const option = document.createElement('option');
    option.value = y;
    option.textContent = y;
    birthYearSelect.appendChild(option);
}

// Inicializar mapa Leaflet
const map = L.map('map').setView([-34.6037, -58.3816], 10); // Buenos Aires centro
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker;

map.on('click', e => {
    const { lat, lng } = e.latlng;

    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map);

    fetch(`https://us1.locationiq.com/v1/reverse.php?key=pk.7257f3970bb73e804534d21a4e355fd5&lat=${lat}&lon=${lng}&format=json`)
        .then(res => res.json())
        .then(data => {
            const address = data.address;

            const possibleCities = [
                address.city,
                address.town,
                address.village,
                address.hamlet,
                address.suburb,
                address.county,
                address.state_district,
                address.state
            ];

            const city = possibleCities.find(val => val && val.trim()) || 'Zona sin nombre';

            document.getElementById('city').value = city;

            const country = address.country || '';
            document.getElementById('country').value = country;

            if (!city) {
                alert('No se pudo detectar la ciudad. Completala manualmente o elegí otro punto.');
            }
        })
        .catch(error => {
            console.error('Error al obtener la dirección:', error);
            alert('Error al conectar con el servicio de mapas. Intentá más tarde.');
        });
});

const form = document.getElementById('registerForm');
form.addEventListener('submit', e => {
    const pwd = document.getElementById('password').value;
    const confirmPwd = document.getElementById('confirmPassword').value;
    if (pwd !== confirmPwd) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
    }
});

// Ajustar tamaño del mapa después de carga
window.addEventListener('load', () => {
    map.invalidateSize();
});


const submitBtn = form.querySelector('button[type="submit"]');

// Función para validar formato de email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateForm() {
    const requiredFields = ['fullName', 'birthYear', 'gender', 'country', 'city', 'email', 'username', 'password', 'confirmPassword'];
    let valid = true;

    for (const field of requiredFields) {
        const input = form.elements[field];
        if (!input || !input.value.trim()) {
            valid = false;
            break;
        }
    }

    // Validar email con regex
    const emailValue = form.elements['email'].value;
    if (!validateEmail(emailValue)) valid = false;

    // Validar contraseñas iguales
    if (form.elements['password'].value !== form.elements['confirmPassword'].value) valid = false;

    submitBtn.disabled = !valid;
}

// Ejecutar validación al cargar y cada vez que cambie un campo
form.addEventListener('input', validateForm);
validateForm(); // inicializar
