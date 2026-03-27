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

$error = '';
$success = '';

// Liste des options (doit correspondre a la BD et index.php)
$categories_disponibles = ['Developpement', 'Design', 'Systeme', 'Business', 'Marketing'];
$licences_disponibles = ['MIT', 'CC-BY', 'GNU GPL', 'Domaine Public'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $categorie = $_POST['categorie'] ?? '';
    $licence = $_POST['licence'] ?? '';
    
    // File Validation
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fichier']['tmp_name'];
        $fileName = $_FILES['fichier']['name'];
        $fileSize = $_FILES['fichier']['size'];
        $fileType = $_FILES['fichier']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // Allowed extensions
        $allowedExts = ['zip', 'rar', 'pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
        
        if (in_array($fileExtension, $allowedExts)) {
            if ($fileSize < 50000000) { // Limit to 50MB
                
                // Construct safe upload path
                $uploadFileDir = 'uploads/';
                // Clean filename to prevent issues
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // File moved successfully, INSERT into database
                    if (isset($pdo)) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO ressources (titre, description, categorie, licence, fichier_url, auteur, auteur_id, statut) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
                            ");
                            
                            $stmt->execute([
                                $titre, 
                                $description, 
                                $categorie, 
                                $licence, 
                                $dest_path, 
                                $_SESSION['user_name'], 
                                $_SESSION['user_id']
                            ]);
                            
                            $success = "Ressource publiee avec succes !";
                            // Vider les champs pour eviter double soumission (facultatif)
                            $titre = $description = $categorie = $licence = '';
                        } catch (Exception $e) {
                            $error = "Erreur SQL : " . $e->getMessage();
                        }
                    }
                } else {
                    $error = "Une erreur est survenue lors de l'enregistrement du fichier sur le serveur. Verifiez les droits d'ecriture du dossier 'uploads'.";
                }
            } else {
                $error = "Le fichier depasse la limite de 50 Mo.";
            }
        } else {
            $error = "Extension de fichier non autorisee. " . implode(", ", $allowedExts) . " uniquement.";
        }
    } else {
        $error = "Veuillez selectionner un fichier valide sans erreur.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier une ressource - OpenShare</title>
    <!-- Google Fonts: Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .publish-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
            background: var(--bg-surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .publish-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .publish-header h1 {
            font-size: 1.8rem;
            color: var(--text-main);
        }
        
        .publish-header p {
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group-half {
            flex: 1;
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

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .file-upload-wrapper {
            position: relative;
            border: 2px dashed var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
            transition: var(--transition);
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: var(--primary-color);
            background: #eff6ff;
        }

        .file-upload-input {
            width: 100%;
            padding: 0.5rem;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
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
                <a href="profil.php" style="display:flex; align-items:center; margin-right:1rem; color:var(--text-muted); font-size:0.9rem; text-decoration:none;">
                    👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
                </a>
                <a href="index.php" class="btn btn-outline">Retour a l'accueil</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="publish-container">
        
        <div class="publish-header">
            <h1>Publier une ressource</h1>
            <p>Partagez vos fichiers, documents, et tutoriels avec la communaute.</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-warning">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?> 
                <br><a href="index.php" style="font-weight:bold; color:#15803d; text-decoration: underline; margin-top:0.5rem; display:inline-block;">Voir les ressources</a>
            </div>
        <?php endif; ?>

        <form action="publish.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="titre" class="form-label">Titre de la ressource</label>
                <input type="text" id="titre" name="titre" class="form-control" placeholder="Ex: Tutoriel React pour Debutants" value="<?= htmlspecialchars($titre ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group-half">
                    <label for="categorie" class="form-label">Categorie</label>
                    <select id="categorie" name="categorie" class="form-control" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach($categories_disponibles as $cat): ?>
                            <option value="<?= $cat ?>" <?= (isset($categorie) && $categorie === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group-half">
                    <label for="licence" class="form-label">Licence</label>
                    <select id="licence" name="licence" class="form-control" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach($licences_disponibles as $lic): ?>
                            <option value="<?= $lic ?>" <?= (isset($licence) && $licence === $lic) ? 'selected' : '' ?>><?= $lic ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description detaillee</label>
                <textarea id="description" name="description" class="form-control" placeholder="Decrivez le contenu de votre ressource..." required><?= htmlspecialchars($description ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Fichier a televerser (Max: 50Mo - PDF, ZIP, RAR, DOCX, JPG)</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="fichier" class="file-upload-input" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" style="padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">Publier la ressource</button>
            
        </form>

    </main>

</body>
</html>
