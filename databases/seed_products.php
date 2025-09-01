<?php
// 1. CONEXIÓN A LA BASE DE DATOS
$brand = 'audi';
$dbName = "{$brand}_prod.sqlite";
$dbPath = __DIR__ . '/' . $dbName;

if (!file_exists($dbPath)) {
    die("Error: La base de datos '{$dbName}' no existe. Ejecuta primero 'create_products_db.php'.\n");
}
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "Conexión a '{$dbName}' exitosa.\n\n";


$json_data = '[{"id_extra":13,"name":"extra.rims_21","description":"extra.rims_21_desc","price":2500.0,"models":"1,2"},{"id_extra":14,"name":"extra.rims_19","description":"extra.rims_19_desc","price":1200.0,"models":"3"},{"id_extra":15,"name":"extra.pkg_city","description":"extra.pkg_city_desc","price":1500.0,"models":"1,2,3"},{"id_extra":16,"name":"extra.pkg_tour","description":"extra.pkg_tour_desc","price":1800.0,"models":"1,2,3"},{"id_extra":17,"name":"extra.seats_sport","description":"extra.seats_sport_desc","price":3500.0,"models":"2,3"},{"id_extra":18,"name":"extra.seats_comfort","description":"extra.seats_comfort_desc","price":2200.0,"models":"1"},{"id_extra":19,"name":"extra.sunroof","description":"extra.sunroof_desc","price":1700.0,"models":"1,2,3"},{"id_extra":20,"name":"extra.headlights_matrix","description":"extra.headlights_matrix_desc","price":2800.0,"models":"1,2"},{"id_extra":21,"name":"extra.sound_bo","description":"extra.sound_bo_desc","price":1500.0,"models":"1,2,3"},{"id_extra":22,"name":"extra.headup","description":"extra.headup_desc","price":1400.0,"models":"1,2"},{"id_extra":23,"name":"extra.suspension_air","description":"extra.suspension_air_desc","price":2100.0,"models":"1,2"},{"id_extra":24,"name":"extra.steering_flat","description":"extra.steering_flat_desc","price":550.0,"models":"2,3"}]';

// Decodifica el JSON a un array asociativo de PHP.
$extras = json_decode($json_data, true);

if ($extras === null) {
    die("Error al decodificar el JSON. Comprueba que el formato es correcto.");
}

echo "Iniciando el seeder de extras y relaciones...\n";

try {
    // Es una buena práctica envolver un seeder en una transacción.
    // Si algo falla a mitad de camino, no se guardará nada.
    $pdo->beginTransaction();
    
    // Usamos tu convención de nombres para la tabla pivote.
    $stmt_pivot = $pdo->prepare(
        "INSERT INTO extra_model (id_extra, id_model) VALUES (:id_extra, :id_model)"
    );

    // Iteramos sobre cada extra del array decodificado.
    foreach ($extras as $extra) {

        // 2. Procesar y insertar las relaciones en la tabla pivote 'extra_model'.
        $extra_id = $extra['id_extra'];
        
        // Comprobamos si la clave 'models' no está vacía.
        if (!empty($extra['models'])) {
            // Dividimos la cadena '1,2,3' en un array ['1', '2', '3'].
            $model_ids = explode(',', $extra['models']);

            // Iteramos sobre cada ID de modelo.
            foreach ($model_ids as $model_id) {
                // trim() es una buena práctica por si hay espacios extra, ej: '1, 2'.
                $model_id_clean = trim($model_id);

                // Insertamos la relación en la tabla pivote.
                $stmt_pivot->execute([
                    ':id_extra' => $extra_id,
                    ':id_model' => (int)$model_id_clean // Convertimos a entero por seguridad.
                ]);
                echo "    - Relación con model_id {$model_id_clean} creada.\n";
            }
        }
    }

    // Si todo ha ido bien, confirmamos los cambios en la base de datos.
    $pdo->commit();
    echo "\n¡Seeder completado con éxito!\n";

} catch (Exception $e) {
    // Si ha habido algún error, revertimos todos los cambios.
    $pdo->rollBack();
    die("\nERROR: Ha ocurrido un problema durante el seeder. Se han revertido los cambios. \nMensaje: " . $e->getMessage() . "\n");
}

