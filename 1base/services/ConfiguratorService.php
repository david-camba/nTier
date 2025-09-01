<?php
require_once '1base/services/Service.php';

class ConfiguratorService_Base extends Service
{

    public function saveExtras(ConfSession_Base $confSession, $extraIds)
    {
        // Los extras vienen como un array de IDs.
        $carModelId = $confSession->id_model;  
        $compatibleExtras = $this->getModel('CarModel')->find($carModelId)->extras;
        $compatibleExtrasIds = $compatibleExtras->pluck('id_extra');
        $invalidIds = array_diff($extraIds, $compatibleExtrasIds);

        if (!empty($invalidIds)) {
            // Opcional: puedes hacer el mensaje de error más específico.
            $invalidIdList = implode(', ', $invalidIds);
            throw new Exception("Se han enviado extras no válidos o no compatibles. Los siguientes IDs de extra no son válidos para este modelo: {$invalidIdList}.", 400);
        }

        // 1. Guardamos los extras como un string separado por comas.
        $confSession->extras = implode(',', $extraIds);
        $confSession->save();
    }

    public function getSummaryData(ConfSession_Base $confSession)
    {
        // Modelo
        $carModel = $this->getModel('CarModel')->find($confSession->id_model);
        
        // Color
        $color = $this->getModel('Color')->find($confSession->id_color);        
        
        // Extras (necesitamos los objetos completos, no solo los IDs)
        $extraIds = explode(',', $confSession->extras);
        $extras = [];
        if (!empty($extraIds)) {
            $allExtras = $this->getModel('Extra')->findAll('id_extra', $extraIds); // findAll puede buscar por un array de IDs
            $extras = $allExtras->toArray();
        }
            
        // 3. Devolvemos una respuesta de éxito con todos los datos del resumen.
        return [
            'model' => $carModel ? $carModel->toArray() : null,
            'color' => $color ? $this->_translateAndFormatColor($color) : null,
            'extras' => $this->_translateAndFormatExtras($extras),
        ];
    }

    /**
     * Calcula el precio total para una sesión de configuración dada.
     *
     * @param ConfSession_Base $confSession El objeto de la sesión de configuración.
     * @return float El precio total calculado.
     */
    public function calculateTotal(ConfSession_Base $confSession)
    {
        $total = 0.0;

        // 1. Añadir el precio base del modelo.
        if ($confSession->id_model) {
            $total += $this->getModelPrice($confSession->id_model);
        }

        // 2. Añadir el sobrecoste del color.
        if ($confSession->id_color) {
            $color = $this->app->getModel('Color')->find($confSession->id_color);
            if ($color) {
                $total += (float)$color->price_increase;
            }
        }
        
        // 3. Añadir el precio de los extras.
        if ($confSession->extras) {
            $extraIds = explode(',', $confSession->extras);
            if (!empty($extraIds)) {
                $extrasCollection = $this->app->getModel('Extra')->findAll('id_extra', $extraIds);
                
                // Usamos reduce para sumar los precios de la colección.
                $totalExtras = $extrasCollection->reduce(function ($sum, $extra) {
                    return $sum + (float)$extra->price;
                }, 0);

                $total += $totalExtras;
            }
        }

        return $total;
    }

    /**
     * Calcula el precio total para una sesión de configuración dada.
     *
     * @return float El precio total calculado.
     */
    public function getModelPrice($carModelId)
    {
        $carModel = $this->getModel('CarModel')->find($carModelId);
        if ($carModel) {
            return (float)$carModel->price;
        }  
        else{
            throw new \Exception("Modelo no encontrado: " . $carModelId);
        }
    }

    // --- Helpers privados para mantener el código limpio ---
    protected function _translateAndFormatColor($color) {
        return ['name' => $this->translate($color->name), 'price' => (float) $color->price_increase];
    }

    protected function _translateAndFormatExtras($extras) {
        return array_map(function($extra) {
            return [
                'name' => $this->translate($extra['name']),
                'price' => (float) $extra['price']
            ];
        }, $extras);
    }

    /**
     * Calcula el precio total para una sesión de configuración dada.
     *
     * @param array $confSession El objeto de la sesión de configuración.
     * @return float El precio total calculado.
     */
    public function calculateTotalLegacy(array $confSession)
    {
        $total = 0.0;

        // 1. Añadir el precio base del modelo.
        if (!empty($confSession['id_model'])) {
            $total += $this->getModelPrice((int)$confSession['id_model']);
        }

        // 2. Añadir el sobrecoste del color.
        if (!empty($confSession['id_color'])) {
            $color = $this->app->getModel('Color')->find((int)$confSession['id_color']);
            if ($color) {
                $total += (float)$color->price_increase;
            }
        }

        // 3. Añadir el precio de los extras.
        if (!empty($confSession['extras'])) {
            $extraIds = array_filter(explode(',', $confSession['extras']));
            if (!empty($extraIds)) {
                $extrasCollection = $this->app->getModel('Extra')->findAll('id_extra', $extraIds);
                
                // Usamos reduce para sumar los precios de la colección.
                $totalExtras = $extrasCollection->reduce(function ($sum, $extra) {
                    return $sum + (float)$extra->price;
                }, 0);

                $total += $totalExtras;
            }
        }

        return $total;
    }

    public function getSummaryDataLegacy(array $confSession)
    {
        // Modelo
        $carModel = null;
        if (!empty($confSession['id_model'])) {
            $carModel = $this->getModel('CarModel')->find((int)$confSession['id_model']);
        }

        // Color
        $color = null;
        if (!empty($confSession['id_color'])) {
            $color = $this->getModel('Color')->find((int)$confSession['id_color']);
        }

        // Extras
        $extras = [];
        if (!empty($confSession['extras'])) {
            $extraIds = array_filter(explode(',', $confSession['extras']));
            if (!empty($extraIds)) {
                $allExtras = $this->getModel('Extra')->findAll('id_extra', $extraIds); // findAll soporta array
                $extras = $allExtras->toArray();
            }
        }

        // Respuesta con todos los datos del resumen
        return [
            'model' => $carModel ? $carModel->toArray() : null,
            'color' => $color ? $this->_translateAndFormatColor($color) : null,
            'extras' => $this->_translateAndFormatExtras($extras),
        ];
    }

}