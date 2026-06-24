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

$doctorsTableSql = "(
    SELECT appointment_id,
        GROUP_CONCAT(DISTINCT TRIM(CONCAT(CONVERT(doctor_staff.firstname USING utf8mb4), ' ', CONVERT(doctor_staff.lastname USING utf8mb4))) ORDER BY doctor_staff.firstname, doctor_staff.lastname SEPARATOR ', ') AS doctor_names
    FROM {$appointmentTreatmentTable} AS grouped_treatments
    LEFT JOIN {$staffTable} AS doctor_staff ON grouped_treatments.staff = doctor_staff.staffid
    GROUP BY appointment_id
) AS treatment_doctors";

$patientProfileIdSql = 'COALESCE(clients.userid, direct_contacts.userid, old_contacts.userid, phone_contacts.userid, email_contacts.userid, 0)';
$patientUniqueIdSql = "COALESCE(
    NULLIF(CONVERT(direct_contacts.uid USING utf8mb4), ''),
    NULLIF(CONVERT(old_contacts.uid USING utf8mb4), ''),
    NULLIF(CONVERT(phone_contacts.uid USING utf8mb4), ''),
    NULLIF(CONVERT(email_contacts.uid USING utf8mb4), ''),
    NULLIF(CONVERT(primary_contacts.uid USING utf8mb4), ''),
    NULLIF(CONVERT(clients.tbl_uniq_id USING utf8mb4), '0'),
    CONVERT({$patientProfileIdSql} USING utf8mb4),
    ''
)";
$patientSql = "COALESCE(
    NULLIF(TRIM(CONCAT(CONVERT(direct_contacts.firstname USING utf8mb4), ' ', CONVERT(direct_contacts.lastname USING utf8mb4))), ''),
    NULLIF(TRIM(CONCAT(CONVERT(old_contacts.firstname USING utf8mb4), ' ', CONVERT(old_contacts.lastname USING utf8mb4))), ''),
    NULLIF(TRIM(CONCAT(CONVERT(phone_contacts.firstname USING utf8mb4), ' ', CONVERT(phone_contacts.lastname USING utf8mb4))), ''),
    NULLIF(TRIM(CONCAT(CONVERT(email_contacts.firstname USING utf8mb4), ' ', CONVERT(email_contacts.lastname USING utf8mb4))), ''),
    CONVERT(clients.company USING utf8mb4),
    CONVERT({$appointmentsTable}.name USING utf8mb4),
    ''
)";

$singleDoctorSql = "TRIM(CONCAT(CONVERT({$staffTable}.firstname USING utf8mb4), ' ', CONVERT({$staffTable}.lastname USING utf8mb4)))";
$doctorSql = "COALESCE(NULLIF(treatment_doctors.doctor_names, ''), {$singleDoctorSql})";

$selectSql = "
    {$appointmentTreatmentTable}.id,
    {$patientProfileIdSql} AS patient_id,
    {$patientUniqueIdSql} AS patient_unique_id,
    {$patientSql} AS patient_name,
    {$doctorSql} AS doctor_name,
    {$appointmentTreatmentTable}.created_date,
    {$appointmentTreatmentTable}.treatment,
    {$appointmentsTable}.description
";

$fromJoinSql = "
    FROM {$appointmentTreatmentTable}
    LEFT JOIN {$appointmentsTable} ON (
        {$appointmentTreatmentTable}.appointment_id = {$appointmentsTable}.id
        OR {$appointmentTreatmentTable}.appointment_id = {$appointmentsTable}.tbl_uniq_id
    )
    LEFT JOIN {$staffTable} ON {$appointmentTreatmentTable}.staff = {$staffTable}.staffid
    LEFT JOIN {$doctorsTableSql} ON treatment_doctors.appointment_id = {$appointmentTreatmentTable}.appointment_id
    LEFT JOIN {$contactsTable} AS direct_contacts ON {$appointmentsTable}.contact_id = direct_contacts.id
    LEFT JOIN {$contactsTable} AS old_contacts ON {$appointmentsTable}.old_contact_id > 0
        AND ({$appointmentsTable}.old_contact_id = old_contacts.id OR {$appointmentsTable}.old_contact_id = old_contacts.tbl_uniq_id)
    LEFT JOIN {$contactsTable} AS phone_contacts ON {$appointmentsTable}.phone <> ''
        AND phone_contacts.phonenumber = {$appointmentsTable}.phone
    LEFT JOIN {$contactsTable} AS email_contacts ON {$appointmentsTable}.email <> ''
        AND email_contacts.email = {$appointmentsTable}.email
    LEFT JOIN {$clientsTable} AS clients ON clients.userid = COALESCE(direct_contacts.userid, old_contacts.userid, phone_contacts.userid, email_contacts.userid)
    LEFT JOIN {$contactsTable} AS primary_contacts ON primary_contacts.userid = clients.userid AND primary_contacts.is_primary = 1
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

if ($endDate !== null && $endDate !== '') {
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
        CONVERT(" . $patientUniqueIdSql . ' USING utf8) LIKE ' . $CI->db->escape('%' . $escapedSearch . '%') . " ESCAPE '!' OR
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

    $totalQuery    = $CI->db->query('SELECT COUNT(DISTINCT ' . $appointmentTreatmentTable . '.id) AS total ' . $fromJoinSql . $baseWhereSql);
$filteredQuery = $CI->db->query('SELECT COUNT(DISTINCT ' . $appointmentTreatmentTable . '.id) AS total ' . $fromJoinSql . $baseWhereSql . $searchWhereSql);
$rowsQuery     = $CI->db->query('SELECT ' . $selectSql . $fromJoinSql . $baseWhereSql . $searchWhereSql . ' GROUP BY ' . $appointmentTreatmentTable . '.id ' . $orderSql . $limitSql);

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
    $patientId = (int) ($aRow['patient_id'] ?? 0);
    $patientName = doctor_treatment_report_clean_cell($aRow['patient_name']);
    $patientUniqueId = doctor_treatment_report_clean_cell($aRow['patient_unique_id'] ?? '');
    $patientLabel = $patientName . ($patientUniqueId !== '' ? ' - ' . $patientUniqueId : '');
    $patientCell = e($patientLabel);

    if ($patientId > 0) {
        $patientCell = '<a href="' . admin_url('clients/client/' . $patientId . '?group=patient_profile') . '">' . $patientCell . '</a>';
    }

    $row = [
        (int) $aRow['id'],
        $patientCell,
        e(doctor_treatment_report_clean_cell($aRow['doctor_name'])),
        _dt($aRow['created_date']),
        nl2br(e(doctor_treatment_report_clean_cell($aRow['treatment']))),
        nl2br(e(doctor_treatment_report_clean_cell($aRow['description']))),
    ];
    $output['data'][] = $row;
    $output['aaData'][] = $row;
}