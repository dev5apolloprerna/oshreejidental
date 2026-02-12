<?php defined('BASEPATH') or exit('No direct script access allowed');

function install_modules_branch($database){

	$CI = &get_instance();
	$CI->db->where('active', 1);
	$CI->db->where_not_in('module_name',['branch','goals','branches','backup','theme_style']);
    $active_modules = $CI->db->get(db_prefix() . 'modules')->result_array();


    $dynamicDB = array(

            'hostname'     => APP_DB_HOSTNAME,
            'username'     => APP_DB_USERNAME,
            'password'     => APP_DB_PASSWORD,
            'database'     => $database,
            'dbdriver'     => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
            'dbprefix'     => db_prefix(),
            'pconnect'     => false,
            'db_debug'     => (ENVIRONMENT !== 'production'),
            'cache_on'     => false,
            'cachedir'     => '',
            'char_set'     => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
            'dbcollat'     => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
            'swap_pre'     => '',
            'encrypt'      => true,
            'compress'     => false,
            'failover'     => [],
            'save_queries' => true,
                  );

    $CI->db = $CI->load->database($dynamicDB, TRUE);

    foreach ($active_modules as $key => $md) {

    	$CI->db->insert(db_prefix() . 'modules', ['module_name' => $md['module_name'], 'installed_version' => $md['installed_version']]);

    	copy(FCPATH .'modules/' . $md['module_name'] . '/install.php',BRANCH_SQL_FOLDER . $md['module_name'] . "_install.php");

        require_once(BRANCH_SQL_FOLDER . $md['module_name'] . "_install.php");
        
        $CI->db->where('module_name', $md['module_name']);
        $CI->db->update(db_prefix() . 'modules', ['active' => 1]);

        unlink(BRANCH_SQL_FOLDER . $md['module_name'] . "_install.php");
    }

    $main = array(

            'hostname'     => APP_DB_HOSTNAME,
            'username'     => APP_DB_USERNAME,
            'password'     => APP_DB_PASSWORD,
            'database'     => APP_DB_NAME,
            'dbdriver'     => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
            'dbprefix'     => db_prefix(),
            'pconnect'     => false,
            'db_debug'     => (ENVIRONMENT !== 'production'),
            'cache_on'     => false,
            'cachedir'     => '',
            'char_set'     => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
            'dbcollat'     => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
            'swap_pre'     => '',
            'encrypt'      => true,
            'compress'     => false,
            'failover'     => [],
            'save_queries' => true,
                  );

    $CI->db = $CI->load->database($main, TRUE);
}