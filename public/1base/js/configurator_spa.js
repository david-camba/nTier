// configurator_spa.js

// Usamos un objeto global para nuestra app para no contaminar el scope.
const ConfiguratorApp = {
    
    // Propiedades para guardar el estado actual
    activeSession: null,
    currentStep: 1,
    translations: {},

    // Propiedades para guardar renderizados de pasos
    models: {},
    colors: {},
    extras: {},
    
    // Elementos del DOM que usaremos a menudo
    elements: {
        contentArea: null,
        stepIndicators: null,
        totalPrice: null,
        assignButton: null,
        // ... y más que necesitemos
    },

    /**
     * El método de inicialización. Se llama cuando la página carga.
     */
    init: function() {
        console.log('Iniciando Configurador SPA...');
        
        // 1. Vincular elementos del DOM
        this.elements.contentArea = document.getElementById('spa-content-area');
        this.elements.stepIndicators = document.querySelectorAll('.step-indicator .step');
        this.elements.totalPrice = document.getElementById('total-price');

        // 2. Leer el estado inicial que el PHP nos ha pasado.
        const sessionDataElement = document.getElementById('active-session-data');

        //for the future
        //const templatesDataElement = document.getElementById('templates-data');

        const translationsElement = document.getElementById('configurator-translations');

        // 3. ¡LA LÓGICA DE DECISIÓN QUE DISEÑASTE!
        try{
            this.activeSession = JSON.parse(sessionDataElement.textContent);
            //this.availableTemplates = JSON.parse(templatesDataElement.textContent); //to be done
            this.translations = JSON.parse(translationsElement.textContent);
            this.determineCurrentStep();
        }
        catch (e){
            console.log(e);
        }
    },

    /**
     * Determina en qué paso del configurador estamos basándose en la sesión activa.
     */
    determineCurrentStep: function() {
        const session = this.activeSession;

        /*
         * ANOTACIÓN DE ARQUITECTURA:
         * El estado actual del configurador se deduce de los datos guardados
         * (id_model, id_color, etc.). Esto funciona bien, pero tiene una limitación:
         * no puede distinguir entre "estar en el paso de extras" y "haber completado
         * el paso de extras sin seleccionar ninguno".
         *
         * Una solución más robusta sería añadir una columna 'current_step' a la
         * tabla 'conf_sessions' para almacenar explícitamente el progreso del usuario.
         * Por simplicidad, hemos optado por la deducción de estado.
         */

        if (session.id_model === null) {
            // Caso 1: No hay modelo.
            console.log('Estado: Sin modelo. Vamos al paso 1.');
            // Aquí podríamos llamar a una API para limpiar la sesión si 'id_color' existiera.
            this.renderStep1_Models();
        } else if (session.id_color === null) {
            // Caso 2: Hay modelo, pero no color.
            console.log('Estado: Sin color. Vamos al paso 2.');
            this.renderStep2_Colors();
        } else if (session.extras === null) {
            // Caso 3: Hay modelo y color.
            console.log('Estado: Modelo y color OK. Vamos al paso 3.');
            this.renderStep3_Extras();
        }
        else{
            this.renderStep4_Summary();
        }
    },

    // --- Métodos para renderizar cada paso ---

    renderStep1_Models: async function(models=null) {
        this.currentStep = 1;
        this.updateStepIndicator();

        this.elements.contentArea.innerHTML = `<p>${this.translations.loading_models || 'Cargando modelo'}</p>`;

        this.updateTotalPrice(0);

        try {
            if(models) this.models = models;            
            else{
                const response = await fetch('/api/configurator/models');
                if (!response.ok) throw new Error('Error del servidor al cargar modelos.');
                
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                this.models = result.data.models;
            }

            // 2. Construye el HTML para la lista de modelos.
            let html = '<h2>' + (this.translations?.step1_title || 'Elige un Modelo') + '</h2>';
            html += '<div class="model-selection-grid">';

            this.models.forEach(model => {
                // Comprobamos si este modelo ya está seleccionado en la sesión activa.
                /*const isSelected = this.activeSession.id_model === model.id;
                const selectedClass = isSelected ? 'selected' : '';*/

                html += `
                    <div class="model-card" data-model-id="${model.id}">
                        <img src="${model.image}" alt="${model.name}">
                        <h3>${model.name}</h3>
                        <p>${this.translations?.from_tag || 'Desde'} ${model.price.toFixed(2)} €</p>
                    </div>
                `;
            });

            html += '</div>';

            // Añadimos el botón de "Siguiente", inicialmente deshabilitado.
            html += `
                <div class="step-actions">
                    <button id="step1-next-btn" disabled>${this.translations?.next_button || 'Siguiente'}</button>
                </div>
            `;

            // 3. "Pinta" el HTML en el contenedor.
            this.elements.contentArea.innerHTML = html;

            // 4. Añade los event listeners a las tarjetas y al botón.
            this.attachStep1Events();

        } catch (error) {
            console.error(error);
            this.elements.contentArea.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    },
    
    attachStep1Events: function() {
        const nextButton = document.getElementById('step1-next-btn');
        let selectedModelId = this.activeSession.id_model;

        // Si ya había un modelo seleccionado, activamos el botón.
        if (selectedModelId) {
            nextButton.disabled = false;
        }

        document.querySelectorAll('.model-card').forEach(card => {
            card.addEventListener('click', () => {
                // Desmarcar todas las demás tarjetas
                document.querySelectorAll('.model-card').forEach(c => c.classList.remove('selected'));
                // Marcar la tarjeta actual
                card.classList.add('selected');
                
                selectedModelId = parseInt(card.dataset.modelId);
                nextButton.disabled = false; // Activar el botón
            });
        });

        nextButton.addEventListener('click', () => {
            if (selectedModelId) {
                this.saveStep1AndGoToColors(selectedModelId);
            }
        });
    },

    // --- Métodos para manejar las acciones de los botones ---

    // ...
    saveStep1AndGoToColors: async function(modelId) {
        console.log(`Guardando modelo con ID: ${modelId}`);
        const sessionId = this.activeSession.id_conf_session;

        // Mostramos un feedback visual
        this.elements.contentArea.innerHTML = `<p>${this.translations.saving_selection || 'Guardando selección...'}</p>`;

        try {
            const response = await fetch(`/api/configurator/session/${sessionId}/model`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ modelId: modelId })
            });

            if (!response.ok) throw new Error('Error del servidor.');
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            // ¡Éxito!
            // 1. Actualizamos nuestro estado de sesión local con los datos del backend.
            this.activeSession = result.data.activeSession;
            this.renderStep2_Colors(result.data.colors,result.data.totalPrice);

        } catch (error) {
            console.error(error);
            // Si falla, volvemos a renderizar el paso 1 para que el usuario pueda reintentar.
            this.renderStep1_Models();
            // Y mostramos un mensaje de error.
            // (Necesitaríamos un div global para notificaciones).
            alert('Error al guardar. Por favor, inténtelo de nuevo.');
        }
    },
    

    renderStep2_Colors: async function(colors=null,totalPrice=null) {
        this.currentStep = 2;
        this.updateStepIndicator();
        
        this.elements.contentArea.innerHTML = `<p>${this.translations.loading_colors || 'Cargando colores...'}</p>`;

        try {
            let renderColors = colors;
            let renderTotalPrice = totalPrice;

            if (!renderColors || !renderTotalPrice){
                // 1. Pide los colores a la API para el modelo guardado en la sesión.
                const sessionId = this.activeSession.id_conf_session;
                const response = await fetch(`/api/configurator/session/${sessionId}/colors`);
                if (!response.ok) throw new Error('Error del servidor al cargar colores.');
                
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                renderColors = result.data.colors;
                renderTotalPrice = result.data.totalPrice;
            }
            this.updateTotalPrice(renderTotalPrice);
            
            // 2. Construye el HTML para la selección de colores.
            let html = `<h2>${this.translations.step2_title || 'Elige un Color'}</h2>`;
            html += '<div class="color-selection-grid">';

            renderColors.forEach(color => {
                const isSelected = this.activeSession.id_color === color.id_color;
                const selectedClass = isSelected ? 'selected' : '';
                
                // Usamos la imagen del color si está disponible.
                const imageSrc = color.img ? `${color.img}` : '/assets/default_car.png';

                html += `
                    <div class="color-card ${selectedClass}" data-color-id="${color.id_color}">
                        <img src="${imageSrc}" alt="${color.name}">
                        <div class="color-info">
                            <h4>${color.name}</h4>
                            <p>+ ${color.price_increase.toFixed(2)} €</p>
                        </div>
                    </div>
                `;
            });

            html += '</div>';

            // 3. Añadimos los botones de acción "Volver" y "Siguiente".
            html += `
                <div class="step-actions">
                    <button id="step2-back-btn" class="back-button">${this.translations.back_button || 'Volver'}</button>
                    <button id="step2-next-btn" disabled>${this.translations.next_button || 'Siguiente'}</button>
                </div>
            `;

            this.elements.contentArea.innerHTML = html;

            // 4. Añadimos los event listeners.
            this.attachStep2Events();

        } catch (error) {
            console.error(error);
            this.elements.contentArea.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    },

    attachStep2Events: function() {
        const backButton = document.getElementById('step2-back-btn');
        const nextButton = document.getElementById('step2-next-btn');
        let selectedColorId = this.activeSession.id_color;

        if (selectedColorId) {
            nextButton.disabled = false;
        }

        document.querySelectorAll('.color-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.color-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                selectedColorId = parseInt(card.dataset.colorId);
                nextButton.disabled = false;
            });
        });

        backButton.addEventListener('click', () => {
            this.handleGoBackToModels();
        });

        nextButton.addEventListener('click', () => {
            if (selectedColorId) {
                this.saveStep2AndGoToExtras(selectedColorId);
            }
        });
    },

    saveStep2AndGoToExtras: async function(colorId) {
        console.log(`Guardando color con ID: ${colorId}`);
        const sessionId = this.activeSession.id_conf_session;
        this.elements.contentArea.innerHTML = `<p>${this.translations.saving_selection || 'Guardando...'}</p>`;

        try {
            const response = await fetch(`/api/configurator/session/${sessionId}/colors?include=next-step`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ colorId: colorId })
            });

            if (!response.ok) throw new Error('Error del servidor.');
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            // Éxito:
            this.activeSession = result.data.activeSession;
            this.renderStep3_Extras(result.data.extras, result.data.totalPrice); // Le pasamos los extras al siguiente paso

        } catch (error) {
            console.error(error);
            this.renderStep2_Colors(); // Si falla, volvemos a renderizar el paso de colores
            alert('Error al guardar el color. Por favor, inténtelo de nuevo.');
        }
    },

    handleGoBackToModels: async function() {
        if (confirm(this.translations.confirm_go_back || '¿Seguro que quieres volver? Se perderá la selección de color y extras.')) {
            
            const sessionId = this.activeSession.id_conf_session;
            this.elements.contentArea.innerHTML = `<p>${this.translations.loading || 'Cargando...'}</p>`;

            try {
                const response = await fetch(`/api/configurator/session/${sessionId}/reset-to-step1`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });

                if (!response.ok) throw new Error('Error del servidor.');
            
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                //if (!this.models) this.models = 

                // Éxito:
                this.activeSession = result.data.activeSession;
                // Renderizamos el paso 1, pasándole los modelos que nos ha devuelto el backend.
                this.renderStep1_Models(result.data.models);

            } catch (error) {
                console.error(error);
                this.renderStep2_Colors(); // Si falla, volvemos a mostrar el paso 2.
                alert('No se pudo volver al paso anterior.');
            }
        }
    },

    renderStep3_Extras: async function(extras = null, totalPrice = null) { // Puede recibir los extras o buscarlos
        this.currentStep = 3;
        this.updateStepIndicator();
        this.elements.contentArea.innerHTML = `<p>${this.translations.loading_extras || 'Cargando extras...'}</p>`;

        try {
            // Si no nos han pasado los extras, los buscamos.
            let renderExtras = extras;
            let renderTotalPrice = totalPrice;

            // Esta lógica ahora está mucho más limpia.
            if (!renderExtras || !renderTotalPrice) {
                const sessionId = this.activeSession.id_conf_session;
                // Llamamos a la NUEVA ruta de la API
                const response = await fetch(`/api/configurator/session/${sessionId}/extras`);
                if (!response.ok) throw new Error('Error del servidor.');
                
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                
                renderExtras = result.data.extras;
                renderTotalPrice = result.data.totalPrice; // Obtenemos el precio de la misma respuesta
            }

            this.updateTotalPrice(renderTotalPrice);
            
            // Construimos el HTML.
            let html = `<h2>${this.translations.step3_title || 'Selecciona Extras'}</h2>`;
            html += '<div class="extras-list">';

            const selectedExtras = this.activeSession.extras ? this.activeSession.extras.split(',') : [];

            renderExtras.forEach(extra => {
                const isChecked = selectedExtras.includes(extra.id_extra.toString());
                const checkedAttr = isChecked ? 'checked' : '';

                html += `
                    <div class="extra-item">
                        <input type="checkbox" id="extra-${extra.id_extra}" name="extras" value="${extra.id_extra}" ${checkedAttr}>
                        <label for="extra-${extra.id_extra}">
                            <strong>${extra.name}</strong> (+ ${extra.price.toFixed(2)} €)
                            <small>${extra.description || ''}</small>
                        </label>
                    </div>
                `;
            });

            html += '</div>';

            // Botones de acción. "Siguiente" ahora es "Ver Resumen".
            html += `
                <div class="step-actions">
                    <button id="step3-back-btn" class="back-button">${this.translations.back_button || 'Volver'}</button>
                    <button id="step3-next-btn">${this.translations.next_button_summary || 'Ver Resumen'}</button>
                </div>
            `;

            this.elements.contentArea.innerHTML = html;
            this.attachStep3Events();

        } catch (error) {
            console.error(error);
            this.elements.contentArea.innerHTML = `<p style="color:red;">${error.message}</p>`;
        }
    },

    attachStep3Events: function() {
        const backButton = document.getElementById('step3-back-btn');
        const nextButton = document.getElementById('step3-next-btn');

        backButton.addEventListener('click', () => {
            // Volver al paso de colores es "seguro", no se pierden datos.
            this.renderStep2_Colors(); 
        });

        nextButton.addEventListener('click', () => {
            // Recogemos todos los checkboxes marcados.
            const selectedExtras = Array.from(document.querySelectorAll('input[name="extras"]:checked'))
                                        .map(cb => cb.value);
            
            this.saveStep3AndGoToSummary(selectedExtras);
        });
    },
    
    saveStep3AndGoToSummary: async function(selectedExtras) {
        console.log(`Guardando extras: ${selectedExtras.join(',')}`);
        const sessionId = this.activeSession.id_conf_session;
        this.elements.contentArea.innerHTML = `<p>${this.translations.saving_summary || 'Guardando y generando resumen...'}</p>`;

        try {
            const response = await fetch(`/api/configurator/session/${sessionId}/extras?include=next-step`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ extraIds: selectedExtras })
            });


            if (!response.ok) throw new Error('Error del servidor.');
            
            const result = await response.json();     
            
            if (!result.success) throw new Error(result.message);   

            console.log(result);

            // ¡Éxito!
            this.activeSession = result.data.activeSession;            
            
            // Llamamos al nuevo método para renderizar el resumen, pasándole los datos.
            this.renderStep4_Summary(result.data.summary, result.data.totalPrice);

        } catch (error) {
            console.error(error);
             this.renderStep3_Extras(); // Si falla, volvemos a renderizar el paso de extras
            alert('Error al guardar los extras. Por favor, inténtelo de nuevo.');
        }
    },
    
    // El nuevo método para renderizar el resumen (Paso 4)
    renderStep4_Summary: async function(summaryData,totalPrice) {
        console.log(summaryData);
        this.currentStep = 4;
        this.updateStepIndicator();
        this.elements.contentArea.innerHTML = `<p>${this.translations.loading_summary || 'Cargando resumen...'}</p>`;

        let renderSummary = summaryData;
        let renderTotalPrice = totalPrice;

        if (!renderSummary) {
            const sessionId = this.activeSession.id_conf_session;

            // --- ¡LLAMADA A LA NUEVA API! ---
            const response = await fetch(`/api/configurator/session/${sessionId}/summary`);
            if (!response.ok) throw new Error('Error del servidor.');
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            
            renderSummary = result.data.summary;
            renderTotalPrice = result.data.totalPrice;
        }
            
        this.updateTotalPrice(renderTotalPrice);
        
        let html = `<h2>${this.translations.step4_title || 'Resumen de la Configuración'}</h2>`;
        html += '<div class="summary-details">';
        html += `<h3>${renderSummary.model.name} <span>(${renderSummary.model.price.toFixed(2)} €)</span></h3>`;
        html += `<p><strong>Color:</strong> ${renderSummary.color.name} <span>(+ ${renderSummary.color.price.toFixed(2)} €)</span></p>`;
        
        if (renderSummary.extras.length > 0) {
            html += '<h4>Extras Seleccionados:</h4><ul>';
            renderSummary.extras.forEach(extra => {
                html += `<li>${extra.name} <span>(+ ${extra.price.toFixed(2)} €)</span></li>`;
            });
            html += '</ul>';
        }
        
        html += '</div>';    
        
        // Botones de acción finales
        html += `
            <div class="step-actions">
                <button id="step4-back-btn" class="back-button">${this.translations.back_button || 'Volver'}</button>
                <button id="assign-client-btn" class="assign-button">${this.translations.assign_client_button || 'Asignar a Cliente'}</button>
            </div>
        `;
        
        this.elements.contentArea.innerHTML = html;
        this.attachStep4Events();
    },
    
    attachStep4Events: function() {
        document.getElementById('step4-back-btn').addEventListener('click', () => {
            // Volver a la selección de extras
            this.renderStep3_Extras();
        });

        document.getElementById('assign-client-btn').addEventListener('click', () => {
            // Redirigimos al script legacy para finalizar
            const sessionId = this.activeSession.id_conf_session;
            window.location.href = `/app/clients.php?conf_session=${sessionId}`;
        });
    },

    /**
     * Actualiza la barra de progreso visual para resaltar el paso actual.
     */
    updateStepIndicator: function() {
        // Si los indicadores no se han encontrado, no hacemos nada.
        if (!this.elements.stepIndicators) return;

        // 1. Iteramos sobre todos los indicadores de paso.
        this.elements.stepIndicators.forEach(indicator => {
            
            // 2. Quitamos la clase 'active' de TODOS los pasos.
            indicator.classList.remove('active');

            // 3. Obtenemos el número de paso del atributo 'data-step'.
            // Lo convertimos a número para poder compararlo.
            const stepNumber = parseInt(indicator.dataset.step);

            // 4. Si el número del indicador coincide con nuestro paso actual...
            if (stepNumber === this.currentStep) {
                // ...le añadimos la clase 'active'.
                indicator.classList.add('active');
            }
        });
    },
    updateTotalPrice: function(price) {
        if (this.elements.totalPrice) {
            this.elements.totalPrice.textContent = price.toFixed(2);
        }
    },
};

// Arrancamos la aplicación cuando el DOM esté listo.
document.addEventListener('DOMContentLoaded', () => {
    ConfiguratorApp.init();
});