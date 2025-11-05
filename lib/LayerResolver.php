<?php


class LayerResolver
{
    private static ?LayerResolver $instance = null;

    public string $rootPath;

    public static function getInstance(): LayerResolver 
    {
        if (self::$instance === null) {
            throw new RuntimeException("App has not been initialized");
        }
        return self::$instance;
    }
    /**
     * Store the complete configuration of the application loaded from Config.php.
     * @var array
     */


    private $modelFactory = null; //guardamos la fábrica la primera vez que se genera para cachear las conexiones con bases de datos

    private array $buildingStack = [];
    protected array $cachedComponents = [];

    //private $isApiRoute = false; // New property to remember the type of route.
    /**
     * The builder stores the configuration
     * @param array $config Application configuration.
     */
    public function __construct(?string $rootPath = null)
    {
        $this->rootPath = $rootPath ?? dirname(__DIR__);
        self::$instance = $this;     
    }

    
    public function getModel(string $modelName, array $constructorArgs=[], ?int $userLayer = null, bool $cache = false) : ORM
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
        if ($this->getUserLayer()) {            
            if ($this->modelFactory === null) {
                $this->modelFactory = $this->getComponent('factory', 'ModelFactory', [$this], $this->getUserLayer());
            }
            return $this->modelFactory->create($modelName, $connectionType, $constructorArgs, $this->getUserLayer());
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
    public function buildController(string $controllerName) {
        require_once 'lib/components/Controller.php';
        return $this->buildComponent("controller", $controllerName);        
    }

    public function buildService(string $serviceName) {
        require_once 'lib/components/Service.php';
        return $this->buildComponent('service', $serviceName);        
    }

    public function buildHelper(string $helperName) {
        require_once 'lib/components/Helper.php';
        return $this->buildComponent('helper', $helperName);        
    }
    
    public function buildComponent(string $type, string $name, ?int $userLayer = null, bool $exactLayerOnly = false)
    {        
        if (isset($this->buildingStack[$type.$name])) {
            throw new Exception(
                "Circular dependency detected for component: {$type}{$name}\n" .
                "Current build stack: " . implode(" -> ", array_keys($this->buildingStack)).
                " -> {$type}{$name}"
            );
        }
        $this->buildingStack[$type.$name] = 'building';

        $componentInfo = $this->findFiles($type, $name, $userLayer, $exactLayerOnly);

        if (!$componentInfo) {
            throw new Exception(ucfirst($type) . " no encontrado: {$name}");
        }

        // 3. Construir el nombre de la clase final.
        $className = "{$name}_{$componentInfo['suffix']}";

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        $injectDependencies = [];
        if ($constructor) {
            $dependencies = $constructor->getParameters();

            foreach ($dependencies as $dependency) {
                $dependencyClass = $dependency->getType()->getName();                

                if (isset($this->cachedComponents[$dependencyClass])) {
                    $injectDependencies[] = $this->cachedComponents[$dependencyClass];
                }
                else{
                    $component = match (true) {
                        $dependencyClass === 'LayerResolver'
                            => $this,
                        $dependencyClass === 'App' 
                            => App::getInstance(),
                        str_ends_with($dependencyClass, 'Service')
                            => $this->buildService($dependencyClass),
                        str_ends_with($dependencyClass, 'Helper') 
                            // => $this->getHelper(substr($dependencyClass, 0, -strlen('Helper'))),
                            => $this->buildHelper($dependencyClass),
                        default 
                            => $this->getModel($dependencyClass), //By convention, we assume model
                    };
                    $injectDependencies[] = $component;
                    
                    $isModel = !str_ends_with($dependencyClass, 'Service') && !str_ends_with($dependencyClass, 'Helper') && $dependencyClass !== 'App';

                    if(!$isModel) $this->cachedComponents[$dependencyClass] = $component;
                }
            }
        }
        $builtComponent = new $className(...$injectDependencies);
        $this->cachedComponents[$name] = $builtComponent;
        unset($this->buildingStack[$type.$name]);
        return $builtComponent;              
    }


     /**
     * The main "factory" method.
     * Create and return an instance of any hierarchical component.
     *
     * @param string $type The type of component ('Controller', 'Model', etc.).
     * @param string $name The base name of the component ('Auth Controller').
     * @param array $constructorArgs The builder's entry arguments will be passed in an array.
     * @param integer|null $userLayer Get a component from a specific layer  
     * @param boolean $exactLayerOnly Get only the component from the fixed layer (don't use if it needs their parents)
     * @return object The instance of the requested object.
     */
    public function getComponent(string $type, string $name, array $constructorArgs = [], ?int $userLayer = null, bool $exactLayerOnly = false) : object
    {
        // 1. Encontrar la información del archivo y la capa.
        $componentInfo = $this->findFiles($type, $name, $userLayer, $exactLayerOnly);

        if (!$componentInfo) {
            throw new Exception(ucfirst($type) . " no encontrado: {$name}");
        }

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
     * The unique "search engine". Find the route to a component file
     * touring the hierarchy defined in the configuration.
     *
     * @param string $type The type of component ('controller', 'model').
     * @param string $name The base name of the component.
     * 
     * 
     * @param boolean $loadFiles If true, classes are automatically loaded with require_once. 
     *                           If false, only paths are returned; the caller must handle loading. 
     * @return array|null An array with ['path', 'suffix'] or null if it is not found.
     */
    public function findFiles(string $type, string $name, ?int $userLayer = null, bool $exactLayerOnly = false, bool $loadFiles=true) : ?array
    {
        if ($userLayer === null) { //if null, we get the user layer from context
            //this allow us to call with specific range.
            $userLayer = $this->getUserLayer() ? $this->getUserLayer() : 1;
        }

        // Obtain the component subfolder from the configuration (ej: 'controllers').
        $componentSubdir = $this->getConfig(['component_types',$type]) ?? null;
        if (!$componentSubdir) {
            throw new Exception("Tipo de componente desconocido: {$type}");
        }
        $extension = ($type === 'view') ? '.xsl' : '.php';
        $relativePath = "{$componentSubdir}/{$name}{$extension}";

        $foundFile = null;
        $loadFiles = $loadFiles ? [] : false; //if loadFiles, we create an empty array to load them in the reverse order they are found to handle inheritance
        
        // Iterate about the hierarchy of this installation
        foreach ($this->getConfig('layers') as $layerKey => $layerInfo) {

            if ($userLayer < $layerInfo['layer']){     //if the userLayer is lower than the current layer, we wont look for a file           
                continue;
            }

            // If we want exact level, and this is not, we skip it.
            if ($exactLayerOnly && $layerInfo['layer'] != $userLayer) {
                continue; 
            }

            // Obtain the layer information from the configuration.
            $layerDir = $layerInfo['directory'];

            $filePath = "{$layerDir}/{$relativePath}";
            
            $absolutePath = $this->rootPath . '/' . $filePath;  //We check if the file exists

            if (file_exists($absolutePath)) {
                if (is_array($loadFiles)) {
                    $loadFiles[] = $absolutePath;
                }

                if ($foundFile === null){
                    // Found! We return the information of the most specific layer.
                    $foundFile = [
                        'path'   => $absolutePath,
                        'suffix' => $layerInfo['suffix'],
                        'name' => $name,
                        'level' => $layerInfo['layer']
                    ];
                }
                continue; 
            }

            //If we are at the exact level we return
            if ($exactLayerOnly && $layerInfo['layer'] == $userLayer) {
                if (is_array($loadFiles) && !empty($loadFiles)) {
                    require_once $loadFiles[0];
                }
                return $foundFile;
            }
        }
        
        // we load the files from the base to the highest layer
        if (is_array($loadFiles) && !empty($loadFiles)) {
            foreach (array_reverse($loadFiles) as $file) {
                require_once $file;
            }
        }
        return $foundFile;
    }   

    /**
     * It is a parent :: calling method () dynamic that facilitates syntax
     * Execute the same method of the object in the father class and return your answer.
     * Automatically detects the name of the method that called it and adjusts the arguments according to the father.
     * @param object $callerObject Receive the object that calls it to be able to execute the method maintaining the State
     * @return mixed The response of the Padre del Controller method, Service, Model ...
     */
    public function callParent(object $callerObject) : mixed
    {
        // 1. detect the method and class that called us
        $backtrace = debug_backtrace(0, 5); // We get the last 5 calls to ensure us to find the real caller

        // 2. We ignore the intermediate methods (if they exist) to obtain the real call method
        $callHelpers = $this->getConfig('parent_call_helpers');
        // If the amount of assistant methods could be extended, it could be put in a config

        $callerInfo = null;
        // We start in index 1, because 0 is always the current method.
        for ($i = 1; $i < count($backtrace); $i++) {
            $frame = $backtrace[$i];            
            // If the name of the current function is not on our list of assistants, then we have found the "real caller."
            if (!in_array($frame['function'], $callHelpers)) {
                $callerInfo = $frame;
                break; 
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

        // 2. get the real father and prepare the method
        $parentClass = get_parent_class($callerClassName);
        if (!method_exists($parentClass, $callerMethodName)) {
            throw new LogicException("El método {$callerMethodName} no existe en la clase padre {$parentClass}.");
        }        

        // 3. Execute the father's method in the context of the current $this
        $method = new ReflectionMethod($parentClass, $callerMethodName); // We create a reflection of the method

        $numParams = $method->getNumberOfParameters(); 
        $callerArgs = array_slice($callerArgs, 0, $numParams);

        $method->setAccessible(true); //Remove the protect for the reflection method can be launched

        $response = $method->invokeArgs($callerObject, $callerArgs); //We invoke about $this (The object of the upper layer you have called, allows us to maintain the State, and we pass the arguments)
        return $response;
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
     * Create and return an instance of a service.
     * Automatically injects the app instance as the first argument of the service builder.
     *
     * @param string $serviceName The base name of the service.
     * @param mixed ...$args (Optional) Additional arguments for specific service
     * @return object The service instance.
     */
    public function getService($serviceName, ...$args)
    {
        // 1. We create an array with the app as first element.
        $constructorArgs = [$this];
        
        // 2. We fuse the additional arguments
        $constructorArgs = array_merge($constructorArgs, $args);

        // 3. We call the GET Component generic with the list of full arguments.
        return $this->getComponent('service', $serviceName.'Service', $constructorArgs);
    }

    public function getHelper($helperName, ...$args)
    {
        // 1. We create an array with the app as first element.
        $constructorArgs = [$this];
        
        // 2. We merge the additional arguments that the developer passed.
        $constructorArgs = array_merge($constructorArgs, $args);

        return $this->getComponent('helper', $helperName.'Helper', $constructorArgs);
    }

    public function getUserLayer()
    {
        return App::getInstance()->getUserLayer();
    }

    protected function getConfig($key, $default = null)
    {
        return App::getInstance()->getConfig($key, $default);
    }
}