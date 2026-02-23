<?php
defined('BASEPATH') or exit('No direct script access allowed');

$branch_db = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : '';
if(isset($_GET['branch_db']) && $_GET['branch_db'] != ''){

    $branch_db = $_GET['branch_db'];
}

if (!empty($branch_db)) 
{
    
    $CI =& get_instance();
     $config_db = $CI->config->config['config_db'];
    if($branch_db == 'u614622744_demo_maninager'){
        $config_db['username'] = 'u614622744_demo_maninager';
        $config_db['password'] = '5ZARn3l~c>';
    }

     if($branch_db == 'u614622744_demo_satellite'){
        $config_db['username'] = 'u614622744_demo_satellite';
        $config_db['password'] = 'FrTF6Gm>0v';
    }
    
    if($branch_db == 'u614622744_demo_iskon'){
        $config_db['username'] = 'u614622744_demo_iskon';
        $config_db['password'] = '|J3+H#oJ1';
    }
   
    $config_db['database'] = $branch_db;
    $CI->db = $CI->load->database($config_db, true);
    
}
