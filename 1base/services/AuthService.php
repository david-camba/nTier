<?php
require_once '1base/services/Service.php';

class AuthService_Base extends Service
{

    public function __construct(App $app)
    {
        $this->app = $app;
        //$this->translator = $this->app->getTranslator(); 
        //sobreescribimos el constructor global de "service" para quitar el translator para que no se inicialice antes de setear las capas
    }

    /**
     * El método principal de seguridad. Orquesta la autenticación y el
     * control de acceso para la petición actual.
     *
     * @param array $routeInfo El plan de acción original del Router.
     * @return array El plan de acción final y validado.
     */
    public function authenticateRequest(array $routeInfo)
    {
        // 1. Intentamos obtener el usuario autenticado.
        $authenticatedUser = $this->getAuthenticatedUser();
        $authenticatedRoute = $this->setAppContext($routeInfo, $authenticatedUser);

        return $authenticatedRoute;
    }

    protected function setAppContext($routeInfo, $authenticatedUser){
    // 2. Guardamos el usuario (o null) en el contexto de la App.
        $this->setContext('user', $authenticatedUser);

        // 3. Aplicamos las reglas de acceso.
        if ($authenticatedUser) {
            // Usuario logueado.
            $userLayer = $authenticatedUser->fetchUserLayer();
            $this->setUserLayer($userLayer);
        
            $userLevel = $authenticatedUser->fetchUserLevel();   
            $this->setUserLevel($userLevel);
            
            $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            if ($currentPath === '/login') {
                // Si está logueado y va al login, lo redirigimos.
                $this->redirect('/app', 301);
                exit();
            }
            // Si no, tiene acceso.
            return $routeInfo;

        } else {
            // Usuario no logueado (invitado).
            //$this->setUserLayer(1); // Nivel de invitado
            $this->setUserLayer(3); //for this app, we'll use the third layer for no logged users
            $this->setUserLevel(0); //Nivel de invitado

            // Comprobamos si la ruta es pública.
            if ($this->isPublicRoute($routeInfo)) {               
                return $routeInfo;
            } else {
                //Si no, le mandamos al login
                $this->redirect('/login');
                exit();
            }
        }
    }

    /**
     * Verifica la cookie de sesión y la base de datos para autenticar al usuario.
     *
     * @return array|null Devuelve un array con los datos del usuario y la sesión
     *                    si la autenticación es exitosa, o null si no lo es.
     */
    protected function getAuthenticatedUser()
    {
        // 1. Obtener el token de la cookie del navegador.
        $token = $_COOKIE['session_token'] ?? null;
        if (!$token) {
            $this->getModel('Request',[],1,false)->log("NO SESSION", null);            
            return null; // Si no hay cookie, no hay usuario.
        }
        // 2. Obtener un "buscador" para el modelo UserSession.
        $session = $this->getModel('UserSession',[],1,false)->find($token, 'token');

        // 4. Validar la sesión encontrada.
        if (!$session) {
            $this->getModel('Request',[],1,false)->log("TOKEN INVALID", $session);
            // El token no es válido o no está en la BBDD.
            // (Aquí podríamos borrar la cookie inválida del usuario).
            setcookie('session_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true,'samesite' => 'Lax' //'domain' => '.tudominio.com', //'secure' => true,
            ]);
            unset($_COOKIE['session_token']);
            return null;
        }

        // 5. Comprobar si la sesión ha expirado.
        $now = new DateTime();
        $expiration = new DateTime($session->expiration_date);

        if ($now > $expiration) {      
            setcookie('session_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true,'samesite' => 'Lax' //'domain' => '.tudominio.com', //'secure' => true,
            ]); //borramos cookie
            unset($_COOKIE['session_token']);
            $this->getModel('Request',[],1,false)->log("EXPIRED SESSION", $session);
            return null;
        }

        // 6. (Opcional pero recomendado) Comprobar IP y User Agent.
        // Esto previene el robo de la cookie de sesión.
        if ($session->ip !== $_SERVER['REMOTE_ADDR'] || $session->user_agent !== $_SERVER['HTTP_USER_AGENT']) {
            // ¡Alerta de seguridad! Alguien podría haber robado la cookie.   
            $this->getModel('Request',[],1,false)->log("SESSION_HIJACK_ATTEMPT", $session);
            $session->delete(); // invalidar esta sesión en la BBDD
            return null;
        }

        // 7. ¡Éxito! La sesión es válida.
        // Devolvemos la información crucial para el resto de la aplicación.
        $this->getModel('Request',[],1,false)->log("SUCCESS", $session); //guardamos la requests

        $user = $this->getModel('User',[],1,false)->find($session->id_user);
        $user->token = $token; //le añadimos el token
        //eliminamos datos sensibles        
        unset($user->password);
        unset($user->tries);

        return $user; //devolvemos el usuario
    }
    
    /**
     * Comprueba si una ruta está en la "lista blanca" de acceso público.
     */
    protected function isPublicRoute(array $routeInfo)
    {
        // ANOTACIÓN: Mover a config/security.php en un futuro.
        if ($routeInfo['type'] === 'legacy_script') {
            $publicRoutes = ['legacy/bienvenida.php'];
            return in_array($routeInfo['script_path'], $publicRoutes);
        }
        
        if ($routeInfo['type'] === 'mvc_action') {
            $publicRoutes = ['AuthController:showLogin', 'AuthController:doLoginAPI'];
            $currentRoute = "{$routeInfo['controller']}:{$routeInfo['action']}";
            return in_array($currentRoute, $publicRoutes);
        }

        return false;
    }

    protected function setUserLayer($userLayer){
        $this->app->setUserLayer($userLayer);
    }

    protected function setUserLevel($userLevel){
        $this->app->setUserLevel($userLevel);
    }

    protected function redirect($url, $statusCode=200){
        $this->app->redirect($url, $statusCode);
    }
}