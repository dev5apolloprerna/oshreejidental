<?php
/**
 * migrate_patient_files.php
 * =========================================================================
 * ROOT-CAUSE FIX for missing patient x-ray/image files after the ID merger.
 *
 * WHAT HAPPENED
 * -------------
 * Before the merger, a patient's files were saved to:
 *     uploads/clients/{OLD_ID}/{file}
 * During the merger, tblcontacts.old_userid was correctly filled in with each
 * patient's pre-merger ID, and tblfiles.rel_id was correctly updated to the
 * new (current) ID. But nobody renamed the folders on disk - they are still
 * sitting under the OLD id, so the app (which now looks for
 * uploads/clients/{NEW_ID}/{file}) can't find them.
 *
 * WHAT THIS SCRIPT DOES
 * ----------------------
 * For every patient with a non-zero old_userid:
 *   1. If uploads/clients/{OLD_ID}/ exists and uploads/clients/{NEW_ID}/
 *      does NOT exist -> rename the folder OLD_ID -> NEW_ID.
 *   2. If BOTH folders exist (e.g. a new file was already uploaded under the
 *      current id before this script ran) -> move every file from the OLD_ID
 *      folder into the NEW_ID folder, renaming on filename collision so
 *      nothing is overwritten, then remove the now-empty OLD_ID folder.
 *   3. If neither folder exists, or only the NEW_ID folder exists -> nothing
 *      to do, logged as SKIP.
 *
 * It is SAFE TO RE-RUN. Already-migrated patients are simply skipped.
 *
 * USAGE
 * -----
 *   1. Copy this file to your server, in the SAME directory that contains
 *      the "uploads" folder (normally your CodeIgniter/Perfex webroot).
 *   2. Fill in the DB credentials below (or pass them as CLI flags).
 *   3. DRY RUN FIRST (default - makes no changes):
 *          php migrate_patient_files.php
 *      Review the printed summary and the generated
 *      migration_report_TIMESTAMP.csv file.
 *   4. When you're happy with the plan, actually apply it:
 *          php migrate_patient_files.php --live
 *
 * CLI OPTIONS
 * -----------
 *   --live                 Actually perform renames/moves (default: dry run)
 *   --path=/abs/path       Path to the "uploads/clients" folder
 *                          (default: <this script's dir>/uploads/clients)
 *   --db-host=HOST
 *   --db-user=USER
 *   --db-pass=PASS
 *   --db-name=NAME
 *   --report=FILE.csv      Where to write the CSV log
 *
 * IMPORTANT: Back up the uploads/clients folder (and the database) before
 * running with --live.
 * =========================================================================
 */

error_reporting(E_ALL & ~E_DEPRECATED);

// ---------------------------------------------------------------------
// 1) CONFIG - fill these in, or override on the command line
// ---------------------------------------------------------------------
$DB_HOST = 'localhost';
$DB_USER = 'CHANGE_ME';
$DB_PASS = 'CHANGE_ME';
$DB_NAME = 'CHANGE_ME';
$DB_PORT = 3306;

$TABLE_PREFIX = 'tbl'; // matches db_prefix() in the app

// ---------------------------------------------------------------------
// 2) Parse CLI args
// ---------------------------------------------------------------------
$options = getopt('', ['live', 'path:', 'db-host:', 'db-user:', 'db-pass:', 'db-name:', 'report:']);

$isLive     = array_key_exists('live', $options);
$basePath   = $options['path'] ?? (__DIR__ . '/uploads/clients');
$DB_HOST    = $options['db-host'] ?? $DB_HOST;
$DB_USER    = $options['db-user'] ?? $DB_USER;
$DB_PASS    = $options['db-pass'] ?? $DB_PASS;
$DB_NAME    = $options['db-name'] ?? $DB_NAME;
$reportFile = $options['report'] ?? (__DIR__ . '/migration_report_' . date('Ymd_His') . '.csv');

$basePath = rtrim($basePath, '/');

fwrite(STDOUT, "===========================================================\n");
fwrite(STDOUT, ' Patient file migration - ' . ($isLive ? 'LIVE RUN' : 'DRY RUN (no changes will be made)') . "\n");
fwrite(STDOUT, " Uploads path: $basePath\n");
fwrite(STDOUT, "===========================================================\n\n");

if (!$isLive) {
    fwrite(STDOUT, "This is a DRY RUN. Nothing on disk will be changed.\n");
    fwrite(STDOUT, "Re-run with --live once you've reviewed the plan below.\n\n");
}

if (!is_dir($basePath)) {
    fwrite(STDERR, "ERROR: uploads path not found: $basePath\n");
    fwrite(STDERR, "Pass the correct location with --path=/full/path/to/uploads/clients\n");
    exit(1);
}

// ---------------------------------------------------------------------
// 3) Connect to the DB and pull the old_id -> new_id map
// ---------------------------------------------------------------------
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "ERROR: DB connection failed: " . $mysqli->connect_error . "\n");
    fwrite(STDERR, "Fill in the DB_* constants at the top of this script, or pass --db-host --db-user --db-pass --db-name\n");
    exit(1);
}

