<?php
// Script pour creer la table signalements
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `signalements` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `ressource_id` int(11) NOT NULL,
      `motif` varchar(255) NOT NULL,
      `details` text DEFAULT NULL,
      `date_signalement` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`ressource_id`) REFERENCES `ressources`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_user_res_report` (`user_id`, `ressource_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>La table 'signalements' a ete creee avec succes.</h2>";
    echo "<p>Le systeme de moderation devrait maintenant fonctionner.</p>";
    echo "<a href='index.php'>Retour a l'accueil</a>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur lors de la creation de la table</h2>";
    echo "Message : " . htmlspecialchars($e->getMessage());
}
?>
