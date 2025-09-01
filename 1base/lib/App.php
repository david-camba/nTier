

<?php
// Incluimos la dependencia del Router, ya que run() la necesita.
require_once 'lib/Router.php';

/**
 * Clase App
 *
 * El núcleo de la aplicación. Actúa como una "super fábrica" para crear
 * componentes (controladores, modelos, etc.) y como el orquestador
 * principal que gestiona el ciclo de vida de una petición HTTP.
 */
class App
{
    /**
     * Almacena la configuración completa de la aplicación cargada desde config.php.
     * @var array
     */
    private $config;
    private $context = [];
    private $userLayer = null; //control access to the 3 layers (vertical layers)
    private $userLevel = null; //control access to user role capabilities (horizontal layers)

    private $translatorService = null; // Un caché para la instancia
    private $modelFactory = null; //guardamos la fábrica la primera vez que se genera para cachear las conexiones con bases de datos

    //private $isApiRoute = false; // Nueva propiedad para recordar el tipo de ruta.

    /**
     * El constructor recibe la configuración y la almacena.
     * @param array $config La configuración de la aplicación.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * El método principal que ejecuta la aplicación.
     * Orquesta el enrutamiento, la seguridad de sesión y la ejecución
     * del controlador o script correspondiente.
     */
    public function run()
    {
        try {
            $this->prepareDebugging();
            // 1. Obtener el plan de acción del Router.
            $router = new Router();
            $requestedRouteInfo = $router->getRouteInfo();   

            // 2. Aplicar la lógica de seguridad de sesión.
            $authService = $this->getService('Auth');
            $finalRouteInfo = $authService->authenticateRequest($requestedRouteInfo);

            debug("Peticion controller - routeInfo",$finalRouteInfo,false);

            //detect if is JSON request
            /*if (!empty($requestedRouteInfo['api_route'])) {                
                $this->isApiRoute = true;
            }*/
            
            // 3. Decidir qué hacer basándose en el tipo de ruta.
            switch ($finalRouteInfo['type']) {
                case 'mvc_action':
                    // Si es una ruta moderna, ejecutamos el flujo MVC.
                    /*$controller = $this->getController($routeInfo['controller']);
                    $response = $controller->{$routeInfo['action']}(...$routeInfo['params']);*/

                    // 1. Obtenemos el objeto Response preparado por el dispatcher.
                    $response = $this->dispatchAction($finalRouteInfo);

                    // 2. Aquí podríamos aplicar middlewares a la respuesta
                    $this->debugResponse($response);

                    $this->sendResponse($response);
                    break;

                case 'legacy_script':
                    // Si es una ruta legacy, ejecutamos el script.
                    $scriptPath = __DIR__ . '/../' . $finalRouteInfo['script_path'];
                    
                    // Verificamos que el archivo existe en esa ubicación fija.
                    if (file_exists($scriptPath)) {
                        // 3. Lo ejecutamos.
                        require_once $scriptPath;
                    } else {
                        // Si el script no existe, es un error 404.
                        throw new Exception("Script legacy no encontrado: {$routeInfo['script_name']}", 404);
                    }
                    break;

                default:
                    // Si el Router devuelve un tipo desconocido, es un error interno.
                    throw new Exception("Tipo de ruta desconocido: '{$routeInfo['type']}'", 500);      
            }
            exit();
        } catch (Throwable $e) {
            // Captura cualquier error o excepción que ocurra durante la ejecución
            // y lo pasa a nuestro manejador de errores central.
            //$this->handleError($e);
            error_log($e);
        }
    }

    public function setUserLayer($userLayer)
    {
        //Solo la layer una vez
        if ($this->userLayer !== null) {
            return;
        }
        $this->userLayer = (int) $userLayer;
    }
    public function getUserLayer()
    {
        return $this->userLayer;
    }

    public function setUserLevel($userLevel)
    {
        //Solo se fija el level una vez
        if ($this->userLevel !== null) {
            return;
        }
        $this->userLevel = (int) $userLevel;
    }
    public function getUserLevel()
    {
        return $this->userLevel;
    }

