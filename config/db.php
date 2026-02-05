<?php
/**
 * FAC-IL-CR
 * Configuración de conexión a base de datos (XAMPP / MySQL)
 */

$host = 'localhost';
$db   = 'fac_il_cr';   // Nombre exacto de la base de datos
$user = 'root';        // Usuario por defecto en XAMPP
$pass = '';            // Contraseña vacía en XAMPP
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
