<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$message = '';
$msgType = 'info';
$validToken = false;
$userEmail = '';

// Etape 1 : Verifier le token via GET
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $pdo->prepare("SELECT id, email FROM utilisateurs WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
        $userEmail = $user['email'];
        // On sauvegarde le token en session pour le POST securise
        $_SESSION['reset_token_active'] = $token;
    } else {
        $message = "Ce lien de reinitialisation est invalide ou a expire. Veuillez refaire une demande.";
        $msgType = "danger";
    }
} 
// Etape 2 : Traiter le nouveau mot de passe via POST
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password']) && isset($_SESSION['reset_token_active'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $token = $_SESSION['reset_token_active'];
    
    if ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
        $msgType = "danger";
        $validToken = true; // Garder le formulaire ouvert
    } elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caracteres.";
        $msgType = "danger";
        $validToken = true;
    } else {
        // Double Check Token is still valid right before update
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Mise a jour du mdp ET Suppression du token (pour ne pas le reutiliser)
            $updateStmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
            if($updateStmt->execute([$hash, $token])) {
                // Succes
                unset($_SESSION['reset_token_active']);
                $message = "Votre mot de passe a ete reinitialise avec succes ! Vous pouvez maintenant vous connecter.";
                $msgType = "success";
                $validToken = false; // Fermer le formulaire
            }
        } else {
            $message = "Session expiree pendant le changement.";
            $msgType = "danger";
        }
    }
} else {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - OpenShare</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8fafc; }
        .auth-card { background: white; padding: 2.5rem; border-radius: var(--radius); box-shadow: var(--shadow-md); width: 100%; max-width: 450px; }
        .auth-card h2 { text-align: center; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .auth-card p.subtitle { text-align: center; color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; font-size: 1rem; }
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.5; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; text-align:center; }
        .auth-links { text-align: center; margin-top: 1.5rem; }
        .auth-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .auth-links a:hover { color: var(--primary-color); text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Creer un nouveau mot de passe</h2>
        
        <?php if($validToken): ?>
            <p class="subtitle">Entrez un nouveau mot de passe pour <strong><?= htmlspecialchars($userEmail) ?></strong>.</p>
        <?php endif; ?>

        <?php if(!empty($message)): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= $message ?>
            </div>
            
            <?php if($msgType === 'success' || !$validToken): ?>
                 <div class="auth-links" style="margin-top:2rem;">
                    <a href="login.php" class="btn btn-primary" style="color:white; padding:0.8rem 2rem; text-decoration:none;">Se connecter a mon compte</a>
                 </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($validToken): ?>
            <form action="reset_password.php" method="POST">
                
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe" required minlength="6">
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirmer le mot de passe" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 1rem; font-size: 1rem;">Mettre a jour le mot de passe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
