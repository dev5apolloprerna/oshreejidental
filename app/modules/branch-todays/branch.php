<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Branch
Description: Module for defining Branch
Version: 1.0.0
Requires at least: 1.0.*
*/

define('BRANCH_MODULE_NAME', 'branch');

// $CI = &get_instance();
// $CI->load->helper(OFFER_MODULE_NAME.'/offer');

hooks()->add_action('admin_init', 'branch_module_init_menu_items');

/**
* Register activation module hook
*/
register_activation_hook(BRANCH_MODULE_NAME, 'branch_module_activation_hook');

function branch_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(BRANCH_MODULE_NAME, [BRANCH_MODULE_NAME]);

/**
 * Init visitors purpose module menu items in setup in admin_init hook
 * @return null
 */
function branch_module_init_menu_items()
{
    $CI = &get_instance();

    if (is_admin()) {
	    $CI->app_menu->add_sidebar_menu_item('branch', [
	        'name'     => _l('branch'),
	        'href'     => admin_url('branch'),
	        'position' => 37, // The menu position
	        'icon'     => 'fa fa-receipt fa-lg', // Font awesome icon
	    ]);
	}
}
