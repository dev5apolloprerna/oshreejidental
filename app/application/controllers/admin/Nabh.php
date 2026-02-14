<?php defined('BASEPATH') or exit('No direct script access allowed');

class Nabh extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('nabh_model');
    }

    // Optional: list for table (same logic as before, checks if file exists)
    public function list_json()
    {
        if (!is_staff_logged_in()) {
            ajax_access_denied();
        }

        $rows = $this->nabh_model->get_all();

        $enDir = FCPATH . 'uploads/nabh/english/';
        $guDir = FCPATH . 'uploads/nabh/gujarati/';

        $out = [];
        foreach ($rows as $r) {
            $enFile = trim((string)($r['english_file_name'] ?? ''));
            $guFile = trim((string)($r['gujarati_file_name'] ?? ''));

            $hasEn = ($enFile !== '' && file_exists($enDir . basename($enFile)));
            $hasGu = ($guFile !== '' && file_exists($guDir . basename($guFile)));

            // Change these if your table has different title fields
            $titleEn = (string)($r['english_title'] ?? $r['title'] ?? 'English Form');
            $titleGu = (string)($r['gujarati_title'] ?? $r['title'] ?? 'Gujarati Form');

            $out[] = [
                'id'        => (int)$r['id'],
                'has_en'    => $hasEn,
                'has_gu'    => $hasGu,
                'title_en'  => $titleEn,
                'title_gu'  => $titleGu,
            ];
        }

        echo json_encode(['status' => true, 'data' => $out]);
        exit;
    }

    // ✅ Serve HTML in iframe (modal)
    public function view_html($id)
    {
        if (!is_staff_logged_in()) {
            access_denied();
        }

        $lang = $this->input->get('lang'); // en / gu
        $lang = in_array($lang, ['en', 'gu'], true) ? $lang : 'gu';

        $row = $this->nabh_model->get($id);
        if (!$row) {
            show_404();
        }

        $enDir = FCPATH . 'uploads/nabh/english/';
        $guDir = FCPATH . 'uploads/nabh/gujarati/';

        $enFile = trim((string)($row['english_file_name'] ?? ''));
        $guFile = trim((string)($row['gujarati_file_name'] ?? ''));

        $enPath = ($enFile !== '') ? ($enDir . basename($enFile)) : '';
        $guPath = ($guFile !== '') ? ($guDir . basename($guFile)) : '';

        // ✅ preferred language, else fallback
        $path = '';
        if ($lang === 'gu') {
            if ($guPath && file_exists($guPath)) $path = $guPath;
            elseif ($enPath && file_exists($enPath)) $path = $enPath;
        } else {
            if ($enPath && file_exists($enPath)) $path = $enPath;
            elseif ($guPath && file_exists($guPath)) $path = $guPath;
        }

        if ($path === '' || !file_exists($path)) {
            show_error('HTML file not found.', 404);
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
