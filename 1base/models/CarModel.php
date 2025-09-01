<?php
// EN: 3audi/models/Model.php

require_once '1base/models/ORM.php';

/**
 * Representa la tabla 'models' del catálogo de productos.
 * Este modelo es específico de la capa Audi.
 */
class CarModel_Base extends ORM
{
    /** @var string El nombre de la tabla en la base de datos. */
    protected $tableName = 'models';
    
    /** @var string El nombre de la clave primaria. */
    protected $primaryKey = 'id_model';

    /** @var array Lista blanca de columnas para búsquedas. */
    protected $fillable_columns = ['id_model','name']; // Permitimos buscar modelos por nombre

    protected function colors(){
        return $this->hasMany("Color");
    }

    protected function extras(){
        return $this->belongsToMany("Extra","extra_model");
    }
}