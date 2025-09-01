<?php
/**
 * Clase base abstracta de la que deben heredar todos los helpers.
 * Proporciona funcionalidades y propiedades comunes.
 */
abstract class Helper
{
    protected $app;
    protected $translator;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->translator = $this->app->getTranslator();
    }

    /**
     * Atajo para obtener una cadena de texto traducida.
     */
    protected function translate($key, array $replacements = [])
    {
        return $this->translator->get($key, $replacements);
    }

    protected function getConfig($key, $default = null)
    {
        return $this->app->getConfig($key, $default);
    }
    
    protected function getContext($key, $default = null)
    {
        return $this->app->getContext($key, $default);
    }

    /**
     * Ejecuta el mismo método en la clase padre y devuelve su respuesta.
     * Detecta automáticamente el nombre del método que lo llamó y ajusta los argumentos según el padre.
     * 
     * @return mixed La respuesta del método padre 
     */
    protected function callParent() : mixed
    {
        $parentReturn = $this->app->callParent($this);
        return $parentReturn;
    }
}