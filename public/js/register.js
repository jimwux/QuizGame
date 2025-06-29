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

    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;

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

// Validación AJAX para email y usuario
const emailInput = document.getElementById('email');
const usernameInput = document.getElementById('username');
const emailFeedback = document.getElementById('emailFeedback');
const usernameFeedback = document.getElementById('usernameFeedback');

emailInput.addEventListener('keyup', () => {
    const email = emailInput.value.trim();

    // Validación por expresión regular
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        emailFeedback.textContent = 'Formato de email inválido';
        emailFeedback.classList.remove('text-success');
        emailFeedback.classList.add('text-danger');
        return;
    }

    // Validación AJAX si pasa la expresión regular
    fetch('/QuizGame/register/validateEmail', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `email=${encodeURIComponent(email)}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.available) {
                emailFeedback.textContent = 'Disponible';
                emailFeedback.classList.remove('text-danger');
                emailFeedback.classList.add('text-success');
            } else {
                emailFeedback.textContent = 'No disponible';
                emailFeedback.classList.remove('text-success');
                emailFeedback.classList.add('text-danger');
            }
        });
});

usernameInput.addEventListener('keyup', () => {
    const username = usernameInput.value.trim();

    // Validar longitud mínima
    if (username.length < 6) {
        usernameFeedback.textContent = 'Debe tener al menos 6 caracteres';
        usernameFeedback.classList.remove('text-success');
        usernameFeedback.classList.add('text-danger');
        return;
    }

    // Validación con expresión regular (solo letras, números, guiones y puntos)
    const usernameRegex = /^[a-zA-Z0-9.-]+$/;
    if (!usernameRegex.test(username)) {
        usernameFeedback.textContent = 'Solo se permiten letras, números, guiones y puntos';
        usernameFeedback.classList.remove('text-success');
        usernameFeedback.classList.add('text-danger');
        return;
    }

    // Validación AJAX si pasa la expresión regular
    fetch('/QuizGame/register/validateUsername', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `username=${encodeURIComponent(username)}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.available) {
                usernameFeedback.textContent = 'Disponible';
                usernameFeedback.classList.remove('text-danger');
                usernameFeedback.classList.add('text-success');
            } else {
                usernameFeedback.textContent = 'No disponible';
                usernameFeedback.classList.remove('text-success');
                usernameFeedback.classList.add('text-danger');
            }
        });
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

    // Validar que haya ingresado ubicación
    const lat = form.elements['latitude'].value;
    const lng = form.elements['longitude'].value;
    if (!lat || !lng) valid = false;

    // Validar contraseñas iguales
    if (form.elements['password'].value !== form.elements['confirmPassword'].value) valid = false;

    submitBtn.disabled = !valid;
}

// Ejecutar validación al cargar y cada vez que cambie un campo
form.addEventListener('input', validateForm);
validateForm(); // inicializar
