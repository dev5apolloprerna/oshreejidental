<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Branch
Description: Default module for defining branch
Version: 2.3.0
Requires at least: 2.3.*
*/

define('BRANCH_MODULE_NAME', 'branch');
define('BRANCH_SQL_FOLDER', FCPATH .'modules/branch/sql' . '/');

define('BRANCH_DOC_FOLDER', FCPATH .'uploads/branch_images' . '/');
define('BRANCHDOC_DOC_FOLDER', base_url().'uploads/branch_images' . '/');
hooks()->add_action('after_cron_run', 'branch_db_cron_run');
hooks()->add_action('admin_init', 'branch_module_init_menu_items');
hooks()->add_action('staff_member_deleted', 'branch_staff_member_deleted');
hooks()->add_action('admin_init', 'branch_permissions');

hooks()->add_filter('migration_tables_to_replace_old_links', 'branch_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'branch_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'branch_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'branch_add_dashboard_widget');

function branch_db_cron_run(){

    $CI = &get_instance();

    $CI->db->select('branch_db');
    $allbranchdb = $CI->db->get(db_prefix(). 'branch')->result_array(); 


    $options = [];
    foreach($allbranchdb as $key => $dbs){

        $url = site_url('cron/index?branch_db='.$dbs['branch_db']);
         
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($crl, CURLOPT_SSL_VERIFYHOST, false);
         
        $response = curl_exec($crl);
         
        curl_close($crl);
    }
}

function branch_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'branch/widget',
        'container' => 'right-4',
    ];

    return $widgets;
}

function branch_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'branch', [
        'staff_id' => $data['transfer_data_to'],
    ]);
}

function branch_global_search_result_output($output, $data)
{
    if ($data['type'] == 'branch') {
        $output = '<a href="' . admin_url('branch/branch/' . $data['result']['id']) . '">' . $data['result']['subject'] . '</a>';
    }

    return $output;
}

function branch_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (staff_can('view',  'branch')) {
        // Branch
        $CI->db->select()->from(db_prefix() . 'branch')->like('description', $q)->or_like('subject', $q)->limit($limit);

        $CI->db->order_by('subject', 'ASC');

        $result[] = [
            'result'         => $CI->db->get()->result_array(),
            'type'           => 'branch',
            'search_heading' => _l('branch'),
        ];
    }

    return $result;
}

function branch_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
        'table' => db_prefix() . 'branch',
        'field' => 'description',
    ];

    return $tables;
}

function branch_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('branch', $capabilities, _l('branch'));
}

function branch_notification()
{
    $CI = &get_instance();
    $CI->load->model('branch/branch_model');
    $branch = $CI->branch_model->get('', true);
    foreach ($branch as $branch) {
        $achievement = $CI->branch_model->calculate_branch_achievement($branch['id']);

        if ($achievement['percent'] >= 100) {
            if (date('Y-m-d') >= $branch['end_date']) {
                if ($branch['notify_when_achieve'] == 1) {
                    $CI->branch_model->notify_staff_members($branch['id'], 'success', $achievement);
                } else {
                    $CI->branch_model->mark_as_notified($branch['id']);
                }
            }
        } else {
            // not yet achieved, check for end date
            if (date('Y-m-d') > $branch['end_date']) {
                if ($branch['notify_when_fail'] == 1) {
                    $CI->branch_model->notify_staff_members($branch['id'], 'failed', $achievement);
                } else {
                    $CI->branch_model->mark_as_notified($branch['id']);
                }
            }
        }
    }
}

/**
* Register activation module hook
*/
register_activation_hook(BRANCH_MODULE_NAME, 'branch_module_activation_hook');

function branch_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

hooks()->add_action('module_activated', 'after_module_activate');

