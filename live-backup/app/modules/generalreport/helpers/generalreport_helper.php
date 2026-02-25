<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Get challan total left for paying if not payments found the original total from the challan will be returned
 * @since  Version 1.0.1
 * @param  mixed $id     challan id
 * @param  mixed $challan_total
 * @return mixed  total left
 */
function service_typete()
{
    $CI = & get_instance();
    $service_type=[];
    $servicetype=$CI->serviceplan_model->get_service_type();
   // echo '<pre>'; print_r($servicetype);exit;
    return $servicetype;
}

function format_challan_number_report($id)
{
    $CI = &get_instance();

    if (!is_object($id)) {
        $CI->db->select('delivery_date,number,prefix,number_format,status')
            ->from(db_prefix() . 'challan')
            ->where('id', $id);

        $challan = $CI->db->get()->row();
    } else {
        $challan = $id;

        $id = $challan->id;
    }

    if (!$challan) {
        return '';
    }


    if ($challan->status == 6) {
        $number = $challan->prefix . 'DRAFT';
    } else {
        $number = sales_number_format($challan->number, $challan->number_format, '',$challan->delivery_date);
    }

    return hooks()->apply_filters('format_challan_number', $number, [
        'id'      => $id,
        'challan' => $challan,
    ]);
}


   