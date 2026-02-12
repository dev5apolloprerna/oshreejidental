<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Holidays
Description: Module for defining Holidays
Version: 1.0.0
Requires at least: 1.0.*
*/

define('FESTIVAL_MODULE_NAME', 'festival');


// $CI = &get_instance();
// $CI->load->helper(OFFER_MODULE_NAME.'/offer');

hooks()->add_action('admin_init', 'festival_module_init_menu_items');

/**
* Register activation module hook
*/
register_activation_hook(FESTIVAL_MODULE_NAME, 'festival_module_activation_hook');

function festival_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(FESTIVAL_MODULE_NAME, [FESTIVAL_MODULE_NAME]);

/**
 * Init visitors purpose module menu items in setup in admin_init hook
 * @return null
 */
function festival_module_init_menu_items()
{
    $CI = &get_instance();

    if (is_admin()) {
	    $CI->app_menu->add_sidebar_menu_item('festival', [
	        'name'     => _l('festival'),
	        'href'     => admin_url('festival/festival'),
	        'position' => 39, // The menu position
	        'icon'     => 'fa fa-tag', // Font awesome icon
	    ]);
	}
}


