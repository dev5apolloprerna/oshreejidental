<?php

defined('BASEPATH') or exit('No direct script access allowed');
$aColumns = [
    // 'image',
    'branch',
    'address',
    // 'vat',
    'phonenumber',
    'website',
    'active',
    'created_at',
];

$sIndexColumn = 'branchid';
$sTable       = db_prefix() . 'branch';


$result = data_tables_init($aColumns, $sIndexColumn, $sTable,[], [], ['branchid']);
$output  = $result['output'];
$rResult = $result['rResult'];
//echo '<pre>'; print_r($rResult);exit;
$path = BRANCHDOC_DOC_FOLDER;// get dynamic url for images

$url  = base_url('assets/images/user-placeholder.jpg');
foreach ($rResult as $aRow) {
    $row = [];
    // if($aRow['image'] == '')
    // {
    //     $image = '<img src="'.$url.'" width="50px" height="50px">';
    // }
    // else
    // {
    //     $image = '<img id="zoom_01" src="'.$path.$aRow['branchid'].'/IMG_'.$aRow['image'].'" width="50px" height="50px" >';
    // }
    // // printrx($image);
    // $row[] = $image;
    $planname = $aRow['branch'];
    $planname .= '<div class="row-options">';
    $planname .= '<a href="' . admin_url('branch/add/' . $aRow['branchid']) . '">' . _l('edit') . '</a>';
    $planname .= ' | <a href="' . admin_url('branch/delete/' . $aRow['branchid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $planname .= '</div>';
    $row[] = $planname;
    // $row[] = $aRow['email'];
    
    
    $row[] = $aRow['phonenumber'];
    $row[] = $aRow['address'];
    $checked = '';
    if ($aRow['active'] == 1) {
        $checked = 'checked';
    }
    // $_data = '<div class="onoffswitch" id="status">
    //     <input type="checkbox" data-switch-url="' . admin_url() . 'branch/change_branch_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['branchid'] . '" data-id="' . $aRow['branchid'] . '" ' . $checked . ' >
    //     <label class="onoffswitch-label" for="c_' . $aRow['branchid'] . '"></label>
    // </div>'; 
    
    // $row[] = $_data;
    $row[] = $aRow['created_at'];
    
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
?>
