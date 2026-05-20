<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'appointment_treatment.id as id',
    'COALESCE(CONCAT(' . db_prefix() . 'contacts.firstname, " ", ' . db_prefix() . 'contacts.lastname), ' . db_prefix() . 'clients.company) as patient_name',
    'CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as doctor_name',
    db_prefix() . 'appointment_treatment.created_date as treatment_date',
    db_prefix() . 'appointment_treatment.treatment as treatment_text',
    db_prefix() . 'appointly_appointments.description as appointment_comment',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'appointment_treatment';

$join = [
    'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'appointment_treatment.staff = ' . db_prefix() . 'staff.staffid',
    'LEFT JOIN ' . db_prefix() . 'appointly_appointments ON ' . db_prefix() . 'appointment_treatment.appointment_id = ' . db_prefix() . 'appointly_appointments.id',
    'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'appointly_appointments.contact_id = ' . db_prefix() . 'contacts.id',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid',
];

$where = [];
$filters = [];

$staffId = $this->ci->input->get('staff_id');
$startDate = $this->ci->input->get('start_date');
$endDate = $this->ci->input->get('end_date');

if ($staffId != '') {
    $filters[] = 'AND ' . db_prefix() . 'appointment_treatment.staff = ' . $this->ci->db->escape_str($staffId);
}

if ($startDate != '' && $endDate != '') {
    $filters[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) BETWEEN "' . $this->ci->db->escape_str($startDate) . '" AND "' . $this->ci->db->escape_str($endDate) . '"';
} elseif ($startDate != '') {
    $filters[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) >= "' . $this->ci->db->escape_str($startDate) . '"';
} elseif ($endDate != '') {
    $filters[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) <= "' . $this->ci->db->escape_str($endDate) . '"';
}

if (count($filters) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filters) . ')');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['id'];
    $row[] = e($aRow['patient_name']);
    $row[] = e($aRow['doctor_name']);
    $row[] = _dt($aRow['treatment_date']);
    $row[] = nl2br(e($aRow['treatment_text']));
    $row[] = nl2br(e($aRow['appointment_comment']));

    $output['aaData'][] = $row;
}
