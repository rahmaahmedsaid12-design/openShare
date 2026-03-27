<?php
session_start();
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
} else {
    die("Base de donnees non configuree.");
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("ID de ressource invalide.");
}

// Check if it's a download request
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("UPDATE ressources SET nb_telechargements = nb_telechargements + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt2 = $pdo->prepare("SELECT fichier_url FROM ressources WHERE id = ?");
        $stmt2->execute([$id]);
        $res = $stmt2->fetch();
        
        if ($res && !empty($res['fichier_url']) && file_exists(__DIR__ . '/' . $res['fichier_url'])) {
            $filepath = __DIR__ . '/' . $res['fichier_url'];
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            die("Fichier introuvable sur le serveur.");
        }
    }
}

// Handle comment submission
$commentMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    if (isset($_SESSION['user_id']) && !empty(trim($_POST['contenu']))) {
        try {
            $stmt = $pdo->prepare("INSERT INTO commentaires (user_id, ressource_id, contenu) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $id, trim($_POST['contenu'])]);
            $commentMessage = "Votre commentaire a ete publie !";
        } catch (Exception $e) {
            $commentMessage = "Erreur lors de la publication : " . $e->getMessage();
        }
    }
}

// Fetch resource details
$resource = null;
$comments = [];
if (isset($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM ressources WHERE id = ? AND statut = 'valide'");
    $stmt->execute([$id]);
    $resource = $stmt->fetch();

    // Fetch comments
    try {
        $stmtComments = $pdo->prepare("
            SELECT c.contenu, c.date_creation, u.nom 
            FROM commentaires c 
            JOIN utilisateurs u ON c.user_id = u.id 
            WHERE c.ressource_id = ? 
            ORDER BY c.date_creation DESC
        ");
        $stmtComments->execute([$id]);
        $comments = $stmtComments->fetchAll();
    } catch (Exception $e) {
        // Comments table might not exist yet if user hasn't run the installer script
        $comments = [];
    }
}

if (!$resource) {
    die("Ressource introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($resource['titre']) ?> - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-container { max-width: 1100px; margin: 3rem auto; padding: 0 2rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .header-banner { background: linear-gradient(135deg, var(--primary-color), #4f46e5); color: white; padding: 3rem 2rem; border-radius: var(--radius); margin-bottom: 2rem; box-shadow: var(--shadow-md); }
        .header-banner h1 { font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.2; }
        .header-meta { display: flex; gap: 1.5rem; opacity: 0.9; font-size: 0.95rem; }
        .header-meta span { display: flex; align-items: center; gap: 5px; }
        .content-card, .sidebar-card { background: var(--bg-surface); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 2rem; }
        .content-card h2 { font-size: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem; }
        .description-text { color: var(--text-muted); line-height: 1.8; font-size: 1.05rem; white-space: pre-wrap; }
        .sidebar-card { position: sticky; top: 100px; height: fit-content; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem; }
        .stat-box { background: #f8fafc; padding: 1rem; border-radius: var(--radius); text-align: center; border: 1px solid var(--border-color); }
        .stat-box .value { font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem; }
        .stat-box .label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .download-btn { font-size: 1.1rem; padding: 1rem; box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39); margin-bottom: 1rem; }
        
        /* Comments section */
        .comment-item { border-bottom: 1px solid var(--border-color); padding: 1.5rem 0; }
        .comment-item:last-child { border-bottom: none; }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .comment-author { font-weight: 600; color: var(--text-main); }
        .comment-date { font-size: 0.85rem; color: var(--text-muted); }
        .comment-content { color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; }
        .comment-form textarea { width: 100%; min-height: 100px; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; margin-bottom: 1rem; resize: vertical; }
        .comment-form textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        @media (max-width: 900px) { .detail-container { grid-template-columns: 1fr; } .sidebar-card { order: -1; position: static; } }
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profil.php" style="display:flex; align-items:center; margin-right:1rem; color:var(--text-muted); font-size:0.9rem; text-decoration:none;">
                        👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <a href="publish.php" class="btn btn-primary">Publier</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline">Retour</a>
            </div>
        </div>
    </nav>

    <!-- Header Banner full width -->
    <div style="max-width:1280px; margin: 2rem auto 0; padding: 0 2rem;">
        <div class="header-banner">
            <span class="badge" style="background: rgba(255,255,255,0.2); color:white; margin-bottom: 1rem; display:inline-block; font-size:0.9rem; padding: 0.4rem 1rem;">
                <?= htmlspecialchars($resource['categorie']) ?>
            </span>
            <h1><?= htmlspecialchars($resource['titre']) ?></h1>
            <div class="header-meta">
                <span>👤 Par <?= htmlspecialchars($resource['auteur']) ?></span>
                <span>📅 Ajoute le <?= date('d/m/Y', strtotime($resource['date_ajout'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="detail-container">
        
        <div class="content-card">
            <h2>A propos de cette ressource</h2>
            <div class="description-text">
                <?= nl2br(htmlspecialchars($resource['description'] ?? 'Aucune description fournie.')) ?>
            </div>
            
            <!-- Comments Section -->
            <div style="margin-top: 3rem; border-top: 2px solid #f1f5f9; padding-top: 2rem;">
                <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem;">Espace Commentaires (<?= count($comments) ?>)</h3>
                
                <?php if(!empty($commentMessage)): ?>
                    <div style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
                        <?= htmlspecialchars($commentMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <form action="detail.php?id=<?= $id ?>" method="POST" class="comment-form">
                        <input type="hidden" name="action" value="comment">
                        <textarea name="contenu" placeholder="Ajouter un commentaire..." required></textarea>
                        <button type="submit" class="btn btn-primary">Publier le commentaire</button>
                    </form>
                <?php else: ?>
                    <div style="background:#f8fafc; padding:1.5rem; text-align:center; border-radius:6px; margin-bottom:2rem; border:1px solid var(--border-color);">
                        <p style="color:var(--text-muted); margin-bottom:1rem;">Vous devez etre connecte pour participer a la discussion.</p>
                        <a href="login.php" class="btn btn-outline" style="display:inline-block;">Se connecter</a>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 2rem;">
                    <?php if(empty($comments)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:2rem 0;">Aucun commentaire pour le moment. Soyez le premier !</p>
                    <?php else: ?>
                        <?php foreach($comments as $c): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <span class="comment-author"><?= htmlspecialchars($c['nom']) ?></span>
                                    <span class="comment-date"><?= date('d/m/Y \a H:i', strtotime($c['date_creation'])) ?></span>
                                </div>
                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($c['contenu'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="sidebar-card">
            <a href="detail.php?id=<?= $id ?>&action=download" class="btn btn-primary btn-block download-btn">
                ⬇️ Telecharger Fichier
            </a>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <p style="text-align:center; font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">
                    Vous telechargez en tant qu'invite. <a href="registre.php">Creez un compte</a> pour publier.
                </p>
            <?php endif; ?>

            <div class="stat-grid">
                <div class="stat-box">
                    <div class="value">⭐ <?= number_format($resource['note_moyenne'] ?? 0, 1) ?></div>
                    <div class="label">Note globale</div>
                </div>
                <div class="stat-box">
                    <div class="value">📥 <?= number_format($resource['nb_telechargements'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="label">Telechargements</div>
                </div>
            </div>

            <!-- Report or Delete Button -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['user_id'] == $resource['auteur_id'] || $_SESSION['role'] === 'admin'): ?>
                    <form action="mes_ressources.php" method="POST" style="text-align:center; margin-bottom:1.5rem;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="resource_id" value="<?= $id ?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:0.85rem; font-weight:bold; text-decoration:underline;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette ressource ?');">
                            🗑️ Supprimer ma ressource
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align:center; margin-bottom:1.5rem;">
                        <button onclick="openReportModal()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:0.85rem; text-decoration:underline;">
                            🚩 Signaler un problème
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="border-top: 1px solid var(--border-color); padding-top:1.5rem;">
                <h3 style="font-size:1rem; margin-bottom:0.75rem;">Details Techniques</h3>
                <ul style="list-style:none; padding:0; color:var(--text-muted); font-size:0.9rem; line-height:2;">
                    <li><strong>Licence :</strong> <?= htmlspecialchars($resource['licence']) ?></li>
                    <li><strong>Statut :</strong> <span style="color: green; font-weight:600;">Valide</span></li>
                </ul>
            </div>

            <?php if(isset($_SESSION['user_id'])): ?>
            <div style="border-top: 1px solid var(--border-color); padding-top:1.5rem; margin-top:1.5rem;">
                <h3 style="font-size:1rem; margin-bottom:0.75rem;">Noter cette ressource</h3>
                <div class="star-rating" id="star-rating" data-id="<?= $id ?>">
                    <span data-value="1">⭐</span>
                    <span data-value="2">⭐</span>
                    <span data-value="3">⭐</span>
                    <span data-value="4">⭐</span>
                    <span data-value="5">⭐</span>
                </div>
                <div id="rating-feedback" style="font-size:0.85rem; margin-top:10px; color:#16a34a;"></div>
            </div>
            
            <style>
                .star-rating span {
                    font-size: 1.5rem;
                    cursor: pointer;
                    filter: grayscale(100%);
                    transition: filter 0.2s;
                }
                .star-rating span:hover,
                .star-rating span.active {
                    filter: grayscale(0%);
                }
            </style>
            
            <script>
                const stars = document.querySelectorAll('.star-rating span');
                const ratingContainer = document.getElementById('star-rating');
                const resId = ratingContainer.dataset.id;
                const feedback = document.getElementById('rating-feedback');

                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        const ratingValue = this.dataset.value;
                        
                        // Highlight stars
                        stars.forEach(s => {
                            if (s.dataset.value <= ratingValue) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });

                        // Submit via AJAX
                        fetch('rate.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                resource_id: resId,
                                rating: ratingValue
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                feedback.style.color = '#16a34a';
                                feedback.textContent = data.message;
                                // Update global stat if element exists
                                const avgDisplay = document.querySelector('.stat-box .value');
                                if(avgDisplay) avgDisplay.innerHTML = '⭐ ' + data.new_average;
                            } else {
                                feedback.style.color = '#dc2626';
                                feedback.textContent = data.message;
                            }
                        })
                        .catch(error => {
                            feedback.style.color = '#dc2626';
                            feedback.textContent = "Erreur réseau.";
                        });
                    });
                });
            </script>
            <?php endif; ?>
        </aside>

    </main>

    <!-- Report Modal -->
    <div id="reportModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:white; padding:2rem; border-radius:var(--radius); width:100%; max-width:450px; box-shadow:var(--shadow-md);">
            <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem;">
                <h3 style="margin:0; font-size:1.2rem;">Signaler cette ressource</h3>
                <span onclick="closeReportModal()" style="cursor:pointer; font-size:1.5rem; color:#94a3b8;">&times;</span>
            </div>
            
            <div id="report-message" style="margin-bottom:1rem; font-size:0.9rem; padding:0.5rem; border-radius:4px; display:none;"></div>

            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600; font-size:0.9rem;">Motif du signalement :</label>
                <select id="report-motif" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:var(--radius); font-family:inherit;">
                    <option value="Contenu Inapproprie">Contenu Inapproprié</option>
                    <option value="Spam ou Publicite">Spam ou Publicité</option>
                    <option value="Lien mort">Lien mort / Fichier corrompu</option>
                    <option value="Droits d'auteur">Violation des droits d'auteur</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600; font-size:0.9rem;">Détails (Optionnel) :</label>
                <textarea id="report-details" rows="3" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:var(--radius); font-family:inherit; resize:vertical;" placeholder="Précisez votre signalement..."></textarea>
            </div>
            
            <button onclick="submitReport()" class="btn btn-primary" style="width:100%; background:#dc2626;">Envoyer le signalement</button>
        </div>
    </div>

    <script>
        const reportModal = document.getElementById('reportModal');
        
        function openReportModal() {
            reportModal.style.display = 'flex';
        }
        
        function closeReportModal() {
            reportModal.style.display = 'none';
            document.getElementById('report-message').style.display = 'none';
        }

        // Send report via AJAX to report.php
        function submitReport() {
            const motif = document.getElementById('report-motif').value;
            const details = document.getElementById('report-details').value;
            const resId = <?= $id ?>;
            const msgBox = document.getElementById('report-message');

            fetch('report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ resource_id: resId, motif: motif, details: details })
            })
            .then(response => response.json())
            .then(data => {
                msgBox.style.display = 'block';
                if(data.success) {
                    msgBox.style.backgroundColor = '#f0fdf4';
                    msgBox.style.color = '#166534';
                    msgBox.style.border = '1px solid #bbf7d0';
                    msgBox.textContent = data.message;
                    setTimeout(closeReportModal, 2500);
                } else {
                    msgBox.style.backgroundColor = '#fef2f2';
                    msgBox.style.color = '#991b1b';
                    msgBox.style.border = '1px solid #fecaca';
                    msgBox.textContent = data.message;
                }
            })
            .catch(error => {
                msgBox.style.display = 'block';
                msgBox.style.backgroundColor = '#fef2f2';
                msgBox.style.color = '#991b1b';
                msgBox.textContent = "Erreur de connexion serveur.";
            });
        }
    </script>
</body>
</html>
