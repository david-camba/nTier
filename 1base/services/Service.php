<?php
class Service
{
    /** @var App */
    protected $app;
    protected $translator;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->translator = $this->app->getTranslator();
    }

    protected function getModel($modelName, array $constructorArgs=[], $userLayer=null, $cache=false)
    {
        return $this->app->getModel($modelName, $constructorArgs, $userLayer, $cache);
    }
    
    protected function getConfig($key, $default = null)
    {
        return $this->app->getConfig($key, $default);
    }
    
    protected function getContext($key, $default = null)
    {
        return $this->app->getContext($key, $default);
    }

    public function setContext($key, $value)
    {
        return $this->app->setContext($key, $value);
    }
    /**
     * Atajo para obtener una cadena de texto traducida.
     */
    protected function translate($key, array $replacements = [])
    {
        return $this->translator->get($key, $replacements);
    }

}