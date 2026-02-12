<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'services_id',
    'challanid',
    'service_plan_id',
    'company', 
    'CONCAT(firstname," ",lastname) AS costomer_name',
    'installation_date',
    'service_type',
    'service_assign_date_staff',
    db_prefix().'service_details.service_status as service_status'
];

$sIndexColumn = 'services_id';
$sTable       = db_prefix() . 'service_details';
$join = [
        'LEFT JOIN '.db_prefix().'clients ON '.db_prefix().'clients.userid = '.db_prefix().'service_details.`customerid`',
        'LEFT JOIN '.db_prefix().'contacts ON '.db_prefix().'contacts.userid = '.db_prefix().'service_details.customerid AND '.db_prefix().'contacts.is_primary = 1',
        'LEFT JOIN '.db_prefix().'installation ON '.db_prefix().'installation.challenid = '.db_prefix().'service_details.`challanid`',  
        'LEFT JOIN '.db_prefix().'customer_service_schedule on '.db_prefix().'customer_service_schedule.service_detail_id = '.db_prefix().'service_details.`services_id`',
];
$beginOfDay = date('Y-m-d',strtotime('midnight'));

$endOfDay   = date('Y-m-d',strtotime('tomorrow'));

$beginThisMonth = date('Y-m-01');

$endThisMonth   = date('Y-m-t');

$beginThisWeek = date('Y-m-d', strtotime('monday this week'));

$endThisWeek   = date('Y-m-d', strtotime('sunday this week'));

$where = [];
$sql ='';
$filter = $this->ci->input->get('range');
$service_card = $this->ci->input->get('service_card');
$area = $this->ci->input->get('area');

if($filter == 'today')
{
    $sql = ' WHERE service_assign_date_staff BETWEEN "'.$beginOfDay.'" AND "'.$endOfDay.'"';
}
elseif($filter == 'this_week')
{
    $sql = ' WHERE service_assign_date_staff BETWEEN "'.$beginThisWeek.'" AND "'.$endThisWeek.'"';
}
elseif($filter == 'this_month')
{
    $sql = ' WHERE service_assign_date_staff BETWEEN "'.$beginThisMonth.'" AND "'.$endThisMonth.'"';
}

$service_cardsql  = '';
if($service_card != ''){

    if($sql != ''){
        $service_cardsql .= ' AND '.db_prefix().'service_details.customerid = ' . $service_card;
    }else{
        $service_cardsql .= ' WHERE '.db_prefix().'service_details.customerid = ' . $service_card;
    }
}

$sql .= $service_cardsql;


$areasql  = '';
if($area != ''){

    if($sql != ''){
        $areasql .= ' AND ('.db_prefix().'clients.address LIKE "%' . $area . '%" OR '.db_prefix().'clients.city LIKE "%' . $area . '%" OR '.db_prefix().'clients.state LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_street LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_city LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_state LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_street LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_city LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_state LIKE "%' . $area . '%")';
    }else{
        $areasql .= ' WHERE ('.db_prefix().'clients.address LIKE "%' . $area . '%" OR '.db_prefix().'clients.city LIKE "%' . $area . '%" OR '.db_prefix().'clients.state LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_street LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_city LIKE "%' . $area . '%" OR '.db_prefix().'clients.billing_state LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_street LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_city LIKE "%' . $area . '%" OR '.db_prefix().'clients.shipping_state LIKE "%' . $area . '%")';
    }
}

$sql .= $areasql;


if($sql != '')
{
    $where=[$sql];
}

//echo '<pre>';print_r($where);exit;
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join,$where, ['services_id','total_services','service_name',db_prefix().'clients.userid as userid','service_schedule_id'],'GROUP by `services_id`');

$output  = $result['output'];
$rResult = $result['rResult'];
//echo '<pre>'; print_r($rResult);exit;
$CI = &get_instance();