$contactsTable = $TABLE_PREFIX . 'contacts';
$sql = "SELECT DISTINCT userid AS new_id, old_userid AS old_id
        FROM `$contactsTable`
        WHERE old_userid > 0 AND old_userid <> userid
        ORDER BY userid ASC";

$result = $mysqli->query($sql);
if (!$result) {
    fwrite(STDERR, "ERROR: query failed: " . $mysqli->error . "\n");
    exit(1);
}

$pairs = [];
$conflicts = [];
while ($row = $result->fetch_assoc()) {
    $newId = (int) $row['new_id'];
    $oldId = (int) $row['old_id'];
    if (isset($pairs[$newId]) && $pairs[$newId] !== $oldId) {
        $conflicts[] = [$newId, $pairs[$newId], $oldId];
        continue;
    }
    $pairs[$newId] = $oldId;
}

fwrite(STDOUT, 'Found ' . count($pairs) . " patients with a recorded pre-merger id.\n");
if ($conflicts) {
    fwrite(STDOUT, count($conflicts) . " patient(s) have CONFLICTING old ids across multiple contact rows - skipped, review manually:\n");
    foreach ($conflicts as [$newId, $old1, $old2]) {
        fwrite(STDOUT, "  new_id=$newId has old_id=$old1 and old_id=$old2\n");
    }
}
fwrite(STDOUT, "\n");

// ---------------------------------------------------------------------
// 4) Helpers
// ---------------------------------------------------------------------
function unique_target_path(string $dir, string $filename): string
{
    $target = $dir . '/' . $filename;
    if (!file_exists($target)) {
        return $target;
    }
    $info = pathinfo($filename);
    $name = $info['filename'];
    $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';
    $i = 1;
    do {
        $candidate = $dir . '/' . $name . '_frommerge' . $i . $ext;
        $i++;
    } while (file_exists($candidate));
    return $candidate;
}

function merge_directory(string $oldDir, string $newDir, bool $live, array &$detailLog): int
{
    $moved = 0;
    $items = scandir($oldDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $srcPath = $oldDir . '/' . $item;
        if (is_dir($srcPath)) {
            $destSubDir = $newDir . '/' . $item;
            if ($live && !is_dir($destSubDir)) {
                mkdir($destSubDir, 0755, true);
            }
            $moved += merge_directory($srcPath, $destSubDir, $live, $detailLog);
            continue;
        }
        $destPath = unique_target_path($newDir, $item);
        if ($destPath !== $newDir . '/' . $item) {
            $detailLog[] = "collision: $item -> " . basename($destPath);
        }
        if ($live) {
            rename($srcPath, $destPath);
        }
        $moved++;
    }
    return $moved;
}

// ---------------------------------------------------------------------
// 5) Process each patient
// ---------------------------------------------------------------------
$csv = fopen($reportFile, 'w');
fputcsv($csv, ['new_id', 'old_id', 'action', 'files_affected', 'detail', 'live_run']);

$counts = ['RENAME' => 0, 'MERGE' => 0, 'SKIP_NO_OLD_FOLDER' => 0, 'SKIP_ALREADY_NEW' => 0];

foreach ($pairs as $newId => $oldId) {
    $oldDir = $basePath . '/' . $oldId;
    $newDir = $basePath . '/' . $newId;

    $oldExists = is_dir($oldDir);
    $newExists = is_dir($newDir);

    if (!$oldExists) {
        $counts['SKIP_NO_OLD_FOLDER']++;
        fputcsv($csv, [$newId, $oldId, 'SKIP_NO_OLD_FOLDER', 0, 'no folder found for old id', $isLive ? 1 : 0]);
        continue;
    }

    if ($oldExists && !$newExists) {
        $fileCount = count(array_diff(scandir($oldDir), ['.', '..']));
        fwrite(STDOUT, "RENAME  clients/$oldId  ->  clients/$newId   ($fileCount item(s))\n");
        if ($isLive) {
            rename($oldDir, $newDir);
        }
        $counts['RENAME']++;
        fputcsv($csv, [$newId, $oldId, 'RENAME', $fileCount, '', $isLive ? 1 : 0]);
        continue;
    }

    if ($oldExists && $newExists) {
        $detailLog = [];
        $fileCount = merge_directory($oldDir, $newDir, $isLive, $detailLog);
        fwrite(STDOUT, "MERGE   clients/$oldId  into clients/$newId   ($fileCount item(s))" . ($detailLog ? ' [' . implode('; ', $detailLog) . ']' : '') . "\n");
        if ($isLive) {
            // remove old dir (and any now-empty subdirs) once emptied
            @rmdir($oldDir);
        }
        $counts['MERGE']++;
        fputcsv($csv, [$newId, $oldId, 'MERGE', $fileCount, implode('; ', $detailLog), $isLive ? 1 : 0]);
        continue;
    }
}

fclose($csv);

fwrite(STDOUT, "\n===========================================================\n");
fwrite(STDOUT, " Summary\n");
fwrite(STDOUT, "===========================================================\n");
foreach ($counts as $label => $n) {
    fwrite(STDOUT, str_pad($label, 22) . ": $n\n");
}
fwrite(STDOUT, "\nDetailed report written to: $reportFile\n");
if (!$isLive) {
    fwrite(STDOUT, "\nThis was a DRY RUN - no files were moved. Re-run with --live to apply.\n");
}
