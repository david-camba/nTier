<?php
/**
 * Script de Consola para Poblar las Tablas de un Concesionario.
 *
 * Inserta datos de ejemplo en las tablas 'clients' y 'proposals'.
 * Usa 'INSERT OR IGNORE' para evitar duplicados.
 */

try {
    // 1. CONEXIÓN A LA BASE DE DATOS
    $dbPath = __DIR__ . '/audi_11.sqlite';
    if (!file_exists($dbPath)) {
        die("Error: La base de datos 'audi_11.sqlite' no existe. Ejecuta primero 'create_dealer_db.php'.\n");
    }
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos 'audi_11.sqlite' exitosa.\n\n";

    // 2. DATOS DE LOS CLIENTES
    $clientsToSeed = [
        ['Javier Bardem', 'Calle Ficción 123, Madrid', 54, 120000.50, 1],
        ['Penélope Cruz', 'Avenida Gran Vía 45, Madrid', 49, 150000.00, 1],
        ['Antonio Banderas', 'Plaza del Sol 1, Málaga', 63, 250000.75, 1],
        ['Úrsula Corberó', 'Paseo de Gracia 92, Barcelona', 34, 85000.00, 0],
        ['Mario Casas', 'Rambla de Cataluña 18, Barcelona', 37, 75000.20, 1],
    ];

    // 3. INSERCIÓN DE CLIENTES
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO clients (name, address, age, estimated_salary, has_financing_access) 
        VALUES (?, ?, ?, ?, ?)
    ");

    echo "Iniciando inserción de clientes...\n";
    foreach ($clientsToSeed as $client) {
        $stmt->execute($client);
        echo " - Insertando cliente: {$client[0]}\n";
    }
    echo "Inserción de clientes completada.\n\n";


    // 4. DATOS DE LAS PROPUESTAS (vinculadas a los clientes)
    $proposalsToSeed = [
        [1, 1103, 85000.00], // Propuesta para Javier Bardem
        [1, 1111, 120000.50], // Otra propuesta para Javier Bardem
        [3, 1147, 95000.75], // Propuesta para Antonio Banderas
        [5, 8096, 65000.00], // Propuesta para Mario Casas
    ];

    // 5. INSERCIÓN DE PROPUESTAS
    $stmt = $pdo->prepare("
        INSERT INTO proposals (client_id, id_conf_session, total_price) VALUES (?, ?, ?)
    ");
    
    echo "Iniciando inserción de propuestas...\n";
    foreach ($proposalsToSeed as $proposal) {
        $stmt->execute($proposal);
    }
    echo "Inserción de propuestas completada.\n\n";

    echo "----------------------------------------\n";
    echo "¡Seeding de datos del concesionario completado!\n";

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage() . "\n");
}