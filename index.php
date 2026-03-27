<?php
session_start();
// Check if db.php file exists and load it to suppress errors gracefully if missing DB configured
$hasDb = file_exists(__DIR__ . '/includes/db.php');
if ($hasDb) {
    require_once __DIR__ . '/includes/db.php';
}

// Initialiser les filtres
$searchTitre = $_GET['titre'] ?? '';
$searchCategorie = $_GET['categorie'] ?? []; // Tableau si plusieurs cases cochees
$searchLicence = $_GET['licence'] ?? '';
$searchNote = $_GET['note'] ?? '';
$searchDate = $_GET['date'] ?? '';

// Simulation de donnees (A remplacer par une requete BDD quand la DB Openshare est prete)
// Si la connexion PDO $pdo est definie, on l'utilise
$resources = [];

if (isset($pdo)) {
    try {
        // === 1. PREPARER LES FILTRES (WHERE CLAUSES) ===
        $whereSql = "WHERE statut = 'valide'";
        $params = [];
        
        if (!empty($searchTitre)) {
            $whereSql .= " AND titre LIKE ?";
            $params[] = '%' . $searchTitre . '%';
        }
        
        // Filtre categorie multiple
        if (!empty($searchCategorie) && is_array($searchCategorie)) {
            $inQuery = implode(',', array_fill(0, count($searchCategorie), '?'));
            $whereSql .= " AND categorie IN ($inQuery)";
            $params = array_merge($params, $searchCategorie);
        }
        
        if (!empty($searchLicence)) {
            $whereSql .= " AND licence = ?";
            $params[] = $searchLicence;
        }
        
        if (!empty($searchNote)) {
            $whereSql .= " AND note_moyenne >= ?";
            $params[] = $searchNote;
        }

        // === 2. COMPTER LE TOTAL POUR LA PAGINATION ===
        $limit = 9; // Nombre de ressources par page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        $countSql = "SELECT COUNT(*) FROM ressources " . $whereSql;
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $totalItems = $stmtCount->fetchColumn();
        
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;

        // === 3. RECUPERER LES RESSOURCES DE LA PAGE ===
        // Ordre : plus telecharges en premier. Toujours executer les params dans le meme ordre.
        $sql = "SELECT * FROM ressources " . $whereSql . " ORDER BY nb_telechargements DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $pdo->prepare($sql);
        // Bind the parameters manually because LIMIT/OFFSET cannot be easily bound in arrays in all PDO setups when combined with positional (?) params
        foreach ($params as $k => $param) {
            $stmt->bindValue($k + 1, $param);
        }
        $stmt->execute();
        
        $resources = $stmt->fetchAll();
    } catch (Exception $e) {
        $dbError = "Erreur de requete : " . $e->getMessage();
    }
} else {
    // Dummy data pour voir le design en attendant la vraie BD
    $resources = [
        ['id' => 1, 'titre' => 'Tutoriel PHP Avance', 'categorie' => 'Developpement', 'licence' => 'MIT', 'note_moyenne' => 4.8, 'nb_telechargements' => 1250, 'date_ajout' => '2023-10-15', 'auteur' => 'Alice'],
        ['id' => 2, 'titre' => 'Assets Graphiques UI Kit', 'categorie' => 'Design', 'licence' => 'CC-BY', 'note_moyenne' => 4.5, 'nb_telechargements' => 980, 'date_ajout' => '2023-11-02', 'auteur' => 'Bob'],
        ['id' => 3, 'titre' => 'Introduction a Python', 'categorie' => 'Developpement', 'licence' => 'GNU GPL', 'note_moyenne' => 4.2, 'nb_telechargements' => 850, 'date_ajout' => '2024-01-20', 'auteur' => 'Charlie'],
        ['id' => 4, 'titre' => 'Templates de CV Modernes', 'categorie' => 'Business', 'licence' => 'Domaine Public', 'note_moyenne' => 4.9, 'nb_telechargements' => 2100, 'date_ajout' => '2023-09-10', 'auteur' => 'Diana'],
        ['id' => 5, 'titre' => 'Manuel de Securite Reseau', 'categorie' => 'Systeme', 'licence' => 'MIT', 'note_moyenne' => 4.1, 'nb_telechargements' => 450, 'date_ajout' => '2024-02-15', 'auteur' => 'Eve'],
        ['id' => 6, 'titre' => 'Icones Vectorielles Minimalistes', 'categorie' => 'Design', 'licence' => 'CC-BY', 'note_moyenne' => 4.6, 'nb_telechargements' => 1120, 'date_ajout' => '2024-03-01', 'auteur' => 'Frank']
    ];
}

