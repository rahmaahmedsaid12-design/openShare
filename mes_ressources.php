<?php
session_start();
// Require user to be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';
$userId = $_SESSION['user_id'];
$message = '';

// Handle resource deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $resId = $_POST['resource_id'] ?? null;
    
    if ($resId) {
        try {
            // Check ownership
            $stmt = $pdo->prepare("SELECT fichier_url FROM ressources WHERE id = ? AND auteur_id = ?");
            $stmt->execute([$resId, $userId]);
            $res = $stmt->fetch();
            
            if ($res) {
                // Delete file from disk
                if (file_exists(__DIR__ . '/' . $res['fichier_url'])) {
                    unlink(__DIR__ . '/' . $res['fichier_url']);
                }
                
                // Delete from DB
                $delStmt = $pdo->prepare("DELETE FROM ressources WHERE id = ?");
                $delStmt->execute([$resId]);
                
                $message = "La ressource a ete supprimee avec succes.";
            } else {
                $message = "Vous n'etes pas autorise a supprimer cette ressource.";
            }
        } catch (Exception $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// Fetch user's resources
$myResources = [];
try {
    // Also fetch current user details for the sidebar
    $stmtUser = $pdo->prepare("SELECT nom, role, photo_profil, date_inscription FROM utilisateurs WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    $stmt = $pdo->prepare("SELECT * FROM ressources WHERE auteur_id = ? ORDER BY date_ajout DESC");
    $stmt->execute([$userId]);
    $myResources = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Erreur de recuperation : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Ressources - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container { max-width: 1000px; margin: 3rem auto; display: grid; grid-template-columns: 250px 1fr; gap: 2rem; align-items: flex-start; }
        .profile-sidebar { background: var(--bg-surface); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 1.5rem; text-align: center; }
        .profile-avatar { width: 100px; height: 100px; background: #eff6ff; color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 1rem; }
        .profile-role-badge { display: inline-block; background: #f1f5f9; color: var(--text-muted); padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-top: 0.5rem; }
        .profile-role-badge.admin { background: #fef2f2; color: #dc2626; }
        .profile-nav { margin-top: 2rem; list-style: none; text-align: left; }
        .profile-nav li { margin-bottom: 0.5rem; }
        .profile-nav a { display: block; padding: 0.75rem 1rem; color: var(--text-main); text-decoration: none; border-radius: 6px; transition: var(--transition); }
        .profile-nav a:hover, .profile-nav a.active { background: #eff6ff; color: var(--primary-color); font-weight: 500; }
        .profile-content { background: var(--bg-surface); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 2.5rem; }
        .profile-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-valide { background: #dcfce7; color: #166534; }
        .status-attente { background: #fef3c7; color: #92400e; }
        .status-rejete { background: #fee2e2; color: #991b1b; }

        .btn-danger { background: #dc2626; color: white; border:none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
        .btn-danger:hover { background: #b91c1c; }

        @media (max-width: 900px) { .profile-container { grid-template-columns: 1fr; } }
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
                <a href="includes/auth.php?action=logout" class="btn btn-outline">Deconnexion</a>
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
                <li><a href="profil.php">Informations personnelles</a></li>
                <li><a href="mes_ressources.php" class="active">Mes ressources</a></li>
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
                <h2>Mes ressources deposees</h2>
                <a href="publish.php" class="btn btn-outline btn-sm">+ Nouvelle Ressource</a>
            </div>

            <?php if(!empty($message)): ?>
                <div class="alert alert-info" style="background:#eff6ff; color:#1e40af; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if(empty($myResources)): ?>
                <div style="text-align:center; padding: 3rem; color:var(--text-muted);">
                    <p>Vous n'avez pas encore publie de ressource.</p>
                    <a href="publish.php" class="btn btn-primary" style="margin-top:1rem;">Commencer le partage</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Stats</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($myResources as $res): ?>
                        <tr>
                            <td>
                                <?php if($res['statut'] === 'valide'): ?>
                                    <a href="detail.php?id=<?= $res['id'] ?>" style="font-weight:600; text-decoration:underline;">
                                        <?= htmlspecialchars($res['titre']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($res['titre']) ?></span>
                                <?php endif; ?>
                                <br>
                                <small style="color:var(--text-muted);"><?= date('d/m/Y', strtotime($res['date_ajout'])) ?></small>
                            </td>
                            <td>
                                <?php 
                                    if ($res['statut'] === 'valide') echo '<span class="status-badge status-valide">Valide</span>';
                                    elseif ($res['statut'] === 'en_attente') echo '<span class="status-badge status-attente">En attente</span>';
                                    else echo '<span class="status-badge status-rejete">Rejeté</span>';
                                ?>
                            </td>
                            <td>
                                ⭐ <?= number_format($res['note_moyenne'], 1) ?><br>
                                📥 <?= number_format($res['nb_telechargements'], 0, ',', ' ') ?>
                            </td>
                            <td>
                                <form action="mes_ressources.php" method="POST">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                                    <button type="submit" class="btn-danger" onclick="return confirm('Etes-vous sur de vouloir supprimer definitivement cette ressource ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>

    </div>

</body>
</html>
