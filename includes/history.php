<?php
declare(strict_types=1);

function log_history(int $project_id, int $user_id, string $action, array $details = []): void
{
    $stmt = db()->prepare(
        'INSERT INTO history_entries (project_id, user_id, action, details_json, created_at)
         VALUES (:project_id, :user_id, :action, :details_json, NOW())'
    );
    $stmt->execute([
        ':project_id' => $project_id,
        ':user_id' => $user_id,
        ':action' => $action,
        ':details_json' => json_encode($details, JSON_UNESCAPED_UNICODE),
    ]);
}
