<?php
/**
 * Chandila Web Development – Project Actions AJAX Handler
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/whatsapp_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$currentUser = getCurrentUser();
$action = $_POST['action'] ?? '';

// 1. Toggle Milestone Status
if ($action === 'toggle_milestone') {
    $milestoneId = (int)($_POST['milestone_id'] ?? 0);
    $isCompleted = !empty($_POST['is_completed']) ? 1 : 0;

    if (!$milestoneId) {
        echo json_encode(['success' => false, 'message' => 'Invalid milestone ID.']);
        exit;
    }

    // Verify permission: if user is developer, verify project is assigned to them
    $stmt = $pdo->prepare("
        SELECT m.*, p.job_id, p.assigned_developer_id 
        FROM project_milestones m 
        JOIN projects p ON m.project_id = p.id 
        WHERE m.id = ?
    ");
    $stmt->execute([$milestoneId]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        echo json_encode(['success' => false, 'message' => 'Milestone not found.']);
        exit;
    }

    if ($currentUser['role'] === 'developer' && $milestone['assigned_developer_id'] != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied: project not assigned to you.']);
        exit;
    }

    $completedAt = $isCompleted ? date('Y-m-d H:i:s') : null;
    $upStmt = $pdo->prepare("UPDATE project_milestones SET is_completed = ?, completed_at = ? WHERE id = ?");
    $upStmt->execute([$isCompleted, $completedAt, $milestoneId]);

    // Log activity
    $actionDesc = $isCompleted ? "Marked milestone '{$milestone['milestone_name']}' as completed." : "Reopened milestone '{$milestone['milestone_name']}'.";
    logActivity($pdo, $milestone['job_id'], (int)$milestone['project_id'], 'Milestone Updated', $actionDesc, $currentUser['full_name']);

    echo json_encode(['success' => true, 'is_completed' => $isCompleted]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);

