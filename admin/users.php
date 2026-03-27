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

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ======== CREATE ========
    if ($_POST['action'] === 'create') {
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (!empty($nom) && !empty($email) && !empty($password)) {
            // Check if email exists
            $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $message = "Erreur : Cet email est déjà utilisé.";
                $msgType = "danger";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$nom, $email, $hash, $role])) {
                    $message = "Utilisateur '$nom' créé avec succès !";
                    $msgType = "success";
                }
            }
        } else {
            $message = "Veuillez remplir tous les champs.";
            $msgType = "danger";
        }
    }
    
    // ======== UPDATE ========
    elseif ($_POST['action'] === 'update' && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        $nom = $_POST['edit_nom'] ?? '';
        $email = $_POST['edit_email'] ?? '';
        $role = $_POST['edit_role'] ?? 'user';
        $statut = $_POST['edit_statut'] ?? 'actif';
        $password = $_POST['edit_password'] ?? ''; // Optional

        if (!empty($nom) && !empty($email)) {
            // Check email uniquely
            $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmtCheck->execute([$email, $userId]);
            if ($stmtCheck->fetch()) {
                $message = "Erreur : Cet email est déjà attribué.";
                $msgType = "danger";
            } else {
                if (!empty($password)) {
                    // Update WITH password
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ?, statut = ?, mot_de_passe = ? WHERE id = ?");
                    $res = $stmt->execute([$nom, $email, $role, $statut, $hash, $userId]);
                } else {
                    // Update WITHOUT password
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ?, statut = ? WHERE id = ?");
                    $res = $stmt->execute([$nom, $email, $role, $statut, $userId]);
                }
                
                if ($res) {
                    $message = "Utilisateur mis à jour !";
                    $msgType = "success";
                    // If admin edited themselves, update session
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['user_name'] = $nom;
                        $_SESSION['role'] = $role;
                    }
                }
            }
        }
    }
    
    // ======== DELETE ========
    elseif ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        
        if ($userId == $_SESSION['user_id']) {
            $message = "Vous ne pouvez pas supprimer votre propre compte admin.";
            $msgType = "danger";
        } else {
            try {
                // Delete user (Resources and Comments will cascade due to FK constraints)
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $message = "Utilisateur supprimé (ainsi que ses contenus).";
                    $msgType = "success";
                }
            } catch (Exception $e) {
                 $message = "Erreur SQL : " . $e->getMessage();
                 $msgType = "danger";
            }
        }
    }
}

// ======== READ ========
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin OpenShare</title>
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
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        .role-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; display:inline-block; margin-right: 5px; }
        .role-admin { background: #fee2e2; color: #b91c1c; }
        .role-user { background: #eff6ff; color: #1d4ed8; }
        
        .statut-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; display:inline-block; }
        .statut-actif { background: #dcfce7; color: #166534; }
        .statut-bloque { background: #1e293b; color: #f8fafc; }

        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.85rem; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-info { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .btn-info:hover { background: #e2e8f0; }

        /* Forms */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: var(--radius); width: 100%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #94a3b8; }
        
        .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
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
                <li><a href="users.php" class="active">Utilisateurs</a></li>
                <li><a href="reports.php">Signalements</a></li>
                <li style="margin-top:2rem;"><a href="../index.php">⬅ Retour au site public</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            
            <header class="admin-header">
                <h2>Gestion des Utilisateurs</h2>
                <div class="user-badge">Connecte en tant que <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            </header>
            
            <?php if(!empty($message)): ?>
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Formulaire de Creation Rapide -->
            <div class="card">
                <div class="card-header">
                    Ajouter un Utilisateur
                </div>
                <form action="users.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <input type="text" name="nom" class="form-control" placeholder="Nom Complet" required>
                        <input type="email" name="email" class="form-control" placeholder="Adresse Email" required>
                        <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                        <select name="role" class="form-control">
                            <option value="user">Utilisateur (User)</option>
                            <option value="admin">Administrateur (Admin)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Creer l'utilisateur</button>
                </form>
            </div>

            <!-- Liste des Utilisateurs (READ, DELETE, UPDATE) -->
            <div class="card">
                <div class="card-header">
                    Liste des Membres (<?= count($users) ?>)
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Nom & Email</th>
                            <th>Role</th>
                            <th>Date d'inscription</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($u['nom']) ?></strong><br>
                                <span style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td>
                                <span class="role-badge role-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span>
                                <span class="statut-badge statut-<?= htmlspecialchars($u['statut'] ?? 'actif') ?>"><?= htmlspecialchars($u['statut'] ?? 'actif') ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></td>
                            <td style="text-align:right; display:flex; gap:0.5rem; justify-content:flex-end;">
                                <!-- Edit Button (Opens Modal via JS) -->
                                <button type="button" class="btn btn-info btn-sm" onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nom'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['role'] ?>', '<?= $u['statut'] ?? 'actif' ?>')">
                                    Editer
                                </button>

                                <!-- Delete Form -->
                                <form action="users.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Attention: Supprimer ce membre effacera aussi toutes ses ressources et commentaires. Continuer ?');">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier l'utilisateur</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_user_id" name="user_id" value="">
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Nom</label>
                    <input type="text" id="edit_nom" name="edit_nom" class="form-control" required>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Email</label>
                    <input type="email" id="edit_email" name="edit_email" class="form-control" required>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Role</label>
                    <select id="edit_role" name="edit_role" class="form-control">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Statut</label>
                    <select id="edit_statut" name="edit_statut" class="form-control">
                        <option value="actif">Actif</option>
                        <option value="bloque">Bloqué</option>
                    </select>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Nouveau mot de passe <span style="font-weight:normal; color:#888;">(optionnel)</span></label>
                    <input type="password" name="edit_password" class="form-control" placeholder="Laissez vide pour conserver l'actuel">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%;">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nom, email, role, statut) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_statut').value = statut;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
