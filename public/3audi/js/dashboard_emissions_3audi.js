document.addEventListener('DOMContentLoaded', () => {
    const modelsLink = document.querySelector('a[href="/api/report-emissions"]');
    const modelsContainer = document.getElementById('show-report');

    if (!modelsLink || !modelsContainer) return;

    /**
     * Renderiza el informe de modelos en el contenedor.
     * @param {Object} result - Respuesta del servidor.
     */
    function renderModelsReport(result) {
        if (result.success) {
            const report = result.data;
            let tableHTML = `
                <h3>${report.title}</h3>
                <table border="1" style="width:100%; color:white;">
                    <thead>
                        <tr>
                            <th>${report.name_tag}</th>
                            <th>${report.price_tag}</th>
                            <th>${report.emissions_tag}</th>
                        </tr>
                    </thead>
                    <tbody>`;

            report.models.forEach(model => {
                tableHTML += `
                    <tr>
                        <td>${model.name}</td>
                        <td>${model.price}</td>
                        <td>${model.emissions}</td>
                    </tr>`;
            });

            tableHTML += '</tbody></table>';
            modelsContainer.innerHTML = tableHTML;
        } else {
            modelsContainer.innerHTML = `<p style="color:red;">Error: ${result.message}</p>`;
        }
    }

    /**
     * Obtiene el informe desde el backend y lo renderiza.
     */
    async function fetchModelsReport() {
        modelsContainer.innerHTML = 'Generando informe...';
        try {
            const response = await fetch('/api/report-emissions');
            const result = await response.json();
            renderModelsReport(result);
        } catch (error) {
            console.error('Error fetching models report:', error);
            modelsContainer.innerHTML = '<p style="color:red;">No se pudo generar el informe de modelos.</p>';
        }
    }

    // Evento para click manual en el enlace
    modelsLink.addEventListener('click', (event) => {
        event.preventDefault();
        fetchModelsReport();
    });

    // Autoejecución si el parámetro reportAfterLoad=emissions está presente
    const params = new URLSearchParams(window.location.search);
    console.log(params)
    if (params.get('reportAfterLoad') === 'emissions') {
        fetchModelsReport();
    }
});