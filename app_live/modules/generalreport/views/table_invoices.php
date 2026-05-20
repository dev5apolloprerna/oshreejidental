<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'number',
    'total',
    'total_tax',
    'YEAR(date) as year',
    'date',
    get_sql_select_client_company(),
    db_prefix() . 'projects.name as project_name',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'invoices.id and rel_type="invoice" ORDER by tag_order ASC) as tags',
    'duedate',
    db_prefix() . 'invoices.status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'invoices';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'invoices.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'invoices.project_id',
];

$custom_fields = get_table_custom_fields('invoice');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);

    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'invoices.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where  = [];


if ($clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'invoices.clientid=' . $this->ci->db->escape_str($clientid));
}

if ($project_id) {
    array_push($where, 'AND project_id=' . $this->ci->db->escape_str($project_id));
}

if (staff_cant('view', 'invoices')) {
    $userWhere = 'AND ' . get_invoices_where_sql_for_staff(get_staff_user_id());
    array_push($where, $userWhere);
}

$filters = [];


if($this->ci->input->get('staff_id') != ''){
   $filters[] = 'AND ' . db_prefix() . 'invoices.sale_agent = ' . $this->ci->input->get('staff_id');
}

if ($this->ci->input->get('status') != '') {
    $filters[] = 'AND ' . db_prefix() . 'invoices.status = ' . $this->ci->input->get('status');
}


$beginOfDay = date('Y-m-d',strtotime('midnight'));

$endOfDay   = date('Y-m-d',strtotime('tomorrow'));

$beginThisMonth = date('Y-m-01');

$endThisMonth   = date('Y-m-t');

$beginThisWeek = date('Y-m-d', strtotime('monday this week'));

$endThisWeek   = date('Y-m-d', strtotime('sunday this week'));


$sql ='';
$filter = $this->ci->input->get('range');
$client_id = $this->ci->input->get('client_id');
$area = $this->ci->input->get('area');
$start_date = $this->ci->input->get('start_date');
$end_date = $this->ci->input->get('end_date');
$deliverymans = $this->ci->input->get('staff_id');
$service_sche_where = '';

if($filter == 'today')
{
     $filters[] = 'AND (date BETWEEN "'.$beginOfDay.'" AND "'.$endOfDay.'")';
   

}
elseif($filter == 'this_week')
{
    $filters[] = 'AND (date BETWEEN "'.$beginThisWeek.'" AND "'.$endThisWeek.'")';
   
}
elseif($filter == 'this_month')
{
    $filters[]= 'AND (date BETWEEN "'.$beginThisMonth.'" AND "'.$endThisMonth.'")';
    
}

if($start_date != '' && $end_date != ''){

    if($filter == ''){
         $filters[] = 'AND (date BETWEEN "'.$start_date.'" AND "'.$end_date.'")';
    }
    
}

$client_idsql  = '';
if($client_id != ''){

   
    $filters[] = 'AND  '. db_prefix() . 'invoices.clientid = '.$client_id;
    
}





if (count($filters) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filters) . ')');
}

$aColumns = hooks()->apply_filters('invoices_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'invoices.id',
    db_prefix() . 'invoices.clientid',
    db_prefix() . 'currencies.name as currency_name',
    'project_id',
    'hash',
    'recurring',
    'deleted_customer_name',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';

    // If is from client area table
    if (is_numeric($clientid) || $project_id) {
        $numberOutput = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['id']) . '" target="_blank">' . e(format_invoice_number($aRow['id'])) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['id']) . '" onclick="init_invoice(' . $aRow['id'] . '); return false;">' . e(format_invoice_number($aRow['id'])) . '</a>';
    }

    if ($aRow['recurring'] > 0) {
        $numberOutput .= '<br /><span class="label label-primary inline-block tw-mt-1"> ' . _l('invoice_recurring_indicator') . '</span>';
    }

    $numberOutput .= '<div class="row-options">';

    $numberOutput .= '<a href="' . site_url('invoice/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (staff_can('edit',  'invoices')) {
        $numberOutput .= ' | <a href="' . admin_url('invoices/invoice/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = e(app_format_money($aRow['total'], $aRow['currency_name']));

    $row[] = e(app_format_money($aRow['total_tax'], $aRow['currency_name']));

    $row[] = e($aRow['year']);

    $row[] = e(_d($aRow['date']));

    if (empty($aRow['deleted_customer_name'])) {
        $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . e($aRow['company']) . '</a>';
    } else {
        $row[] = e($aRow['deleted_customer_name']);
    }

    $row[] = '<a href="' . admin_url('projects/view/' . $aRow['project_id']) . '">' . e($aRow['project_name']) . '</a>';;

    $row[] = render_tags($aRow['tags']);

    $row[] = e(_d($aRow['duedate']));

    $row[] = format_invoice_status($aRow[db_prefix() . 'invoices.status']);

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('invoices_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}