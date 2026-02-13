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
    'u614622744_demo_main_db'     => ['username' => 'u614622744_demo_main_db',     'password' => 'b/K7;5&OeZ?'],
    'u614622744_demo_maninager'   => ['username' => 'u614622744_demo_maninager',   'password' => '5ZARn3l~c>'],
    'u614622744_demo_satellite'   => ['username' => 'u614622744_demo_satellite',   'password' => 'FrTF6Gm>0v'],
    'u614622744_demo_iskon'       => ['username' => 'u614622744_demo_iskon',       'password' => '|J3+H#oJ1'],
];

/**
 * If cookie has invalid db → fallback to main db
 */
if (empty($branch_db) || !isset($allowed[$branch_db])) {
    $branch_db = 'u614622744_demo_main_db';

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