function after_module_activate($module)
{   

    $moduleName = $module['system_name'];

    if($moduleName != 'branch'){

        $CI = &get_instance();
        $CI->db->select('branch_db');
        $allbranchdb = $CI->db->get(db_prefix(). 'branch')->result_array();   
            
        foreach($allbranchdb as $brchdb){

            $dynamicDB = array(

                'hostname'     => APP_DB_HOSTNAME,
                'username'     => APP_DB_USERNAME,
                'password'     => APP_DB_PASSWORD,
                'database'     => $brchdb['branch_db'],
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

            


            $CI->db->where('module_name', $moduleName);
            $CI->db->where_not_in('module_name',['branch']);
            $module_exists_in_database = $CI->db->get(db_prefix() . 'modules')->row();

            if (empty($module_exists_in_database)) {
            
                $CI->db->where('module_name', $moduleName);
                $CI->db->insert(db_prefix() . 'modules', ['module_name' => $moduleName, 'installed_version' => $module['headers']['version']]);
            }

            copy($module['path'] . 'install.php',BRANCH_SQL_FOLDER . "sql.php");

            require_once(BRANCH_SQL_FOLDER . "sql.php");
            
            $CI->db->where('module_name', $moduleName);
            $CI->db->update(db_prefix() . 'modules', ['active' => 1]);

            unlink(BRANCH_SQL_FOLDER . "sql.php");

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

    }

    
}


$CI = &get_instance();
$CI->load->helper(BRANCH_MODULE_NAME . '/module_installer');




/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(BRANCH_MODULE_NAME, [BRANCH_MODULE_NAME]);

/**
* Init branch module menu items in setup in admin_init hook
* @return null
*/
function branch_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->db->where('module_name', BRANCH_MODULE_NAME);
    $CI->db->where('active', 1);
    $module_exists_in_database = $CI->db->get(db_prefix() . 'modules')->row();
    
    if(!empty($module_exists_in_database)){

        $CI->app_menu->add_sidebar_menu_item('branch', [
            'name'     => _l('branch'),
            'href'     => admin_url('branch'),
            'icon'     => 'fa fa-hospital',
            'position' => 45,
            'badge'    => [],
        ]);
    }
}


/**
* Get branch types for the branch feature
*
* @return array
*/
function get_branch_types()
{
    $types = [
        [
            'key'       => 1,
            'lang_key'  => 'branch_type_total_income',
            'subtext'   => 'branch_type_income_subtext',
            'dashboard' => has_permission('invoices', 'view'),
        ],
        [
            'key'       => 8,
            'lang_key'  => 'branch_type_invoiced_amount',
            'subtext'   => '',
            'dashboard' => has_permission('invoices', 'view'),
        ],
        [
            'key'       => 2,
            'lang_key'  => 'branch_type_convert_leads',
            'dashboard' => is_staff_member(),
        ],
        [
            'key'       => 3,
            'lang_key'  => 'branch_type_increase_customers_without_leads_conversions',
            'subtext'   => 'branch_type_increase_customers_without_leads_conversions_subtext',
            'dashboard' => has_permission('customers', 'view'),
        ],
        [
            'key'       => 4,
            'lang_key'  => 'branch_type_increase_customers_with_leads_conversions',
            'subtext'   => 'branch_type_increase_customers_with_leads_conversions_subtext',
            'dashboard' => has_permission('customers', 'view'),

        ],
        [
            'key'       => 5,
            'lang_key'  => 'branch_type_make_contracts_by_type_calc_database',
            'subtext'   => 'branch_type_make_contracts_by_type_calc_database_subtext',
            'dashboard' => has_permission('contracts', 'view'),
        ],
        [
            'key'       => 7,
            'lang_key'  => 'branch_type_make_contracts_by_type_calc_date',
            'subtext'   => 'branch_type_make_contracts_by_type_calc_date_subtext',
            'dashboard' => has_permission('contracts', 'view'),
        ],
        [
            'key'       => 6,
            'lang_key'  => 'branch_type_total_estimates_converted',
            'subtext'   => 'branch_type_total_estimates_converted_subtext',
            'dashboard' => has_permission('estimates', 'view'),
        ],
    ];

    return hooks()->apply_filters('get_branch_types', $types);
}

/**
* Get branch type by given key
*
* @param  int $key
*
* @return array
*/
function get_branch_type($key)
{
    foreach (get_branch_types() as $type) {
        if ($type['key'] == $key) {
            return $type;
        }
    }
}

/**
* Translate branch type based on passed key
*
* @param  mixed $key
*
* @return string
*/
function format_branch_type($key)
{
    foreach (get_branch_types() as $type) {
        if ($type['key'] == $key) {
            return _l($type['lang_key']);
        }
    }

    return $type;
}

function branch_icon_image_url($id, $type = 'IMG')
{
    $url  = base_url('assets/images/user-placeholder.jpg');
    $CI   = &get_instance();
    $path = $CI->app_object_cache->get('branch-profile-image-path-' . $id);

    if (!$path) {
        $CI->app_object_cache->add('branch-profile-image-path-' . $id, $url);

        $CI->db->select('image');
        $CI->db->from(db_prefix() . 'branch');
        $CI->db->where('branchid', $id);
        $branch = $CI->db->get()->row();

        if ($branch && !empty($branch->image)) {
            $path = 'uploads/branch_images/' . $id . '/' . $type . '_' . $branch->image;
            $CI->app_object_cache->set('branch-profile-image-path-' . $id, $path);
        }
    }

    if ($path && file_exists($path)) {
        $url = base_url($path);
    }

    return $url;
}