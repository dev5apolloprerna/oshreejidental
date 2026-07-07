<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Read branch from GET (for testing) or Cookie.
 * But NEVER use raw cookie as DB name.
 */
$branch_db = '';
if (!empty($_GET['branch_db'])) {
    $branch_db = trim($_GET['branch_db']);
} elseif (!empty($_COOKIE['branch'])) {
    $branch_db = trim($_COOKIE['branch']);
}

$CI =& get_instance();

/**
 * Get base db config and override safely.
 * NOTE: make sure $config['config_db'] exists in your config.
 */
$config_db = $CI->config->config['config_db'];

/**
 * ✅ Allow only these databases (WHITELIST)
 * key = db name, value = [username, password]
 */
$allowed = [
    'u614622744_main_db'     => ['username' => 'u614622744_main_db',     'password' => 'b/K7;5&OeZ?'],
    'u614622744_maninagar_db'   => ['username' => 'u614622744_maninagar_db',   'password' => 'unC[G$q$pN2['],
    'u614622744_satellite_db'   => ['username' => 'u614622744_satellite_db',   'password' => 'zWvwu$!gS$6&'],
    'u614622744_iskon_ambli_db'       => ['username' => 'u614622744_iskon_ambli_db',       'password' => 'zWvwu$!gS$6&'],
];

$main_db = isset($config_db['database']) ? $config_db['database'] : '';
$main_db_user = isset($config_db['username']) ? $config_db['username'] : '';
$main_db_pass = isset($config_db['password']) ? $config_db['password'] : '';
$allowed[$main_db] = ['username' => $main_db_user, 'password' => $main_db_pass];

if ($branch_db !== '' && preg_match('/^[A-Za-z0-9_]+$/', $branch_db)) {
    $mysqli = @new mysqli($config_db['hostname'], $main_db_user, $main_db_pass, $main_db);
    if (!$mysqli->connect_errno) {
        $table = db_prefix() . 'branch';
        $branch_db_escaped = $mysqli->real_escape_string($branch_db);
        $result = $mysqli->query("SELECT branch_db FROM `{$table}` WHERE branch_db = '{$branch_db_escaped}' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $allowed[$branch_db] = ['username' => APP_DB_USERNAME, 'password' => APP_DB_PASSWORD];
            $result->free();
        }
        $mysqli->close();
    }
}


/**
 * If cookie has invalid db → fallback to main db
 */
if (empty($branch_db) || !isset($allowed[$branch_db])) {
    $branch_db = $main_db;

    // optional: clear bad cookie so it doesn't keep breaking
    if (isset($_COOKIE['branch'])) {
        setcookie('branch', '', time() - 3600, '/');
        unset($_COOKIE['branch']);
    }
}


$config_db['username'] = $allowed[$branch_db]['username'];
$config_db['password'] = $allowed[$branch_db]['password'];
$config_db['database'] = $branch_db;

$CI->db = $CI->load->database($config_db, true);
