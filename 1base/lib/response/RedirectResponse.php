<?php

require_once 'lib/response/Response.php';

/**
 * Representa una respuesta de redirección HTTP.
 */
class RedirectResponse extends Response
{
    /**
     * @param string $url La URL a la que se debe redirigir.
     * @param int $statusCode El código de estado (302 por defecto para redirección temporal).
     */
    public function __construct($url, $statusCode = 302)
    {
        // El contenido de una redirección está vacío.
        // La magia está en la cabecera 'Location'.
        $headers = ['Location' => $url];
        
        parent::__construct('', $statusCode, $headers);
    }
}