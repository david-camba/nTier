<?php
/**
 * Script de Consola para Crear la Base de Datos de Productos.
 *
 * Crea el archivo 'audi_prod.sqlite' (o el que corresponda a la marca)
 * y define las tablas de catálogo: 'models', 'colors', 'extras'.
 */


// 1. CONFIGURACIÓN Y CONEXIÓN
// Para este ejemplo, crearemos la BBDD de Audi.
$brand = 'audi';
$dbName = "{$brand}_prod.sqlite";
$dbPath = __DIR__ . '/' . $dbName;

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON;');

echo "Conexión a la base de datos '{$dbName}' exitosa.\n";









exit();

//TABLAS YA CREADAS


// CREACIÓN DE LA TABLA PIVOTE 'extra_model'
echo "Creando tabla pivote 'extra_model'...\n";
$pdo->exec("
    CREATE TABLE extra_model (
        id_extra INTEGER NOT NULL,
        id_model INTEGER NOT NULL,
        -- Define una clave primaria compuesta para asegurar que cada par extra-modelo sea único.
        PRIMARY KEY (id_extra, id_model),
        
        -- Define la clave foránea que apunta a la tabla de extras.
        FOREIGN KEY (id_extra) REFERENCES extras(id_extra)
            -- ON DELETE CASCADE: Si se borra un extra, se borran sus relaciones aquí.
            ON DELETE CASCADE,
            
        -- Define la clave foránea que apunta a la tabla de modelos.
        FOREIGN KEY (id_model) REFERENCES models(id_model)
            -- ON DELETE CASCADE: Si se borra un modelo, se borran sus relaciones aquí.
            ON DELETE CASCADE
    );
");
echo "Tabla 'extra_model' creada.\n\n";

// 2. CREACIÓN DE LA TABLA 'models'
echo "Creando tabla 'models'...\n";
//$pdo->exec("DROP TABLE IF EXISTS models;"); // Para desarrollo
$pdo->exec("
    CREATE TABLE models (
        id_model    INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT NOT NULL,
        price       REAL NOT NULL,
        emissions   INTEGER -- Emisiones en g/km, para el cálculo de impuestos
    );
");
echo "Tabla 'models' creada.\n\n";

// 3. CREACIÓN DE LA TABLA 'colors'
echo "Creando tabla 'colors'...\n";
//$pdo->exec("DROP TABLE IF EXISTS colors;"); // Para desarrollo
$pdo->exec("
    CREATE TABLE colors (
        id_color    INTEGER PRIMARY KEY AUTOINCREMENT,
        id_model    INTEGER NOT NULL,
        name        TEXT NOT NULL,
        img         TEXT, -- Ruta a la imagen del coche con este color
        price_increase REAL NOT NULL DEFAULT 0, -- Incremento de precio por el color
        FOREIGN KEY (id_model) REFERENCES models(id_model)
    );
");
echo "Tabla 'colors' creada.\n\n";

// 4. CREACIÓN DE LA TABLA 'extras'
echo "Creando tabla 'extras'...\n";
//$pdo->exec("DROP TABLE IF EXISTS extras;"); // Para desarrollo
$pdo->exec("
    CREATE TABLE extras (
        id_extra    INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT NOT NULL,
        description TEXT,
        price       REAL NOT NULL,
        models      TEXT -- IDs de los modelos compatibles, separados por comas. ej: '1,3,5'
    );
");
echo "Tabla 'extras' creada.\n\n";

echo "----------------------------------------\n";
echo "¡Base de datos de productos lista!\n";

