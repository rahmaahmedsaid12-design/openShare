<?php
session_start();
// Require user to be logged in and have admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$message = '';
$msgType = 'info';

// Handle Admin Actions (Ignore or Delete Resource)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ======== IGNORE/DELETE REPORT ONLY ========
    if ($_POST['action'] === 'ignore_report' && isset($_POST['report_id'])) {
        $reportId = $_POST['report_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM signalements WHERE id = ?");
            $stmt->execute([$reportId]);
            $message = "Le signalement a été ignoré et fermé.";
            $msgType = "success";
        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
            $msgType = "danger";
        }
    }
    
    // ======== DELETE ENTIRE RESOURCE ========
    // Note: Due to ON DELETE CASCADE on our DB, deleting the resource 
    // will also delete all associated comments and reports automatically!
    elseif ($_POST['action'] === 'delete_resource' && isset($_POST['resource_id'])) {
        $resId = $_POST['resource_id'];
        try {
            // First fetch the file path to delete it from disk
            $stmtFile = $pdo->prepare("SELECT fichier_url FROM ressources WHERE id = ?");
            $stmtFile->execute([$resId]);
            $res = $stmtFile->fetch();
            
            // Delete from Database
            $stmt = $pdo->prepare("DELETE FROM ressources WHERE id = ?");
            if ($stmt->execute([$resId])) {
                // Delete actual physical file
                if ($res && !empty($res['fichier_url']) && file_exists(__DIR__ . '/../' . $res['fichier_url'])) {
                    unlink(__DIR__ . '/../' . $res['fichier_url']);
                }
                $message = "La ressource litigieuse a été supprimée avec succès (fichiers inclus).";
                $msgType = "success";
            }
        } catch (Exception $e) {
            $message = "Erreur technique : " . $e->getMessage();
            $msgType = "danger";
        }
    }
    // ======== BLOCK USER ========
    elseif ($_POST['action'] === 'block_user' && isset($_POST['author_id']) && isset($_POST['report_id'])) {
        $authorId = $_POST['author_id'];
        $reportId = $_POST['report_id'];
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'bloque' WHERE id = ?");
            if ($stmt->execute([$authorId])) {
                // Delete the report after blocking the user
                $stmtDel = $pdo->prepare("DELETE FROM signalements WHERE id = ?");
                $stmtDel->execute([$reportId]);
                $message = "L'utilisateur a été bloqué et le signalement fermé.";
                $msgType = "success";
            }
        } catch (Exception $e) {
            $message = "Erreur technique : " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// ======== FETCH ALL REPORTS ========
// Joining users (who reported) and resources (what is reported)
$reports = [];
try {
    $stmt = $pdo->query("
        SELECT 
            s.id as report_id, s.motif, s.details, s.date_signalement,
            u.nom as reporter_name, u.email as reporter_email,
            r.id as resource_id, r.titre as resource_title, r.statut as resource_status,
            r.auteur_id as author_id
        FROM signalements s
        JOIN utilisateurs u ON s.user_id = u.id
        JOIN ressources r ON s.ressource_id = r.id
        ORDER BY s.date_signalement DESC
    ");
    $reports = $stmt->fetchAll();
} catch (Exception $e) {
    // If the table doesn't exist yet
    $message = "La table 'signalements' n'existe pas encore. Veuillez exécuter le script install_reports.php.";
    $msgType = "warning";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération des Signalements - Admin OpenShare</title>
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
        
        .card { background: white; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2rem; }
        .card-header { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); font-size: 1.2rem; font-weight: 600; display:flex; justify-content:space-between; align-items:center; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.85rem; }
        .btn-danger { background: #dc2626; color: white; border: none; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-outline-danger { background: transparent; color: #dc2626; border: 1px solid #fecaca; }
        .btn-outline-danger:hover { background: #fef2f2; border-color: #dc2626;}
        .btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        
        .badge-status { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: #e2e8f0; color: #475569; }
        .motif-highlight { color: #dc2626; font-weight: 600; }
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
                <li><a href="resources.php">Ressources</a></li>
                <li><a href="users.php">Utilisateurs</a></li>
                <li><a href="reports.php" class="active">Signalements <?php if(count($reports)>0) echo "<span style='background:#dc2626; color:white; border-radius:10px; padding:2px 6px; font-size:0.75rem; margin-left:5px;'>".count($reports)."</span>"; ?></a></li>
                <li style="margin-top:2rem;"><a href="../index.php">⬅ Retour au site public</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            
            <header class="admin-header">
                <h2>Modération des Signalements</h2>
                <div class="user-badge">Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            </header>
            
            <?php if(!empty($message)): ?>
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    Dossiers en attente d'examen (<?= count($reports) ?>)
                </div>
                
                <?php if(empty($reports)): ?>
                    <p style="text-align:center; padding: 2rem; color: var(--text-muted);">Aucun signalement actif actuellement. Tout est en ordre ! 🎉</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:25%">Motif & Détails</th>
                                <th style="width:25%">Ressource Signalée</th>
                                <th style="width:20%">Signalé Par</th>
                                <th style="text-align:right; width:30%">Action Requise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reports as $r): ?>
                            <tr>
                                <td>
                                    <div class="motif-highlight">🚩 <?= htmlspecialchars($r['motif']) ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:5px; line-height:1.4;">
                                        "<?= nl2br(htmlspecialchars($r['details'] ?? 'Aucun détail fourni.')) ?>"
                                    </div>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:10px;">
                                        <?= date('d/m/Y H:i', strtotime($r['date_signalement'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($r['resource_title']) ?></strong><br>
                                    <a href="../detail.php?id=<?= $r['resource_id'] ?>" target="_blank" style="font-size:0.85rem; text-decoration:none;">📄 Voir la ressource ↗</a>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($r['reporter_name']) ?></strong><br>
                                    <span style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($r['reporter_email']) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; flex-direction:column; gap:0.5rem; align-items:flex-end;">
                                        <!-- Ignore Ticket Form -->
                                        <form action="reports.php" method="POST">
                                            <input type="hidden" name="action" value="ignore_report">
                                            <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;" title="Ferme ce ticket sans toucher à la ressource">
                                                ✅ Ignorer (Faux positif)
                                            </button>
                                        </form>

                                        <!-- Nuke Resource Form -->
                                        <form action="reports.php" method="POST">
                                            <input type="hidden" name="action" value="delete_resource">
                                            <input type="hidden" name="resource_id" value="<?= $r['resource_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="width:100%; margin-bottom: 0.5rem;" onclick="return confirm('ATTENTION ! Cela supprimera définitivement la ressource et les fichiers attachés. Continuer ?');">
                                                🗑️ Supprimer la ressource
                                            </button>
                                        </form>

                                        <!-- Block User Form -->
                                        <form action="reports.php" method="POST">
                                            <input type="hidden" name="action" value="block_user">
                                            <input type="hidden" name="author_id" value="<?= $r['author_id'] ?>">
                                            <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" style="width:100%; background: #000; color: #fff; border:none;" onclick="return confirm('Êtes-vous sûr de vouloir bloquer cet utilisateur ? Il ne pourra plus se connecter.');">
                                                🔨 Bloquer l'auteur
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>
</html>
