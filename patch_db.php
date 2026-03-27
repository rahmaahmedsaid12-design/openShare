<?php
require_once __DIR__ . '/includes/db.php';
try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN statut ENUM('actif', 'bloque') DEFAULT 'actif'");
    echo "Column statut added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column statut already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
