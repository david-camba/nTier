<?php
/**
 * Abstract base class of which all helpers must inherit.
 * It provides common functionalities and properties.
 */
abstract class Helper
{
    protected App $app;
    protected TranslatorService $translator;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->translator = $this->app->getTranslator();
    }

    /**
     * Shortcut to obtain a translated text chain.
     *
     * @param string $key
     * @param array $replacements
     * @return void
     */
    protected function translate(string $key, array $replacements = [])
    {
        return $this->translator->get($key, $replacements);
    }

    protected function getConfig(array|string $key, mixed $default = null)
    {
        return $this->app->getConfig($key, $default);
    }
    
    protected function getContext($key, $default = null)
    {
        return $this->app->getContext($key, $default);
    }

    /**
     * Execute the same method in the father class and return your answer.
     * Automatically detects the name of the method that called it and adjusts the arguments according to the father.
     * 
     * @return mixed The response of the father method
     */
    protected function callParent() : mixed
    {
        $parentReturn = $this->app->callParent($this);
        return $parentReturn;
    }
}