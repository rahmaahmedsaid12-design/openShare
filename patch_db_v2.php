<?php
require_once __DIR__ . '/includes/db.php';
try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN photo_profil VARCHAR(255) DEFAULT NULL");
    echo "Column photo_profil added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column photo_profil already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    $sql = "CREATE TABLE IF NOT EXISTS tutoriels_sauvegardes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        youtube_id VARCHAR(50) NOT NULL,
        titre VARCHAR(255) NOT NULL,
        nom_chaine VARCHAR(255) NOT NULL,
        date_sauvegarde TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table tutoriels_sauvegardes created or already exists.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
