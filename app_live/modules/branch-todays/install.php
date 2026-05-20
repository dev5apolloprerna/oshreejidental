<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'branch')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "branch` (
  `branchid` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
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
  `datecreated` datetime NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  
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
