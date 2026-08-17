<?php
declare(strict_types=1);

/*
 * OPTIONAL PASSWORD
 * Leave blank for no password.
 * For a private team page, set a password before uploading.
 */
$CHECKLIST_PASSWORD = 'stream';

/*
 * Shared state storage.
 * Keep this in the protected /private folder.
 */
$STATE_FILE = __DIR__ . '/private/e360tv-checklist-state.json';

/*
 * Backups are created before a full reset.
 */
$BACKUP_DIR = __DIR__ . '/private/backups';
