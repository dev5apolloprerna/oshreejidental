<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: General Report
Description: Module for defining General Report
Version: 1.0.0
Requires at least: 1.0.*
*/

define('GENGERALREPORT_MODULE_NAME', 'generalreport');

$CI = &get_instance();

$CI->load->helper(GENGERALREPORT_MODULE_NAME.'/generalreport');


hooks()->add_action('admin_init', 'general_report_module_init_menu_items');

/**
* Register activation module hook
*/
register_activation_hook(GENGERALREPORT_MODULE_NAME, 'general_report_module_activation_hook');

function general_report_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(GENGERALREPORT_MODULE_NAME, [GENGERALREPORT_MODULE_NAME]);

/**
 * Init visitors purpose module menu items in setup in admin_init hook
 * @return null
 */
function general_report_module_init_menu_items()
{
    $CI = &get_instance();

//     if (is_admin()) {
// 	    $CI->app_menu->add_sidebar_menu_item('generalreport', [
// 	        'name'     => _l('General Report'),
// 	        'href'     => admin_url('generalreport'),
// 	        'position' => 42, // The menu position
// 	        'icon'     => 'fa fa-area-chart', // Font awesome icon
// 	    ]);
// 	}
}

