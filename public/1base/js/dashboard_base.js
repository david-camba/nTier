document.addEventListener('DOMContentLoaded', () => {
    const reportsLink = document.querySelector('a[href="/api/employees-report"]');
    const reportContainer = document.getElementById('show-report');

    if (!reportsLink || !reportContainer) return;

    /**
     * Función para renderizar la tabla del reporte
     */
    function renderReport(result) {
        if (result.success) {
            const report = result.data;
            let tableHTML = `<h3>${report.title}</h3>
                             <table border="1" style="width:100%; color:white;">
                                <thead>
                                    <tr>
                                        <th>${report.name_tag}</th>
                                        <th>${report.user_tag}</th>
                                        <th>${report.email_tag}</th>
                                        <th>${report.role_tag}</th>
                                    </tr>
                                </thead>
                                <tbody>`;

            report.team_members.forEach(member => {
                tableHTML += `<tr>
                                <td>${member.name}</td>
                                <td>${member.username}</td>
                                <td>${member.email}</td>
                                <td>${member.role_code}</td>
                              </tr>`;
            });

            tableHTML += '</tbody></table>';
            reportContainer.innerHTML = tableHTML;
        } else {
            reportContainer.innerHTML = `<p style="color:red;">Error: ${result.message}</p>`;
        }
    }

    /**
     * Función para cargar el reporte vía fetch
     */
    function loadEmployeesReport() {
        reportContainer.innerHTML = 'Generando informe...';

        fetch('/api/employees-report')
            .then(response => response.json())
            .then(renderReport) //equivalente a= .then(json => renderReport(json))
            .catch(error => {
                console.error('Error fetching report:', error);
                reportContainer.innerHTML = '<p style="color:red;">Error fetching report.</p>';
            });
    }

    // Listener del click en el link
    reportsLink.addEventListener('click', (event) => {
        event.preventDefault();
        loadEmployeesReport();
    });

    // Comprobar parámetro en la URL y cargar automáticamente si existe
    const params = new URLSearchParams(window.location.search);
    if (params.get('reportAfterLoad') === 'employees') {
        loadEmployeesReport();
    }
});