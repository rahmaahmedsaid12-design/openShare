<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$message = '';
$msgType = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, nom FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate a secure token (64 hex characters)
        $token = bin2hex(random_bytes(32));
        // Token expires in 1 hour
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save to database
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmtUpdate->execute([$token, $expires, $email]);
        
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        
        // For production, you would use mail() or a professional mailer (PHPMailer/SendGrid).
        // Since we are in local WAMP, we display the link directly for testing!
        $message = "Un email contenant les instructions de réinitialisation a été envoyé à <strong>" . htmlspecialchars($email) . "</strong>.<br><br>";
        $message .= "<strong style='color:#dc2626;'>[MODE TEST LOCAL ACTIVÉ] - Cliquez ici pour simuler la réception de l'email : </strong><br>";
        $message .= "<a href='" . $resetLink . "' style='word-break: break-all; color:#2563eb; text-decoration:underline;'>$resetLink</a>";
        $msgType = "success";
    } else {
        // For security reasons, it's often better to show the same message whether the email exists or not to prevent email enumeration.
        // But for development clarity, we will show an error.
        $message = "Aucun compte n'est associé à cette adresse email.";
        $msgType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - OpenShare</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8fafc; }
        .auth-card { background: white; padding: 2.5rem; border-radius: var(--radius); box-shadow: var(--shadow-md); width: 100%; max-width: 450px; }
        .auth-card h2 { text-align: center; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .auth-card p.subtitle { text-align: center; color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.5; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .auth-links { text-align: center; margin-top: 1.5rem; }
        .auth-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .auth-links a:hover { color: var(--primary-color); text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Mot de passe oublié ?</h2>
        <p class="subtitle">Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
        
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="prenom@exemple.com" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" style="padding: 1rem; font-size: 1rem;">Envoyer le lien de réinitialisation</button>
        </form>
        
        <div class="auth-links">
            <a href="login.php">← Retour à la connexion</a>
        </div>
    </div>
</body>
</html>
