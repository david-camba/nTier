<?php
/**
 * Script de Consola para Insertar Usuarios de Prueba (Seeding).
 *
 * Este script añade tres usuarios predefinidos a la tabla 'users'.
 * Utiliza 'INSERT OR IGNORE' para poder ejecutarse varias veces sin
 * generar errores si los usuarios ya existen.
 *
 * Hashea las contraseñas de forma segura usando password_hash().
 *
 * Para ejecutarlo:
 * 1. Abre una terminal en la raíz del proyecto.
 * 2. Lanza el comando: php databases/seed_users.php
 */

try {
    // 1. CONEXIÓN A LA BASE DE DATOS
    // --------------------------------------------------------------------
    $dbPath = __DIR__ . '/audi_master.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos 'audi_master.sqlite' exitosa.\n\n";

    // 2. DATOS DE LOS USUARIOS
    // --------------------------------------------------------------------
    // Definimos los usuarios en un array para procesarlos en un bucle.
    $usersToSeed = [
        [
            'id_dealer' => 11,
            'username'          => 'manager',
            'email'             => 'manager@example.com', // Usamos un email más realista
            'password_plain'    => 'manager',
            'name'              => 'Manny Manager',
            'user_code'         => 'M',
            'user_level'        => 2,
            'layer_level'       => 3,
        ],
        [
            'id_dealer' => 11,
            'username'          => 'salesman',
            'email'             => 'salesman@example.com',
            'password_plain'    => 'salesman',
            'name'              => 'Selly Seller',
            'user_code'         => 'S',
            'user_level'        => 1,
            'layer_level'       => 3,
        ],
        [
            'id_dealer' => 11,
            'username'          => 'saleswoman',
            'email'             => 'saleswoman@example.com',
            'password_plain'    => 'saleswoman',
            'name'              => 'Mont Shu',
            'user_code'         => 'S',
            'user_level'        => 1,
            'user_layer'        => 3,
        ],
    ];

    // 3. PROCESO DE INSERCIÓN
    // --------------------------------------------------------------------
    // Preparamos la consulta SQL una sola vez para ser más eficientes.
    // 'INSERT OR IGNORE' es específico de SQLite. Si el 'username' o 'email'
    // ya existen (por la restricción UNIQUE), simplemente no inserta la fila.
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO users 
            (id_dealer, username, email, password, name, user_code, user_level, user_layer) 
        VALUES 
            (:id_dealer, :username, :email, :password, :name, :user_code, :user_level, :user_layer)
    ");

    echo "Iniciando inserción de usuarios...\n";

    foreach ($usersToSeed as $user) {
        // Hasheamos la contraseña de forma segura
        $hashedPassword = password_hash($user['password_plain'], PASSWORD_DEFAULT);

        // Vinculamos los parámetros
        $stmt->bindValue(':id_dealer', $user['id_dealer'], PDO::PARAM_INT);
        $stmt->bindValue(':username', $user['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $user['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':name', $user['name'], PDO::PARAM_STR);
        $stmt->bindValue(':user_code', $user['user_code'], PDO::PARAM_STR);
        $stmt->bindValue(':user_level', $user['user_level'], PDO::PARAM_INT);
        $stmt->bindValue(':user_layer', $user['user_layer'], PDO::PARAM_INT);

        /* OPCION SIN BINDS
        // ¡Y aquí es donde metes las variables!
        $stmt->execute([
            ':username'         => $usernameDelUsuario,
            ':email'            => $emailDelUsuario,
            ':id_dealer' => $idConcesionario
        ]);
        */
        
        // Ejecutamos la inserción
        $stmt->execute();
        
        echo " - Intentando insertar usuario: {$user['username']}... ";
        if ($stmt->rowCount() > 0) {
            echo "¡Insertado!\n";
        } else {
            echo "Ya existe, omitido.\n";
        }
    }

    echo "\n----------------------------------------\n";
    echo "¡Proceso de seeding completado!\n";

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage() . "\n");
}