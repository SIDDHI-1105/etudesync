<?php
// includes/db.php
// Simple PDO MySQL connection used by the app

$DB_HOST = '127.0.0.1';
$DB_NAME = 'etudesync';
$DB_USER = 'root';
$DB_PASS = ''; // change if you have a root password

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Friendly error — in production hide the error text
    die("Database connection failed: " . $e->getMessage());
}
