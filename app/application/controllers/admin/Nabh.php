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
            $this->_json(false, 'Not logged in');
        }

        // ✅ payload comes from POST (FormData)
        $payloadStr = $this->input->post('payload');
        $payload = json_decode($payloadStr, true);

        if (!is_array($payload)) {
            $this->_json(false, 'Invalid payload JSON');
        }

        $nabh_pdf_id         = (int)($payload['nabh_pdf_id'] ?? 0);
        $appointment_id      = (int)($payload['appointment_id'] ?? 0);
        $appointment_type_id = (int)($payload['appointment_type_id'] ?? 0);
        $patient_id          = (int)($payload['patient_id'] ?? 0);
        $doctor_id           = (int)($payload['doctor_id'] ?? 0);
        $lang                = in_array(($payload['lang'] ?? ''), ['en','gu'], true) ? $payload['lang'] : 'gu';

        if (!$nabh_pdf_id || !$appointment_id || !$patient_id) {
            $this->_json(false, 'Missing required fields');
        }

        $formData = $payload['form_data'] ?? [];
        if (!is_array($formData)) $formData = [];

        $insert = [
            'nabh_pdf_id'         => $nabh_pdf_id,
            'appointment_id'      => $appointment_id,
            'appointment_type_id' => $appointment_type_id,
            'patient_id'          => $patient_id,
            'doctor_id'           => $doctor_id,
            'lang'                => $lang,
            'patient_name'        => (string)($payload['patient_name'] ?? ''),
            'doctor_name'         => (string)($payload['doctor_name'] ?? ''),
            'form_data_json'      => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'created_by'          => get_staff_user_id(),
            'created_at'          => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix().'nabh_form_submissions', $insert);
        $id = $this->db->insert_id();

        $this->_json(true, 'Saved successfully', ['id' => $id]);
    }
    public function get_submission()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }

    $nabh_pdf_id     = (int) $this->input->get('nabh_pdf_id');
    $appointment_id  = (int) $this->input->get('appointment_id');
    $patient_id      = (int) $this->input->get('patient_id');
    $lang            = $this->input->get('lang', true);

    if ($nabh_pdf_id <= 0) {
        echo json_encode(['status' => false, 'message' => 'Missing nabh_pdf_id']);
        return;
    }

    $this->db->from('tblnabh_pdf_submissions'); // ✅ change table name as per yours
    $this->db->where('nabh_pdf_id', $nabh_pdf_id);

    // Use whichever key is correct in your project:
    if ($appointment_id > 0) $this->db->where('appointment_id', $appointment_id);
    else if ($patient_id > 0) $this->db->where('patient_id', $patient_id);

    if (!empty($lang)) $this->db->where('lang', $lang);

    $row = $this->db->order_by('id', 'DESC')->get()->row_array();

    if (!$row) {
        echo json_encode(['status' => true, 'found' => false, 'data' => null]);
        return;
    }

    // If form_data column is JSON string
    $row['form_data'] = !empty($row['form_data']) ? json_decode($row['form_data'], true) : [];

    echo json_encode(['status' => true, 'found' => true, 'data' => $row]);
}




}
