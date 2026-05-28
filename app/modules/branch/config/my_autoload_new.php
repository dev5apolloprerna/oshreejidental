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

/**
 * ✅ Allow only these databases (WHITELIST)
 * key = db name, value = [username, password]
 */
$allowed = [
    'u614622744_main_db'     => ['username' => 'u614622744_main_db',     'password' => ''],
    'u614622744_maninagar_db'   => ['username' => 'u614622744_maninagar_db',   'password' => 'unC[G$q$pN2['],
    'u614622744_satellite_db'   => ['username' => 'u614622744_satellite_db',   'password' => 'zWvwu$!gS$6&'],
    'u614622744_iskon_ambli_db'       => ['username' => 'u614622744_iskon_ambli_db',       'password' => 'zWvwu$!gS$6&'],
];

$branch_aliases = [
    'u614622744_maninagar_db' => 'u614622744_maninagar_db',
    'u614622744_satellite_db'  => 'u614622744_satellite_db',
    'u614622744_iskon_ambli_db'  => 'u614622744_iskon_ambli_db',
];

if (!empty($branch_db) && isset($branch_aliases[$branch_db])) {
    $branch_db = $branch_aliases[$branch_db];
}

/**
 * If cookie has invalid db → fallback to main db
 */
$branch_aliases = [
    'u614622744_maninagar_db' => 'u614622744_maninagar_db',
    'u614622744_satellite_db'  => 'u614622744_satellite_db',
    'u614622744_iskon_ambli_db'  => 'u614622744_iskon_ambli_db',
];

if (!empty($branch_db) && isset($branch_aliases[$branch_db])) {
    $branch_db = $branch_aliases[$branch_db];
}

$CI =& get_instance();
$config_db = $CI->config->config['config_db'];
$default_db = isset($config_db['database']) ? $config_db['database'] : 'u614622744_main_db';

// If cookie has invalid db → fallback to configured default db.
if (empty($branch_db) || !in_array($branch_db, $allowed, true)) {
    $branch_db = $default_db;

    // optional: clear bad cookie so it doesn't keep breaking
    if (isset($_COOKIE['branch'])) {
        setcookie('branch', '', time() - 3600, '/');
        unset($_COOKIE['branch']);
    }
}

    $config_db['database'] = $branch_db;

$CI->db = $CI->load->database($config_db, true);
