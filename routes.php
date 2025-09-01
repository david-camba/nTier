<?php
// EN: 1base/routes/web.php

/**
 * Archivo de definición de rutas para la aplicación.
 *
 * Este archivo devuelve un array que mapea métodos HTTP y patrones de URL
 * a un controlador y una acción específicos.
 * Es cargado por el constructor de la clase Router.
 */

return [
    // Rutas que responden a peticiones GET
    'GET' => [
        // La raíz del dominio y /app apuntan al Dashboard.
        // La App se encargará de la lógica de sesión para decidir si muestra
        // el Dashboard o redirige al Login.
        '/'           => [
            'controller' => 'DashboardController', 
            'action' => 'showDashboard'
        ],
        '/app'        => [
            'controller' => 'DashboardController', 
            'action' => 'showDashboard'
        ],
        '/api/employees-report' => [
            'controller' => 'DashboardController', 
            'action' => 'showEmployeesReportAPI', 
            'api_route' => true
        ],
        '/api/report-emissions' => [
            'controller' => 'EmissionsController', 
            'action' => 'showReportEmissions', 
            'api_route' => true
        ],

        '/app/spa-emissions' => [
            'controller' => 'EmissionsSPAController', 
            'action' => 'show', 
            'api_route' => true
        ],

        // Una ruta explícita para la página de login.
        '/login'      => [
            'controller' => 'AuthController', 
            'action' => 'showLogin'
        ],

        '/logout' => [
            'controller' => 'AuthController', 
            'action' => 'doLogout'
        ],

        // La página del configurador de pedidos.
        '/app/config' => [
            'controller' => 'ConfiguratorController', 
            'action' => 'showConfigurator'
        ],
        '/api/configurator/models' => [ 
            'controller' => 'ConfiguratorController', 
            'action' => 'getModelsAPI', 
            'api_route'  => true 
        ],
        '/api/configurator/session/(\d+)/colors' => [
            'controller' => 'ConfiguratorController',
            'action' => 'getColorsForSessionAPI', 
            'api_route'  => true
        ],

        '/api/configurator/session/(\d+)/extras' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'getExtrasForSessionAPI', // Nuevo nombre de método
            'api_route'  => true
        ],

        '/api/configurator/session/(\d+)/summary' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'getSummaryForSessionAPI',
            'api_route'  => true
        ],        
    ],

    // Rutas que responden a peticiones POST
    'POST' => [
        // La ruta de la API para procesar el login vía AJAX.
        '/api/login' => [
            'controller' => 'AuthController', 
            'action' => 'doLoginAPI', 
            'api_route' => true
        ],
    ],

    'PUT' => [ 
        '/api/configurator/session/(\d+)/reset-to-step1' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'resetToStep1',
            'api_route'  => true
        ],
    ],

    'PATCH' => [
        '/api/configurator/session/(\d+)/model' => [
            'controller' => 'ConfiguratorController',
            'action' => 'saveModelsAPI',
            'api_route'  => true
        ],

        '/api/configurator/session/(\d+)/colors' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'saveColorsAPI' ,
            'api_route'  => true
        ],
        '/api/configurator/session/(\d+)/extras' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'saveExtrasAPI',
            'api_route'  => true
        ],
    ],

    // Podríamos añadir más métodos aquí si fuera necesario
    // 'PUT' => [ ... ],
    // 'DELETE' => [ ... ],
];