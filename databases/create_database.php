<?php
/**
 * Script de Consola para Crear y Configurar la Base de Datos Principal.
 *
 * Este script crea el archivo de base de datos SQLite 'audi_master.sqlite'
 * y define la estructura de las tablas necesarias para la aplicación.
 *
 * Se puede ejecutar de forma segura varias veces, ya que utiliza
 * 'CREATE TABLE IF NOT EXISTS'.
 *
 * Para ejecutarlo:
 * 1. Abre una terminal en la raíz del proyecto.
 * 2. Lanza el comando: php databases/create_database.php
 */

// Usamos un bloque try-catch para manejar cualquier posible error de base de datos.
try {
    // 1. CONFIGURACIÓN Y CONEXIÓN
    // --------------------------------------------------------------------
    // La ruta al archivo de la base de datos. __DIR__ asegura que la ruta
    // siempre es correcta, sin importar desde dónde se ejecute el script.
    $dbPath = __DIR__ . '/audi_master.sqlite';

    // Creamos o nos conectamos a la base de datos SQLite usando PDO.
    $pdo = new PDO('sqlite:' . $dbPath);

    // Configuramos PDO para que lance excepciones en caso de error. Esencial.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos 'audi_master.sqlite' exitosa.\n";


    // 2. CREACIÓN DE LA TABLA 'users'
    // --------------------------------------------------------------------
    echo "Creando tabla 'users'...\n";
    $pdo->exec("DROP TABLE IF EXISTS users;");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id_user         INTEGER PRIMARY KEY AUTOINCREMENT,
            id_dealer INTEGER NOT NULL,
            username        TEXT NOT NULL UNIQUE,
            email           TEXT NOT NULL UNIQUE,
            password        TEXT NOT NULL,
            name            TEXT,
            user_layer      INTEGER,
            user_level      INTEGER,  
            user_code       TEXT,          
            tries           INTEGER NOT NULL DEFAULT 0
        );
    ");
    echo "Tabla 'users' creada o ya existente.\n\n";


    // 3. CREACIÓN DE LA TABLA 'users_sess'
    // --------------------------------------------------------------------
    echo "Creando tabla 'users_sess'...\n";
    $pdo->exec("DROP TABLE IF EXISTS users_sess;");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users_sess (
            id_session      INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user         INTEGER NOT NULL,
            id_dealer       INTEGER NOT NULL,
            token           TEXT NOT NULL UNIQUE,
            ip              TEXT NOT NULL,
            user_agent      TEXT NOT NULL,
            expiration_date TEXT NOT NULL, -- Formato: 'YYYY-MM-DD HH:MM:SS'
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "Tabla 'users_sess' creada o ya existente.\n\n";

    //$pdo->exec("ALTER TABLE users_sess ADD COLUMN created_at TEXT DEFAULT (datetime('now'))");



    // 4. CREACIÓN DE LA TABLA 'requests'
    // --------------------------------------------------------------------
    echo "Creando tabla 'requests'...\n";
    //$pdo->exec("DROP TABLE IF EXISTS requests;");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id_request      INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user         INTEGER, -- Puede ser NULL para peticiones de invitados
            id_dealer       INTEGER, -- Puede ser NULL para peticiones de invitados
            token           TEXT,
            ip              TEXT NOT NULL,
            user_agent      TEXT,
            request_method  TEXT NOT NULL,
            request_uri     TEXT NOT NULL,
            request_time    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            code            TEXT NOT NULL
        );
    ");
    echo "Tabla 'requests' creada o ya existente.\n\n";

    echo "----------------------------------------\n";
    echo "¡Proceso completado! La base de datos está lista.\n";

} catch (PDOException $e) {
    // Si algo falla, detenemos el script y mostramos un error claro.
    die("Error de base de datos: " . $e->getMessage() . "\n");
}