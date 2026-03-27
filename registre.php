<?php
session_start();
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!empty($nom) && !empty($email) && !empty($password) && !empty($password_confirm)) {
        if ($password !== $password_confirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            if (isset($pdo)) {
                try {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        $error = "Cette adresse email est deja utilisee.";
                    } else {
                        // Insert new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $insert = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'user')");
                        $insert->execute([$nom, $email, $hashed_password]);
                        
                        $success = "Inscription reussie ! Vous pouvez maintenant vous connecter.";
                    }
                } catch (Exception $e) {
                    $error = "Erreur de base de donnees. La table utilisateurs existe-t-elle ?";
                }
            } else {
                $error = "Base de donnees non configuree.";
            }
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
    <title>Inscription - OpenShare</title>
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
            max-width: 450px;
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
        
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
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
                <a href="login.php" class="btn btn-outline">Connexion</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container auth-container">
        
        <div class="auth-card">
            <div class="auth-header">
                <h1>Creer un compte</h1>
                <p>Rejoignez OpenShare pour partager vos ressources</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?> <a href="login.php">Se connecter</a>
                </div>
            <?php else: ?>

            <form action="registre.php" method="POST">
                
                <div class="form-group">
                    <label for="nom" class="form-label">Nom complet</label>
                    <input type="text" id="nom" name="nom" class="form-control" placeholder="Jean Dupont" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="vous@exemple.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.75rem;">Creer mon compte</button>
            </form>
            
            <?php endif; ?>
            
            <div class="auth-footer">
                Deja un compte ? <a href="login.php">Connectez-vous</a>
            </div>
        </div>

    </div>

</body>
</html>
