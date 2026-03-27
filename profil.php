<?php
session_start();
// Require user to be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $photoUpdateSql = "";
    $photoParams = [];
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/profils/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['photo_profil']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo_profil']['tmp_name'], $targetPath)) {
            $photoUpdateSql = ", photo_profil = ?";
            $photoParams[] = 'uploads/profils/' . $fileName;
        }
    }

    if (!empty($nom) && !empty($email)) {
        try {
            // Check if email is already taken by ANOTHER user
            $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmtCheck->execute([$email, $userId]);
            
            if ($stmtCheck->fetch()) {
                $message = "Cette adresse email est déjà utilisée par un autre compte.";
                $messageType = "warning";
            } else {
                $sql = "UPDATE utilisateurs SET nom = ?, email = ?" . $photoUpdateSql . " WHERE id = ?";
                $params = array_merge([$nom, $email], $photoParams, [$userId]);
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute($params);
                
                // Update session variables
                $_SESSION['user_name'] = $nom;
                
                $message = "Votre profil a été mis à jour avec succès.";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Erreur de base de données : " . $e->getMessage();
            $messageType = "warning";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
        $messageType = "warning";
    }
}

// Fetch current user details
try {
    $stmt = $pdo->prepare("SELECT nom, email, role, photo_profil, date_inscription FROM utilisateurs WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    die("Erreur de récupération du profil : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 3rem auto;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            align-items: flex-start;
        }

        .profile-sidebar {
            background: var(--bg-surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #eff6ff;
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }

        .profile-role-badge {
            display: inline-block;
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }

        .profile-role-badge.admin {
            background: #fef2f2;
            color: #dc2626;
        }

        .profile-nav {
            margin-top: 2rem;
            list-style: none;
            text-align: left;
        }

        .profile-nav li {
            margin-bottom: 0.5rem;
        }

        .profile-nav a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
        }

        .profile-nav a:hover, .profile-nav a.active {
            background: #eff6ff;
            color: var(--primary-color);
            font-weight: 500;
        }

        .profile-content {
            background: var(--bg-surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 2.5rem;
        }

        .profile-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
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

        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding:1rem; border-radius:var(--radius); margin-bottom:1.5rem; }
        .alert-warning { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; padding:1rem; border-radius:var(--radius); margin-bottom:1.5rem; }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
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
                <a href="publish.php" class="btn btn-primary" style="margin-right:1rem;">Publier</a>
                <a href="includes/auth.php?action=logout" class="btn btn-outline">Déconnexion</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="profile-container">
        
        <!-- Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-avatar">
                <?php if (!empty($user['photo_profil'])): ?>
                    <img src="<?= htmlspecialchars($user['photo_profil']) ?>" alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr(htmlspecialchars($user['nom']), 0, 1)) ?>
                <?php endif; ?>
            </div>
            <h3 style="font-size:1.2rem; color:var(--text-main);"><?= htmlspecialchars($user['nom']) ?></h3>
            <span class="profile-role-badge <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
                <?= htmlspecialchars($user['role']) ?>
            </span>

            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:1rem;">
                Membre depuis le <?= date('d/m/Y', strtotime($user['date_inscription'])) ?>
            </p>

            <ul class="profile-nav">
                <li><a href="profil.php" class="active">Informations personnelles</a></li>
                <li><a href="mes_ressources.php">Mes ressources</a></li>
                <?php if($user['role'] === 'admin'): ?>
                    <li style="margin-top:1rem; border-top:1px solid var(--border-color); padding-top:1rem;">
                        <a href="admin/dashboard.php" style="color:#dc2626;">⚙️ Panel Admin</a>
                    </li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Form Area -->
        <main class="profile-content">
            <div class="profile-header">
                <h2>Modifier le profil</h2>
                <p style="color:var(--text-muted); font-size:0.95rem; margin-top:0.25rem;">Mettez à jour vos informations de compte.</p>
            </div>

            <?php if(!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="profil.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="photo_profil" class="form-label">Photo de profil</label>
                    <input type="file" id="photo_profil" name="photo_profil" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="nom" class="form-label">Nom complet</label>
                    <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
                </div>
                
                <div class="form-group" style="margin-bottom:2rem;">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="padding:0.75rem 2rem;">Enregistrer les modifications</button>
            </form>
        </main>

    </div>

</body>
</html>
