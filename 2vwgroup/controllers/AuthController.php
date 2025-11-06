<?php
class AuthController_2GrupoVW extends AuthController_Base
{
    public function showLogin() {
        $view = $this->parentResponse();
        $view->set('login_2_header_subtitle', $this->translate('login_2_header_subtitle'));
        $view->set('login_background_image_url', $this->translate('/2vwgroup/img/BackgroundVW.jpg'));
        return $this->view($view);              
    }

    public function showLogin_Admin() {
        $view = $this->parentResponse(); //como no hay "showLogin_Admin" en la primera capa, se hará fallback a "showLogin()"
        $view->set('login_2_header_subtitle', 'ADMIN MAGIC');
        return $this->view($view); 
    }
}