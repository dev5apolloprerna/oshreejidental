<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('doctor_treatment_report_clean_cell')) {
    /**
     * DataTables JSON encoding fails when live data contains malformed UTF-8.
     * Normalize report values before adding them to the response.
     */
    function doctor_treatment_report_clean_cell($value)
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
         $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $cleaned === null ? '' : $cleaned;
    }
}

$CI = get_instance();

$draw   = (int) $CI->input->post('draw');
$start  = max(0, (int) $CI->input->post('start'));
$length = (int) $CI->input->post('length');

$appointmentTreatmentTable = db_prefix() . 'appointment_treatment';
$appointmentsTable        = db_prefix() . 'appointly_appointments';
$staffTable               = db_prefix() . 'staff';
$contactsTable            = db_prefix() . 'contacts';
$clientsTable             = db_prefix() . 'clients';

$patientSql = "COALESCE(NULLIF(TRIM(CONCAT(CONVERT({$contactsTable}.firstname USING utf8mb4), ' ', CONVERT({$contactsTable}.lastname USING utf8mb4))), ''), CONVERT({$clientsTable}.company USING utf8mb4), CONVERT({$appointmentsTable}.name USING utf8mb4), '')";
$singleDoctorSql = "TRIM(CONCAT(CONVERT({$staffTable}.firstname USING utf8mb4), ' ', CONVERT({$staffTable}.lastname USING utf8mb4)))";
$doctorSql = "COALESCE(NULLIF((
    SELECT GROUP_CONCAT(DISTINCT TRIM(CONCAT(CONVERT(doctor_staff.firstname USING utf8mb4), ' ', CONVERT(doctor_staff.lastname USING utf8mb4))) ORDER BY doctor_staff.firstname, doctor_staff.lastname SEPARATOR ', ')
    FROM {$appointmentTreatmentTable} AS related_treatments
    LEFT JOIN {$staffTable} AS doctor_staff ON related_treatments.staff = doctor_staff.staffid
    WHERE related_treatments.appointment_id = {$appointmentTreatmentTable}.appointment_id
), ''), {$singleDoctorSql})";



$selectSql = "
    {$appointmentTreatmentTable}.id,
    {$patientSql} AS patient_name,
    {$doctorSql} AS doctor_name,
    {$appointmentTreatmentTable}.created_date,
    {$appointmentTreatmentTable}.treatment,
    {$appointmentsTable}.description
";

$fromJoinSql = "
    FROM {$appointmentTreatmentTable}
    LEFT JOIN {$staffTable} ON {$appointmentTreatmentTable}.staff = {$staffTable}.staffid
    LEFT JOIN {$appointmentsTable} ON (
        {$appointmentTreatmentTable}.appointment_id = {$appointmentsTable}.id
        OR {$appointmentTreatmentTable}.appointment_id = {$appointmentsTable}.tbl_uniq_id
    )
    LEFT JOIN {$contactsTable} ON {$appointmentsTable}.contact_id = {$contactsTable}.id
    LEFT JOIN {$clientsTable} ON {$contactsTable}.userid = {$clientsTable}.userid
";

$whereParts = [];

$staffId   = $CI->input->get('staff_id');
$startDate = $CI->input->get('start_date');
$endDate   = $CI->input->get('end_date');

if ($staffId !== null && $staffId !== '') {
    $whereParts[] = 'EXISTS (SELECT 1 FROM ' . $appointmentTreatmentTable . ' AS filter_treatments WHERE filter_treatments.appointment_id = ' . $appointmentTreatmentTable . '.appointment_id AND filter_treatments.staff = ' . (int) $staffId . ')';
}



if ($startDate !== null && $startDate !== '') {
    $whereParts[] = 'DATE(' . $appointmentTreatmentTable . '.created_date) >= ' . $CI->db->escape(to_sql_date($startDate));
}

