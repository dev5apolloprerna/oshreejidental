<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Get challan total left for paying if not payments found the original total from the challan will be returned
 * @since  Version 1.0.1
 * @param  mixed $id     challan id
 * @param  mixed $challan_total
 * @return mixed  total left
 */
function service_type()
{
    $CI = & get_instance();
    $service_type=[];
    $servicetype=$CI->serviceplan_model->get_service_type();
   // echo '<pre>'; print_r($servicetype);exit;
    return $servicetype;
}


   