foreach ($rResult as $aRow) {
    $row = [];
    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['services_id'] . '"><label></label></div>';
    $row[] = $aRow['services_id'];
    $tag = 'OD';
    if($aRow['challanid'] != 0 && $aRow['service_plan_id'] != 0){
        $tag = 'FREE';
    }
    if($aRow['service_plan_id'] != 0 && $aRow['challanid'] == 0){
        $tag = 'AMC';
    }
    $row[] = '<div class="tags-labels"><span class="label label-tag tag-id-0"    style="pointer-events: none !important;    margin-top: 0px;" ><span class="tag">'.$tag.'</span><span class="hide"></span></span></div>';
    $CI->db->select('delivery_date,number,prefix,number_format,status')
            ->from(db_prefix() . 'challan')
            ->where('id', $aRow['challanid']);

        $challan = $CI->db->get()->row();
        
    $row[] = '<a href="'.base_url('admin/challans/list_challan/' . $aRow['challanid']).'" onclick="init_challan('.$aRow['challanid'].'); return false;">'.sales_number_format($challan->number, $challan->number_format, '',$challan->delivery_date).'</a>';
    $company ='<a href="'.admin_url('clients/client/'.$aRow["userid"]).'">'.$aRow['company'].'</a>';
    $company .= '<div class="row-options">';
    $company .= '<a href="'.admin_url('report/delete/'.$aRow['services_id']).'" class="text-danger _delete">'._l('delete').'</a></div>';
    //$row[] = $company;

    $row[] = '<a href="'.admin_url('clients/client/'.$aRow["userid"].'?group=contacts').'">'.$aRow['costomer_name'].'</a>';
   if($aRow['installation_date'] == '0000-00-00' || $aRow['installation_date'] == '')
   {
        $installation_date='';
   }
   else
   {
    $installation_date = date('d-M-Y',strtotime($aRow['installation_date']));
   }
    $row[]=$installation_date;
    $serviceType=$aRow['service_type'];
    if($serviceType == 0)
    {
        $serviceType=$aRow['service_name'];
    }
    else
    {
        $serviceType=$aRow['service_name'];
    }
    $row[] = $serviceType;
    
    $services = $this->ci->report_model->get_customer_service($aRow['services_id']);
    //print_r($services);exit;
    $date=date('Y-m-d');
    $service = '<ul>';
    
    foreach ($services as $key => $value) 
    {   
        
        if($value['customer_prefered_datetime'] != '' && $value['customer_prefered_datetime'] != '0000-00-00'){
            
            $service .= (strtotime($value['customer_prefered_datetime']) < strtotime($date)) ||  ($value['end_time'] != '' && $value['end_time'] != '0000-00-00') ? '<li><del>'.date('d-M-Y',strtotime($value['customer_prefered_datetime'])).'</del></li>' : '<li>' . date('d-M-Y',strtotime($value['customer_prefered_datetime'])).'</li>';
        }else{
            
            $service .= (strtotime($value['service_assign_date_staff']) < strtotime($date)) || ($value['end_time'] != '' && $value['end_time'] != '0000-00-00') ? '<li><del>'.date('d-M-Y',strtotime($value['service_assign_date_staff'])).'</del></li>' : '<li>' . date('d-M-Y',strtotime($value['service_assign_date_staff'])).'</li>';    
            
        }
       
         
    }  
    $service .= '</ul>';
    
    
    $row[]=$service;
    $schedule ='<button type="button" class="btn btn-info pull-left" onclick="getid('.$aRow['services_id'].');return false;" >Assign</button>';
    $row[] = $schedule;
    

    
    $complete_services = $this->ci->report_model->get_complete_service($aRow['services_id']);
    //print_r($complete_services);

    $completed= '<ul>';
    foreach ($complete_services as $key => $values) 
    {        
        
        $completed .='<li>'.date('d-M-Y',strtotime($values['service_assign_date_staff'])).'</li>';
        
    }  
    $completed .= '</ul>';
   
    //$row[]=$completed;
    
    $upcomming_services = $this->ci->report_model->get_upcoming_service($aRow['services_id']);

    $upcomming = '<ul>';
    
    if(count($services) > 1){
        
        foreach ($upcomming_services as $key => $value) 
        {        
            $upcomming .='<li>'.date('d-M-Y',strtotime($value['service_assign_date_staff'])).'</li>';
        }  
        $upcomming .= '</ul>';    
    }
    
    //$row[]=$upcomming;
    $status_color='';
    if($aRow['service_status'] == 'Deactive')
    {
        $status_color='text-danger';
    }
    else
    {
        $status_color='text-success';
    }
   // $row[]='<div class="'.$status_color.'">'.$aRow['service_status'].'</div>';

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
    
}   
