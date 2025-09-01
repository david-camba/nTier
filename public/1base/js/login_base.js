// Espera a que todo el contenido del DOM esté cargado antes de ejecutar el script.
document.addEventListener('DOMContentLoaded', () => {

    const loginForm = document.getElementById('login-form');
    if (!loginForm) return; // Salir si el formulario no está en la página

    loginForm.addEventListener('submit', (event) => {
        // 1. Prevenir el envío tradicional del formulario, que recargaría la página.
        event.preventDefault();

        // 2. Referencias a los elementos del formulario para feedback al usuario.
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const messageDiv = document.getElementById('login-message');
        const submitButton = loginForm.querySelector('button');

        // 3. Preparar la UI para la petición: deshabilitar botón, limpiar mensajes.
        submitButton.disabled = true;
        submitButton.textContent = '...';
        messageDiv.textContent = '';
        messageDiv.style.color = 'white';

        // 4. Crear el objeto de datos que se enviará como JSON.
        const loginData = {
            username: usernameInput.value,
            password: passwordInput.value
        };

        // 5. Realizar la petición AJAX con fetch a nuestra API.
        fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json' // Le decimos al servidor que preferimos una respuesta JSON.
            },
            body: JSON.stringify(loginData)
        })
        .then(response => {
            // Comprobamos si la respuesta HTTP fue exitosa (ej. 200 OK) o si fue un error (ej. 401 Unauthorized).
            if (!response.ok) {
                // Si el servidor devolvió un error, intentamos leer el JSON del error.
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Error en las credenciales.');
                });
            }
            return response.json(); // Si la respuesta fue OK, procesamos el JSON.
        })
        .then(json => {
            // 6. Procesar la respuesta JSON de ÉXITO del backend.
            if (json.success) {
                messageDiv.style.color = 'lime';
                messageDiv.textContent = json.data.message;
                
                // Redirigimos al dashboard.
                window.location.href = json.data.redirectUrl;
            }
            else{
                return json.json().then(errorData => {
                    throw new Error(errorData.message || 'Error en las credenciales.');
                });
            }
        })
        .catch(error => {
            console.log("error: ",error)
            // 7. Procesar cualquier error, ya sea de red o devuelto por el backend.
            messageDiv.style.color = 'red';
            messageDiv.textContent = error.message;
            
            // Reactivamos el botón para que el usuario pueda intentarlo de nuevo.
            submitButton.disabled = false;
            submitButton.textContent = 'Acceder';
        });
    });
});