<?php defined('BASEPATH') or exit('No direct script access allowed');

class Nabh extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
       1) LIST FORMS FOR APPOINTMENT TYPE
    ==========================================================*/
    public function list_json()
    {
        $appointment_type_id = (int)$this->input->post('appointment_type_id');

        if (!$appointment_type_id) {
            echo json_encode(['status'=>false,'data'=>[]]); exit;
        }

        $this->db->where('appointment_type_id', $appointment_type_id);
        $rows = $this->db->get(db_prefix().'appointment_type_pdf_master')->result_array();

        $data = [];

        foreach ($rows as $r) {

            $pdf = $this->db->where('pdf_id',$r['appointment_pdf_id'])
                            ->get('tblnabh_master')
                            ->row_array();

            if (!$pdf) continue;

            $data[] = [
                'id'        => $pdf['pdf_id'],
                'title_en'  => $pdf['pdf_name'],
                'title_gu'  => $pdf['pdf_name'],
                'has_en'    => !empty($pdf['english_file_name']),
                'has_gu'    => !empty($pdf['gujarati_file_name']),
            ];
        }

        echo json_encode(['status'=>true,'data'=>$data]);
        exit;
    }
                public function all_forms_json()
    {
        $patient_id = (int) $this->input->post('patient_id');

        if ($patient_id <= 0) {
            echo json_encode(['status' => false, 'data' => [], 'message' => 'Invalid patient id']);
            exit;
        }

        $forms = $this->db
            ->select('pdf_id, pdf_name, english_file_name, gujarati_file_name')
            ->from('tblnabh_master')
            ->order_by('pdf_name', 'ASC')
            ->get()
            ->result_array();

        if (empty($forms)) {
            echo json_encode(['status' => true, 'data' => []]);
            exit;
        }

        $form_ids = array_values(array_filter(array_map('intval', array_column($forms, 'pdf_id'))));

        $submissions = [];
        if (!empty($form_ids)) {
            $submissions = $this->db
                ->select('id, nabh_pdf_id, appointment_id, appointment_type_id, patient_name, doctor_id, doctor_name, updated_at, created_at, form_data_json')
                ->from(db_prefix() . 'nabh_form_submissions')
                ->where('patient_id', $patient_id)
                ->where_in('nabh_pdf_id', $form_ids)
                ->order_by('id', 'DESC')
                ->get()
                ->result_array();
        }

        $latest_submission_by_form = [];
        foreach ($submissions as $submission) {
            $form_id = (int) ($submission['nabh_pdf_id'] ?? 0);
            if ($form_id <= 0) {
                continue;
            }
            if (!isset($latest_submission_by_form[$form_id])) {
                $latest_submission_by_form[$form_id] = $submission;
            }
        }

        $rows = [];
        foreach ($forms as $form) {
            $form_id = (int) ($form['pdf_id'] ?? 0);
            if ($form_id <= 0) {
                continue;
            }

            $submission = $latest_submission_by_form[$form_id] ?? null;

            $filled_data = [];
            if (!empty($submission['form_data_json'])) {
                $decoded = json_decode($submission['form_data_json'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $f_key => $f_val) {
                        $val = is_scalar($f_val) ? trim((string) $f_val) : '';
                        if ($val === '' || $val === '0' || strtolower($val) === 'null') {
                            continue;
                        }
                        $filled_data[] = $f_key . ': ' . $val;
                        if (count($filled_data) >= 3) {
                            break;
                        }
                    }
                }
            }

            $rows[] = [
                'appointment_id' => !empty($submission['appointment_id']) ? (int) $submission['appointment_id'] : 0,
                'appointment_type_id' => !empty($submission['appointment_type_id']) ? (int) $submission['appointment_type_id'] : 0,
                'appointment_date' => '-',
                'form_id' => $form_id,
                'form_name' => $form['pdf_name'] ?? ('Form #' . $form_id),
                'has_en' => !empty($form['english_file_name']),
                'has_gu' => !empty($form['gujarati_file_name']),
                'is_filled' => !empty($submission),
                'doctor_id' => !empty($submission['doctor_id']) ? (int) $submission['doctor_id'] : 0,
                'patient_name' => !empty($submission['patient_name']) ? trim((string) $submission['patient_name']) : '',
                'doctor_name' => !empty($submission['doctor_name']) ? trim((string) $submission['doctor_name']) : '',
                'filled_at' => !empty($submission['updated_at']) ? $submission['updated_at'] : ($submission['created_at'] ?? ''),
                'filled_preview' => !empty($filled_data) ? implode(' | ', $filled_data) : '',
            ];
        }

        echo json_encode(['status' => true, 'data' => $rows]);
        exit;
    }

    public function patient_history_pdf($patient_id = 0)
    {
        $patient_id = (int) $patient_id;
        if ($patient_id <= 0) {
            show_error('Invalid patient id', 422);
        }

        $patient = $this->db
            ->select('userid, company, phonenumber')
            ->from(db_prefix() . 'clients')
            ->where('userid', $patient_id)
            ->get()
            ->row();

        $patient_all_data = [];
        if ($patient) {
            $patient_all_data = (array) $patient;
        }

        $contacts_table = db_prefix() . 'contacts';
        $contact_fields = $this->db->list_fields($contacts_table);
        $contact_select_fields = ['id', 'userid', 'email'];
        foreach (['uid', 'gender', 'blood_group', 'phonenumber', 'rx_str_date', 'rx_end_date'] as $optional_contact_field) {
            if (in_array($optional_contact_field, $contact_fields, true)) {
                $contact_select_fields[] = $optional_contact_field;
            }
        }

        $primary_contact = $this->db
            ->select(implode(', ', array_unique($contact_select_fields)))
            ->from($contacts_table)
            ->where('userid', $patient_id)
            ->order_by('is_primary', 'DESC')
            ->order_by('id', 'ASC')
            ->get()
            ->row();

        if ($primary_contact) {
            $patient_all_data = array_merge($patient_all_data, (array) $primary_contact);
        }

        
        $medical_history = $this->db
            ->from(db_prefix() . 'medical_history')
            ->where('userid', $patient_id)
            ->get()
            ->row_array();

        $contacts = $this->db
            ->select('id')
            ->from(db_prefix() . 'contacts')
            ->where('userid', $patient_id)
            ->get()
            ->result_array();

        $contact_ids = array_values(array_filter(array_map('intval', array_column($contacts, 'id'))));

        $appointments = [];
        if (!empty($contact_ids)) {
            $appointment_table = db_prefix() . 'appointly_appointments';
            $appointment_fields = $this->db->list_fields($appointment_table);

            $appointment_select = ['id', 'date', 'type_id', 'description'];
            if (in_array('start_hour', $appointment_fields, true)) {
                $appointment_select[] = 'start_hour';
            } elseif (in_array('start_time', $appointment_fields, true)) {
                $appointment_select[] = 'start_time';
            }

            if (in_array('finish_hour', $appointment_fields, true)) {
                $appointment_select[] = 'finish_hour';
            } elseif (in_array('end_hour', $appointment_fields, true)) {
                $appointment_select[] = 'end_hour';
            } elseif (in_array('end_time', $appointment_fields, true)) {
                $appointment_select[] = 'end_time';
            }

            $appointments = $this->db
                ->select(implode(', ', array_unique($appointment_select)))
                ->from($appointment_table)
                ->where_in('contact_id', $contact_ids)
                ->order_by('date', 'DESC')
                ->get()
                ->result_array();
        }

        $appointment_ids = array_values(array_filter(array_map('intval', array_column($appointments, 'id'))));
         $appointment_history_by_id = [];
        foreach ($appointments as $appointment_row) {
            $row_id = (int) ($appointment_row['id'] ?? 0);
            if ($row_id <= 0) {
                continue;
            }
            $appointment_history_by_id[$row_id] = $appointment_row;
        }

        $treatments = [];
        if (!empty($appointment_ids)) {
            $treatments = $this->db
                ->select(db_prefix() . 'appointment_treatment.appointment_id, ' . db_prefix() . 'appointment_treatment.treatment, ' . db_prefix() . 'appointment_treatment.staff, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname')
                ->from(db_prefix() . 'appointment_treatment')
                ->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'appointment_treatment.staff', 'left')
                ->where_in(db_prefix() . 'appointment_treatment.appointment_id', $appointment_ids)
                ->order_by(db_prefix() . 'appointment_treatment.id', 'DESC')
                ->get()
                ->result_array();
        }

        $treatments_by_appointment = [];
        foreach ($treatments as $tr) {
            $aid = (int) ($tr['appointment_id'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            if (!isset($treatments_by_appointment[$aid])) {
                $treatments_by_appointment[$aid] = [];
            }
            $doctor_name = trim((string) (($tr['firstname'] ?? '') . ' ' . ($tr['lastname'] ?? '')));
            $entry = trim((string) ($tr['treatment'] ?? ''));
            if ($doctor_name !== '') {
                $entry .= ($entry !== '' ? ' (Dr. ' . $doctor_name . ')' : 'Dr. ' . $doctor_name);
            }
            if ($entry !== '') {
                $treatments_by_appointment[$aid][] = $entry;
            }
        }
        
     $prescriptions = [];
        if (!empty($appointment_ids)) {
            $prescriptions = $this->db
                ->select(
                    db_prefix() . 'appointment_prescriptions.id as prescription_id, ' .
                    db_prefix() . 'appointment_prescriptions.appointment_id, ' .
                    db_prefix() . 'appointment_prescription_items.description, ' .
                    db_prefix() . 'appointment_prescription_items.qty, ' .
                    db_prefix() . 'appointment_prescription_items.days, ' .
                    db_prefix() . 'appointment_prescription_items.time_slot'
                )
                ->from(db_prefix() . 'appointment_prescriptions')
                ->join(
                    db_prefix() . 'appointment_prescription_items',
                    db_prefix() . 'appointment_prescription_items.prescription_id = ' . db_prefix() . 'appointment_prescriptions.id',
                    'left'
                )
                ->where_in(db_prefix() . 'appointment_prescriptions.appointment_id', $appointment_ids)
                ->order_by(db_prefix() . 'appointment_prescriptions.id', 'DESC')
                ->get()
                ->result_array();
        }
        
        $submissions = $this->db
            ->select(
                db_prefix() . 'nabh_form_submissions.*, ' .
                'tblnabh_master.pdf_name, ' .
                db_prefix() . 'appointly_appointments.date as appointment_date, ' .
                db_prefix() . 'staff.firstname as doctor_firstname, ' .
                db_prefix() . 'staff.lastname as doctor_lastname'
            )
            ->from(db_prefix() . 'nabh_form_submissions')
            ->join('tblnabh_master', 'tblnabh_master.pdf_id = ' . db_prefix() . 'nabh_form_submissions.nabh_pdf_id', 'left')
            ->join(db_prefix() . 'appointly_appointments', db_prefix() . 'appointly_appointments.id = ' . db_prefix() . 'nabh_form_submissions.appointment_id', 'left')
            ->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'nabh_form_submissions.doctor_id', 'left')
            ->where(db_prefix() . 'nabh_form_submissions.patient_id', $patient_id)
            ->order_by(db_prefix() . 'nabh_form_submissions.updated_at', 'DESC')
            ->order_by(db_prefix() . 'nabh_form_submissions.id', 'DESC')
            ->get()
            ->result_array();

$xray_files = [];
        $files_table = db_prefix() . 'files';
        if ($this->db->table_exists($files_table)) {
            $file_fields = $this->db->list_fields($files_table);
            $file_select = ['id', 'file_name'];
            foreach (['filetype', 'dateadded', 'xray_title'] as $optional_file_field) {
                if (in_array($optional_file_field, $file_fields, true)) {
                    $file_select[] = $optional_file_field;
                }
            }

            $this->db->select(implode(', ', array_unique($file_select)));
            $this->db->from($files_table);
            $this->db->where('rel_id', $patient_id);
            if (in_array('rel_type', $file_fields, true)) {
                $this->db->where('rel_type', 'customer');
            }
            $this->db->order_by('id', 'DESC');
            $xray_files = $this->db->get()->result_array();
        }

        $lab_work_tasks = [];
        $tasks_table = db_prefix() . 'tasks';
        if ($this->db->table_exists($tasks_table)) {
            $task_fields = $this->db->list_fields($tasks_table);
            $task_select = ['id', 'name'];
            foreach (['description', 'startdate', 'duedate', 'status'] as $optional_task_field) {
                if (in_array($optional_task_field, $task_fields, true)) {
                    $task_select[] = $optional_task_field;
                }
            }

            $this->db->select(implode(', ', array_unique($task_select)));
            $this->db->from($tasks_table);
            $this->db->where('rel_id', $patient_id);
            if (in_array('rel_type', $task_fields, true)) {
                $this->db->where('rel_type', 'customer');
            }
            if (in_array('is_lab_task', $task_fields, true)) {
                $this->db->where('is_lab_task', 1);
            }
            $this->db->order_by('id', 'DESC');
            $lab_work_tasks = $this->db->get()->result_array();
        }


        $patient_name = trim((string) ($patient->company ?? ''));
        $title = 'Patient Complete Clinical History';

        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans, sans-serif; font-size:11px; color:#222;}'
            . 'h2{margin:0 0 8px 0;} h3{margin:14px 0 6px 0;}'
            . '.meta{margin-bottom:10px;} table{width:100%; border-collapse:collapse; margin-bottom:10px;}'
            . 'th,td{border:1px solid #d5d5d5; padding:5px; vertical-align:top;}'
            . 'th{background:#f5f5f5;} .small{color:#666; font-size:10px;}'
            . '</style></head><body>';

        $html .= '<h2>' . html_escape($title) . '</h2>';
        $html .= '<div class="meta"><strong>Patient:</strong> ' . html_escape($patient_name !== '' ? $patient_name : ('ID ' . $patient_id))
            . ' | <strong>Phone:</strong> ' . html_escape((string) ($patient->phonenumber ?? '-'))
            . ' | <strong>Email:</strong> ' . html_escape((string) ($primary_contact->email ?? '-'))
            . '<br><strong>Generated:</strong> ' . date('d/m/Y H:i') . '</div>';

        $html .= '<h3>Patient Profile (All Available Data)</h3><table><tbody>';
        if (empty($patient_all_data)) {
            $html .= '<tr><td>No patient profile data available.</td></tr>';
        } else {
            foreach ($patient_all_data as $field => $value) {
                $val = trim((string) $value);
                if ($val === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', (string) $field));
                $html .= '<tr><th style="width:220px;">' . html_escape($label) . '</th><td>' . html_escape($val) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';


$rx_start_date = (string) ($primary_contact->rx_str_date ?? '');
        if ($rx_start_date === '' || $rx_start_date === '0000-00-00') {
            $rx_start_date = '-';
        }
        $rx_end_date = (string) ($primary_contact->rx_end_date ?? '');
        if ($rx_end_date === '' || $rx_end_date === '0000-00-00') {
            $rx_end_date = '-';
        }
        $current_treatment = trim((string) ($medical_history['current_treatment'] ?? ''));
        if ($current_treatment === '') {
            $current_treatment = '-';
        }

        $patient_profile_required_rows = [
            'Gender' => trim((string) ($primary_contact->gender ?? '')) !== '' ? (string) $primary_contact->gender : '-',
            'Blood G' => trim((string) ($primary_contact->blood_group ?? '')) !== '' ? (string) $primary_contact->blood_group : '-',
            'Email' => trim((string) ($primary_contact->email ?? '')) !== '' ? (string) $primary_contact->email : '-',
            'Phone' => trim((string) ($patient->phonenumber ?? ($primary_contact->phonenumber ?? ''))) !== '' ? (string) ($patient->phonenumber ?? $primary_contact->phonenumber) : '-',
            'Current RX Start Date' => $rx_start_date,
            'Current RX End Date' => $rx_end_date,
            'Current Treatment' => $current_treatment,
        ];

        $html .= '<h3>Patient Summary</h3><table><tbody>';
        foreach ($patient_profile_required_rows as $field_label => $field_value) {
            $html .= '<tr><th style="width:220px;">' . html_escape($field_label) . '</th><td>' . html_escape((string) $field_value) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h3>Patient Profile (All Available Data)</h3><table><tbody>';
        if (empty($patient_all_data)) {
            $html .= '<tr><td>No patient profile data available.</td></tr>';
        } else {
            foreach ($patient_all_data as $field => $value) {
                $val = trim((string) $value);
                if ($val === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', (string) $field));
                $html .= '<tr><th style="width:220px;">' . html_escape($label) . '</th><td>' . html_escape($val) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';
        
        $html .= '<h3>Medical History</h3><table><tbody>';
        if (empty($medical_history)) {
            $html .= '<tr><td>No medical history available.</td></tr>';
        } else {
            foreach ($medical_history as $field => $value) {
                if (in_array($field, ['id', 'userid'], true)) {
                    continue;
                }
                $val = trim((string) $value);
                if ($val === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', $field));
                $html .= '<tr><th style="width:220px;">' . html_escape($label) . '</th><td>' . html_escape($val) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h3>Prescription History</h3>';
        $html .= '<table><thead><tr>'
            . '<th style="width:35px;">#</th>'
            . '<th style="width:85px;">Appt ID</th>'
            . '<th style="width:90px;">Date</th>'
            . '<th style="width:90px;">Time</th>'
            . '<th>Medicine</th>'
            . '<th style="width:60px;">Qty</th>'
            . '<th style="width:60px;">Days</th>'
            . '<th style="width:120px;">Time Slot</th>'
            . '</tr></thead><tbody>';
        if (empty($prescriptions)) {
            $html .= '<tr><td colspan="8" style="text-align:center;">No prescription history found.</td></tr>';
        } else {
            $row_index = 1;
            foreach ($prescriptions as $prescription_row) {
                $aid = (int) ($prescription_row['appointment_id'] ?? 0);
                $appt_data = $appointment_history_by_id[$aid] ?? [];
                $appt_date = !empty($appt_data['date']) ? date('d/m/Y', strtotime($appt_data['date'])) : '-';
                $start_time = $appt_data['start_hour'] ?? ($appt_data['start_time'] ?? '');
                $end_time = $appt_data['finish_hour'] ?? ($appt_data['end_hour'] ?? ($appt_data['end_time'] ?? ''));
                $appt_time = trim((string) $start_time);
                if (!empty($end_time)) {
                    $appt_time .= ($appt_time !== '' ? ' - ' : '') . trim((string) $end_time);
                }

                $html .= '<tr>'
                    . '<td>' . (int) $row_index++ . '</td>'
                    . '<td>' . $aid . '</td>'
                    . '<td>' . html_escape($appt_date) . '</td>'
                    . '<td>' . html_escape($appt_time !== '' ? $appt_time : '-') . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['description'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['qty'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['days'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['time_slot'] ?? '-')) . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';
        
        $html .= '<h3>Uploaded X-Ray / Image Files</h3>';
        $html .= '<table><thead><tr>'
            . '<th style="width:35px;">#</th>'
            . '<th style="width:180px;">Title/File</th>'
            . '<th style="width:120px;">Added On</th>'
            . '<th style="width:120px;">Type</th>'
            . '<th>Preview / Path</th>'
            . '</tr></thead><tbody>';
        if (empty($xray_files)) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No uploaded files found.</td></tr>';
        } else {
            $xray_index = 1;
            foreach ($xray_files as $xray_file) {
                $file_name = trim((string) ($xray_file['file_name'] ?? ''));
                if ($file_name === '') {
                    continue;
                }
                $file_url = base_url('uploads/clients/' . $patient_id . '/' . $file_name);
                $file_ext = strtolower((string) pathinfo($file_name, PATHINFO_EXTENSION));
                $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
                $preview = html_escape($file_url);
                if ($is_image) {
                    $preview = '<img src="' . html_escape($file_url) . '" style="max-width:180px; max-height:120px; display:block; margin-bottom:4px;" />'
                        . '<span class="small">' . html_escape($file_url) . '</span>';
                }

                $title_or_name = trim((string) ($xray_file['xray_title'] ?? ''));
                if ($title_or_name === '') {
                    $title_or_name = $file_name;
                }

                $added_on = trim((string) ($xray_file['dateadded'] ?? ''));
                $file_type = trim((string) ($xray_file['filetype'] ?? $file_ext));

                $html .= '<tr>'
                    . '<td>' . (int) $xray_index++ . '</td>'
                    . '<td>' . html_escape($title_or_name) . '</td>'
                    . '<td>' . html_escape($added_on !== '' ? $added_on : '-') . '</td>'
                    . '<td>' . html_escape($file_type !== '' ? $file_type : '-') . '</td>'
                    . '<td>' . $preview . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h3>Lab Work Data</h3>';
        $html .= '<table><thead><tr>'
            . '<th style="width:35px;">#</th>'
            . '<th style="width:70px;">Task ID</th>'
            . '<th style="width:170px;">Task Name</th>'
            . '<th style="width:140px;">Date Range</th>'
            . '<th style="width:90px;">Status</th>'
            . '<th>Description</th>'
            . '</tr></thead><tbody>';
        if (empty($lab_work_tasks)) {
            $html .= '<tr><td colspan="6" style="text-align:center;">No lab work data found.</td></tr>';
        } else {
            foreach ($lab_work_tasks as $task_index => $lab_work_task) {
                $start_date = !empty($lab_work_task['startdate']) ? date('d/m/Y', strtotime((string) $lab_work_task['startdate'])) : '-';
                $end_date = !empty($lab_work_task['duedate']) ? date('d/m/Y', strtotime((string) $lab_work_task['duedate'])) : '-';
                $date_range = $start_date;
                if ($end_date !== '-') {
                    $date_range .= ' to ' . $end_date;
                }

                $html .= '<tr>'
                    . '<td>' . (int) ($task_index + 1) . '</td>'
                    . '<td>' . (int) ($lab_work_task['id'] ?? 0) . '</td>'
                    . '<td>' . html_escape((string) ($lab_work_task['name'] ?? '-')) . '</td>'
                    . '<td>' . html_escape($date_range) . '</td>'
                    . '<td>' . html_escape((string) ($lab_work_task['status'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($lab_work_task['description'] ?? '-')) . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h3>Prescription History</h3>';
        $html .= '<table><thead><tr>'
            . '<th style="width:35px;">#</th>'
            . '<th style="width:85px;">Appt ID</th>'
            . '<th style="width:90px;">Date</th>'
            . '<th style="width:90px;">Time</th>'
            . '<th>Medicine</th>'
            . '<th style="width:60px;">Qty</th>'
            . '<th style="width:60px;">Days</th>'
            . '<th style="width:120px;">Time Slot</th>'
            . '</tr></thead><tbody>';
        if (empty($prescriptions)) {
            $html .= '<tr><td colspan="8" style="text-align:center;">No prescription history found.</td></tr>';
        } else {
            $row_index = 1;
            foreach ($prescriptions as $prescription_row) {
                $aid = (int) ($prescription_row['appointment_id'] ?? 0);
                $appt_data = $appointment_history_by_id[$aid] ?? [];
                $appt_date = !empty($appt_data['date']) ? date('d/m/Y', strtotime($appt_data['date'])) : '-';
                $start_time = $appt_data['start_hour'] ?? ($appt_data['start_time'] ?? '');
                $end_time = $appt_data['finish_hour'] ?? ($appt_data['end_hour'] ?? ($appt_data['end_time'] ?? ''));
                $appt_time = trim((string) $start_time);
                if (!empty($end_time)) {
                    $appt_time .= ($appt_time !== '' ? ' - ' : '') . trim((string) $end_time);
                }

                $html .= '<tr>'
                    . '<td>' . (int) $row_index++ . '</td>'
                    . '<td>' . $aid . '</td>'
                    . '<td>' . html_escape($appt_date) . '</td>'
                    . '<td>' . html_escape($appt_time !== '' ? $appt_time : '-') . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['description'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['qty'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['days'] ?? '-')) . '</td>'
                    . '<td>' . html_escape((string) ($prescription_row['time_slot'] ?? '-')) . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';
        
        $html .= '<h3>Appointment & Treatment History</h3>';
        $html .= '<table><thead><tr><th style="width:35px;">#</th><th style="width:90px;">Date</th><th style="width:70px;">Appt ID</th><th style="width:90px;">Time</th><th>Details</th><th>Treatments</th></tr></thead><tbody>';
        if (empty($appointments)) {
            $html .= '<tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>';
        } else {
            foreach ($appointments as $i => $appt) {
                $aid = (int) ($appt['id'] ?? 0);
                $tlist = $treatments_by_appointment[$aid] ?? [];
                $start_time = $appt['start_hour'] ?? ($appt['start_time'] ?? '');
                $end_time = $appt['finish_hour'] ?? ($appt['end_hour'] ?? ($appt['end_time'] ?? ''));
                $time = trim((string) $start_time);
                if (!empty($end_time)) {
                    $time .= ($time !== '' ? ' - ' : '') . trim((string) $end_time);
                }
                $html .= '<tr>'
                    . '<td>' . (int) ($i + 1) . '</td>'
                    . '<td>' . (!empty($appt['date']) ? date('d/m/Y', strtotime($appt['date'])) : '-') . '</td>'
                    . '<td>' . $aid . '</td>'
                    . '<td>' . html_escape($time !== '' ? $time : '-') . '</td>'
                    . '<td>' . html_escape((string) (!empty($appt['description']) ? $appt['description'] : '-')) . '</td>'
                    . '<td>' . (!empty($tlist) ? html_escape(implode(', ', array_unique($tlist))) : '-') . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h3>NABH Filled Forms History</h3>';
        $html .= '<table><thead><tr>'
            . '<th style="width:35px;">#</th>'
            . '<th style="width:85px;">Date</th>'
            . '<th style="width:70px;">Appt ID</th>'
            . '<th style="width:170px;">NABH Form</th>'
            . '<th style="width:120px;">Doctor</th>'
            . '</tr></thead><tbody>';

        if (empty($submissions)) {
            $html .= '<tr><td colspan="6" style="text-align:center;">No NABH history found.</td></tr>';
        } else {
            foreach ($submissions as $index => $row) {
                $doctor_name = trim((string) ($row['doctor_name'] ?? ''));
                if ($doctor_name === '') {
                    $doctor_name = trim((string) (($row['doctor_firstname'] ?? '') . ' ' . ($row['doctor_lastname'] ?? '')));
                }

                $details = [];
                if (!empty($row['form_data_json'])) {
                    $decoded = json_decode($row['form_data_json'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $k => $v) {
                            if (!is_scalar($v)) {
                                continue;
                            }
                            $val = trim((string) $v);
                            if ($val === '' || $val === '0' || strtolower($val) === 'null') {
                                continue;
                            }
                            $details[] = html_escape((string) $k) . ': ' . html_escape($val);
                        }
                    }
                }

                $html .= '<tr>'
                    . '<td>' . (int) ($index + 1) . '</td>'
                    . '<td>' . (!empty($row['appointment_date']) ? date('d/m/Y', strtotime($row['appointment_date'])) : '-') . '</td>'
                    . '<td>' . (int) ($row['appointment_id'] ?? 0) . '</td>'
                    . '<td>' . html_escape((string) ($row['pdf_name'] ?? '')) . '</td>'
                    . '<td>' . html_escape($doctor_name !== '' ? $doctor_name : '-') . '<div class="small">'
                    . html_escape((string) (!empty($row['updated_at']) ? $row['updated_at'] : ($row['created_at'] ?? '')))
                    . '</div></td>'
                    . '</tr>';
            }
        }

        $html .= '</tbody></table></body></html>';

        $filename = 'patient_complete_history_' . $patient_id . '_' . date('Ymd_His') . '.pdf';
        return $this->render_pdf_with_dompdf($html, $filename);
    }
    /* =========================================================
       2) LOAD FORM (HTML + DB DATA + INJECT JS)
    ==========================================================*/
    public function form($pdf_id)
    {

        $pdf_id = (int)$pdf_id;

        $appointment_id      = (int)$this->input->get('appointment_id');
        $appointment_type_id = (int)$this->input->get('appointment_type_id');
        $patient_id          = (int)$this->input->get('patient_id');
        $doctor_id           = (int)$this->input->get('doctor_id');
        $lang                = $this->input->get('lang');

        // 1️⃣ Get template
        $pdf = $this->db->where('pdf_id',$pdf_id)
                        ->get('tblnabh_master')
                        ->row_array();

                                if (!$pdf) show_error('Invalid PDF');

        $fileName = ($lang == 'en') 
            ? $pdf['english_file_name'] 
            : $pdf['gujarati_file_name'];

        if (!$fileName) show_error('Template file missing');

        $lang = ($lang === 'en') ? 'en' : 'gu';

        $fileName = ($lang === 'en')
            ? trim((string)($pdf['english_file_name'] ?? ''))
            : trim((string)($pdf['gujarati_file_name'] ?? ''));

        if ($fileName === '') {
            // fallback: if selected language missing, try the other
            $fileName = trim((string)($pdf['english_file_name'] ?? '')) ?: trim((string)($pdf['gujarati_file_name'] ?? ''));
        }

        $langFolder = ($lang === 'en') ? 'english' : 'gujarati';

        $path = FCPATH . 'uploads/nabh/' . $langFolder . '/' . basename($fileName);
        if (!file_exists($path)) {
            show_error('Template file not found: ' . $path);
            return;
        }

        $html = file_get_contents($path);



        // 2️⃣ Get saved submission
        $this->db->where('nabh_pdf_id',$pdf_id);
        $this->db->where('patient_id',$patient_id);
        $this->db->where('appointment_id',$appointment_id);
        $this->db->where('lang',$lang);

        $row = $this->db->order_by('id','DESC')
                        ->get(db_prefix().'nabh_form_submissions')
                        ->row_array();

        $saved = [];
        if ($row && !empty($row['form_data_json'])) {
            $saved = json_decode($row['form_data_json'], true);
        }

        if (!is_array($saved)) {
            $saved = [];
        }

        $patient_name = $this->input->get('patient_name', true);
        $doctor_name  = $this->input->get('doctor_name', true);

        $doctor_signature_image = $this->input->get('doctor_signature_image', true);
        $patient_signature_image = $this->input->get('patient_signature_image', true);

        // if encoded, CI usually decodes, but safe:
        $patient_name = urldecode((string)$patient_name);
        $doctor_name  = urldecode((string)$doctor_name);


        $doctor_signature_image = urldecode((string)$doctor_signature_image);
        $patient_signature_image = urldecode((string)$patient_signature_image);
        // backward-compatible alias to avoid undefined-variable notices from older typo usage
        $patient_signature_imag = $patient_signature_image;

        if ($patient_name === '' && $row && !empty($row['patient_name'])) {
            $patient_name = trim((string)$row['patient_name']);
        }

        if ($doctor_name === '' && $row && !empty($row['doctor_name'])) {
            $doctor_name = trim((string)$row['doctor_name']);
        }

        if ($doctor_name === '' && $doctor_id > 0 && function_exists('get_staff_full_name')) {
            $doctor_name = trim((string)get_staff_full_name($doctor_id));
        }

        if ($doctor_signature_image === '' && !empty($saved['doctor_signature_image'])) {
            $doctor_signature_image = trim((string)$saved['doctor_signature_image']);
        }

        if ($doctor_signature_image === '' && $doctor_id > 0) {
            //$doctor_signature_image = $this->resolve_doctor_signature_image_from_staff_dir($doctor_id);
                    $doctor_signature_image = $this->resolve_doctor_signature_image($doctor_id);
        }


        if ($patient_signature_image === '' && !empty($saved['patient_signature_image'])) {
            $patient_signature_image = trim((string)$saved['patient_signature_image']);
        }



        if ($patient_signature_image === '' && $patient_id > 0) {
            $patient_signature_image = $this->resolve_patient_signature_image_from_consent($patient_id, $appointment_id);
        }

        if ($patient_signature_image !== '' && empty($saved['patient_signature_image'])) {
            $saved['patient_signature_image'] = $patient_signature_image;
        }



        $ctx = [
          'pdf_id'              => $pdf_id,
          'appointment_id'      => $appointment_id,
          'appointment_type_id' => $appointment_type_id,
          'patient_id'          => $patient_id,
          'doctor_id'           => $doctor_id,
          'lang'                => $lang,
          'patient_name'        => $patient_name,
          'doctor_name'         => $doctor_name,
          'doctor_signature_image' => $doctor_signature_image,
          'patient_signature_image' => $patient_signature_image !== '' ? $patient_signature_image : ($patient_signature_imag ?: null),
        ];

     $html = $this->append_universal_signature_block($html);

    // ✅ 6️⃣ THIS IS WHERE YOU PUT IT
    $html = $this->inject_global($html, $ctx, $saved);

    // ✅ 7️⃣ Output
    echo $html;
    exit;
}


    /* =========================================================
       3) SAVE SUBMISSION (UPSERT)
    ==========================================================*/
    public function save_submission()
    {
        $payload = [];

        $payloadStr = $this->input->post('payload', false);
        if (is_string($payloadStr) && trim($payloadStr) !== '') {
            $payload = json_decode($payloadStr, true);
        }

        if (!is_array($payload) || empty($payload)) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && trim($raw) !== '') {
                $decodedRaw = json_decode($raw, true);
                if (is_array($decodedRaw)) {
                    $payload = $decodedRaw;
                }
            }
        }

        if (!is_array($payload) || empty($payload)) {
            $payload = $this->input->post(null, false);
        }

        if (!is_array($payload) || empty($payload)) {
            echo json_encode(['status'=>false,'message'=>'Missing payload']); exit;
        }

        if (!isset($payload['form_data']) || !is_array($payload['form_data'])) {
            $payload['form_data'] = [];
        }


        $patient_name = trim((string)($payload['patient_name'] ?? ''));
        $doctor_name  = trim($payload['doctor_name'] ?? '');

        // fallback: if top-level not provided, attempt from form_data
        if ($patient_name === '' && isset($payload['form_data']['patient_name'])) {
            $patient_name = trim((string)$payload['form_data']['patient_name']);
        }
        if ($doctor_name === '' && isset($payload['form_data']['doctor_name'])) {
            $doctor_name = trim((string)$payload['form_data']['doctor_name']);
        }


        $pdf_id        = (int)$payload['nabh_pdf_id'];
        $appointment_id= (int)$payload['appointment_id'];
        $appointment_type_id = (int)($payload['appointment_type_id'] ?? 0);
        $patient_id    = (int)$payload['patient_id'];
        $doctor_id     = (int)$payload['doctor_id'];
        $lang          = $payload['lang'];

        $formData      = is_array($payload['form_data']) ? $payload['form_data'] : [];

       if ($doctor_name === '' && $doctor_id > 0 && function_exists('get_staff_full_name')) {
            $doctor_name = trim((string)get_staff_full_name($doctor_id));
        }

        if ($patient_name === '' && isset($formData['patient_name'])) {
            $patient_name = trim((string)$formData['patient_name']);
        }

        if ($doctor_name === '' && isset($formData['doctor_name'])) {
            $doctor_name = trim((string)$formData['doctor_name']);
        }

        $doctorSignatureImage  = trim((string)($formData['doctor_signature_image'] ?? ''));
        $patientSignatureImage = trim((string)($formData['patient_signature_image'] ?? ''));

        $resolvedDoctorSignatureImage = $doctor_id > 0 ? $this->resolve_doctor_signature_image($doctor_id) : '';
        if ($doctorSignatureImage === '') {
            $doctorSignatureImage = $resolvedDoctorSignatureImage;
        }

        if ($patientSignatureImage === '' && $patient_id > 0) {
            $patientSignatureImage = $this->resolve_patient_signature_image_from_consent($patient_id, $appointment_id);
        }

        if ($doctorSignatureImage !== '' && $patientSignatureImage !== '' && $doctorSignatureImage === $patientSignatureImage && $resolvedDoctorSignatureImage === '') {
            $doctorSignatureImage = '';
        }

        if ($doctorSignatureImage !== '') {
            $formData['doctor_signature_image'] = $doctorSignatureImage;
        } else {
            unset($formData['doctor_signature_image']);
        }

        if ($patientSignatureImage !== '') {
            $formData['patient_signature_image'] = $patientSignatureImage;
        }


        $table = db_prefix().'nabh_form_submissions';

        $this->db->where('nabh_pdf_id',$pdf_id);
        $this->db->where('patient_id',$patient_id);
        $this->db->where('appointment_id',$appointment_id);
        $this->db->where('lang',$lang);

        $existing = $this->db->get($table)->row_array();

        $data = [
            'nabh_pdf_id'=>$pdf_id,
            'appointment_id'=>$appointment_id,
            'appointment_type_id' => $appointment_type_id,
            'patient_id'=>$patient_id,
            'doctor_id'=>$doctor_id,
            'lang'=>$lang,
            'patient_name'   => $patient_name,
            'doctor_name'    => $doctor_name,
              'form_data_json' => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'updated_at'=>date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id',$existing['id'])->update($table,$data);
            echo json_encode(['status'=>true,'message'=>'Updated']);
        } else {
            $data['created_at']=date('Y-m-d H:i:s');
            $this->db->insert($table,$data);
            echo json_encode(['status'=>true,'message'=>'Saved']);
        }

        exit;
    }


    /* =========================================================
       4) COMMON SCRIPT INJECTION
    ==========================================================*/
    private function _inject_common_script($ctx,$saved)
    {
        $ctx['admin_base']=rtrim(admin_url(),'/').'/';
        $ctx['csrf_name']=$this->security->get_csrf_token_name();
        $ctx['csrf_hash']=$this->security->get_csrf_hash();

        $ctxJson=json_encode($ctx,JSON_UNESCAPED_UNICODE);
        $savedJson=json_encode($saved,JSON_UNESCAPED_UNICODE);

        return <<<HTML
<script>
window.__NABH_CTX={$ctxJson};
window.__NABH_SAVED={$savedJson};

(function(){

  function setValue(el,val){
    if(!el) return;
    if(el.type==='checkbox') el.checked=(val==1);
    else el.value=val??'';
  }

  document.addEventListener('DOMContentLoaded',function(){
    var data=window.__NABH_SAVED||{};
    Object.keys(data).forEach(function(k){
      document.querySelectorAll('[name="'+k+'"]').forEach(function(el){
        setValue(el,data[k]);
      });
    });
  });

  document.addEventListener('click',async function(e){
    if(e.target.id!=='submitBtn') return;

    var CTX=window.__NABH_CTX;

    var formData={};
    document.querySelectorAll('input[name],textarea[name],select[name]').forEach(function(el){
      formData[el.name]=el.type==='checkbox'? (el.checked?1:0):el.value;
    });

    var payload={
      nabh_pdf_id:CTX.pdf_id,
      appointment_id:CTX.appointment_id,
      appointment_type_id:CTX.appointment_type_id,
      patient_id:CTX.patient_id,
      doctor_id:CTX.doctor_id,
      lang:CTX.lang,
      form_data:formData
    };

    var fd=new FormData();
    fd.append(CTX.csrf_name,CTX.csrf_hash);
    fd.append('payload',JSON.stringify(payload));

    var res=await fetch(CTX.admin_base+'nabh/save_submission',{method:'POST',body:fd});
    var json=await res.json();
    alert(json.message||'Saved');
  });

})();
</script>
HTML;
    }

private function inject_global($html, $ctx, $saved)
{
    $ctx['admin_base'] = rtrim(admin_url(), '/') . '/';
    $ctx['csrf_name']  = $this->security->get_csrf_token_name();
    $ctx['csrf_hash']  = $this->security->get_csrf_hash();

    $ctxJson   = json_encode($ctx, JSON_UNESCAPED_UNICODE);
    $savedJson = json_encode($saved, JSON_UNESCAPED_UNICODE);

    $script = "
<script>
window.__NABH_CTX = {$ctxJson};
window.__NABH_SAVED = {$savedJson};
</script>
<script src=\"" . site_url('assets/js/nabh-global.js') . "\"></script>
";


    if (stripos($html, '</body>') !== false) {
        return str_ireplace('</body>', $script . '</body>', $html);
    }

    return $html . $script;
}


public function print_pdf()

{
    // Accept JSON POST OR GET (new tab)
    $raw = file_get_contents('php://input');
    $req = json_decode($raw, true);

    $isJson = is_array($req) && !empty($req);         // AJAX JSON call?
    $isGet  = !$isJson;                               // open new tab call?

    if (!$req) {
        $req = [
            'nabh_pdf_id'         => (int)$this->input->get('nabh_pdf_id'),
            'appointment_id'      => (int)$this->input->get('appointment_id'),
            'appointment_type_id' => (int)$this->input->get('appointment_type_id'),
            'patient_id'          => (int)$this->input->get('patient_id'),
            'doctor_id'           => (int)$this->input->get('doctor_id'),
            'lang'                => $this->input->get('lang') ?: 'en',
            'patient_name'        => $this->input->get('patient_name') ?: '',
            'doctor_name'         => $this->input->get('doctor_name') ?: '',
            'form_data_json'      => [],
        ];
    }

    $nabh_pdf_id    = (int)($req['nabh_pdf_id'] ?? 0);
    $appointment_id = (int)($req['appointment_id'] ?? 0);
    $doctor_id      = (int)($req['doctor_id'] ?? 0);
    $lang           = (($req['lang'] ?? 'en') === 'gu') ? 'gu' : 'en';

    if (empty($req['doctor_name']) && $doctor_id > 0 && function_exists('get_staff_full_name')) {
        $req['doctor_name'] = trim((string)get_staff_full_name($doctor_id));
    }

    // helper to respond nicely instead of CI error page
    $respondTemplateMissing = function($msg) use ($isJson) {
        if ($isJson) {
            return $this->output
                ->set_status_header(200) // keep 200 so frontend can handle easily
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status'  => false,
                    'code'    => 'TEMPLATE_MISSING',
                    'message' => $msg,
                ]));
        }

        // GET/new tab case
        if (function_exists('set_alert')) {
            set_alert('warning', $msg);
        }
        // redirect back if possible, else go to module list
        $ref = $this->input->server('HTTP_REFERER');
        redirect($ref ?: admin_url('nabh'));
        exit;
    };

    if ($nabh_pdf_id <= 0 || $appointment_id <= 0) {
        return $this->output
            ->set_status_header(422)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => false, 'message' => 'Missing nabh_pdf_id / appointment_id']));
    }

    // 1) Load NABH master
    $master = $this->db->where('pdf_id', $nabh_pdf_id)->get('tblnabh_master')->row();
    if (!$master) {
        return $this->output->set_status_header(404)->set_output('NABH master record not found');
    }

    // 2) Resolve template filename ONLY for selected language (NO fallback)
    $file = ($lang === 'gu')
        ? trim((string)($master->gujarati_file_name ?? ''))
        : trim((string)($master->english_file_name ?? ''));

    // ✅ if db has no file for that language => disable + message
    if ($file === '') {
        return $respondTemplateMissing('File not exist for this language.');
    }

    // 3) Load HTML from correct folder
    $folder = ($lang === 'gu') ? 'gujarati/' : 'english/';
    $path   = FCPATH . 'uploads/nabh/' . $folder . basename($file);

    // ✅ if file missing on disk => disable + message
    if (!file_exists($path)) {
        return $respondTemplateMissing('File not exist for this language.');
    }

    $html = file_get_contents($path);

    // 4) Load saved JSON (prefer request, else DB)
    $saved = $req['form_data_json'] ?? [];
    if (!is_array($saved)) $saved = [];

    if (empty($saved)) {
        $this->db->where('nabh_pdf_id', $nabh_pdf_id);
        $this->db->where('appointment_id', $appointment_id);
        $this->db->where('lang', $lang);
        $this->db->order_by('id', 'DESC');
        $row = $this->db->get(db_prefix() . 'nabh_form_submissions')->row();

        if ($row && !empty($row->form_data_json)) {
            $decoded = json_decode($row->form_data_json, true);
            if (is_array($decoded)) $saved = $decoded;
        }
        if (empty($req['patient_name']) && $row && !empty($row->patient_name)) $req['patient_name'] = $row->patient_name;
        if (empty($req['doctor_name'])  && $row && !empty($row->doctor_name))  $req['doctor_name']  = $row->doctor_name;

        if ($doctor_id <= 0 && $row && !empty($row->doctor_id)) {
            $doctor_id = (int) $row->doctor_id;
            $req['doctor_id'] = $doctor_id;
        }
    }

    // 5) Ensure common fields exist
    if (!isset($saved['patient_name']) && !empty($req['patient_name'])) $saved['patient_name'] = $req['patient_name'];
    if (!isset($saved['doctor_name'])  && !empty($req['doctor_name']))  $saved['doctor_name']  = $req['doctor_name'];

    if (!isset($saved['doctor_signature_image']) || trim((string)$saved['doctor_signature_image']) === '') {
        $fallbackDoctorSignImage = $this->resolve_doctor_signature_image($doctor_id);        if ($fallbackDoctorSignImage !== '') {
            $saved['doctor_signature_image'] = $fallbackDoctorSignImage;
        }
    }

    if (!isset($saved['patient_signature_image']) || trim((string)$saved['patient_signature_image']) === '') {        $fallbackPatientSignImage = $this->resolve_patient_signature_image_from_consent((int)($req['patient_id'] ?? 0), $appointment_id);
        if ($fallbackPatientSignImage !== '') {
            $saved['patient_signature_image'] = $fallbackPatientSignImage;
        }
    }


    if (!isset($saved['today_date'])) $saved['today_date'] = date('d/m/Y');

    $html = $this->append_universal_signature_block($html);


    // 6) Fill HTML server-side (NO JS in PDF)
    $html = $this->apply_saved_to_html_for_pdf($html, $saved);

    // 7) Remove submit bar/buttons in PDF
    $html = preg_replace('~<div[^>]*class="submit-bar"[^>]*>.*?</div>~is', '', $html);
    $html = preg_replace('~<button[^>]*id="submitBtn"[^>]*>.*?</button>~is', '', $html);
    $html = preg_replace('~<div[^>]*id="status"[^>]*>.*?</div>~is', '', $html);

    // 8) Force Gujarati font css (for BOTH engines)
    if ($lang === 'gu') {
        $guFontRegular = FCPATH . 'assets/fonts/NotoSansGujarati-Regular.ttf';
        if (!file_exists($guFontRegular)) show_error('Gujarati font missing: ' . $guFontRegular);

        $guFontBold = FCPATH . 'assets/fonts/NotoSansGujarati-Bold.ttf'; // optional

        $fontRegUrl  = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $guFontRegular);
        $fontBoldUrl = file_exists($guFontBold)
            ? 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $guFontBold)
            : '';

        $forceCss = '<style>
            @font-face{
              font-family:"NotoSansGujarati";
              src:url("' . $fontRegUrl . '") format("truetype");
              font-weight:normal;
              font-style:normal;
            }' .
            ($fontBoldUrl ? '
            @font-face{
              font-family:"NotoSansGujarati";
              src:url("' . $fontBoldUrl . '") format("truetype");
              font-weight:bold;
              font-style:normal;
            }' : '') .
            '
            body, table, tr, td, th, p, div, span, h1,h2,h3,h4,h5,h6 {
              font-family:"NotoSansGujarati" !important;
            }
        </style>';

        if (stripos($html, '</head>') !== false) $html = str_ireplace('</head>', $forceCss . '</head>', $html);
        else $html = $forceCss . $html;
    }

    // 9) Choose engine
    $title    = trim((string)($master->pdf_name ?? 'NABH'));
    $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $title) . '_' . date('Ymd_His') . '.pdf';

    if ($lang === 'gu') {
        return $this->render_pdf_with_mpdf($html, $filename);
    }

    return $this->render_pdf_with_dompdf($html, $filename);
}


private function resolve_patient_signature_image_from_consent(int $patient_id, int $appointment_id = 0): string
{
    if ($patient_id <= 0) {
        return '';
    }

    $table = db_prefix() . 'patient_signatures';

    if ($appointment_id > 0) {
        $row = $this->db
            ->select('signature_value')
            ->from($table)
            ->where('appointment_id', $appointment_id)
            ->order_by('id', 'DESC')
            ->get()
            ->row_array();

        $sig = $this->normalize_patient_signature_value((string)($row['signature_value'] ?? ''));
        if ($sig !== '') {
            return $sig;
        }
    }

    $row = $this->db
        ->select($table . '.signature_value')
        ->from($table)
        ->join(db_prefix() . 'appointly_appointments', db_prefix() . 'appointly_appointments.id = ' . $table . '.appointment_id', 'left')
        ->join(db_prefix() . 'contacts', db_prefix() . 'contacts.id = ' . db_prefix() . 'appointly_appointments.contact_id', 'left')
        ->where(db_prefix() . 'contacts.userid', $patient_id)
        ->order_by($table . '.id', 'DESC')
        ->limit(1)
        ->get()
        ->row_array();

    return $this->normalize_patient_signature_value((string)($row['signature_value'] ?? ''));
}

private function normalize_patient_signature_value(string $signatureValue): string
{
    $value = trim($signatureValue);
    if ($value === '') {
        return '';
    }

    if (strpos($value, 'data:image/') === 0) {
        return $value;
    }

    $value = preg_replace('/\s+/', '', $value);
    if ($value === '') {
        return '';
    }

    return 'data:image/png;base64,' . $value;
}

private function append_universal_signature_block(string $html): string
{
    if (stripos($html, 'id="nabh-universal-signatures"') !== false) {
        return $html;
    }

    $block = '<div id="nabh-universal-signatures" style="margin-top:24px;">'
        . '<table style="width:100%; border-collapse:collapse;">'
        . '<tr>'
        . '<td style="width:50%; vertical-align:top; padding-right:16px;">'
        . '<div style="font-weight:600; margin-bottom:6px;">Patient Signature</div>'
        . '<img name="patient_signature_image" alt="Patient Signature" style="max-height:80px; max-width:100%; display:block; margin-bottom:8px;" />'
        . '<input type="text" name="patient_signature" placeholder="Patient Signature" style="width:100%; border:none; border-top:1px solid #333; padding-top:4px;" />'
        . '</td>'
        . '<td style="width:50%; vertical-align:top; padding-left:16px;">'
        . '<div style="font-weight:600; margin-bottom:6px;">Doctor Image</div>'
        . '<img name="doctor_signature_image" alt="Doctor Image" style="max-height:100px; max-width:100%; display:block; margin-bottom:8px;" />'
        . '<input type="text" name="doctor_name" placeholder="Doctor Name" style="width:100%; border:none; border-top:1px solid #333; padding-top:4px;" />'
        . '</td>'
        . '</tr>'
        . '</table>'
        . '</div>';

    if (stripos($html, '</body>') !== false) {
        return str_ireplace('</body>', $block . '</body>', $html);
    }

    return $html . $block;
}



private function resolve_doctor_profile_image(int $doctor_id): string
{
    if ($doctor_id <= 0) {
        return '';
    }

    $staff = $this->db
        ->select('*')
        ->from(db_prefix() . 'staff')
        ->where('staffid', $doctor_id)
        ->get()
        ->row_array();

    $profileImage = trim((string)($staff['profile_image'] ?? $staff['staff_profile_image'] ?? ''));
    $baseDir = FCPATH . 'uploads/staff_profile_images/' . $doctor_id . '/';

    if ($profileImage !== '') {
        $preferred = [
            $baseDir . 'small_' . $profileImage,
            $baseDir . $profileImage,
        ];

        foreach ($preferred as $candidate) {
            if (file_exists($candidate)) {
                $relative = str_replace(FCPATH, '', $candidate);
                return rtrim(site_url(), '/') . '/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relative), '/');
            }
        }
    }

    if (!is_dir($baseDir)) {
        return '';
    }

    $files = glob($baseDir . '*.{png,jpg,jpeg,gif,webp,svg}', GLOB_BRACE);
    if (empty($files)) {
        return '';
    }

    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    $relative = str_replace(FCPATH, '', $files[0]);
    return rtrim(site_url(), '/') . '/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relative), '/');
}


   private function apply_saved_to_html_for_pdf($html, array $saved)
{
    if (empty($saved)) return $html;

    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $saved = $this->apply_party_defaults_for_pdf($xpath, $saved);

        foreach ($saved as $key => $val) {
        $key = (string)$key;

        $isImageValue = $this->is_signature_image_value((string)$val);
        $normalizedImageValue = $isImageValue ? $this->normalize_signature_src_for_pdf((string)$val) : '';
        $isSignatureImageKey = $this->is_signature_related_context(strtolower($key)) && $isImageValue;

        // by name
        foreach ($xpath->query("//input[@name='$key']") as $input) {
            if ($isSignatureImageKey) {
                continue;
            }

            $type = strtolower($input->getAttribute('type'));
            if ($type === 'checkbox') {
                $checked = ($val == 1 || $val === true || $val === "1" || $val === "on");
                $checked ? $input->setAttribute('checked','checked') : $input->removeAttribute('checked');
            } elseif ($type === 'radio') {
                ((string)$input->getAttribute('value') === (string)$val) ? $input->setAttribute('checked','checked')
                    : $input->removeAttribute('checked');
            } else {
                $input->setAttribute('value', (string)$val);
            }
        }

        foreach ($xpath->query("//textarea[@name='$key']") as $ta) {
            while ($ta->firstChild) $ta->removeChild($ta->firstChild);
            $ta->appendChild($dom->createTextNode((string)$val));
        }

        foreach ($xpath->query("//select[@name='$key']") as $sel) {
            foreach ($xpath->query(".//option", $sel) as $opt) {
                ((string)$opt->getAttribute('value') === (string)$val)
                    ? $opt->setAttribute('selected','selected')
                    : $opt->removeAttribute('selected');
            }
        }

        // fallback by id (if your json keys are ids)
        foreach ($xpath->query("//input[@id='$key']") as $input) {
            if ($isSignatureImageKey) {
                continue;
            }

            $type = strtolower($input->getAttribute('type'));
            if ($type === 'checkbox') {
                $checked = ($val == 1 || $val === true || $val === "1" || $val === "on");
                $checked ? $input->setAttribute('checked','checked') : $input->removeAttribute('checked');
            } elseif ($type === 'radio') {
                ((string)$input->getAttribute('value') === (string)$val)
                    ? $input->setAttribute('checked','checked')
                    : $input->removeAttribute('checked');
            } else {
                $input->setAttribute('value', (string)$val);
            }
        }

        foreach ($xpath->query("//textarea[@id='$key']") as $ta) {
            while ($ta->firstChild) $ta->removeChild($ta->firstChild);
            $ta->appendChild($dom->createTextNode((string)$val));
        }

        foreach ($xpath->query("//select[@id='$key']") as $sel) {
            foreach ($xpath->query(".//option", $sel) as $opt) {
                ((string)$opt->getAttribute('value') === (string)$val)
                    ? $opt->setAttribute('selected','selected')
                    : $opt->removeAttribute('selected');
            }
        }

        if ($normalizedImageValue !== '') {
        $normalizedVal = $this->normalize_signature_src_for_pdf((string)$val);
            foreach ($xpath->query("//img[@name='$key']") as $img) {
                $img->setAttribute('src', $normalizedImageValue);
            }

            foreach ($xpath->query("//img[@id='$key']") as $img) {
                $img->setAttribute('src', $normalizedImageValue);            }
        }

        $submitBars = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' submit-bar ')]");

        if ($submitBars && $submitBars->length > 0) {
            foreach ($submitBars as $bar) {
                $existingStyle = $bar->getAttribute('style');
                $bar->setAttribute('style', $existingStyle . '; display:none !important;');
            }
        }
    }

    $out = $dom->saveHTML();
    libxml_clear_errors();
    return $out;

}

private function apply_party_defaults_for_pdf(DOMXPath $xpath, array $saved): array
{
    $doctorName = trim((string)($saved['doctor_name'] ?? ''));
    $patientName = trim((string)($saved['patient_name'] ?? ''));

    if ($doctorName === '' && $patientName === '') {
        return $saved;
    }

    $doctorSign = $this->extract_signature_value($saved, 'doctor');
    $patientSign = $this->extract_signature_value($saved, 'patient');
    $doctorSignImage = $this->extract_signature_image_value($saved, 'doctor');
    $patientSignImage = $this->extract_signature_image_value($saved, 'patient');

    if ($doctorSign === '' && $doctorName !== '') {
        $doctorSign = $doctorName;
    }
    if ($patientSign === '' && $patientName !== '') {
        $patientSign = $patientName;
    }

    if (!isset($saved['doctor_signature']) && $doctorSign !== '') {
        $saved['doctor_signature'] = $doctorSign;
    }
    if (!isset($saved['patient_signature']) && $patientSign !== '') {
        $saved['patient_signature'] = $patientSign;
    }
    if (!isset($saved['doctor_signature_image']) && $doctorSignImage !== '') {
        $saved['doctor_signature_image'] = $doctorSignImage;
    }
    if (!isset($saved['patient_signature_image']) && $patientSignImage !== '') {
        $saved['patient_signature_image'] = $patientSignImage;
    }

    $this->apply_signature_images_for_pdf($xpath, $saved, $doctorSignImage, $patientSignImage);

    foreach ($xpath->query('//input|//textarea') as $node) {
        $keyName = trim((string)$node->getAttribute('name'));
        $keyId   = trim((string)$node->getAttribute('id'));

        $existingByName = ($keyName !== '' && isset($saved[$keyName]) && trim((string)$saved[$keyName]) !== '');
        $existingById   = ($keyId !== '' && isset($saved[$keyId]) && trim((string)$saved[$keyId]) !== '');
        if ($existingByName || $existingById) {
            continue;
        }

        $context = $this->collect_context_text_for_pdf($xpath, $node);
        if ($context === '') {
            continue;
        }

        $fillValue = '';
        if ($this->is_doctor_related_context($context) && $doctorName !== '') {
            $fillValue = $this->is_signature_related_context($context)
                ? ($doctorSign !== '' ? $doctorSign : $doctorName)
                : $doctorName;
        } elseif ($this->is_patient_related_context($context) && $patientName !== '') {
            $fillValue = $this->is_signature_related_context($context)
                ? ($patientSign !== '' ? $patientSign : $patientName)
                : $patientName;
        }

        if ($fillValue === '') {
            continue;
        }

        if ($keyName !== '' && !isset($saved[$keyName])) {
            $saved[$keyName] = $fillValue;
        }

        if ($keyId !== '' && !isset($saved[$keyId])) {
            $saved[$keyId] = $fillValue;
        }
    }

    return $saved;
}

private function apply_signature_images_for_pdf(DOMXPath $xpath, array $saved, string $doctorSignImage, string $patientSignImage): void
{
    if ($doctorSignImage === '' && $patientSignImage === '') {
        return;
    }

    foreach ($xpath->query('//img') as $img) {
        $context = $this->collect_context_text_for_pdf($xpath, $img);
        if ($context === '' || !$this->is_signature_related_context($context)) {
            continue;
        }

        $currentSrc = trim((string)$img->getAttribute('src'));
        if ($currentSrc !== '') {
            continue;
        }

        if ($doctorSignImage !== '' && $this->is_doctor_related_context($context)) {
          //  $img->setAttribute('src', $doctorSignImage);
                       $img->setAttribute('src', $this->normalize_signature_src_for_pdf($doctorSignImage)); 
            continue;
        }

        if ($patientSignImage !== '' && $this->is_patient_related_context($context)) {
             $img->setAttribute('src', $this->normalize_signature_src_for_pdf($patientSignImage));
           // $img->setAttribute('src', $patientSignImage);
        }
    }
}

private function normalize_signature_src_for_pdf(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }

    // base64 data URI — pass through directly (works in both Dompdf and mPDF)
    if (strpos($src, 'data:image/') === 0) {
        return $src;
    }

    // Already a file:// path — pass through
    if (strpos($src, 'file://') === 0) {
        return $src;
    }

    // Relative path starting with /uploads/
    if (strpos($src, '/uploads/') === 0) {
        $candidate = FCPATH . ltrim($src, '/');
        if (file_exists($candidate)) {
            return 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', realpath($candidate));
        }
    }

    // Absolute URL starting with the site base (http:// or https://)
    // resolve_doctor_signature_image() returns site_url()-based URLs — convert to file://
    $siteBase = rtrim(site_url(), '/');
    if (strpos($src, $siteBase) === 0) {
        $relative = ltrim(substr($src, strlen($siteBase)), '/');
        $candidate = FCPATH . $relative;
        if (file_exists($candidate)) {
            return 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', realpath($candidate));
        }
    }

    // Fallback: any http/https URL — try to map to local file via FCPATH
    if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
        // Strip scheme + host to get path portion
        $parsed = parse_url($src);
        if (!empty($parsed['path'])) {
            $candidate = FCPATH . ltrim($parsed['path'], '/');
            if (file_exists($candidate)) {
                return 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', realpath($candidate));
            }
        }
        // Cannot map to local — return as-is (Dompdf isRemoteEnabled will handle it)
        return $src;
    }

    return $src;
}

private function extract_signature_image_value(array $saved, string $party): string
{
    foreach ($saved as $k => $v) {
        if (!is_scalar($v)) {
            continue;
        }

        $key = strtolower((string)$k);
        $value = trim((string)$v);
        if ($value === '') {
            continue;
        }

        if (!$this->is_signature_related_context($key)) {
            continue;
        }

        if (!$this->is_signature_image_value($value)) {
            continue;
        }

        if ($party === 'doctor' && $this->is_doctor_related_context($key)) {
            return $value;
        }

        if ($party === 'patient' && $this->is_patient_related_context($key)) {
            return $value;
        }
    }

    return '';
}

private function is_signature_image_value(string $value): bool
{
    $v = strtolower(trim($value));

    if (strpos($v, 'data:image/') === 0) {
        return true;
    }

    if (preg_match('/\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i', $v)) {
        return true;
    }

    return (strpos($v, '/uploads/') !== false) || (strpos($v, 'http://') === 0) || (strpos($v, 'https://') === 0);
}

private function extract_signature_value(array $saved, string $party): string
{
    foreach ($saved as $k => $v) {
        if (!is_scalar($v)) {
            continue;
        }

        $key = strtolower((string)$k);
        $value = trim((string)$v);
        if ($value === '') {
            continue;
        }

        $isSignKey = (strpos($key, 'sign') !== false) || (strpos($key, 'signature') !== false);
        if (!$isSignKey) {
            continue;
        }

        if ($party === 'doctor' && $this->is_doctor_related_context($key)) {
            return $value;
        }

        if ($party === 'patient' && $this->is_patient_related_context($key)) {
            return $value;
        }
    }

    return '';
}

private function collect_context_text_for_pdf(DOMXPath $xpath, DOMNode $node): string
{
    $chunks = [];
    $chunks[] = strtolower(trim((string)$node->getAttribute('name')));
    $chunks[] = strtolower(trim((string)$node->getAttribute('id')));
    $chunks[] = strtolower(trim((string)$node->getAttribute('placeholder')));
    $chunks[] = strtolower(trim((string)$node->getAttribute('alt')));
    $chunks[] = strtolower(trim((string)$node->getAttribute('title')));
    $chunks[] = strtolower(trim((string)$node->getAttribute('class')));

    if ($node->parentNode) {
        $chunks[] = strtolower(trim((string)$node->parentNode->textContent));
    }

    $tableRow = $xpath->query('ancestor::tr[1]', $node)->item(0);
    if ($tableRow) {
        $chunks[] = strtolower(trim((string)$tableRow->textContent));
    }

    $label = $xpath->query('preceding::label[1]', $node)->item(0);
    if ($label) {
        $chunks[] = strtolower(trim((string)$label->textContent));
    }

    $name = trim((string)$node->getAttribute('name'));
    $id = trim((string)$node->getAttribute('id'));

    if ($id !== '') {
        $forLabel = $xpath->query("//label[@for='$id']")->item(0);
        if ($forLabel) {
            $chunks[] = strtolower(trim((string)$forLabel->textContent));
        }
    }

    if ($name !== '') {
        $forLabel = $xpath->query("//label[@for='$name']")->item(0);
        if ($forLabel) {
            $chunks[] = strtolower(trim((string)$forLabel->textContent));
        }
    }

    return trim(implode(' ', array_filter($chunks)));
}

private function is_doctor_related_context(string $context): bool
{
    return (bool) preg_match('/\b(doctor|consultant|dr\.?|surgeon|surgen)\b/i', $context);
}

private function is_patient_related_context(string $context): bool
{
    return (strpos($context, 'patient') !== false)
        || (strpos($context, 'relative') !== false)
        || (strpos($context, 'guardian') !== false)
        || (strpos($context, 'attendant') !== false);
}

private function is_signature_related_context(string $context): bool
{
    return (bool) preg_match('/\b(sign|signature|signator|signatory)\b/i', $context);
}


/*private function render_pdf_with_mpdf(string $html, string $filename)
{
    @set_time_limit(300);
    @ini_set('pcre.backtrack_limit', '10000000');
    @ini_set('pcre.recursion_limit', '10000000');

    $autoload = APPPATH . 'vendor/autoload.php';
    if (!file_exists($autoload)) show_error('Composer autoload not found: ' . $autoload);
    require_once $autoload;

    // ✅ temp folder must exist + writable
    $tmp = FCPATH . 'uploads/mpdf_tmp';
    if (!is_dir($tmp)) @mkdir($tmp, 0777, true);

    $fontDirPath = FCPATH . 'assets/fonts';
    $guReg  = $fontDirPath . '/NotoSansGujarati-Regular.ttf';
    $guBold = $fontDirPath . '/NotoSansGujarati-Bold.ttf';

    if (!file_exists($guReg)) show_error('Gujarati font missing: ' . $guReg);

    $defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

    $fontDirs = $defaultConfig['fontDir'];
    $fontData = $defaultFontConfig['fontdata'];

    $fontDirs[] = $fontDirPath;

    $fontData['notosansgujarati'] = [
        'R' => 'NotoSansGujarati-Regular.ttf',
        'B' => file_exists($guBold) ? 'NotoSansGujarati-Bold.ttf' : 'NotoSansGujarati-Regular.ttf',
    ];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'tempDir' => $tmp,

        'fontDir' => $fontDirs,
        'fontdata' => $fontData,
        'default_font' => 'notosansgujarati',

        // ✅ speed helpers
        'simpleTables' => true,
        'packTableData' => true,
        'use_kwt' => true,
        'dpi' => 96,
        'img_dpi' => 96,
    ]);

    // ❌ these are slow if you already force Gujarati font
    $mpdf->autoScriptToLang = false;
    $mpdf->autoLangToFont   = false;

    // ✅ remove slow web font loads (google fonts etc.)
    $html = preg_replace('~<link[^>]+fonts\.googleapis[^>]*>~i', '', $html);
    $html = preg_replace('~@import\s+url\([^)]+googleapis[^)]+\);~i', '', $html);

    // ✅ enforce font (no @font-face needed for mPDF when fontdata is set)
    $forceCss = '<style>
        @page { margin: 10mm; }
        body, table, tr, td, th, p, div, span, h1,h2,h3,h4,h5,h6 { font-family: notosansgujarati !important; }
    </style>';

    if (stripos($html, '</head>') !== false) $html = str_ireplace('</head>', $forceCss . '</head>', $html);
    else $html = $forceCss . $html;

    // ✅ IMPORTANT: many templates use "page { ... }" (wrong). Convert to @page
    $html = preg_replace('~\bpage\s*\{~i', '@page {', $html);

    // Keep this low; high values can slow layout a lot
    $mpdf->shrink_tables_to_fit = 0;
        
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::DEFAULT_MODE);

    $pdfBinary = $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

    return $this->output
        ->set_content_type('application/pdf')
        ->set_header('Content-Disposition: inline; filename="' . $filename . '"')
        ->set_output($pdfBinary);
}*/

private function render_pdf_with_mpdf($html, $filename)
{
    $autoload = APPPATH . 'vendor/autoload.php';
    if (!file_exists($autoload)) show_error('Composer autoload not found: ' . $autoload);
    require_once $autoload;

    // ✅ Font directory (local)
    $fontDirPath = FCPATH . 'assets/fonts/';
    $fontFile    = $fontDirPath . 'NotoSansGujarati-Regular.ttf';

    if (!is_dir($fontDirPath)) show_error('Font folder not found: ' . $fontDirPath);
    if (!file_exists($fontFile)) show_error('Font file not found: ' . $fontFile);

    // ✅ Merge defaults + custom font
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs      = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData          = $defaultFontConfig['fontdata'];

    $fontDirs[] = $fontDirPath;

    // key = notogujarati (same name used in CSS)
    $fontData['notogujarati'] = [
        'R' => 'NotoSansGujarati-Regular.ttf',
        // Optional if you have bold:
         'B' => 'NotoSansGujarati-Bold.ttf',
    ];

$fontData['liberationsans'] = [
        'R' => 'LiberationSans-Regular.ttf',
        'B' => 'LiberationSans-Bold.ttf',
    ];
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,

        'fontDir'   => $fontDirs,
        'fontdata'  => $fontData,
        'default_font' => 'notogujarati',
        'tempDir' => APPPATH . 'cache/mpdf', // ✅ make sure writable
    ]);

    // ✅ THIS FIXES BROKEN GUJARATI WORDS / SHAPING
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont   = true;

    // Speed/stability
    $mpdf->setAutoTopMargin = 'stretch';
    $mpdf->setAutoBottomMargin = 'stretch';
    $html = $this->mpdf_convert_checkboxes_to_symbols($html);
    $html = $this->replace_text_inputs_with_underline($html);

    $mpdf->WriteHTML($html);

    // Inline view in browser
    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    exit;
}


private function mpdf_convert_checkboxes_to_symbols($html)
{
    // Convert <input type="checkbox" checked> into ☑ and unchecked into ☐
    return preg_replace_callback('~<input\b([^>]*\btype\s*=\s*["\']checkbox["\'][^>]*)>~i', function ($m) {

        $attrs = $m[1];

        // checked?
        $isChecked = (bool)preg_match('~\bchecked\b~i', $attrs);

        // keep it safe: use DejaVu Sans for these symbols (mPDF always supports it)
        $symbol = $isChecked ? '☑' : '☐';

        // No layout css, only font fallback so symbols never disappear
        return '<span style="font-family: dejavusans, sans-serif;">' . $symbol . '</span>';
    }, $html);
}

private function replace_text_inputs_with_underline($html)
{
    return preg_replace_callback('~<input\b([^>]*?)>~i', function ($m) {

        $attrs = $m[1];

        // detect type (default text if missing)
        $type = 'text';
        if (preg_match('~\btype\s*=\s*["\']([^"\']+)["\']~i', $attrs, $tm)) {
            $type = strtolower(trim($tm[1]));
        }

        // only convert these to underline
        if (!in_array($type, ['text', 'date', 'number', 'tel', 'email'], true)) {
            return $m[0]; // keep as-is (hidden, radio, file, etc.)
        }

        // get value=""
        $value = '';
        if (preg_match('~\bvalue\s*=\s*["\']([^"\']*)["\']~i', $attrs, $vm)) {
            $value = $vm[1];
        }

        // width from style="width:xxx"
        $width = '200px';
        if (preg_match('~\bstyle\s*=\s*["\']([^"\']*)["\']~i', $attrs, $sm)) {
            if (preg_match('~width\s*:\s*([^;]+)~i', $sm[1], $wm)) {
                $width = trim($wm[1]);
            }
        }

        // if size="30" then make a reasonable width
        if ($width === '200px' && preg_match('~\bsize\s*=\s*["\'](\d+)["\']~i', $attrs, $sz)) {
            $width = ((int)$sz[1] * 6) . 'px';
        }

        // keep underline even if empty
        $safeValue = trim($value) === '' ? '&nbsp;' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return '<span class="mpdf-uline" style="width:' . $width . ';">' . $safeValue . '</span>';
    }, $html);
}
private function render_pdf_with_dompdf(string $html, string $filename)
{
    $autoload = APPPATH . 'vendor/autoload.php';
    if (!file_exists($autoload)) show_error('Composer autoload not found: ' . $autoload);
    require_once $autoload;

    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    // Allow loading local file:// images (signatures stored on disk)
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('fontSubsettingEnabled', true);
    $options->set('chroot', FCPATH);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfBinary = $dompdf->output();

    return $this->output
        ->set_content_type('application/pdf')
        ->set_header('Content-Disposition: inline; filename="' . $filename . '"')
        ->set_output($pdfBinary);
}
}