<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Demo
Description: Default module for demo
Version: 2.3.0
Requires at least: 2.3.*
*/

define('DEMO_MODULE_NAME', 'demo');



/**
* Register activation module hook
*/
register_activation_hook(DEMO_MODULE_NAME, 'demo_module_activation_hookdemo');

function demo_module_activation_hookdemo(){
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}