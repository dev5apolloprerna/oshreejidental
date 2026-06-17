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

// -----------------------------------------------------------------------
// FIX 1: Removed SQL aliases (AS ...) from $aColumns.
//         Perfex/Workice data_tables_init uses column expressions as array
//         keys; aliases break key lookup and can also confuse ORDER BY.
// FIX 2: $sIndexColumn must match the exact expression used in $aColumns.
// FIX 3: Filters are pushed directly into $where (not double-wrapped via
//         prepare_dt_filter inside an extra AND (...) group), which was
//         producing malformed SQL and a PHP error instead of JSON.
// -----------------------------------------------------------------------

$aColumns = [
    db_prefix() . 'appointment_treatment.id',
    "COALESCE(NULLIF(TRIM(CONCAT(" . db_prefix() . "contacts.firstname, ' ', " . db_prefix() . "contacts.lastname)), ''), " . db_prefix() . "clients.company) as patient_name",
    "TRIM(CONCAT(" . db_prefix() . "staff.firstname, ' ', " . db_prefix() . "staff.lastname)) as doctor_name",
    db_prefix() . 'appointment_treatment.created_date',
    db_prefix() . 'appointment_treatment.treatment',
    db_prefix() . 'appointly_appointments.description',
];

$sIndexColumn = db_prefix() . 'appointment_treatment.id';
$sTable       = db_prefix() . 'appointment_treatment';

$join = [
    'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'appointment_treatment.staff = ' . db_prefix() . 'staff.staffid',
    'LEFT JOIN ' . db_prefix() . 'appointly_appointments ON ' . db_prefix() . 'appointment_treatment.appointment_id = ' . db_prefix() . 'appointly_appointments.id',
    'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'appointly_appointments.contact_id = ' . db_prefix() . 'contacts.id',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid',
];

// Build WHERE clauses directly — no extra wrapping needed.
$where = [];

$staffId   = $this->ci->input->get('staff_id');
$startDate = $this->ci->input->get('start_date');
$endDate   = $this->ci->input->get('end_date');

if ($staffId != '') {
    $where[] = 'AND ' . db_prefix() . 'appointment_treatment.staff = ' . (int) $staffId;
}

if ($startDate != '' && $endDate != '') {
    $where[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) BETWEEN "'
        . $this->ci->db->escape_str($startDate) . '" AND "'
        . $this->ci->db->escape_str($endDate) . '"';
} elseif ($startDate != '') {
    $where[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) >= "'
        . $this->ci->db->escape_str($startDate) . '"';
} elseif ($endDate != '') {
    $where[] = 'AND DATE(' . db_prefix() . 'appointment_treatment.created_date) <= "'
        . $this->ci->db->escape_str($endDate) . '"';
}

$searchAs = [
    1 => "COALESCE(NULLIF(TRIM(CONCAT(" . db_prefix() . "contacts.firstname, ' ', " . db_prefix() . "contacts.lastname)), ''), " . db_prefix() . "clients.company)",
    2 => "TRIM(CONCAT(" . db_prefix() . "staff.firstname, ' ', " . db_prefix() . "staff.lastname))",
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [], '', $searchAs);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Use simple column-name fallback so the view works whether the
    // framework returns full-expression keys or short column-name keys.
    $row[] = isset($aRow[db_prefix() . 'appointment_treatment.id'])
           ? $aRow[db_prefix() . 'appointment_treatment.id']
           : (isset($aRow['id']) ? $aRow['id'] : '');

    $row[] = e(doctor_treatment_report_clean_cell(isset($aRow['patient_name']) ? $aRow['patient_name'] : ''));

    $row[] = e(doctor_treatment_report_clean_cell(isset($aRow['doctor_name']) ? $aRow['doctor_name'] : ''));

    $dateVal = isset($aRow[db_prefix() . 'appointment_treatment.created_date'])
             ? $aRow[db_prefix() . 'appointment_treatment.created_date']
             : (isset($aRow['created_date']) ? $aRow['created_date'] : '');
    $row[] = _dt($dateVal);

    $treatmentVal = isset($aRow[db_prefix() . 'appointment_treatment.treatment'])
                  ? $aRow[db_prefix() . 'appointment_treatment.treatment']
                  : (isset($aRow['treatment']) ? $aRow['treatment'] : '');
    $row[] = nl2br(e(doctor_treatment_report_clean_cell($treatmentVal)));

    $descVal = isset($aRow[db_prefix() . 'appointly_appointments.description'])
             ? $aRow[db_prefix() . 'appointly_appointments.description']
             : (isset($aRow['description']) ? $aRow['description'] : '');
    $row[] = nl2br(e(doctor_treatment_report_clean_cell($descVal)));

    $output['aaData'][] = $row;
}