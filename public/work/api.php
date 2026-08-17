<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function is_authorized(string $password): bool
{
    if ($password === '') {
        return true;
    }

    return isset($_SESSION['e360tv_checklist_authorized'])
        && $_SESSION['e360tv_checklist_authorized'] === true;
}

function ensure_storage(string $stateFile, string $backupDir): void
{
    $dir = dirname($stateFile);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        respond(['ok' => false, 'error' => 'Unable to create the state directory.'], 500);
    }

    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        respond(['ok' => false, 'error' => 'Unable to create the backup directory.'], 500);
    }

    if (!file_exists($stateFile)) {
        if (file_put_contents($stateFile, "{}\n", LOCK_EX) === false) {
            respond(['ok' => false, 'error' => 'Unable to create the state file.'], 500);
        }
    }
}

function read_state_locked(string $stateFile): array
{
    $handle = fopen($stateFile, 'c+');
    if ($handle === false) {
        respond(['ok' => false, 'error' => 'Unable to open the state file.'], 500);
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            respond(['ok' => false, 'error' => 'Unable to lock the state file for reading.'], 500);
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $decoded = json_decode($raw ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function update_state_locked(string $stateFile, callable $updater): array
{
    $handle = fopen($stateFile, 'c+');
    if ($handle === false) {
        respond(['ok' => false, 'error' => 'Unable to open the state file.'], 500);
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            respond(['ok' => false, 'error' => 'Unable to lock the state file for writing.'], 500);
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $state = json_decode($raw ?: '{}', true);
        if (!is_array($state)) {
            $state = [];
        }

        $state = $updater($state);
        $encoded = json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($encoded === false) {
            respond(['ok' => false, 'error' => 'Unable to encode checklist state.'], 500);
        }

        rewind($handle);
        if (!ftruncate($handle, 0)) {
            respond(['ok' => false, 'error' => 'Unable to clear the state file.'], 500);
        }

        if (fwrite($handle, $encoded . "\n") === false) {
            respond(['ok' => false, 'error' => 'Unable to write the state file.'], 500);
        }

        fflush($handle);
        return $state;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

if (!is_authorized($CHECKLIST_PASSWORD)) {
    respond(['ok' => false, 'error' => 'Unauthorized.'], 401);
}

ensure_storage($STATE_FILE, $BACKUP_DIR);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respond([
        'ok' => true,
        'state' => read_state_locked($STATE_FILE),
        'server_time' => gmdate('c'),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    respond(['ok' => false, 'error' => 'JSON request required.'], 415);
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '', true);
if (!is_array($input)) {
    respond(['ok' => false, 'error' => 'Invalid JSON.'], 400);
}

$csrf = (string)($input['csrf'] ?? '');
if (
    !isset($_SESSION['e360tv_checklist_csrf'])
    || !hash_equals((string)$_SESSION['e360tv_checklist_csrf'], $csrf)
) {
    respond(['ok' => false, 'error' => 'Security token failed. Reload the page.'], 403);
}

$action = (string)($input['action'] ?? '');

if ($action === 'save_task') {
    $taskId = trim((string)($input['task_id'] ?? ''));
    $status = trim((string)($input['status'] ?? 'Not Started'));
    $notes = trim((string)($input['notes'] ?? ''));
    $updatedBy = trim((string)($input['updated_by'] ?? 'Anonymous'));

    $allowedStatuses = [
        'Not Started',
        'In Progress',
        'Blocked',
        'Ready for Retest',
        'Done',
        'Not Applicable',
    ];

    if (!preg_match('/^[A-Z]{2,4}-\d{2}$/', $taskId)) {
        respond(['ok' => false, 'error' => 'Invalid task ID.'], 400);
    }

    if (!in_array($status, $allowedStatuses, true)) {
        respond(['ok' => false, 'error' => 'Invalid status.'], 400);
    }

    if (strlen($notes) > 15000) {
        respond(['ok' => false, 'error' => 'Notes are too long.'], 400);
    }

    if ($updatedBy === '') {
        $updatedBy = 'Anonymous';
    }
    $updatedBy = function_exists('mb_substr')
        ? mb_substr($updatedBy, 0, 80)
        : substr($updatedBy, 0, 80);

    $savedAt = gmdate('c');

    $state = update_state_locked(
        $STATE_FILE,
        static function (array $state) use ($taskId, $status, $notes, $updatedBy, $savedAt): array {
            $state[$taskId] = [
                'status' => $status,
                'done' => $status === 'Done',
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $savedAt,
            ];
            return $state;
        }
    );

    respond([
        'ok' => true,
        'task' => $state[$taskId],
        'server_time' => $savedAt,
    ]);
}

if ($action === 'reset_all') {
    if (($input['confirm'] ?? '') !== 'RESET') {
        respond(['ok' => false, 'error' => 'Reset confirmation is required.'], 400);
    }

    $currentState = read_state_locked($STATE_FILE);
    if ($currentState !== []) {
        $backupName = $BACKUP_DIR . '/state-' . gmdate('Ymd-His') . '.json';
        file_put_contents(
            $backupName,
            json_encode(
                $currentState,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) . "\n",
            LOCK_EX
        );
    }

    update_state_locked($STATE_FILE, static fn(array $state): array => []);

    respond([
        'ok' => true,
        'state' => [],
        'server_time' => gmdate('c'),
    ]);
}

respond(['ok' => false, 'error' => 'Unknown action.'], 400);
