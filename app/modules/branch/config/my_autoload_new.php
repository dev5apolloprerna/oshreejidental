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
    'u614622744_main_branch'     => ['username' => 'u614622744_main_branch',     'password' => '3nAoBp=2nS$'],
    'u614622744_maninagar_db'   => ['username' => 'u614622744_maninagar_db',   'password' => 'unC[G$q$pN2['],
    'u614622744_satellite_db'   => ['username' => 'u614622744_satellite_db',   'password' => 'zWvwu$!gS$6&'],
    'u614622744_iskon_ambli_db'       => ['username' => 'u614622744_iskon_ambli_db',       'password' => 'zWvwu$!gS$6&'],
];

/**
 * If cookie has invalid db → fallback to main db
 */
if (empty($branch_db) || !isset($allowed[$branch_db])) {
    $branch_db = 'u614622744_main_branch';

    // optional: clear bad cookie so it doesn't keep breaking
    if (isset($_COOKIE['branch'])) {
        setcookie('branch', '', time() - 3600, '/');
        unset($_COOKIE['branch']);
    }
}

$CI =& get_instance();

/**
 * Get base db config and override safely
 * NOTE: make sure $config['config_db'] exists in your config
 */
$config_db = $CI->config->config['config_db'];

$config_db['username'] = $allowed[$branch_db]['username'];
$config_db['password'] = $allowed[$branch_db]['password'];
$config_db['database'] = $branch_db;

$CI->db = $CI->load->database($config_db, true);