    public function setContext($key, $value)
    {
        $this->context[$key] = $value;
    }

    public function getContext($key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
 * El nuevo "dispatcher" inteligente.
 * Encuentra y ejecuta la implementación de controlador/acción más específica
 * basándose en la capa vertical y el rol horizontal del usuario.
 */
private function dispatchAction(array $routeInfo)
{
    $controllerName = $routeInfo['controller'];
    $actionName = $routeInfo['action'];
    $params = $routeInfo['params'];
    $userLevel = $this->getUserLevel();
    
    // 1. Determinar el sufijo del rol del usuario (si lo tiene).
    $roleSuffix = ($userLevel != 0 && ($role = $this->getConfig(['user_roles', $userLevel])) !== null)
        ? $role
        : '';

    // 2. INTENTO A: Buscar un Controlador especializado para el rol.
    $specializedControllerName = "{$controllerName}_{$roleSuffix}";
    try {
        // Intentamos obtener el controlador con el sufijo del rol.
        $controller = $this->getController($specializedControllerName);
        
        // Si tiene éxito, lo usamos. No necesitamos comprobar el método.
        // Asumimos que si existe un AuthControllerManager, tiene un método showLogin.
        return $controller->{$actionName}(...$params);

    } catch (Exception $e) {
        // No pasa nada. Significa que el controlador especializado no existe.
        // Continuamos al siguiente intento.
    }

    // 3. INTENTO B: Usar el Controlador normal, pero buscar el Método especializado del rol del usuario.    
    $controller = $this->getController($controllerName);
    $specializedActionName = "{$actionName}_{$roleSuffix}";

    
    if ($roleSuffix && method_exists($controller, $specializedActionName)) {
        // ¡Encontramos un método especializado! Lo ejecutamos.
        $response = $controller->{$specializedActionName}(...$params);
        return $response;
    }
    // Si el controllador tiene la propiedad fallbackRole = true, busco en bucle hacia abajo

    // 4. INTENTO C: Usar el Controlador normal, pero buscar un Método especializado de roles de usuarios con menores privilegios    
    if($controller->useUserLevelFallback()){
        $fallbackLevel = $userLevel - 1;
        while($fallbackLevel > 0){
            $currentRoleSuffix = $this->getConfig('user_roles')[$fallbackLevel];
            $currentActionName = "{$actionName}_{$currentRoleSuffix}";

            if ($roleSuffix && method_exists($controller, $currentActionName)) {
                $response = $controller->{$currentActionName}(...$params);
                return $response;
            }

            $fallbackLevel--;
        }
    }


    // 5. FALLBACK: Usar el Controlador y el Método normales.
    if (method_exists($controller, $actionName)) {
        return $controller->{$actionName}(...$params);        
    }
    
    // 5. Si nada de lo anterior funciona, es un 404.
    throw new Exception("Acción no encontrada para la ruta: {$controllerName}->{$actionName}", 404);
    }

    
    public function getModel($modelName, array $constructorArgs=[], $userLayer = null, $cache = false)
    {
        $connectionType = $this->getConfig(['model_connections',$modelName]);           

        // CASO 1: Override explícito. Sin caché.
        if ($userLayer !== null && !$cache) {
            $factory = $this->getComponent('factory', 'ModelFactory', [$this], $userLayer);
            return $factory->create($modelName, $connectionType, $constructorArgs, $userLayer);
        }

        // CASO 2: Override explícito. Con cache.
        if ($userLayer !== null && $cache)  {
            $this->modelFactory = $this->getComponent('factory', 'ModelFactory', [$this], $userLayer);
            return $this->modelFactory->create($modelName, $connectionType, $constructorArgs, $userLayer);
        }
        
        // CASO 3: Usuario autenticado. Con caché.
        if ($this->userLayer) {
            
            if ($this->modelFactory === null) {
                $this->modelFactory = $this->getComponent('factory', 'ModelFactory', [$this], $this->userLayer);
            }
            return $this->modelFactory->create($modelName, $connectionType, $constructorArgs, $this->userLayer);
        }
        
        // CASO 4: Invitado. Nivel 1, sin caché.
        $guestFactory = $this->getComponent('factory', 'ModelFactory', [$this], 1);
        return $guestFactory->create($modelName, $connectionType, $constructorArgs, 1);
    }

    /**
     * Crea y devuelve una instancia de un Controlador.
     * Inyecta automáticamente la App en el constructor. 
     * Nunca recibe argumentos aparte de "App", los parámetros recibidos se gestionan desde el método llamado, no desde la función
     */
    public function getController($controllerName) {
        return $this->getComponent('controller', $controllerName, [$this]);
    }


     /**
     * El método "fábrica" principal.
     * Crea y devuelve una instancia de cualquier componente jerárquico.
     *
     * @param string $type El tipo de componente ('controller', 'model', etc.).
     * @param string $name El nombre base del componente ('AuthController').
     * @param array $constructorArgs Los argumentos de entrada del constructor se pasarán en un array.
     * @return object La instancia del objeto solicitado.
     */
    public function getComponent($type, $name, $constructorArgs = [], $userLayer = null, $exactLayerOnly = false)
    {
        // 1. Encontrar la información del archivo y la capa.
        $componentInfo = $this->findFile($type, $name, $userLayer, $exactLayerOnly);
        debug("getComponent - componentInfo",$componentInfo,false);

        if (!$componentInfo) {
            throw new Exception(ucfirst($type) . " no encontrado: {$name}");
        }

        // 2. Cargar el archivo y toda su cadena de herencia.
        require_once $componentInfo['path'];

        // 3. Construir el nombre de la clase final.
        $className = "{$name}_{$componentInfo['suffix']}";

        // 4. Verificar que la clase existe.
        if (!class_exists($className)) {
            throw new Exception("Error de Carga: La clase '{$className}' no está definida en el archivo '{$componentInfo['path']}'.");
        }

        // 5. Instanciar la clase con argumentos (si los hay).
        if (empty($constructorArgs)) {
            return new $className();
        } else {
            $reflection = new ReflectionClass($className);
            return $reflection->newInstanceArgs($constructorArgs); //reflection nos permite N argumentos en un array
        }
    }

    /**
     * Es un parent::callingMethod() dinámico que facilita la sintaxis
     * Ejecuta el mismo método del objeto en la clase padre y devuelve su respuesta.
     * Detecta automáticamente el nombre del método que lo llamó y ajusta los argumentos según el padre.
     * @param object $callerObject Recibe el objeto que la llama para poder ejecutar el método manteniendo el estado
     * @return mixed La respuesta del método padre del Controller, Service, Model...
     */
    public function callParent(object $callerObject) : mixed
    {
        // 1. Detectar el método y la clase que nos llamó
        $backtrace = debug_backtrace(0, 5); //obtenemos las 5 últimas llamadas para aseguranos de encontrar al llamador real

        // 2. Ignoramos los métodos intermedios (si existen) para obtener el metodo llamador real
        $callHelpers = $this->getConfig('parent_call_helpers');
        //si se extendiera la cantidad de métodos ayudantes se podría meter en un config 

        $callerInfo = null;
        // Empezamos en el índice 1, porque el 0 siempre es el método actual.
        for ($i = 1; $i < count($backtrace); $i++) {
            $frame = $backtrace[$i];            
            // Si el nombre de la función actual NO está en nuestra lista de ayudantes,
            // entonces hemos encontrado al "llamador real".
            if (!in_array($frame['function'], $callHelpers)) {
                $callerInfo = $frame;
                break; // Salimos del bucle.
            }
        }

        if (!$callerInfo) {
            throw new LogicException("No se pudo determinar el método llamante real.");
        }

        $callerClassName = $callerInfo['class'] ?? null;
        $callerMethodName = $callerInfo['function'] ?? null;
        $callerArgs = $callerInfo['args'] ?? [];

        if (!$callerMethodName) {
            throw new LogicException("No se pudo determinar el método o clase que llama a getParentView.");
        }

        

        // 2. Obtener el padre real y preparar el método
        $parentClass = get_parent_class($callerClassName);
        if (!method_exists($parentClass, $callerMethodName)) {
            throw new LogicException("El método {$callerMethodName} no existe en la clase padre {$parentClass}.");
        }        

        // 3. Ejecutar el método del padre en el contexto del $this actual
        $method = new ReflectionMethod($parentClass, $callerMethodName); //creamos un reflection del metodo

        $numParams = $method->getNumberOfParameters(); 
        $callerArgs = array_slice($callerArgs, 0, $numParams); //limitamos el numero de parametros a los del padre, por si ha habido alguna ampliacion

        $method->setAccessible(true); //quitamos el protected para que el reflexion method pueda lanzarse

        $response = $method->invokeArgs($callerObject, $callerArgs); //invocamos sobre $this (el objeto de la capa superior que haya llamado, nos permite mantener el estado, y pasamos los argumentos)

        return $response;
    }



    /**
     * Crea y devuelve una instancia de la clase View, configurada
     * con la ruta a la plantilla XSLT más específica que exista.
     *
     * @param string $viewName El nombre de la vista a renderizar (ej: 'login').
     * @return View
     */
    public function getView($viewName)
    {
        // Incluimos el archivo de la clase View.
        require_once 'lib/View.php';
        
        // Simplemente creamos la instancia y le pasamos
        // la App y el nombre de la vista que debe gestionar.
        return new View($this, $viewName);
    }

        /**
     * Crea y devuelve un objeto de respuesta, cargando el archivo
     * de su clase solo cuando es necesario.
     *
     * @param string $type El tipo de respuesta (ej. 'json', 'view').
     * @param mixed ...$args Los argumentos para el constructor de la respuesta.
     * @return Response
     */
    public function getResponse($type, ...$args)
    {
        // 1. Construimos el nombre de la clase y la ruta al archivo.
        require_once "lib/response/Response.php";
        $className = ucfirst($type) . 'Response'; // Asumiendo la convención con sufijo
        $filePath = "lib/response/{$className}.php";  // Usamos el nombre de clase como nombre de archivo

        // 2. Cargamos el archivo de forma condicional, solo si no ha sido cargado antes.
        // Gracias al include_path, PHP encontrará "lib/response/..." en "1base/".
        require_once $filePath;

        // 3. Verificamos que la carga fue exitosa y la clase existe.
        if (!class_exists($className)) {
            // Este error solo ocurriría si el archivo no existe o tiene un nombre de clase incorrecto.
            throw new Exception("No se pudo cargar la clase de respuesta: {$className}");
        }
        
        // 4. Instanciamos la clase.
        return new $className(...$args);
    }

    /**
     * El "buscador" único. Encuentra la ruta a un archivo de componente
     * recorriendo la jerarquía definida en la configuración.
     *
     * @param string $type El tipo de componente ('controller', 'model').
     * @param string $name El nombre base del componente.
     * @return array|null Un array con ['path', 'suffix'] o null si no se encuentra.
     */
    public function findFile($type, $name, $userLayer = null, $exactLayerOnly = false)
    {
        if ($userLayer === null) { //if null, we get the user layer from context
            //this allow us to call with specific range.
            $userLayer = $this->userLayer ? $this->getUserLayer() : 1;
        }

        // Obtener la subcarpeta del componente desde la configuración (ej: 'controllers').
        $componentSubdir = $this->getConfig(['component_types',$type]) ?? null;
        if (!$componentSubdir) {
            throw new Exception("Tipo de componente desconocido: {$type}");
        }
        $extension = ($type === 'view') ? '.xsl' : '.php';
        $relativePath = "{$componentSubdir}/{$name}{$extension}";

        $currentLayer = max(array_column($this->getConfig('layers'), 'layer')); //get the level of the highest rank of the hierarchy
        
        // Iterar sobre la jerarquía de esta instalación (ej: ['audi', 'vwgroup', 'base']).
        foreach ($this->getConfig('layers') as $layerKey => $layerInfo) {

            if ($userLayer < $currentLayer){     //if the userLayer is lower than the current layer, we wont look for a file           
                $currentLayer--;
                continue;
            }

            // Si queremos nivel exacto, y este no es, lo saltamos.
            if ($exactLayerOnly && $currentLayer != $userLayer) {
                $currentLayer--;
                continue; 
            }

            // Obtener la información de la capa desde la configuración.
            $layerDir = $layerInfo['directory'];

            $filePath = "{$layerDir}/{$relativePath}";
            
            $absolutePath = __DIR__ . '/../' . '/../' . $filePath; //comprobamos si el archivo existe

            if (file_exists($absolutePath)) {
                // ¡Encontrado! Devolvemos la información de la capa más específica.
                return [
                    'path'   => $absolutePath,
                    'suffix' => $layerInfo['suffix'],
                    'name' => $name,
                    'level' => $currentLayer
                ];
            }

            //si estabamos en el nivel exacto y no lo hemos encontrado, devolvemos null
            if ($exactLayerOnly && $currentLayer == $userLayer) {
                return null;
            }

            $currentLayer--;
        }
        return null;
    }   

    /**
     * Accede a una clave de configuración usando path tipo 'a.b.c' o array ['a','b','c'].
     */
    public function getConfig(string|array $path, mixed $default = null): mixed {
        
        $keys = is_string($path) ? explode('.', $path) : $path;

        $value = $this->config;


        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public function getTranslator()
    {
        // Si ya hemos creado el traductor en esta petición, lo devolvemos (caché).
        if ($this->translatorService !== null) {
            return $this->translatorService;
        }

        // --- LÓGICA DE DETECCIÓN DE IDIOMA MEJORADA ---

        $defaultLanguage = 'en'; // Nuestro idioma por defecto
        $finalLang = $defaultLanguage; // Asumimos el por defecto para empezar
        $cookieName = 'user_language';
        $cookieDuration = time() + (86400 * 365); // 1 año

        // 1. Prioridad Máxima: ¿El usuario está cambiando el idioma AHORA MISMO?
        if (isset($_GET['lang'])) {
            $finalLang = $_GET['lang'];
            // Guardamos esta elección explícita en la sesión y en la cookie.
            $_SESSION['lang'] = $finalLang;
            setcookie($cookieName, $finalLang, $cookieDuration, '/');
        }
        // 2. Segunda Prioridad: ¿El usuario tiene una cookie de una visita anterior?
        elseif (isset($_COOKIE[$cookieName])) {
            $finalLang = $_COOKIE[$cookieName];
            // Lo guardamos en la sesión para esta visita.
            $_SESSION['lang'] = $finalLang;
        }
        // 3. Tercera Prioridad: ¿Hay un idioma guardado en la sesión activa?
        elseif (isset($_SESSION['lang'])) {
            $finalLang = $_SESSION['lang'];
        }
        // 4. Cuarta Prioridad: ¿Podemos detectar el idioma del navegador?
        elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // Esto nos da algo como "es-ES,es;q=0.9,en;q=0.8".
            // Nos quedamos con los dos primeros caracteres.
            $finalLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            // Creamos la cookie por primera vez para este visitante.
            setcookie($cookieName, $finalLang, $cookieDuration, '/');
            $_SESSION['lang'] = $finalLang;
        }
        // Si ninguna de las anteriores se cumple, se usará el $defaultLanguage.

        // --- FIN DE LA LÓGICA DE DETECCIÓN ---
        $this->setContext('language_code',$finalLang);

        debug("translator solo se crea una vez",$finalLang,false);
        
        // Creamos el servicio de traducción con el idioma final decidido.
        $this->translatorService = $this->getService('Translator', $finalLang);

        return $this->translatorService;
    }

/**
     * Crea y devuelve una instancia de un Servicio.
     * Inyecta automáticamente la instancia de la App como primer
     * argumento del constructor del servicio.
     *
     * @param string $serviceName El nombre base del servicio.
     * @param mixed ...$args (Opcional) Argumentos ADICIONALES que el desarrollador quiera pasar.
     * @return object La instancia del servicio.
     */
    public function getService($serviceName, ...$args)
    {
        // 1. Creamos un array con la App como primer elemento.
        $constructorArgs = [$this];
        
        // 2. Fusionamos los argumentos adicionales que pasó el desarrollador.
        $constructorArgs = array_merge($constructorArgs, $args);

        // 3. Llamamos al getComponent genérico con la lista de argumentos completa.
        return $this->getComponent('service', $serviceName.'Service', $constructorArgs);
    }

    public function getHelper($helperName, ...$args)
    {
        // 1. Creamos un array con la App como primer elemento.
        $constructorArgs = [$this];
        
        // 2. Fusionamos los argumentos adicionales que pasó el desarrollador.
        $constructorArgs = array_merge($constructorArgs, $args);

        // 3. Llamamos al getComponent genérico con la lista de argumentos completa.
        return $this->getComponent('helper', $helperName.'Helper', $constructorArgs);
    }
    
    public function redirect(string $url, $statusCode = 200)
    {
        $redirectResponse = $this->getResponse('redirect', $url, $statusCode);
        $this->sendResponse($redirectResponse);
        exit();
    }

    private function sendResponse(Response $response)
    {
        // --- PASO 1: ENVIAR CABECERAS ---
        
        // Enviamos el código de estado HTTP (ej. 200, 404, 302).
        http_response_code($response->getStatusCode());

        // Enviamos todas las cabeceras definidas en el objeto de respuesta.
        foreach ($response->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        // --- PASO 2: ENVIAR CONTENIDO (basado en el tipo de respuesta) ---
        
        $content = $response->getContent();

        if ($response instanceof ViewResponse) {
            // Si la respuesta es una vista, llamamos a su método render().
            // El objeto $content es en realidad nuestro objeto View.
            $content->render($response->getUserLayer());

        } elseif ($response instanceof JsonResponse) {
            // Si es JSON, codificamos el contenido (que es un array/objeto) y lo imprimimos.
            echo json_encode($content);

        } elseif ($response instanceof FileResponse) {
            // Si es un archivo, leemos su contenido y lo enviamos directamente.
            // El $content es la ruta al archivo.
            readfile($content);

        } elseif ($response instanceof RedirectResponse) {
            // Para una redirección, no hay contenido que enviar. La cabecera
            // 'Location' que ya enviamos es todo lo que se necesita.

        } else {
            // Para una respuesta base o desconocida, simplemente imprimimos el contenido.
            echo $content;
        }
        exit();
    }

    private function prepareDebugging()
    {
        if (!defined('DEBUG_ON') || !DEBUG_ON || !defined('DEBUG_PANEL') || !DEBUG_PANEL) return;

        //IMPERSONATE DEBUGGING: if Debug Panel is active, we override with the requested layer and user level
        if(defined('FIXED_USER_LAYER') && FIXED_USER_LAYER){
            $this->setUserLayer(FIXED_USER_LAYER);
        }
        if(defined('FIXED_USER_LEVEL') && FIXED_USER_LEVEL){
            $this->setUserLevel(FIXED_USER_LEVEL);
        }        
    }

    private function debugResponse($response){
        //IMPERSONATE DEBUGGING: we render the debug panel if not a JSON request (it would mess our JSON response)
        if (!defined('DEBUG_ON') || !DEBUG_ON || !defined('DEBUG_PANEL') || !DEBUG_PANEL) return;

        if (is_object($response) && $response instanceof ViewResponse) {
            $GLOBALS['renderDebugPanel']();
        }
    }

    // --- Aquí irán los demás métodos:
    // - handleError()
}