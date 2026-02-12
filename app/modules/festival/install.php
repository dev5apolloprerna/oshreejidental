<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = & get_instance();

if (!$CI->db->table_exists(db_prefix() . 'holidays')) {

    $CI->db->query('CREATE TABLE `'. db_prefix() .'holidays` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(250) NOT NULL,
        `date` DATE NOT NULL,
        `message` TEXT NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}