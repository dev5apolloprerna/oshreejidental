<?php defined('BASEPATH') or exit('No direct script access allowed');

class Nabh extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * AJAX
     * POST: appointment_type_id
     * Returns only assigned NABH forms for appointment type
     */
    public function list_json()
    {
        if (!is_staff_logged_in()) {
            ajax_access_denied();
        }

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $appointment_type_id = (int)$this->input->post('appointment_type_id');
        if ($appointment_type_id <= 0) {
            echo json_encode(['status' => false, 'data' => [], 'message' => 'appointment_type_id required']);
            exit;
        }

        $mapTable  = db_prefix() . 'appointment_type_pdf_master';
        $nabhTable = db_prefix() . 'nabh_master';

        // ✅ only assigned forms
        $this->db->select("n.pdf_id, n.pdf_name, n.english_file_name, n.gujarati_file_name", false);
        $this->db->from($mapTable . ' m');
        $this->db->join($nabhTable . ' n', 'n.pdf_id = m.appointment_pdf_id', 'inner');
        $this->db->where('m.appointment_type_id', $appointment_type_id);
        $this->db->order_by('n.pdf_id', 'ASC');
        $rows = $this->db->get()->result_array();

        // ✅ same folder logic as your old code
        $enDir = FCPATH . 'uploads/nabh/english/';
        $guDir = FCPATH . 'uploads/nabh/gujarati/';

        $out = [];
        foreach ($rows as $r) {
            $enFile = trim((string)($r['english_file_name'] ?? ''));
            $guFile = trim((string)($r['gujarati_file_name'] ?? ''));

            $hasEn = ($enFile !== '' && file_exists($enDir . basename($enFile)));
            $hasGu = ($guFile !== '' && file_exists($guDir . basename($guFile)));

            // you have pdf_name only (same for both languages)
            $title = (string)($r['pdf_name'] ?? 'NABH Form');

            $out[] = [
                // ✅ keep JS compatible: r.id
                'id'       => (int)$r['pdf_id'],

                // ✅ keep JS compatible: r.title_en / r.title_gu
                'title_en' => $title,
                'title_gu' => $title,

                'has_en'   => $hasEn ? 1 : 0,
                'has_gu'   => $hasGu ? 1 : 0,
            ];
        }

        echo json_encode(['status' => true, 'data' => $out]);
        exit;
    }

    /**
     * ✅ Serve HTML in iframe (modal)
     * URL: admin/nabh/view_html/{pdf_id}?lang=en|gu
     */
    public function view_html($pdf_id)
    {
        if (!is_staff_logged_in()) {
            access_denied();
        }

        $pdf_id = (int)$pdf_id;
        if ($pdf_id <= 0) show_404();

        $lang = $this->input->get('lang'); // en / gu
        $lang = in_array($lang, ['en', 'gu'], true) ? $lang : 'gu';

        $nabhTable = db_prefix() . 'nabh_master';

        // ✅ pdf_id is primary key in your table
        $this->db->where('pdf_id', $pdf_id);
        $row = $this->db->get($nabhTable)->row_array();

        if (!$row) {
            show_404();
        }

        $enDir = FCPATH . 'uploads/nabh/english/';
        $guDir = FCPATH . 'uploads/nabh/gujarati/';

        $enFile = trim((string)($row['english_file_name'] ?? ''));
        $guFile = trim((string)($row['gujarati_file_name'] ?? ''));

        $enPath = ($enFile !== '') ? ($enDir . basename($enFile)) : '';
        $guPath = ($guFile !== '') ? ($guDir . basename($guFile)) : '';

        // ✅ preferred language, else fallback (same as old code)
        $path = '';
        if ($lang === 'gu') {
            if ($guPath && file_exists($guPath)) $path = $guPath;
            elseif ($enPath && file_exists($enPath)) $path = $enPath;
        } else {
            if ($enPath && file_exists($enPath)) $path = $enPath;
            elseif ($guPath && file_exists($guPath)) $path = $guPath;
        }

        if ($path === '' || !file_exists($path)) {
            show_error('HTML file not found in uploads/nabh folder.', 404);
        }

        // ✅ HTML only
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['html', 'htm'], true)) {
            show_error('This form file is not an HTML file.', 404);
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
    public function save_submission()
    {
        if (!is_staff_logged_in()) {
            return $this->_json(false, 'Not logged in', null, true);
        }

        // ✅ 1) Payload from FormData (payload field)
        $payloadStr = $this->input->post('payload');

        // ✅ 2) If not present, fallback to raw JSON request body
        if (!$payloadStr) {
            $payloadStr = $this->input->raw_input_stream;
        }

        if (!$payloadStr || trim($payloadStr) === '') {
            return $this->_json(false, 'Missing payload', null, true);
        }

        $payload = json_decode($payloadStr, true);

        // ✅ Debug if invalid JSON (will show what server received)
        if (!is_array($payload)) {
            return $this->_json(false, 'Invalid JSON body', [
                'received' => substr($payloadStr, 0, 200)
            ], true);
        }

        // ✅ Required fields
        $nabh_pdf_id         = (int)($payload['nabh_pdf_id'] ?? 0);
        $appointment_id      = (int)($payload['appointment_id'] ?? 0);
        $appointment_type_id = (int)($payload['appointment_type_id'] ?? 0);
        $patient_id          = (int)($payload['patient_id'] ?? 0);
        $doctor_id           = (int)($payload['doctor_id'] ?? 0);
        $lang                = in_array(($payload['lang'] ?? ''), ['en','gu'], true) ? $payload['lang'] : 'gu';

        if ($nabh_pdf_id <= 0 || $patient_id <= 0) {
            return $this->_json(false, 'Missing required fields (nabh_pdf_id/patient_id)', null, true);
        }

        // ✅ Form data JSON
        $formData = $payload['form_data'] ?? [];
        if (!is_array($formData)) $formData = [];

        // Optional: keep patient/doctor name inside JSON too
        $patient_name = (string)($payload['patient_name'] ?? ($formData['patient_name'] ?? ''));
        $doctor_name  = (string)($payload['doctor_name']  ?? ($formData['doctor_name']  ?? ''));

        if ($patient_name !== '' && !isset($formData['patient_name'])) $formData['patient_name'] = $patient_name;
        if ($doctor_name  !== '' && !isset($formData['doctor_name']))  $formData['doctor_name']  = $doctor_name;

        $table = db_prefix().'nabh_form_submissions';

        /**
         * ✅ UPSERT RULE (update if already exists):
         * same nabh_pdf_id + patient_id + appointment_id + lang
         * (appointment_id can be 0 in some cases; then it matches 0)
         */
        $this->db->from($table);
        $this->db->where('nabh_pdf_id', $nabh_pdf_id);
        $this->db->where('patient_id', $patient_id);
        $this->db->where('appointment_id', $appointment_id);
        $this->db->where('lang', $lang);

        $existing = $this->db->order_by('id', 'DESC')->get()->row_array();

        $now = date('Y-m-d H:i:s');
        $staff_id = get_staff_user_id();

        $data = [
            'nabh_pdf_id'         => $nabh_pdf_id,
            'appointment_id'      => $appointment_id,
            'appointment_type_id' => $appointment_type_id,
            'patient_id'          => $patient_id,
            'doctor_id'           => $doctor_id,
            'lang'                => $lang,
            'patient_name'        => $patient_name,
            'doctor_name'         => $doctor_name,
            'form_data_json'      => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'updated_by'          => $staff_id,
            'updated_at'          => $now,
        ];

        if ($existing) {
            $this->db->where('id', (int)$existing['id']);
            $this->db->update($table, $data);

            return $this->_json(true, 'Updated successfully', [
                'id' => (int)$existing['id']
            ], true);
        }

        // Insert new
        $data['created_by'] = $staff_id;
        $data['created_at'] = $now;

        // remove updated_* if your table doesn't have them
        // (safe even if it exists; but if not exists, uncomment next 2 lines)
        // unset($data['updated_by'], $data['updated_at']);

        $this->db->insert($table, $data);
        $id = (int)$this->db->insert_id();

        return $this->_json(true, 'Saved successfully', ['id' => $id], true);
    }


       private function _json($status, $message, $data = null, $include_csrf = false)
    {
        $out = [
            'status'  => (bool)$status,
            'message' => (string)$message,
            'data'    => $data
        ];

        if ($include_csrf) {
            $out['csrf_name'] = $this->security->get_csrf_token_name();
            $out['csrf_hash'] = $this->security->get_csrf_hash();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out);
        exit;
    }



     public function get_submission()
    {
        if (!is_staff_logged_in()) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Not logged in']));
            return;
        }

        $nabh_pdf_id    = (int)$this->input->get('nabh_pdf_id');
        $appointment_id = (int)$this->input->get('appointment_id');
        $patient_id     = (int)$this->input->get('patient_id');
        $lang           = $this->input->get('lang', true);
        $lang           = in_array($lang, ['en','gu'], true) ? $lang : 'gu';

        $table = db_prefix().'nabh_form_submissions';

        $this->db->from($table);
        $this->db->where('nabh_pdf_id', $nabh_pdf_id);
        $this->db->where('lang', $lang);
        if ($appointment_id > 0) $this->db->where('appointment_id', $appointment_id);
        if ($patient_id > 0)     $this->db->where('patient_id', $patient_id);

        $row = $this->db->order_by('id', 'DESC')->get()->row_array();

        $formData = [];
        if ($row && !empty($row['form_data_json'])) {
            $tmp = json_decode($row['form_data_json'], true);
            if (is_array($tmp)) $formData = $tmp;
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'found'  => $row ? true : false,
                'data'   => $row ? $formData : null,
            ]));
    }




}
