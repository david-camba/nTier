<?php
require_once '1base/services/Service.php';

class EmissionsService_Base extends Service
{
    public function getEmissionsData()
    {
        $allModels = $this->getModel('CarModel')->all();

        // 2. PREPARAR LOS DATOS PARA LA RESPUESTA
        // Mapeamos la colección de objetos a un array simple para la API.
        $reportData = $allModels->map(function ($model) {
            return [
                'name' => $model->name,
                'price' => number_format($model->price, 2, ',', '.') . ' €',
                'emissions' => $model->emissions . ' g/km',
            ];
        })->toArray(); // Suponiendo que Collection::toArray() está corregido para manejar arrays

        return $reportData;
    }
}