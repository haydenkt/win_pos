<?php

function auditLog(
    mysqli $conn,
    string $action,
    string $entityType,
    $entityId,
    string $summary,
    ?array $oldData = null,
    ?array $newData = null
): void {
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $username = (string) ($_SESSION['user'] ?? 'System');
    $entityId = $entityId === null ? null : (string) $entityId;
    $oldJson = $oldData === null ? null : json_encode($oldData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $newJson = $newData === null ? null : json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

    $stmt = $conn->prepare(
        'INSERT INTO audit_logs
        (user_id, username, action, entity_type, entity_id, summary, old_data, new_data, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('issssssss', $userId, $username, $action, $entityType, $entityId, $summary, $oldJson, $newJson, $ip);
    $stmt->execute();
    $stmt->close();
}
