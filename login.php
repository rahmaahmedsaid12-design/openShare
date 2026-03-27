<?php
session_start();
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        if (isset($pdo)) {
            try {
                // Assuming we will create a 'utilisateurs' table later
                $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['mot_de_passe'])) {
                    if (isset($user['statut']) && $user['statut'] === 'bloque') {
                        $error = "Votre compte a été bloqué par un administrateur.";
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nom'];
                        $_SESSION['role'] = $user['role'];
                        
                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            } catch (Exception $e) {
                // Handle missing table gracefully during demo
                $error = "Erreur de connexion a la base de donnees. La table utilisateurs existe-t-elle ?";
            }
        } else {
            $error = "Base de donnees non configuree.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            min-height: calc(100vh - 80px); /* Minus navbar roughly */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .auth-card {
            background: var(--bg-surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            font-size: 1.5rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            color: var(--text-muted);
        }
        
        .auth-footer a {
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">💠</span>
                OpenShare
            </a>
            
            <div class="nav-actions">
                <a href="registre.php" class="btn btn-outline">S'inscrire</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container auth-container">
        
        <div class="auth-card">
            <div class="auth-header">
                <h1>Bon retour</h1>
                <p>Connectez-vous pour acceder a vos ressources</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="vous@exemple.com" required>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label for="password" class="form-label" style="margin-bottom: 0;">Mot de passe</label>
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--primary-color); text-decoration: none;">Oublié ?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.75rem;">Se Connecter</button>
            </form>
            
            <div class="auth-footer">
                Pas encore de compte ? <a href="registre.php">S'inscrire ici</a>
            </div>
        </div>

    </div>

</body>
</html>
