<?php
/** 
* App class 
* 
* The core of the application: Service Locator 
* Act to create components (controllers, models, etc.) and as the orchestrador 
* Main that manages the life cycle of an HTTP request. 
*/
class App
{
    private static ?App $instance = null;

    public string $rootPath;

    private Router $router;

    public static function getInstance(): App {
        if (self::$instance === null) {
            throw new RuntimeException("App has not been initialized");
        }
        return self::$instance;
    }
    /**
     * Store the complete configuration of the application loaded from Config.php.
     * @var array
     */
    private array $config;

    /**
     * Store the user context. It's set by the AuthService
     * @var array
     */
    private array $context;

    private $userLayer = null; //control access to the 3 layers (vertical layers)
    private $userLevel = null; //control access to user role capabilities (horizontal layers)

    private LayerResolver $layerResolver;

    //private $isApiRoute = false; // New property to remember the type of route.
    /**
     * The builder stores the configuration
     * @param array $config Application configuration.
     */
    public function __construct(array $config, Router $router, LayerResolver $layerResolver, ?string $rootPath = null)
    {
        $this->config = $config;
        $this->router = $router;
        $this->layerResolver = $layerResolver;
        $this->rootPath = $rootPath ?? dirname(__DIR__);
        self::$instance = $this;     
        
        //TO-DO (DONE): No Class should receive App - 

        //TO-DO (DONE): New Class: LOADER - Refactor getComponent, buildComponent, findFiles to new class Loader. Then, inyect on App, and from there to Translator, View. This Loader could be used as support class for testing, minimizing/eliminating the need of "require/use" also in that envioroment.   
        
        //TO-DO: AuthService shouldn't set User, Layer and Level, it should return the instruccion to App to handle
    }

    /** 
    * The main method that executes the application. 
    * Orchestra the routing, session security and execution 
    * of the corresponding controller or script. 
    */
    public function run()
    {
        try {
            $this->prepareDebugging();
            // 1. Obtain the router's action plan.
            $requestedRouteInfo = $this->router->getRouteInfo();   

            // 2. Apply the session security logic.
            require_once 'lib/components/Component.php';
            $finalRouteInfo = $this->layerResolver->buildService('AuthService')->authenticateRequest($requestedRouteInfo);

            //detect if is JSON request
            /*if (!empty($requestedRouteInfo['api_route'])) {                
                $this->isApiRoute = true;
            }*/
            
            // 3. Decide what to do based on the type of route.
            switch ($finalRouteInfo['type']) {
                case 'mvc_action':
                    // If it is a modern route, we execute the MVC flow.

                    // 1. We obtain the Responsible object prepared by the Dispatcher.
                    $response = $this->dispatchAction($finalRouteInfo);

                    // 2. Here we could apply Middlewares to the answer
                    $this->applyMiddleware($response);

                    $this->sendResponse($response);
                    break;

                case 'legacy_script':
                    // If it is a legacy route, we execute the script.
                    $scriptPath = $this->rootPath.'/'.$finalRouteInfo['script_path'];
                    
                    // We verify that the file exists in that fixed location.
                    if (file_exists($scriptPath)) {
                        // 3. We execute it.
                        require_once $scriptPath;
                        exit();
                    } else {
                        // If the script does not exist, it is a 404 error.
                        throw new Exception("Script legacy no encontrado: {$finalRouteInfo['script_name']}", 404);
                    }
                    break;
                default:
                    // If the Router returns an unknown, it is an internal error.
                    throw new Exception("Tipo de ruta desconocido: '{$finalRouteInfo['type']}'", 500);   
            }
        } catch (Throwable $e) {
            // Captures any error or exception that occurs during execution
            // And it passes it to our central error handler.
            $this->logError($e);
            throw $e;
        }
    }

    public function setUserLayer($userLayer)
    {
        //Only set the layer once
        if ($this->userLayer !== null) {
            return;
        }
        $this->userLayer = (int) $userLayer;
    }

    public function getLayerResolver()
    {
        return $this->layerResolver;
    }

    public function getUserLayer()
    {
        return $this->userLayer;
    }

