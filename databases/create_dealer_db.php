<?php
/**
 * Script de Consola para Crear la Base de Datos de un Concesionario.
 *
 * Crea el archivo 'db_audi_11.sqlite' y define las tablas 'clients'
 * y 'proposals'.
 *
 * Se puede ejecutar de forma segura varias veces.
 */

try {
    // 1. CONFIGURACIÓN Y CONEXIÓN
    $dbPath = __DIR__ . '/db_audi_11.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos 'db_audi_11.sqlite' exitosa.\n";

    // 2. CREACIÓN DE LA TABLA 'clients'
    echo "Creando tabla 'clients'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            client_id           INTEGER PRIMARY KEY AUTOINCREMENT,
            name                TEXT NOT NULL,
            address             TEXT,
            age                 INTEGER,
            estimated_salary    REAL,
            has_financing_access INTEGER NOT NULL DEFAULT 0, -- 0 for false, 1 for true
            created_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        );
    ");
    echo "Tabla 'clients' creada o ya existente.\n\n";

    // 3. CREACIÓN DE LA TABLA 'proposals'
    echo "Creando tabla 'proposals'...\n";
    $pdo->exec("DROP TABLE IF EXISTS proposals;"); // Para desarrollo
    $pdo->exec("
        CREATE TABLE proposals (
            id_proposal         INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id           INTEGER NOT NULL,
            id_conf_session     INTEGER NOT NULL,
            total_price         REAL NOT NULL,
            created_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (client_id) REFERENCES clients(client_id),
            FOREIGN KEY (id_conf_session) REFERENCES conf_sessions(id_conf_session)
        );
    ");
    echo "Tabla 'proposals' creada.\n\n";

        // --- ¡NUEVA TABLA 'conf_sessions'! ---
    echo "Creando tabla 'conf_sessions'...\n";
    $pdo->exec("DROP TABLE IF EXISTS conf_sessions;"); // Para desarrollo
    $pdo->exec("
        CREATE TABLE conf_sessions (
            id_conf_session     INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user             INTEGER NOT NULL,
            id_model            INTEGER,
            id_color            INTEGER,
            extras              TEXT, -- Almacenará IDs separados por comas, ej: '4,5,6'
            last_modification   TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            assigned            INTEGER NOT NULL DEFAULT 0, -- 0 para false, 1 para true
            template            INTEGER NOT NULL DEFAULT 0  -- 0 para false, 1 para true
        );
    ");
    echo "Tabla 'conf_sessions' creada.\n\n";

    // --- ¡NUEVO! AÑADIMOS UN TRIGGER PARA 'last_modification' ---
    // SQLite no tiene 'ON UPDATE CURRENT_TIMESTAMP', así que usamos un trigger.
    echo "Creando trigger para 'conf_sessions'...\n";
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS update_conf_sessions_last_modification
        AFTER UPDATE ON conf_sessions
        FOR EACH ROW
        BEGIN
            UPDATE conf_sessions
            SET last_modification = (datetime('now', 'localtime'))
            WHERE id_conf_session = OLD.id_conf_session;
        END;
    ");
    echo "Trigger creado.\n\n";

    
    echo "----------------------------------------\n";
    echo "¡Base de datos del concesionario lista!\n";

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage() . "\n");
}