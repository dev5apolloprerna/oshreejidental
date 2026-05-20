<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'branch')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "branch` (
  `branchid` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `branch_db` varchar(15)  DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `vat` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(30) DEFAULT NULL,
  `country` int NOT NULL DEFAULT '0',
  `city` varchar(100)  DEFAULT NULL,
  `zip` varchar(15)  DEFAULT NULL,
  `state` varchar(50)  DEFAULT NULL,
  `address` varchar(191)  DEFAULT NULL,
  `website` varchar(150)  DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `leadid` int DEFAULT NULL,
  `billing_street` varchar(200)  DEFAULT NULL,
  `billing_city` varchar(100)  DEFAULT NULL,
  `billing_state` varchar(100)  DEFAULT NULL,
  `billing_zip` varchar(100)  DEFAULT NULL,
  `billing_country` int DEFAULT '0',
  `shipping_street` varchar(200)  DEFAULT NULL,
  `shipping_city` varchar(100)  DEFAULT NULL,
  `shipping_state` varchar(100)  DEFAULT NULL,
  `shipping_zip` varchar(100)  DEFAULT NULL,
  `shipping_country` int DEFAULT '0',
  `longitude` varchar(191)  DEFAULT NULL,
  `latitude` varchar(191)  DEFAULT NULL,
  `default_language` varchar(40)  DEFAULT NULL,
  `default_currency` int NOT NULL DEFAULT '0',
  `show_primary_contact` int NOT NULL DEFAULT '0',
  `stripe_id` varchar(40)  DEFAULT NULL,
  `registration_confirmed` int NOT NULL DEFAULT '1',
  `addedfrom` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'branch`
  ADD PRIMARY KEY (`branchid`),
  ADD KEY `staff_id` (`staff_id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'branch`
  MODIFY `branchid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'medical_history')) {

     $CI->db->query('CREATE TABLE `' . db_prefix() . "medical_history` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `occupation` varchar(255) NOT NULL,
  `allergies` varchar(255) NOT NULL,
  `medication` varchar(255) NOT NULL,
  `tobaco_past` varchar(255) NOT NULL,
  `tobaco_present` varchar(255) NOT NULL,
  `alcohol_past` varchar(255) NOT NULL,
  `alcohol_present` varchar(255) NOT NULL,
  `marital_status` varchar(255) NOT NULL,
  `medical_history` varchar(255) NOT NULL,
  `surgical_history` varchar(255) NOT NULL,
  `enviro_factors` varchar(255) NOT NULL,
  `risk_factors` varchar(255) NOT NULL,
  `chief_complaint` varchar(255) NOT NULL,
  `dental_history` varchar(255) NOT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `disease` varchar(255) NOT NULL,
  `clinical_findings` varchar(255) NOT NULL,
  `current_treatment` varchar(255) NOT NULL,
  `previous_medication` varchar(255) NOT NULL,
  `current_medication` varchar(255) NOT NULL,
  `treatment_plan` varchar(255) NOT NULL,
  `history_comment` varchar(255) NOT NULL,
  `datecreated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');


     $CI->db->query('ALTER TABLE `' . db_prefix() . 'medical_history`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');


}


install_config_files();

function install_config_files(){

  // insert and line into application/config/app-config.php file
    $app_config_path = APPPATH . 'config/app-config.php';
    $app_config_file = file_get_contents($app_config_path);
// check require_once(FCPATH . 'modules/saas/config/my_config.php'); is already added or not into the app-config.php file
// if not added then add the line require_once(FCPATH . 'modules/saas/config/my_config.php'); into the app-config.php file last line
    if (strpos($app_config_file, "require_once(FCPATH . 'modules/branch/config/my_config.php');") !== false) {
        // already added
    } else {
        // not added
        $app_config_file = str_replace("define('APP_CSRF_PROTECTION', true);", "define('APP_CSRF_PROTECTION', true);\n\n\nrequire_once(FCPATH . 'modules/branch/config/my_config.php'); // added by branch", $app_config_file);
        if (!$fp = fopen($app_config_path, 'wb')) {
            die('Unable to write to config file');
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $app_config_file, strlen($app_config_file));
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($app_config_path, 0644);
    }
// replace 'database' => config_item('default_database'), with 'database' => APP_DB_NAME, in application/config/database.php file
    $database_path = APPPATH . 'config/database.php';
    $database_file = file_get_contents($database_path);
    $database_file = str_replace("APP_DB_NAME", "config_item('default_database')", $database_file);

    if (!$fp = fopen($database_path, 'wb')) {
        die('Unable to write to config file');
    }

    flock($fp, LOCK_EX);
    fwrite($fp, $database_file, strlen($database_file));
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($database_path, 0644);

    //upload my_autoload_samples.php to application/config and rename it to my_autoload.php
    $sample_autoload = module_dir_path('branch') . 'config/autoload.sample.php';
// upload the $sample_autoload into application/config folder and rename it to my_autoload.php
    $autoload_path = APPPATH . 'config/my_autoload.php';
    @chmod($autoload_path, 0666);
    if (@copy($sample_autoload, $autoload_path) === false) {
        die('Unable to copy sample autoload file to config folder . please make sure you have permission to copy autoload.sample file');
    }


}
