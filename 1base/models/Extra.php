<?php
require_once '1base/models/ORM.php';

/**
 * Representa la tabla 'extras' del catálogo de productos.
 */
class Extra_Base extends ORM
{
    /** @var string El nombre de la tabla. */
    protected $tableName = 'extras';
    
    /** @var string El nombre de la clave primaria. */
    protected $primaryKey = 'id_extra';

    /** @var array Lista blanca de columnas para búsquedas. */
    protected $fillable_columns = ['id_extra','name']; // Permitimos buscar extras por nombre
}