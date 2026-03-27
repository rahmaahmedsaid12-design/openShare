<?php
// Script pour installer la base de donnees depuis database.sql
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connexion a MySQL sans specifier de base de donnees (puisque le script la cree)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lire le contenu de database.sql
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        die("Erreur : Le fichier database.sql est introuvable.");
    }

    $sql = file_get_contents($sql_file);

    // Executer le script SQL complet
    $pdo->exec($sql);

    echo "<h2 style='color: green;'>La base de donnees et les tables ont ete creees avec succes !</h2>";
    echo "<p>Vous pouvez maintenant retourner a la page d'accueil ou vous connecter.</p>";
    echo "<a href='index.php'>Retour a l'accueil</a>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erreur d'installation</h2>";
    echo "Message : " . htmlspecialchars($e->getMessage());
}
?>
