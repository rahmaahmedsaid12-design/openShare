<?php
session_start();
// Require user to be logged in and have admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$message = '';

// Handle Actions (Validate or Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['resource_id'])) {
    $resId = $_POST['resource_id'];
    $action = $_POST['action'];

    if (in_array($action, ['valider', 'rejeter'])) {
        try {
            $newStatus = ($action === 'valider') ? 'valide' : 'rejete';
            $stmt = $pdo->prepare("UPDATE ressources SET statut = ? WHERE id = ?");
            $stmt->execute([$newStatus, $resId]);
            $message = "La ressource a ete ".($action === 'valider' ? "validee" : "rejetee")." avec succes.";
        } catch (Exception $e) {
            $message = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// Fetch pending resources
$pendingResources = [];
try {
    $stmt = $pdo->query("SELECT * FROM ressources WHERE statut = 'en_attente' ORDER BY date_ajout ASC");
    $pendingResources = $stmt->fetchAll();
    
    // Fetch count again for the sidebar badge
    $stmtCount = $pdo->query("SELECT COUNT(id) FROM ressources WHERE statut = 'en_attente'");
    $pendingCount = $stmtCount->fetchColumn();
} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ressources - Admin OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #1e293b; color: white; padding: 2rem 0; flex-shrink: 0; }
        .admin-logo { padding: 0 2rem 2rem; font-size: 1.5rem; font-weight: 700; border-bottom: 1px solid #334155; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; }
        .admin-nav { list-style: none; }
        .admin-nav li { margin-bottom: 0.5rem; }
        .admin-nav a { display: block; padding: 0.75rem 2rem; color: #cbd5e1; text-decoration: none; transition: var(--transition); }
        .admin-nav a:hover, .admin-nav a.active { background: var(--primary-color); color: white; }
        .admin-content { flex-grow: 1; background: var(--bg-body); padding: 2rem; min-width: 0; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: white; padding: 1rem 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); }
        .card { background: white; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 1.5rem; }
        .card-header { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); font-size: 1.1rem; font-weight: 600; display:flex; justify-content:space-between; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        td p { margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted); }
        
        .action-btns { display: flex; gap: 0.5rem; }
        .btn-success { background: #16a34a; color: white; padding: 0.4rem 0.8rem; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:0.85rem;}
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: white; padding: 0.4rem 0.8rem; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:0.85rem;}
        .btn-danger:hover { background: #b91c1c; }
        
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
    </style>
</head>
<body>

    <div class="admin-layout">
        
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <span class="logo-icon">💠</span> Admin Panel
            </div>
            <ul class="admin-nav">
                <li><a href="dashboard.php">Vue d'ensemble</a></li>
                <li><a href="resources.php" class="active">Ressources <span class="badge" style="background:red; color:white; padding:2px 6px; float:right;"><?= $pendingCount ?? 0 ?></span></a></li>
                <li><a href="users.php">Utilisateurs</a></li>
                <li><a href="reports.php">Signalements</a></li>
                <li style="margin-top:2rem;"><a href="../index.php">⬅ Retour au site public</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            
            <header class="admin-header">
                <h2>Gestion des Ressources</h2>
                <div class="user-badge">Connecte en tant que <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            </header>
            
            <?php if(!empty($message)): ?>
                <div class="alert-info"><?= $message ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    Ressources en attente de validation
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Details</th>
                            <th>Auteur</th>
                            <th>Fichier</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($pendingResources)): ?>
                            <?php foreach($pendingResources as $res): ?>
                            <tr>
                                <td style="max-width:300px;">
                                    <strong><?= htmlspecialchars($res['titre']) ?></strong>
                                    <span class="badge" style="background:#eff6ff; color:#2563eb; font-size:0.7rem; margin-left:5px;"><?= htmlspecialchars($res['categorie']) ?></span>
                                    <p style="margin-top:5px; font-size:0.85rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($res['description']) ?></p>
                                </td>
                                <td><?= htmlspecialchars($res['auteur']) ?></td>
                                <td>
                                    <a href="../<?= htmlspecialchars($res['fichier_url']) ?>" target="_blank" style="font-size:0.85rem; text-decoration:underline;">Voir fichier</a><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($res['licence']) ?></small>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($res['date_ajout'])) ?></td>
                                <td>
                                    <form action="resources.php" method="POST" class="action-btns">
                                        <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                                        <button type="submit" name="action" value="valider" class="btn-success">Valider</button>
                                        <button type="submit" name="action" value="rejeter" class="btn-danger" onclick="return confirm('Etes-vous sur de vouloir rejeter cette ressource ?');">Rejeter</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                    🎉 Aucune ressource en attente pour le moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>

    </div>

</body>
</html>
