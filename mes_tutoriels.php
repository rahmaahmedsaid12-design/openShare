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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tutorial') {
    $tutId = $_POST['tutorial_id'] ?? null;
    
    if ($tutId) {
        try {
            // Delete from DB, ensuring it belongs to user
            $delStmt = $pdo->prepare("DELETE FROM tutoriels_sauvegardes WHERE id = ? AND user_id = ?");
            $delStmt->execute([$tutId, $userId]);
            
            if ($delStmt->rowCount() > 0) {
                $message = "Le tutoriel a été retiré de vos favoris.";
            } else {
                $message = "Vous n'êtes pas autorisé à supprimer ce tutoriel.";
            }
        } catch (Exception $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// Fetch user's active resources for sidebar and their details
$myTutorials = [];
try {
    $stmtUser = $pdo->prepare("SELECT nom, role, photo_profil, date_inscription FROM utilisateurs WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    $stmt = $pdo->prepare("SELECT * FROM tutoriels_sauvegardes WHERE user_id = ? ORDER BY date_sauvegarde DESC");
    $stmt->execute([$userId]);
    $myTutorials = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Erreur de récupération : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Tutoriels - OpenShare</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container { max-width: 1000px; margin: 3rem auto; display: grid; grid-template-columns: 250px 1fr; gap: 2rem; align-items: flex-start; }
        .profile-sidebar { background: var(--bg-surface); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 1.5rem; text-align: center; }
        .profile-avatar { width: 100px; height: 100px; background: #eff6ff; color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 1rem; overflow: hidden; }
        .profile-role-badge { display: inline-block; background: #f1f5f9; color: var(--text-muted); padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-top: 0.5rem; }
        .profile-role-badge.admin { background: #fef2f2; color: #dc2626; }
        .profile-nav { margin-top: 2rem; list-style: none; text-align: left; }
        .profile-nav li { margin-bottom: 0.5rem; }
        .profile-nav a { display: block; padding: 0.75rem 1rem; color: var(--text-main); text-decoration: none; border-radius: 6px; transition: var(--transition); }
        .profile-nav a:hover, .profile-nav a.active { background: #eff6ff; color: var(--primary-color); font-weight: 500; }
        .profile-content { background: var(--bg-surface); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 2.5rem; }
        .profile-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; }
        
        .tutorial-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .tut-card { background: white; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; }
        .btn-danger { background: #dc2626; color: white; border:none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem; width: 100%; margin-top: auto; }
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
                <li><a href="profil.php">Informations personnelles</a></li>
                <li><a href="mes_ressources.php">Mes ressources</a></li>
                <li><a href="mes_tutoriels.php" class="active">Mes tutoriels sauvegardés</a></li>
                <?php if($user['role'] === 'admin'): ?>
                    <li style="margin-top:1rem; border-top:1px solid var(--border-color); padding-top:1rem;">
                        <a href="admin/dashboard.php" style="color:#dc2626;">⚙️ Panel Admin</a>
                    </li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="profile-content">
            <div class="profile-header">
                <h2>Mes tutoriels sauvegardés</h2>
                <a href="index.php" class="btn btn-outline btn-sm">Parcourir plus</a>
            </div>

            <?php if(!empty($message)): ?>
                <div class="alert alert-info" style="background:#eff6ff; color:#1e40af; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if(empty($myTutorials)): ?>
                <div style="text-align:center; padding: 3rem; color:var(--text-muted);">
                    <p>Vous n'avez pas encore sauvegardé de tutoriel.</p>
                </div>
            <?php else: ?>
                <div class="tutorial-grid">
                    <?php foreach($myTutorials as $tut): ?>
                    <div class="tut-card">
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($tut['youtube_id']) ?>" 
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" 
                                    allowfullscreen></iframe>
                        </div>
                        <div style="padding: 1rem; display: flex; flex-direction: column; flex-grow: 1;">
                            <h3 style="font-size: 1rem; margin-bottom: 0.5rem; line-height: 1.4;"><?= htmlspecialchars($tut['titre']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">📺 <?= htmlspecialchars($tut['nom_chaine']) ?><br>📅 Ajouté le <?= date('d/m/Y', strtotime($tut['date_sauvegarde'])) ?></p>
                            <form action="mes_tutoriels.php" method="POST" style="margin-top: auto;">
                                <input type="hidden" name="action" value="delete_tutorial">
                                <input type="hidden" name="tutorial_id" value="<?= $tut['id'] ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Etes-vous sûr de vouloir retirer ce tutoriel ?');">Retirer des favoris</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

    </div>

</body>
</html>
