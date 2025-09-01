<?php

require_once '1base/models/ORM.php';

/**
 * Representa la tabla 'colors' del catálogo de productos.
 */
class Color_Base extends ORM
{
    /** @var string El nombre de la tabla. */
    protected $tableName = 'colors';
    
    /** @var string El nombre de la clave primaria. */
    protected $primaryKey = 'id_color';

    /** @var array Lista blanca de columnas para búsquedas. */
    protected $fillable_columns = ['id_model','id_color']; 
    // Permitimos buscar colores por id_model
}