<?php
// Fichero de traducciones al Español

return [
    // --- Login Page ---
    'login_page_title' => 'Portal de Acceso',
    'login_header_brand' => 'Bienvenido a %s', // %s se reemplazará por el nombre de la marca
    'login_form_username_placeholder' => 'Nombre de Usuario',
    'login_form_password_placeholder' => 'Contraseña',
    'login_form_submit_button' => 'Acceder',
    'login_form_guest_access' => 'Continuar como invitado',
    'login_translation_message' => 'Elige idioma:', 

    // --- Mensajes de la API de Login ---
    'login_api_error_credentials' => 'Usuario o contraseña incorrectos.',
    'login_api_error_missing_fields' => 'Usuario y contraseña son obligatorios.',
    'login_api_error_account_locked' => 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente.',
    'login_api_success_redirecting' => '¡Login correcto! Redirigiendo...',
    'login_userlevel_warning' => 'IMPORTANTE: No puedes tener un Nivel de Usuario (Vendedor, Manager, etc.) a menos que hayas iniciado sesión. Y por favor, no uses el panel de debug para eso 😉',
    'login_userlevel_joke' => 'Vaya, lo usaste... parece que la arquitectura por capas puede soportar incluso esto 😄',


     // --- Dashboard & Layout ---
    'menu_dashboard' => 'Dashboard',
    'menu_configurator' => 'Configurador de Pedidos - powered by LifeTree.js',
    'menu_clients_legacy' => 'Clientes (Legacy Integration)',
    'menu_user_management' => 'Gestión de Usuarios', // 'User Management'
    'menu_report' => 'Informe (solo para managers)', // 'Reports'
    'user_greeting' => 'Hola',
    'menu_logout' => 'Cerrar Sesión',
    'dashboard_title' => 'Panel Principal',
    'dashboard_welcome_message' => '¡Hola de nuevo! Ojalá hoy vendas muchísimo.',
    'dashboard_welcome_message_manager' => '¡Hola de nuevo! Eres el mejor manager del mundo.',
    'dashboard_vw_message' => 'VOLKSWAGEN se preocupa por una energía limpia. <i style="color: lightgreen;">Recordatorio: nuestro foco siempre debe ser reducir las emisiones</i>. Recordatorio patrocinado por la vista principal en la capa 2.',
    

    'views_layer_message' => '"El sistema de capas permite crear vistas hijas en capas superiores no solo de la vista principal, sino también de las plantillas que esta importa." - dijo la plantilla importada de la capa 3',

    'report_team_title' => 'Estas personas trabajan para ti, son geniales.',

    'name_tag' => 'Nombre',
    'user_tag' => 'Usuario',
    'email_tag' => 'Email',
    'role_tag' => 'Puesto',
    'adress_tag' => 'Dirección',
    'age_tag' => 'Edad',
    'salary_tag' => 'Salario Estimado (Dato exclusivo de Audi)',
    'financing_tag' => 'Acceso a financiación',
    'registrationdate_tag' => 'Fecha de alta',
    'totalprice_tag' => 'Precio total',
    'proposaldate_tag' => 'Fecha de propuesta',
    'backmenu_tag' => 'Volver al menu',
    'model_tag' => 'Modelo',
    'price_tag' => 'Precio',
    'emissions_tag' => 'Emisiones',
    'from_tag' => 'Desde',
    

    'report_models_title' => 'Informe de emisiones',

    'clients_title' => 'Código Legacy - Búsqueda clientes',
    'clients_intro_message' => 'Escribe "javier" u otras letras para buscar un cliente',
    'clients_intro_message_assigning' => 'Escribe "javier" u otras letras para buscar un cliente y asignarle tu propuesta',
    'clients_only_managers' => 'Lo sentimos, esta sección está restringida solo a los managers.',
    'clients_details' => 'Detalles del cliente',
    'clients_proposals_title' => 'Propuestas relacionadas',
    'clients_search' => 'Buscar cliente por nombre...',


    'role_M' => 'Manager',
    'role_S' => 'Vendedor/a',

    // --- Configurator Page ---
    'configurator_title' => 'Configurador de Pedido',
    'configurator_templates_label' => 'Cargar plantilla:',
    'configurator_templates_default_option' => 'Seleccionar una plantilla',
    'configurator_no_templates_message' => 'No tienes plantillas guardadas.',
    'configurator_step1_title' => 'Elige un modelo',
    'configurator_step2_title' => 'Elige un color',
    'configurator_step3_title' => 'Extras',
    'configurator_step4_title' => 'Asignar Cliente',
    'configurator_loading_message' => 'Cargando configurador...',
    'configurator_price_label' => 'Precio Total Estimado',
    'configurator_save_template_button' => 'Guardar como Plantilla',
    'configurator_assign_client_button' => 'Asignar a Cliente',
    'configurator_extras_picker_title' => 'Escoge tantos extras como quieras',
    'configurator_model_card_price_prefix' => 'Desde ',
    'configurator_model_picker_title' => 'Elige tu modelo favorito',
    'configurator_summary_title' => 'Resumen de la compra',
    'configurator_summary_final_price' => 'Precio Final',
    'configurator_summary_no_extras' => 'Sin extras',

    'configurator_loading_models' => 'Cargando modelos...', 
    'configurator_next_button' => 'Siguiente', 
    'configurator_loading_colors' => 'Cargando colores...', 
    'configurator_back_button' => 'Volver', 

    // --- Nombres de Colores ---
    'color.black_mythos' => 'Negro Mito',
    'color.silver_floret' => 'Plata Florete',
    'color.white_glacier' => 'Blanco Glaciar',
    'color.black_orca' => 'Negro Orca',
    'color.red_matador' => 'Rojo Pasion',
    'color.silver_cuvee' => 'Plata Cuvée',
    'color.white_ibis' => 'Blanco Ibis',

    // --- Nombres de Extras ---
    'extra.rims_21' => 'Llantas de aleación de 21"',
    'extra.rims_21_desc' => 'Diseño de 5 radios en V, gris contraste, torneado brillante.',
    'extra.rims_19' => 'Llantas de aleación de 19"',
    'extra.rims_19_desc' => 'Diseño multirradio, acabado en bronce.',
    'extra.pkg_city' => 'Paquete de asistentes City',
    'extra.pkg_city_desc' => 'Incluye Audi pre sense 360°, cross traffic assist y exit warning.',
    'extra.pkg_tour' => 'Paquete de asistentes Tour',
    'extra.pkg_tour_desc' => 'Control de crucero adaptativo con asistente de conducción en atascos.',
    'extra.seats_sport' => 'Asientos deportivos S',
    'extra.seats_sport_desc' => 'Tapicería en cuero Valcona con acolchado en rombos y grabado S.',
    'extra.seats_comfort' => 'Asientos confort con memoria',
    'extra.seats_comfort_desc' => 'Ajuste eléctrico completo y función de memoria para el asiento del conductor.',
    'extra.sunroof' => 'Techo panorámico corredizo',
    'extra.sunroof_desc' => 'Dos piezas de cristal con accionamiento eléctrico para ventilación y apertura.',
    'extra.headlights_matrix' => 'Faros HD Matrix LED',
    'extra.headlights_matrix_desc' => 'Con intermitentes dinámicos y luz láser Audi de largo alcance.',
    'extra.sound_bo' => 'Sistema de sonido Bang & Olufsen 3D',
    'extra.sound_bo_desc' => 'Sonido premium con 19 altavoces, subwoofer y 755 vatios de potencia.',
    'extra.headup' => 'Head-up display',
    'extra.headup_desc' => 'Proyecta información relevante de la conducción directamente en el parabrisas.',
    'extra.suspension_air' => 'Suspensión neumática adaptativa',
    'extra.suspension_air_desc' => 'Ajuste electrónico de la altura y dureza de la suspensión.',
    'extra.steering_flat' => 'Volante deportivo achatado',
    'extra.steering_flat_desc' => 'Diseño de 3 radios forrado en cuero perforado, con levas de cambio.',

    'not_found_message' => 'Upps, no se encontró esta página... URL requested: %s',
    'go_back_main' => 'Volver a la página principal',


    'banner_github_invitation' => 'github.com/nTier-backend-framework',
    'banner_github_invitation_tree' => 'github.com/LifeTree-frontend-framework',
    'banner_little_prince' => 'Lo esencial es invisible a los ojos. - El Principito',
    'banner_lifetree_quote' => 'Los árboles son poemas que la tierra escribe en el cielo. - Kahlil Gibran',

    'login_explanation' => 'Framework MVCS (Modelo-Vista-Controlador-Servicios) multicapa vertical y horizontal hecho desde 0. Configurable, motor multicapa de inyección de dependencias y carga de archivos, Service Locator disponible, ORM propio, soporte de módulos legacy independientes, funcionalidades de autenticación y traducción, middleware, testing de integración y unitario, cacheo de vistas, factory de modelos para desacoplar a la DB y mucho mimo puesto en mejorar la experiencia del desarrollador en el manejo de capas. Routing.',


];


