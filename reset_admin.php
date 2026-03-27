<?php
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

// Le mot de passe que vous souhaitez (changez-le ici si besoin)
$nouveauMotDePasse = "password123";
$emailAdmin = "admin@openshare.com";

// On hache le mot de passe de maniere securisee
$motDePasseHache = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
    $stmt->execute([$motDePasseHache, $emailAdmin]);
    
    echo "<h2 style='color: green;'>Mot de passe administrateur reinitialise avec succes !</h2>";
    echo "<p>Email : <strong>" . htmlspecialchars($emailAdmin) . "</strong></p>";
    echo "<p>Nouveau mot de passe : <strong>" . htmlspecialchars($nouveauMotDePasse) . "</strong></p>";
    echo "<br><a href='login.php'>Retourner a la page de connexion</a>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