// Liste des categories pour la sidebar
$categories_disponibles = ['Developpement', 'Design', 'Systeme', 'Business', 'Marketing'];
$licences_disponibles = ['MIT', 'CC-BY', 'GNU GPL', 'Domaine Public'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShare - Plateforme de Ressources</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 0.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .pagination a {
            background: white;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .pagination a:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .pagination .active {
            background: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        .pagination .disabled {
            background: #f1f5f9;
            color: #94a3b8;
            border: 1px solid var(--border-color);
            cursor: not-allowed;
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profil.php" style="display:flex; align-items:center; margin-right:1rem; color:var(--text-muted); font-size:0.9rem; text-decoration:none;">
                        👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <a href="publish.php" class="btn btn-primary">Publier</a>
                    <a href="includes/auth.php?action=logout" class="btn btn-outline">Deconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                    <a href="registre.php" class="btn btn-primary">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-container">
        
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            <h2 class="sidebar-title">Filtres</h2>
            
            <form action="index.php" method="GET" id="filter-form">
                
                <!-- On conserve le titre de recherche s'il y en a un -->
                <?php if(!empty($searchTitre)): ?>
                    <input type="hidden" name="titre" value="<?= htmlspecialchars($searchTitre) ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <h3>Categories</h3>
                    <div class="filter-options">
                        <?php foreach($categories_disponibles as $cat): ?>
                        <label class="checkbox-container">
                            <input type="checkbox" name="categorie[]" value="<?= $cat ?>" <?= (in_array($cat, $searchCategorie)) ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            <?= $cat ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Licence</h3>
                    <div class="filter-options">
                        <label class="radio-container">
                            <input type="radio" name="licence" value="" <?= empty($searchLicence) ? 'checked' : '' ?>>
                            <span class="radiomark"></span>
                            Toutes
                        </label>
                        <?php foreach($licences_disponibles as $lic): ?>
                        <label class="radio-container">
                            <input type="radio" name="licence" value="<?= $lic ?>" <?= ($searchLicence === $lic) ? 'checked' : '' ?>>
                            <span class="radiomark"></span>
                            <?= $lic ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Note Minimale</h3>
                    <select name="note" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les notes</option>
                        <option value="4" <?= ($searchNote == '4') ? 'selected' : '' ?>>4 Etoiles et +</option>
                        <option value="3" <?= ($searchNote == '3') ? 'selected' : '' ?>>3 Etoiles et +</option>
                        <option value="2" <?= ($searchNote == '2') ? 'selected' : '' ?>>2 Etoiles et +</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;">Appliquer les filtres</button>
            </form>
        </aside>

        <!-- Content Area -->
        <section class="content-area">
            
            <!-- Search Bar -->
            <div class="search-header">
                <form action="index.php" method="GET" class="search-form">
                    <!-- Conserve filter states if submitted -->
                    <?php 
                    if(!empty($searchCategorie)) {
                        foreach($searchCategorie as $cat) {
                            echo '<input type="hidden" name="categorie[]" value="'.htmlspecialchars($cat).'">';
                        }
                    }
                    if(!empty($searchLicence)) echo '<input type="hidden" name="licence" value="'.htmlspecialchars($searchLicence).'">';
                    if(!empty($searchNote)) echo '<input type="hidden" name="note" value="'.htmlspecialchars($searchNote).'">';
                    ?>
                    
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="titre" class="search-input" placeholder="Rechercher une ressource..." value="<?= htmlspecialchars($searchTitre) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </form>
            </div>

            <?php if(isset($dbError)): ?>
                <div class="alert alert-warning">
                    <?= $dbError ?> <br><small>Affichage de donnees de test en attendant que la table "resources" soit creee.</small>
                </div>
            <?php endif; ?>

            <!-- Resource Grid -->
            <div class="resource-grid">
                <?php if(empty($resources)): ?>
                    <div class="empty-state">
                        <p>Aucune ressource trouvee pour ces criteres.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($resources as $res): ?>
                    <div class="card">
                        <div class="card-header">
                            <span class="badge badge-category"><?= htmlspecialchars($res['categorie']) ?></span>
                            <span class="badge badge-rating">⭐ <?= number_format($res['note_moyenne'], 1) ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($res['titre']) ?></h3>
                            <div class="card-meta">
                                <span>👤 <?= htmlspecialchars($res['auteur']) ?></span>
                                <span>📅 <?= date('d/m/Y', strtotime($res['date_ajout'])) ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="stats">
                                <span class="stat-item">📥 <?= number_format($res['nb_telechargements'], 0, ',', ' ') ?></span>
                                <span class="stat-item licence-text">📄 <?= htmlspecialchars($res['licence']) ?></span>
                            </div>
                            <a href="detail.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline">Voir Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Section Tutoriels YouTube -->
            <div id="youtube-section" style="margin-top: 3rem; display: none;">
                <div class="search-header" style="justify-content: flex-start; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.5rem; color: var(--text-main);">Tutoriels YouTube Associés</h2>
                </div>
                <div id="youtube-loader" style="display: none; text-align: center; padding: 2rem;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
                    <p style="margin-top: 1rem; color: var(--text-muted);">Recherche de tutoriels en cours...</p>
                </div>
                <div id="youtube-error" class="alert alert-warning" style="display: none;"></div>
                <div id="youtube-grid" class="resource-grid"></div>
            </div>

            <!-- Pagination Block -->
            <?php if(isset($totalPages) && $totalPages > 1): ?>
                <div class="pagination">
                    <?php 
                        // Preparer les parametres de recherche courants pour les liens de pagination
                        $queryParams = $_GET;
                        // On retire la page actuelle de l'array
                        unset($queryParams['page']);
                        $queryString = http_build_query($queryParams);
                        $queryPrefix = !empty($queryString) ? "&" . $queryString : "";
                    ?>

                    <!-- Bouton Precedent -->
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $queryPrefix ?>">&laquo; Précédent</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Précédent</span>
                    <?php endif; ?>

                    <!-- Numeros de page -->
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= $queryPrefix ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Bouton Suivant -->
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $queryPrefix ?>">Suivant &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Suivant &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </section>
    </main>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/youtube.js"></script>
</body>
</html>
