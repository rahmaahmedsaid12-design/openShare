<?php
session_start();
// Require user to be logged in and have admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Prepare stats
$stats = [
    'users' => 0,
    'resources' => 0,
    'downloads' => 0,
    'pending' => 0
];

try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(id) FROM utilisateurs");
    $stats['users'] = $stmt->fetchColumn();

    // Total active resources
    $stmt = $pdo->query("SELECT COUNT(id) FROM ressources WHERE statut = 'valide'");
    $stats['resources'] = $stmt->fetchColumn();

    // Total downloads
    $stmt = $pdo->query("SELECT SUM(nb_telechargements) FROM ressources");
    $stats['downloads'] = $stmt->fetchColumn() ?? 0;

    // Total pending validation
    $stmt = $pdo->query("SELECT COUNT(id) FROM ressources WHERE statut = 'en_attente'");
    $stats['pending'] = $stmt->fetchColumn();

    // Recent users
    $stmt = $pdo->query("SELECT nom, email, date_inscription FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll();

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px;
            background: #1e293b;
            color: white;
            padding: 2rem 0;
            flex-shrink: 0;
        }

        .admin-logo {
            padding: 0 2rem 2rem;
            font-size: 1.5rem;
            font-weight: 700;
            border-bottom: 1px solid #334155;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 0.5rem;
        }

        .admin-nav a {
            display: block;
            padding: 0.75rem 2rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: var(--transition);
        }

        .admin-nav a:hover, .admin-nav a.active {
            background: var(--primary-color);
            color: white;
        }

        .admin-content {
            flex-grow: 1;
            background: var(--bg-body);
            padding: 2rem;
            min-width: 0;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.users { background: #eff6ff; color: #3b82f6; }
        .stat-icon.docs { background: #f0fdf4; color: #22c55e; }
        .stat-icon.dl { background: #fefce8; color: #eab308; }
        .stat-icon.warn { background: #fef2f2; color: #ef4444; }

        .stat-info .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .stat-info .label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .card-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.1rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
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
                <li><a href="dashboard.php" class="active">Vue d'ensemble</a></li>
                <li><a href="resources.php">Ressources <span class="badge" style="background:red; color:white; padding:2px 6px; float:right;"><?= $stats['pending'] ?></span></a></li>
                <li><a href="users.php">Utilisateurs</a></li>
                <li><a href="reports.php">Signalements</a></li>
                <li style="margin-top:2rem;"><a href="../index.php">⬅ Retour au site public</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            
            <header class="admin-header">
                <h2>Tableau de bord</h2>
                <div class="user-badge">
                    Connecte en tant que <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
            </header>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-warning"><?= $error ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">👥</div>
                    <div class="stat-info">
                        <div class="value"><?= number_format($stats['users']) ?></div>
                        <div class="label">Membres</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon docs">📄</div>
                    <div class="stat-info">
                        <div class="value"><?= number_format($stats['resources']) ?></div>
                        <div class="label">Ressources Actives</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon dl">📥</div>
                    <div class="stat-info">
                        <div class="value"><?= number_format($stats['downloads']) ?></div>
                        <div class="label">Telechargements</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warn">⏳</div>
                    <div class="stat-info">
                        <div class="value"><?= number_format($stats['pending']) ?></div>
                        <div class="label">En Attente</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Nouveaux Membres recents</div>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date d'inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($recentUsers)): ?>
                            <?php foreach($recentUsers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['nom']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding:2rem;">Aucun membre trouve.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>

    </div>

</body>
</html>