    public function setUserLevel($userLevel)
    {
        //Only set the level once
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
    * The new intelligent "dispatcher". 
    * Find and execute the most specific controller/action implementation 
    * Based on the vertical layer and the horizontal role of the user. 
    */
    private function dispatchAction(array $routeInfo) : Response
    {
    $controllerName = $routeInfo['controller'];
    $actionName = $routeInfo['action'];
    $params = $routeInfo['params'];
    $userLevel = $this->getUserLevel();
    
    // 1. Determine the user's role suffix (if you have it).
    $roleSuffix = ($userLevel != 0 && ($role = $this->getConfig(['user_roles', $userLevel])) !== null)
        ? $role
        : '';

    // 2. I try to: look for a specialized controller for the role
    // This is useful to create specific functionalities for roles, like "Emissions_Manager.php".
    // Only managers will be able to access this specific controller.
    $specializedControllerName = "{$controllerName}_{$roleSuffix}";
    try {
        // We try to obtain the controller with the suffix of the role.
        $controller = $this->layerResolver->buildController($specializedControllerName);        
        // If we succeed, we use it. We do not need to check the method.
        return $controller->{$actionName}(...$params);
    } catch (Exception $e) {
        // No problem. It means that the specialized controller does not exist.
    }

    // 3. I try B: Use the normal controller, but look for the specialized method of the user's role.  
    $controller = $this->layerResolver->buildController($controllerName);
    $specializedActionName = "{$actionName}_{$roleSuffix}";

    
    if ($roleSuffix && method_exists($controller, $specializedActionName)) {
        // We find a specialized method! We execute it.
        $response = $controller->{$specializedActionName}(...$params);
        return $response;
    }
    // If the controller has the property fallbackRole = true, I'm looking for a fallback of the levels below

    // 4. I try C: Use the normal controller, but look for a specialized method of roles of users with lower privileges   
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


    // 5. Fallback: Use the normal controller and method.
    if (method_exists($controller, $actionName)) {
        return $controller->{$actionName}(...$params);        
    }
    
    // 6. If none of the above works, it is a 404.
    throw new Exception("Acción no encontrada para la ruta: {$controllerName}->{$actionName}", 404);
    }

    /**
     * Create and return an response object, loading the file of your class only when necessary.
     *
     * @param string $type The type of response (ej. 'json', 'view').
     * @param mixed ...$args The arguments for the response builder
     * @return Response
     */
    public function getResponse(string $type, mixed ...$args) : Response
    {
        // 1. We build the name of the class and the route to the file.
        require_once "lib/response/Response.php";
        $className = ucfirst($type) . 'Response'; // Assuming the convention with suffix
        $filePath = "lib/response/{$className}.php";  // We use class name as file name

        // 2. We load the file
        require_once $filePath;

        // 3. We verify that the load was successful and the class exists.
        if (!class_exists($className)) {
            // This error would only happen if the file does not exist or has an incorrect class name.
            throw new Exception("No se pudo cargar la clase de respuesta: {$className}");
        }
        
        // 4. We instant the class.
        return new $className(...$args);
    }

    /**
     * Access a configuration key using type path type 'a.b.c' or array ['a','b','c'].
     *
     * @param string|array $path
     * @param mixed $default
     * @return mixed
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

    public function redirect(string $url, $statusCode = 200) : never
    {
        $redirectResponse = $this->getResponse('redirect', $url, $statusCode);
        $this->sendResponse($redirectResponse);
        exit();
    }

    protected function sendResponse(Response $response)
    {
        // --- Step 1: Send headers ---
        
        // We send the HTTP status code (ej. 200, 404, 302).
        http_response_code($response->getStatusCode());

        // We send all the headwaters defined in the response object
        foreach ($response->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        // --- PASO 2: ENVIAR CONTENIDO (basado en el tipo de respuesta) ---
        
        $content = $response->getContent();

        if ($response instanceof ViewResponse) {
            // If the answer is a view, we call your render method ().
            $viewRendered = $content->render($response->getUserLayer());
            echo $viewRendered;
        } elseif ($response instanceof JsonResponse) {
            // If it is JSON, we encode the content (which is an array/object) and printed it.
            echo json_encode($content);

        } elseif ($response instanceof FileResponse) {
            // If it is a file, we read its content and send it directly.
            // $content It is the route to the file.
            readfile($content);

        } elseif ($response instanceof RedirectResponse) {
            // For a redirection, there is no content to send. The header
            // 'Location' That we already send is all that is needed.

        } else {
            // For a base or unknown response, we simply print the content.
            echo $content;
        }
        exit();
    }

    /**
     * Set the layers for the request if they they were set by the debugging panel of "debug.php" 
     *
     * @return void
     */
    private function prepareDebugging() : void
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

    private function applyMiddleware($response){
        //IMPERSONATE DEBUGGING: we render the debug panel if not a JSON request (it would mess our JSON response)
        if (!defined('DEBUG_ON') || !DEBUG_ON || !defined('DEBUG_PANEL') || !DEBUG_PANEL) return;

        if (is_object($response) && $response instanceof ViewResponse) {
            $debugPanelHTML = $GLOBALS['renderDebugPanel'](); 

            $view = $response->getContent();
            $view->add('injected_blocks', $debugPanelHTML); //el contenido de "injected_block" se usará como código directamente injectado en el HTML

            //Ejemplo DEMO de como usar la funcionalidad "ServiceLocator" de "LayerResolver" en cualquier parte.
            App::getInstance()->getLayerResolver()->buildHelper('BannerHelper')->addBanner($view);  
            //Nota: por supuesto, aquí podríamos hacer simplemente
            // $this->layerResolver->build...        
            // DECISION: se ha decidido crear "getLayerResolver()" para dar la posibilidad de instanciar como "Service Locator" en caso de necesidad en cualquier parte, pero no se debería usar como parte del flujo normal.            
        }
    }

    protected function logError(Throwable $e)
    {
        if (empty($this->config['error_log_path'])) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $argLimit = $this->config['log_arg_length_limit'] ?? 512; // Un límite por defecto si no está en config

        // Construcción del Stack Trace
        $traceLines = [];
        $trace = $e->getTrace();
        
        foreach ($trace as $i => $frame) {
            $traceLines[] = sprintf(
                "#%d %s(%d): %s",
                $i,
                $frame['file'] ?? '[internal function]',
                $frame['line'] ?? '?',
                $this->formatTraceFunctionCall($frame, $argLimit) // Pasamos el límite
            );
        }
        // Añadimos la línea final {main}
        $traceLines[] = '#' . (count($trace)) . ' {main}';
        
        $customTraceString = implode("\n", $traceLines);

        // Mensaje de log final
        $logMessage = sprintf(
            "[%s]\n%s: \"%s\" in %s:%d\n\nStack trace:\n%s\n",
            $timestamp,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $customTraceString
        );
        
        $logEntry = $logMessage . str_repeat('-', 80) . "\n\n"; // Doble salto de línea para más espacio

        @file_put_contents($this->rootPath . $this->config['error_log_path'], $logEntry, FILE_APPEND);
    }

    /**
     * Formatea una llamada a función/método desde un frame del stack trace.
     */
    private function formatTraceFunctionCall(array $frame, int $argLimit): string
    {
        $call = '';
        if (isset($frame['class'])) $call .= $frame['class'];
        if (isset($frame['type'])) $call .= $frame['type'];
        if (isset($frame['function'])) $call .= $frame['function'];
        
        $call .= '(' . $this->formatArgs($frame['args'] ?? [], $argLimit) . ')';

        return $call;
    }

    /**
     * Formatea los argumentos de una función para el log.
     */
    private function formatArgs(array $args, int $argLimit): string
    {
        if (empty($args)) {
            return '';
        }

        $output = [];
        foreach ($args as $arg) {
            // Usamos JSON_PRETTY_PRINT para una legibilidad espectacular
            $argString = json_encode($arg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // Si el argumento es un objeto complejo o un array, puede tener saltos de línea.
            // Lo indentamos para que quede bien en el log.
            if (strpos($argString, "\n") !== false) {
                $argString = str_replace("\n", "\n    ", $argString); // Indenta cada línea
            }

            // Aplicamos el límite de longitud configurable (solo si es mayor que 0)
            if ($argLimit > 0 && strlen($argString) > $argLimit) {
                $argString = substr($argString, 0, $argLimit) . '... (truncated)';
            }
            $output[] = $argString;
        }

        $formattedArgs = implode(', ', $output);

        // Si los argumentos son multilínea, los formateamos de forma especial
        if (strpos($formattedArgs, "\n") !== false) {
            return "\n    " . $formattedArgs . "\n";
        }

        return $formattedArgs;
    }
}