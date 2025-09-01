document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    // Extraemos el valor de 'conf_session'. Será `null` si no está presente.
    const confSessionId = urlParams.get('conf_session');

    const searchBox = document.getElementById('search-box');
    const resultsContainer = document.getElementById('results-container');

    if (!searchBox) return;

    searchBox.addEventListener('input', () => {
        const searchTerm = searchBox.value.trim();

        // Si el campo está vacío, limpiamos los resultados
        if (searchTerm.length < 1) {
            resultsContainer.innerHTML = '';
            return;
        }

        // Hacemos la petición AJAX al propio script, añadiendo los parámetros
        fetch(`/app/clients.php?ajax=true&search-name=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(result => {
                // Limpiamos los resultados anteriores
                resultsContainer.innerHTML = '';
                
                if (result.clients.length > 0) {
                    const list = document.createElement('ul');

                    console.log(confSessionId)
                    
                    result.clients.forEach(client => {
                        const listItem = document.createElement('li');
                        // --- ¡NUEVO! Construimos la URL dinámicamente ---
                        // La variable `confSessionId` la hemos definido en el PHP
                        let linkUrl = `?client_id=${client.client_id}`;
                        if (typeof confSessionId !== 'undefined' && confSessionId) {
                            linkUrl = `?conf_session=${confSessionId}&client_id=${client.client_id}`;
                        }
                        listItem.innerHTML = `<a href="${linkUrl}">${client.name}</a>`;
                        list.appendChild(listItem);
                    });
                    resultsContainer.appendChild(list);
                    resultsContainer.style.display = 'block'; // ¡Mostramos el contenedor!
                } else {
                    // Opcional: mostrar un mensaje de "no encontrado"
                    resultsContainer.innerHTML = '<div style="padding: 10px; color: #888;">No se encontraron clientes.</div>';
                    resultsContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error en la búsqueda:', error);
                resultsContainer.innerHTML = '<p>Error al buscar.</p>';
            });
    });

    // --- ¡NUEVO! Lógica para ocultar los resultados ---
    // Si el usuario hace clic en cualquier otro sitio de la página,
    // ocultamos el menú de resultados.
    document.addEventListener('click', (event) => {
        if (!searchBox.contains(event.target)) {
            resultsContainer.style.display = 'none';
        }
    });
    
    // Si el usuario vuelve a hacer clic en la caja de búsqueda, y hay resultados,
    // se deberían volver a mostrar.
    searchBox.addEventListener('focus', () => {
        if (resultsContainer.innerHTML !== '') {
            resultsContainer.style.display = 'block';
        }
    });
});

