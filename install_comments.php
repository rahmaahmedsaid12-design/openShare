<?php
// Script pour creer la table commentaires
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `commentaires` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `ressource_id` int(11) NOT NULL,
      `contenu` text NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`ressource_id`) REFERENCES `ressources`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>La table 'commentaires' a ete creee avec succes.</h2>";
    echo "<p>Le systeme de commentaires devrait maintenant fonctionner.</p>";
    echo "<a href='index.php'>Retour a l'accueil</a>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la creation de la table</h2>";
    echo "Message : " . htmlspecialchars($e->getMessage());
}
?>
