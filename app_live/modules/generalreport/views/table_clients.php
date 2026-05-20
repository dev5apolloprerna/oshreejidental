<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
           
            db_prefix() . 'clients.userid as userid',
            'company',
            'CONCAT(firstname, " ", lastname) as fullname',
            'email',
            db_prefix() . 'clients.phonenumber as phonenumber',
            db_prefix() . 'clients.active',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'customer_groups JOIN ' . db_prefix() . 'customers_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'customers_groups.id WHERE customer_id = ' . db_prefix() . 'clients.userid ORDER by name ASC) as customerGroups',
            db_prefix() . 'clients.datecreated as datecreated',
            db_prefix() . 'contacts.uid as uid',

        ];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'clients';
 $join = [
            'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'contacts.is_primary=1',
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
$start_date = $this->ci->input->get('start_date');
$end_date = $this->ci->input->get('end_date');
$deliverymans = $this->ci->input->get('deliverymans');
$service_sche_where = '';

if($filter == 'today')
{
    $sql = ' WHERE ('.db_prefix() . 'clients.datecreated BETWEEN "'.$beginOfDay.'" AND "'.$endOfDay.'")';
   

}
elseif($filter == 'this_week')
{
    $sql = ' WHERE ('.db_prefix() . 'clients.datecreated BETWEEN "'.$beginThisWeek.'" AND "'.$endThisWeek.'")';
   
}
elseif($filter == 'this_month')
{
    $sql = ' WHERE ('.db_prefix() . 'clients.datecreated BETWEEN "'.$beginThisMonth.'" AND "'.$endThisMonth.'")';
    
}

if($start_date != '' && $end_date != ''){

    if($filter == ''){
         $sql = ' WHERE ('.db_prefix() . 'clients.datecreated BETWEEN "'.$start_date.'" AND "'.$end_date.'")';
    }
    
}





if($sql != '')
{
    $where=[$sql];
}

//echo '<pre>';print_r($where);exit;
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join,$where, []);

$output  = $result['output'];
$rResult = $result['rResult'];
//echo '<pre>'; print_r($rResult);exit;
$CI = &get_instance();

 foreach ($rResult as $aRow) {
            $row = [];

            // Bulk actions
            // $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['userid'] . '"><label></label></div>';
            // User id
            $row[] = $aRow['userid'];

            $uid = $aRow['uid'];

             $isPerson = false;


              $uid .= '<div class="row-options">';
            $uid .= '<a href="' . admin_url('clients/client/' . $aRow['userid'] . ($isPerson && $aRow['contact_id'] ? '?group=contacts' : '?group=patient_profile')) . '">' . _l('view') . '</a>';

            // if ($aRow['registration_confirmed'] == 0 && is_admin()) {
            //     $company .= ' | <a href="' . admin_url('clients/confirm_registration/' . $aRow['userid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
            // }

            if (!$isPerson) {
                $uid .= ' | <a href="' . admin_url('clients/client/' . $aRow['userid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
            }

            if ($hasPermissionDelete) {
                $uid .= ' | <a href="' . admin_url('clients/delete/' . $aRow['userid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }

            $uid .= '</div>';

             $row[] = $uid;

            // Company
            $company  = e($aRow['company']);
           
            if ($company == '') {
                $company  = _l('no_company_view_profile');
                $isPerson = true;
            }

            $url = admin_url('clients/client/' . $aRow['userid']."?group=patient_profile");

            if ($isPerson && $aRow['contact_id']) {
                $url .= '?contactid=' . $aRow['contact_id'];
            }

            $company = '<a href="' . $url . '">' . $company . '</a>';

            // $company .= '<div class="row-options">';
            // $company .= '<a href="' . admin_url('clients/client/' . $aRow['userid'] . ($isPerson && $aRow['contact_id'] ? '?group=contacts' : '?group=patient_profile')) . '">' . _l('view') . '</a>';

            // // if ($aRow['registration_confirmed'] == 0 && is_admin()) {
            // //     $company .= ' | <a href="' . admin_url('clients/confirm_registration/' . $aRow['userid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
            // // }

            // if (!$isPerson) {
            //     $company .= ' | <a href="' . admin_url('clients/client/' . $aRow['userid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
            // }

            // if ($hasPermissionDelete) {
            //     $company .= ' | <a href="' . admin_url('clients/delete/' . $aRow['userid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            // }

            // $company .= '</div>';

            $row[] = $company;

            // Primary contact
            // $row[] = ($aRow['contact_id'] ? '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?contactid=' . $aRow['contact_id']) . '" target="_blank">' . e(trim($aRow['fullname'])) . '</a>' : '');

            // Primary contact email
            $row[] = ($aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '');

            // Primary contact phone
            $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '');

            // Toggle active/inactive customer
            $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
    <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'clients/change_client_status" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['userid'] . '" data-id="' . $aRow['userid'] . '" ' . ($aRow[db_prefix() . 'clients.active'] == 1 ? 'checked' : '') . '>
    <label class="onoffswitch-label" for="' . $aRow['userid'] . '"></label>
    </div>';

            // For exporting
            $toggleActive .= '<span class="hide">' . ($aRow[db_prefix() . 'clients.active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

            //$row[] = $toggleActive;

            // Customer groups parsing
            $groupsRow = '';
            if ($aRow['customerGroups']) {
                $groups = explode(',', $aRow['customerGroups']);
                foreach ($groups as $group) {
                    $groupsRow .= '<span class="label label-default mleft5 customer-group-list pointer">' . e($group) . '</span>';
                }
            }

            $row[] = $groupsRow;

            $row[] = e(_dt($aRow['datecreated']));

            // Custom fields add values
            // foreach ($customFieldsColumns as $customFieldColumn) {
            //     $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            // }

            $row['DT_RowClass'] = 'has-row-options';

            if ($aRow['registration_confirmed'] == 0) {
                $row['DT_RowClass'] .= ' info requires-confirmation';
                $row['Data_Title']  = _l('customer_requires_registration_confirmation');
                $row['Data_Toggle'] = 'tooltip';
            }

            if ($aRow[db_prefix().'clients.active'] == 0) {
                $row['DT_RowClass'] .= ' secondary';
            }
            
            $row = hooks()->apply_filters('customers_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }
