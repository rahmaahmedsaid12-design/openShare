<?php
$host = 'localhost';
$dbname = 'openshare_db'; // Replace with your actual database name if different
$username = 'root'; // Default WAMP username
$password = ''; // Default WAMP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion a la base de donnees: " . $e->getMessage());
}
?>
