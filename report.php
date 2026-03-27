<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour signaler une ressource.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $resId = $data['resource_id'] ?? null;
    $motif = $data['motif'] ?? '';
    $details = $data['details'] ?? '';
    $userId = $_SESSION['user_id'];

    if (!$resId || empty($motif)) {
        echo json_encode(['success' => false, 'message' => 'Données incomplètes.']);
        exit;
    }

    try {
        // Check if resource exists
        $stmt = $pdo->prepare("SELECT id FROM ressources WHERE id = ?");
        $stmt->execute([$resId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ressource introuvable.']);
            exit;
        }

        // Insert report
        $stmtInsert = $pdo->prepare("INSERT INTO signalements (user_id, ressource_id, motif, details) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$userId, $resId, $motif, $details]);

        echo json_encode(['success' => true, 'message' => 'Signalement envoyé à l\'équipe de modération. Merci !']);
        
    } catch (PDOException $e) {
        // SQLSTATE 23000 = Integrity constraint violation (Duplicate entry)
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Vous avez déjà signalé cette ressource.']);
        } else {
            error_log($e->getMessage()); 
            echo json_encode(['success' => false, 'message' => 'Erreur technique. Assurez-vous d\'avoir créé la table des signalements.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
