<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'title',
    'date',
    'message',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'holidays';


$result = data_tables_init($aColumns, $sIndexColumn, $sTable,[], [], ['id']);
$output  = $result['output'];
$rResult = $result['rResult'];
// echo '<pre>'; print_r($rResult);exit;

foreach ($rResult as $aRow) {
    $row = [];
    
    $planname = $aRow['title'];
    $planname .= '<div class="row-options">';
    $planname .= '<a href="' . admin_url('festival/festival/add/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    $planname .= ' | <a href="' . admin_url('festival/festival/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $planname .= '</div>';
    $row[] = $planname;
    $row[] = $aRow['date'];
    $row[] = $aRow['message'];
    
   
    
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
?>
