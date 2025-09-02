<?php
require_once '1base/services/Service.php';

class AuthService_Base extends Service
{

    public function __construct(App $app)
    {
        $this->app = $app;

        //NO: $this->translator = $this->app->getTranslator(); 
        // We overwritten the global "Service" builder to remove the translator so that it is not initialized before setting the layers
    }

    /** 
    * The main security method. Orchestra Authentication and 
    * Access control for the current request. 
    * 
    * @param Array $routeInfo The original router action plan. 
    * @return Array The final and validated action plan. 
    */
    public function authenticateRequest(array $routeInfo): array
    {
        // 1. Intentamos obtener el usuario autenticado.
        $authenticatedUser = $this->getAuthenticatedUser();
        $authenticatedRoute = $this->setUserContextAndAuthenticateRoute($routeInfo, $authenticatedUser);

        return $authenticatedRoute;
    }

    /**
     * Set user context for the app and return the authenticated route.
     *
     * @param array $routeInfo
     * @param User|null $authenticatedUser
     * @return array
     */
    protected function setUserContextAndAuthenticateRoute(array $routeInfo, ?User $authenticatedUser): array
    {
        // 1. We keep the user (or null) in the context of the app.
        $this->setContext('user', $authenticatedUser);

        // 2. Apply the access rules.
        if ($authenticatedUser) {
            // Logged user.
            $userLayer = $authenticatedUser->fetchUserLayer();
            $this->setUserLayer($userLayer);
        
            $userLevel = $authenticatedUser->fetchUserLevel();   
            $this->setUserLevel($userLevel);
            
            $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            if ($currentPath === '/login') {
                // If you are login and go to login, we redirect it.
                $this->redirect('/app', 301);
                exit();
            }
            // If not, you have access.
            return $routeInfo;

        } else {
            // User not logged (guest).

            //$this->setUserLayer(1); // Guest level
            $this->setUserLayer(3); //for this app, we'll use the third layer for no logged users

            $this->setUserLevel(0); // Guest level

            // We check if the route is public.
            if ($this->isPublicRoute($routeInfo)) {               
                return $routeInfo;
            } else {
                // If not, we send to login
                $this->redirect('/login');
                exit();
            }
        }
    }

    /** 
    * Verify the session cookie and the database to authenticate the user. 
    * 
    * @return array | null returns an array with user data and session 
    * If the authentication is successful, or null if it is not. 
    */
    protected function getAuthenticatedUser() : ?User
    {
        // 1. Obtain the token from the cookie of the browser.
        $token = $_COOKIE['session_token'] ?? null;
        if (!$token) {
            $this->getModel('Request',[],1,false)->log("NO SESSION", null);            
            return null; // Si no hay cookie, no hay usuario.
        }
        // 2. Obtain a "search engine" for the User Session model.
        $session = $this->getModel('UserSession',[],1,false)->find($token, 'token');

        // 4. Validate the session found.
        if (!$session) {
            $this->getModel('Request',[],1,false)->log("TOKEN INVALID", $session);
            // Token is not valid or is not in the BBDD. 
            // (Here we could erase the user's invalid cookie).
            setcookie('session_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true,'samesite' => 'Lax' //'domain' => 'dominio.com', //'secure' => true,
            ]);
            unset($_COOKIE['session_token']);
            return null;
        }

        // 5. Check if the session has expired.
        $now = new DateTime();
        $expiration = new DateTime($session->expiration_date);

        if ($now > $expiration) {      
            setcookie('session_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true,'samesite' => 'Lax' //'domain' => '.tudominio.com', //'secure' => true,
            ]); 
            unset($_COOKIE['session_token']); // We delete cookie
            $this->getModel('Request',[],1,false)->log("EXPIRED SESSION", $session);
            return null;
        }

        // 6. (Optional but recommended) Check IP and User Agent. 
        // This prevents the theft of the cookie by session.
        if ($session->ip !== $_SERVER['REMOTE_ADDR'] || $session->user_agent !== $_SERVER['HTTP_USER_AGENT']) {
            // Safety alert! Someone could have stolen the cookie.
            $this->getModel('Request',[],1,false)->log("SESSION_HIJACK_ATTEMPT", $session);
            $session->delete(); // Invalidate this session in the BBDD
            return null;
        }

        // 7. Success! The session is valid. 
        // We return the crucial information for the rest of the application.
        $this->getModel('Request',[],1,false)->log("SUCCESS", $session); // We keep the request

        $user = $this->getModel('User',[],1,false)->find($session->id_user);
        $user->token = $token; // We add the token
        // We eliminate sensitive data      
        unset($user->password);
        unset($user->tries);

        return $user; // We return the user
    }
    
    
    /**
     * Check if a route is on the "white list" of public access.
     *
     * @param array $routeInfo
     * @return boolean
     */
    protected function isPublicRoute(array $routeInfo) : bool
    {
        // TO EXTEND: move to security.php 
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

    protected function setUserLayer($userLayer): void
    { 
        $this->app->setUserLayer($userLayer);
    }

    protected function setUserLevel($userLevel) : void
    {
        $this->app->setUserLevel($userLevel);
    }

    protected function redirect($url, $statusCode=200) : void
    {
        $this->app->redirect($url, $statusCode);
    }
}