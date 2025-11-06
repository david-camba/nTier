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
            'json_route' => true
        ],
        '/api/report-emissions' => [
            'controller' => 'EmissionsController', 
            'action' => 'showReportEmissions', 
            'json_route' => true
        ],

        '/app/spa-emissions' => [
            'controller' => 'EmissionsSPAController', 
            'action' => 'show', 
            'json_route' => true
        ],

        // Una ruta explícita para la página de login.
        '/login'      => [
            'controller' => 'AuthController', 
            'action' => 'showLogin'
        ],

        '/guest-access'        => [
            'controller' => 'AuthController', 
            'action' => 'createAuthUser'
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
            'json_route'  => true 
        ],
        '/api/configurator/session/(\d+)/colors' => [
            'controller' => 'ConfiguratorController',
            'action' => 'getColorsForSessionAPI', 
            'json_route'  => true
        ],

        '/api/configurator/session/(\d+)/extras' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'getExtrasForSessionAPI', // Nuevo nombre de método
            'json_route'  => true
        ],

        '/api/configurator/session/(\d+)/summary' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'getSummaryForSessionAPI',
            'json_route'  => true
        ],        
    ],

    // Rutas que responden a peticiones POST
    'POST' => [
        // La ruta de la API para procesar el login vía AJAX.
        '/api/login' => [
            'controller' => 'AuthController', 
            'action' => 'doLoginAPI', 
            'json_route' => true
        ],
    ],

    'PUT' => [ 
        '/api/configurator/session/(\d+)/reset-to-step1' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'resetToStep1',
            'json_route'  => true
        ],
    ],

    'PATCH' => [
        '/api/configurator/session/(\d+)/model' => [
            'controller' => 'ConfiguratorController',
            'action' => 'saveModelsAPI',
            'json_route'  => true
        ],

        '/api/configurator/session/(\d+)/colors' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'saveColorsAPI' ,
            'json_route'  => true
        ],
        '/api/configurator/session/(\d+)/extras' => [
            'controller' => 'ConfiguratorController',
            'action'     => 'saveExtrasAPI',
            'json_route'  => true
        ],
    ],

    // Podríamos añadir más métodos aquí si fuera necesario
    // 'PUT' => [ ... ],
    // 'DELETE' => [ ... ],
];