<?php
defined('BASEPATH') or exit('No direct script access allowed');

$branch_db = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : '';

if(isset($_GET['branch_db']) && $_GET['branch_db'] != ''){

    $branch_db = $_GET['branch_db'];
}

if (!empty($branch_db)) {
    
    $CI =& get_instance();
     $config_db = $CI->config->config['config_db'];
    if($branch_db == 'u614622744_maninagar_db'){
        $config_db['username'] = 'u614622744_maninagar_db';
        $config_db['password'] = 'unC[G$q$pN2[';
    }
     if($branch_db == 'u614622744_satellite_db'){
        $config_db['username'] = 'u614622744_satellite_db';
        $config_db['password'] = 'zWvwu$!gS$6&';
    }
   
    $config_db['database'] = $branch_db;
    $CI->db = $CI->load->database($config_db, true);
}
