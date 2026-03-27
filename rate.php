<?php
session_start();
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour voter.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $resId = $data['resource_id'] ?? null;
    $rating = $data['rating'] ?? null;
    $userId = $_SESSION['user_id'];

    if (!$resId || !$rating || !is_numeric($rating) || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Données invalides.']);
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

        // Insert or update rating
        $stmt = $pdo->prepare("
            INSERT INTO evaluations (user_id, ressource_id, note) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE note = ?
        ");
        $stmt->execute([$userId, $resId, $rating, $rating]);

        // Calculate new average
        $stmtAvg = $pdo->prepare("SELECT AVG(note) as moyenne FROM evaluations WHERE ressource_id = ?");
        $stmtAvg->execute([$resId]);
        $newAvg = $stmtAvg->fetchColumn();

        // Update resource average
        $stmtUpdate = $pdo->prepare("UPDATE ressources SET note_moyenne = ? WHERE id = ?");
        $stmtUpdate->execute([$newAvg, $resId]);

        echo json_encode([
            'success' => true, 
            'message' => 'Merci pour votre vote !', 
            'new_average' => number_format($newAvg, 1)
        ]);
        
    } catch (Exception $e) {
        // Return JSON error, but log the real SQL error gracefully without revealing schema details to user ideally
        error_log($e->getMessage()); 
        echo json_encode(['success' => false, 'message' => 'Une erreur SQL est survenue. Vérifiez la table "evaluations".']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
