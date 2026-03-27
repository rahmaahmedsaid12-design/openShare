<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour sauvegarder des tutoriels.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_youtube') {
    $userId = $_SESSION['user_id'];
    $youtubeId = $_POST['youtube_id'] ?? '';
    $titre = $_POST['titre'] ?? '';
    $nomChaine = $_POST['nom_chaine'] ?? '';

    if (empty($youtubeId) || empty($titre)) {
        echo json_encode(['success' => false, 'message' => 'Données vidéo incomplètes.']);
        exit;
    }

    try {
        // Vérifier si la vidéo est déjà sauvegardée par l'utilisateur
        $stmtCheck = $pdo->prepare("SELECT id FROM tutoriels_sauvegardes WHERE user_id = ? AND youtube_id = ?");
        $stmtCheck->execute([$userId, $youtubeId]);
        
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ce tutoriel est déjà dans vos favoris.']);
            exit;
        }

        // Insérer la sauvegarde
        $stmt = $pdo->prepare("INSERT INTO tutoriels_sauvegardes (user_id, youtube_id, titre, nom_chaine) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $youtubeId, $titre, $nomChaine]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}
