<?php
// Script pour ajouter les colonnes de reinitialisation a la table utilisateurs
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

try {
    // Check if columns exist first to prevent errors
    $checkSql = "SHOW COLUMNS FROM `utilisateurs` LIKE 'reset_token'";
    $stmt = $pdo->query($checkSql);
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        $sql = "
        ALTER TABLE `utilisateurs` 
        ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL AFTER `role`,
        ADD COLUMN `reset_expires` TIMESTAMP NULL DEFAULT NULL AFTER `reset_token`;
        ";
        
        $pdo->exec($sql);
        echo "<h2 style='color: green;'>La table 'utilisateurs' a ete mise a jour avec succes.</h2>";
        echo "<p>Les colonnes pour les mots de passe oublies sont pretes.</p>";
    } else {
        echo "<h2 style='color: blue;'>Les colonnes existent deja. Aucune action necessaire.</h2>";
    }
    
    echo "<a href='index.php'>Retour a l'accueil</a>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la mise a jour de la table</h2>";
    echo "Message : " . htmlspecialchars($e->getMessage());
}
?>
