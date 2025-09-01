document.addEventListener('DOMContentLoaded', () => {
    const allowedPaths = ['/', '/app'];
    if (allowedPaths.includes(window.location.pathname)) return;

    const employeesReportLink = document.querySelector('a[href="/api/employees-report"]');    
    if (employeesReportLink){
        employeesReportLink.href = '/app?reportAfterLoad=employees';
    }

    const emmisionsReportLink = document.querySelector('a[href="/api/report-emissions"]');  
    if (emmisionsReportLink){
        emmisionsReportLink.href = '/app?reportAfterLoad=emissions';
    }
    
});