exit();

//ya hecho


// 2. DATOS DE LOS MODELOS

$modelsToSeed = [
    ['name' => 'A8', 'price' => 95000, 'emissions' => 150], // ID será 1
    ['name' => 'Q8', 'price' => 85000, 'emissions' => 180], // ID será 2
    ['name' => 'A5', 'price' => 55000, 'emissions' => 130], // ID será 3
];

$stmt = $pdo->prepare("INSERT INTO models (name, price, emissions) VALUES (:name, :price, :emissions)");
echo "Insertando modelos...\n";
foreach ($modelsToSeed as $model) {
    $stmt->execute($model);
}
echo "Modelos insertados.\n\n";

// 3. DATOS DE LOS COLORES (con claves de traducción)
$colorsToSeed = [
    // A8 (ID 1)
    // [id_model, clave_traduccion, img, precio]
    [1, 'color.black_mythos', '3audi/img/A8-Black.png', 0],
    [1, 'color.silver_floret', '3audi/img/A8-Silver.png', 800],
    [1, 'color.white_glacier', '3audi/img/A8-White.png', 800],
    // Q8 (ID 2)
    [2, 'color.black_orca', '3audi/img/Q8-Black.png', 0],
    [2, 'color.red_matador', '3audi/img/Q8-Red.png', 1200],
    // A5 (ID 3)
    [3, 'color.black_mythos', '3audi/img/A5-Black.png', 0], // Reutilizamos clave
    [3, 'color.silver_cuvee', '3audi/img/A5-Silver.png', 600],
    [3, 'color.white_ibis', '3audi/img/A5-White.png', 0],
];

$stmt = $pdo->prepare("INSERT INTO colors (id_model, name, img, price_increase) VALUES (?, ?, ?, ?)");
echo "Insertando colores...\n";
foreach ($colorsToSeed as $color) {
    $stmt->execute($color);
}
echo "Colores insertados.\n\n";

    // 4. DATOS DE LOS EXTRAS (con claves de traducción)
$extrasToSeed = [
    // [clave_nombre, clave_descripcion, precio, modelos]
    ['extra.rims_21', 'extra.rims_21_desc', 2500, '1,2'],
    ['extra.rims_19', 'extra.rims_19_desc', 1200, '3'],
    ['extra.pkg_city', 'extra.pkg_city_desc', 1500, '1,2,3'],
    ['extra.pkg_tour', 'extra.pkg_tour_desc', 1800, '1,2,3'],
    ['extra.seats_sport', 'extra.seats_sport_desc', 3500, '2,3'],
    ['extra.seats_comfort', 'extra.seats_comfort_desc', 2200, '1'],
    ['extra.sunroof', 'extra.sunroof_desc', 1700, '1,2,3'],
    ['extra.headlights_matrix', 'extra.headlights_matrix_desc', 2800, '1,2'],
    ['extra.sound_bo', 'extra.sound_bo_desc', 1500, '1,2,3'],
    ['extra.headup', 'extra.headup_desc', 1400, '1,2'],
    ['extra.suspension_air', 'extra.suspension_air_desc', 2100, '1,2'],
    ['extra.steering_flat', 'extra.steering_flat_desc', 550, '2,3'],
];

$stmt = $pdo->prepare("INSERT INTO extras (name, description, price, models) VALUES (?, ?, ?, ?)");
echo "Insertando extras...\n";
foreach ($extrasToSeed as $extra) {
    $stmt->execute($extra);
}
echo "Extras insertados.\n\n";

echo "----------------------------------------\n";
echo "¡Seeding de datos de productos completado!\n";