if ($endDate !== null && $endDate !== '') 
{
    $whereParts[] = 'DATE(' . $appointmentTreatmentTable . '.created_date) <= ' . $CI->db->escape(to_sql_date($endDate));
}

$baseWhereSql = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

$searchWhereSql = '';
$search = $CI->input->post('search');
if (isset($search['value']) && trim($search['value']) !== '') {
    $escapedSearch = $CI->db->escape_like_str(trim($search['value']));
    $searchWhereSql = ($baseWhereSql === '' ? ' WHERE ' : ' AND ') . '(
        CONVERT(' . $appointmentTreatmentTable . '.id USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
        CONVERT(" . $patientSql . ' USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
        CONVERT(" . $doctorSql . ' USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
        CONVERT(" . $appointmentTreatmentTable . '.created_date USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
        CONVERT(" . $appointmentTreatmentTable . '.treatment USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
        CONVERT(" . $appointmentsTable . '.description USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!'
    )";
}

$orderColumns = [
    $appointmentTreatmentTable . '.id',
    'patient_name',
    'doctor_name',
    $appointmentTreatmentTable . '.created_date',
    $appointmentTreatmentTable . '.treatment',
    $appointmentsTable . '.description',
];

$orderSql = ' ORDER BY ' . $appointmentTreatmentTable . '.id DESC';
$order = $CI->input->post('order');
if (isset($order[0]['column'], $order[0]['dir'], $orderColumns[(int) $order[0]['column']])) {
    $direction = strtoupper($order[0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $orderSql = ' ORDER BY ' . $orderColumns[(int) $order[0]['column']] . ' ' . $direction;
}


$limitSql = '';
if ($length !== -1) {
    $limitSql = ' LIMIT ' . $start . ', ' . max(0, $length);
}

    // Use simple column-name fallback so the view works whether the
    // framework returns full-expression keys or short column-name keys.
    
    $oldDbDebug = $CI->db->db_debug;
    $CI->db->db_debug = false;

    $totalQuery    = $CI->db->query('SELECT COUNT(*) AS total ' . $fromJoinSql . $baseWhereSql);
    $filteredQuery = $CI->db->query('SELECT COUNT(*) AS total ' . $fromJoinSql . $baseWhereSql . $searchWhereSql);
    $rowsQuery     = $CI->db->query('SELECT ' . $selectSql . $fromJoinSql . $baseWhereSql . $searchWhereSql . $orderSql . $limitSql);

   $CI->db->db_debug = $oldDbDebug;

    if (!$totalQuery || !$filteredQuery || !$rowsQuery) {
    $output = [
        'draw'                 => $draw,
        'recordsTotal'         => 0,
        'recordsFiltered'      => 0,
        'iTotalRecords'        => 0,
        'iTotalDisplayRecords' => 0,
        'data'                 => [],
        'aaData'               => [],
        'error'                => 'Unable to load doctor treatment report. Please verify the appointment treatment database table and columns.',
    ];

    return;
}

    $totalRecords = (int) $totalQuery->row()->total;
    $filteredRecords = (int) $filteredQuery->row()->total;


    $output = [
        'draw'                 => $draw,
        'recordsTotal'         => $totalRecords,
        'recordsFiltered'      => $filteredRecords,
        'iTotalRecords'        => $totalRecords,
        'iTotalDisplayRecords' => $filteredRecords,
        'data'                 => [],
        'aaData'               => [],
    ];

foreach ($rowsQuery->result_array() as $aRow) {
    $row = [
        (int) $aRow['id'],
        e(doctor_treatment_report_clean_cell($aRow['patient_name'])),
        e(doctor_treatment_report_clean_cell($aRow['doctor_name'])),
        _dt($aRow['created_date']),
        nl2br(e(doctor_treatment_report_clean_cell($aRow['treatment']))),
        nl2br(e(doctor_treatment_report_clean_cell($aRow['description']))),
    ];
    $output['data'][] = $row;
    $output['aaData'][] = $row;
}