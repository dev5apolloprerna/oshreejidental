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
            echo json_encode(['status' => false, 'data' => []]);
            exit;
        }

        $this->db->where('appointment_type_id', $appointment_type_id);
        $rows = $this->db->get(db_prefix() . 'appointment_type_pdf_master')->result_array();

        $data = [];

        foreach ($rows as $r) {
            $pdf = $this->db->where('pdf_id', $r['appointment_pdf_id'])
                ->get('tblnabh_master')
                ->row_array();

            if (!$pdf) continue;

            $data[] = [
                'id'       => $pdf['pdf_id'],
                'title_en' => $pdf['pdf_name'],
                'title_gu' => $pdf['pdf_name'],
                'has_en'   => !empty($pdf['english_file_name']),
                'has_gu'   => !empty($pdf['gujarati_file_name']),
            ];
        }

        echo json_encode(['status' => true, 'data' => $data]);
        exit;
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

        // 1) Get template
        $pdf = $this->db->where('pdf_id', $pdf_id)->get('tblnabh_master')->row_array();
        if (!$pdf) show_error('Invalid PDF');

        $lang = ($lang === 'en') ? 'en' : 'gu';

        $fileName = ($lang === 'en')
            ? trim((string)($pdf['english_file_name'] ?? ''))
            : trim((string)($pdf['gujarati_file_name'] ?? ''));

        if ($fileName === '') {
            // fallback other language
            $fileName = trim((string)($pdf['english_file_name'] ?? '')) ?: trim((string)($pdf['gujarati_file_name'] ?? ''));
        }

        if ($fileName === '') show_error('Template file missing');

        $langFolder = ($lang === 'en') ? 'english' : 'gujarati';
        $path = FCPATH . 'uploads/nabh/' . $langFolder . '/' . basename($fileName);

        if (!file_exists($path)) show_error('Template file not found: ' . $path);

        $html = file_get_contents($path);

        // 2) Get saved submission
        $row = $this->db->where('nabh_pdf_id', $pdf_id)
            ->where('patient_id', $patient_id)
            ->where('appointment_id', $appointment_id)
            ->where('lang', $lang)
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'nabh_form_submissions')
            ->row_array();

        $saved = [];
        if ($row && !empty($row['form_data_json'])) {
            $decoded = json_decode($row['form_data_json'], true);
            if (is_array($decoded)) $saved = $decoded;
        }

        $patient_name = urldecode((string)$this->input->get('patient_name', true));
        $doctor_name  = urldecode((string)$this->input->get('doctor_name', true));

        $ctx = [
            'pdf_id'              => $pdf_id,
            'appointment_id'      => $appointment_id,
            'appointment_type_id' => $appointment_type_id,
            'patient_id'          => $patient_id,
            'doctor_id'           => $doctor_id,
            'lang'                => $lang,
            'patient_name'        => $patient_name,
            'doctor_name'         => $doctor_name,
        ];

        $html = $this->inject_global($html, $ctx, $saved);

        echo $html;
        exit;
    }

    /* =========================================================
       3) SAVE SUBMISSION (UPSERT)
    ==========================================================*/
    public function save_submission()
    {
        $payloadStr = $this->input->post('payload');
        if (!$payloadStr) {
            echo json_encode(['status' => false, 'message' => 'Missing payload']);
            exit;
        }

        $payload = json_decode($payloadStr, true);
        if (!is_array($payload)) {
            echo json_encode(['status' => false, 'message' => 'Invalid JSON']);
            exit;
        }

        $patient_name = trim($payload['patient_name'] ?? '');
        $doctor_name  = trim($payload['doctor_name'] ?? '');

        // fallback: from form_data
        if ($patient_name === '' && isset($payload['form_data']['patient_name'])) {
            $patient_name = trim((string)$payload['form_data']['patient_name']);
        }
        if ($doctor_name === '' && isset($payload['form_data']['doctor_name'])) {
            $doctor_name = trim((string)$payload['form_data']['doctor_name']);
        }

        $pdf_id              = (int)($payload['nabh_pdf_id'] ?? 0);
        $appointment_id      = (int)($payload['appointment_id'] ?? 0);
        $appointment_type_id = (int)($payload['appointment_type_id'] ?? 0);
        $patient_id          = (int)($payload['patient_id'] ?? 0);
        $doctor_id           = (int)($payload['doctor_id'] ?? 0);
        $lang                = (($payload['lang'] ?? 'en') === 'gu') ? 'gu' : 'en';

        $table = db_prefix() . 'nabh_form_submissions';

        $existing = $this->db->where('nabh_pdf_id', $pdf_id)
            ->where('patient_id', $patient_id)
            ->where('appointment_id', $appointment_id)
            ->where('lang', $lang)
            ->get($table)
            ->row_array();

        $data = [
            'nabh_pdf_id'          => $pdf_id,
            'appointment_id'       => $appointment_id,
            'appointment_type_id'  => $appointment_type_id,
            'patient_id'           => $patient_id,
            'doctor_id'            => $doctor_id,
            'lang'                 => $lang,
            'patient_name'         => $patient_name,
            'doctor_name'          => $doctor_name,
            'form_data_json'       => json_encode($payload['form_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'updated_at'           => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id', $existing['id'])->update($table, $data);
            echo json_encode(['status' => true, 'message' => 'Updated']);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert($table, $data);
            echo json_encode(['status' => true, 'message' => 'Saved']);
        }
        exit;
    }

    /* =========================================================
       4) GLOBAL SCRIPT INJECTION (FORM PAGE)
    ==========================================================*/
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

    /* =========================================================
       5) PRINT PDF (mPDF for Gujarati, DOMPDF for English)
       ✅ Fixed Gujarati words + Fixed '?' issue (UTF-8)
    ==========================================================*/
    public function print_pdf()
    {
        $raw = file_get_contents('php://input');
        $req = json_decode($raw, true);

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
        $lang           = (($req['lang'] ?? 'en') === 'gu') ? 'gu' : 'en';

        if ($nabh_pdf_id <= 0 || $appointment_id <= 0) {
            return $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Missing nabh_pdf_id / appointment_id']));
        }

        // 1) Master
        $master = $this->db->where('pdf_id', $nabh_pdf_id)->get('tblnabh_master')->row();
        if (!$master) return $this->output->set_status_header(404)->set_output('NABH master record not found');

        // 2) Template filename (fallback other language)
        $file = ($lang === 'gu') ? ($master->gujarati_file_name ?? '') : ($master->english_file_name ?? '');
        $file = trim((string)$file);
        if ($file === '') {
            $file = trim((string)($master->english_file_name ?? '')) ?: trim((string)($master->gujarati_file_name ?? ''));
        }
        if ($file === '') return $this->output->set_status_header(404)->set_output('Template file missing for both languages');

        // 3) Load HTML
        $folder = ($lang === 'gu') ? 'gujarati/' : 'english/';
        $path = FCPATH . 'uploads/nabh/' . $folder . basename($file);
        if (!file_exists($path)) return $this->output->set_status_header(404)->set_output('HTML template file missing: ' . $path);

        $html = file_get_contents($path);

        // ✅ Fix: Ensure HTML is treated as UTF-8 (prevents '?' from bad decoding paths)
        // (If your file is saved in UTF-8 this is safe; if not, still helps.)
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        // 4) Saved JSON (request first, else DB)
        $saved = $req['form_data_json'] ?? [];
        if (!is_array($saved)) $saved = [];

        if (empty($saved)) {
            $row = $this->db->where('nabh_pdf_id', $nabh_pdf_id)
                ->where('appointment_id', $appointment_id)
                ->where('lang', $lang)
                ->order_by('id', 'DESC')
                ->get(db_prefix() . 'nabh_form_submissions')
                ->row();

            if ($row && !empty($row->form_data_json)) {
                $decoded = json_decode($row->form_data_json, true);
                if (is_array($decoded)) $saved = $decoded;
            }

            if (empty($req['patient_name']) && $row && !empty($row->patient_name)) $req['patient_name'] = $row->patient_name;
            if (empty($req['doctor_name'])  && $row && !empty($row->doctor_name))  $req['doctor_name']  = $row->doctor_name;
        }

        // 5) Common fields
        if (!isset($saved['patient_name']) && !empty($req['patient_name'])) $saved['patient_name'] = $req['patient_name'];
        if (!isset($saved['doctor_name'])  && !empty($req['doctor_name']))  $saved['doctor_name']  = $req['doctor_name'];
        if (!isset($saved['today_date'])) $saved['today_date'] = date('d/m/Y');

        // 6) Convert inputs/select/textarea into printable text (server-side)
        $html = $this->apply_saved_to_html_for_pdf($html, $saved);

        // 7) Remove submit controls
        $html = preg_replace('~<div[^>]*class="submit-bar"[^>]*>.*?</div>~is', '', $html);
        $html = preg_replace('~<button[^>]*id="submitBtn"[^>]*>.*?</button>~is', '', $html);
        $html = preg_replace('~<div[^>]*id="status"[^>]*>.*?</div>~is', '', $html);

        // ✅ 8) Gujarati font force CSS (NO @font-face; mPDF handles font files)
        if ($lang === 'gu') {
            $forceCss = '<style>
                body, table, tr, td, th, p, div, span, h1,h2,h3,h4,h5,h6 {
                    font-family: notogujarati !important;
                }
            </style>';

            if (stripos($html, '</head>') !== false) $html = str_ireplace('</head>', $forceCss . '</head>', $html);
            else $html = $forceCss . $html;
        }

        // 9) Output
        $title = trim((string)($master->pdf_name ?? 'NABH'));
        $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $title) . '_' . date('Ymd_His') . '.pdf';

        if ($lang === 'gu') return $this->render_pdf_with_mpdf($html, $filename);
        return $this->render_pdf_with_dompdf($html, $filename);
    }

    /* =========================================================
       6) APPLY SAVED VALUES TO HTML FOR PDF
       ✅ Fixed '?' issue with mb_convert_encoding + HTML-ENTITIES
    ==========================================================*/
    private function apply_saved_to_html_for_pdf($html, array $saved)
    {
        libxml_use_internal_errors(true);

        // ✅ IMPORTANT: DOMDocument can break UTF-8 unless we convert to HTML-ENTITIES
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        $getSaved = function ($node) use ($saved) {
            $name = (string)$node->getAttribute('name');
            $id   = (string)$node->getAttribute('id');

            if ($name !== '' && array_key_exists($name, $saved)) return $saved[$name];
            if ($id !== '' && array_key_exists($id, $saved)) return $saved[$id];
            return null;
        };

        $copyAttrs = function ($from, $to) {
            $cls = $from->getAttribute('class');
            $sty = $from->getAttribute('style');
            if ($cls !== '') $to->setAttribute('class', $cls);
            if ($sty !== '') $to->setAttribute('style', $sty);
        };

        foreach ($xpath->query('//input') as $input) {
            $type = strtolower((string)$input->getAttribute('type'));
            if ($type === '') $type = 'text';

            $val = $getSaved($input);
            if ($val === null) $val = (string)$input->getAttribute('value');

            if ($type === 'checkbox') {
                $mark = ($val == 1 || $val === "1" || $val === true || $val === "on") ? '[x]' : '[ ]';
                $span = $dom->createElement('span');
                $copyAttrs($input, $span);
                $span->appendChild($dom->createTextNode($mark));
                $input->parentNode->replaceChild($span, $input);
                continue;
            }

            if ($type === 'radio') {
                $radioVal = (string)$input->getAttribute('value');
                $selected = ((string)$val === $radioVal);
                $span = $dom->createElement('span');
                $copyAttrs($input, $span);
                $span->appendChild($dom->createTextNode($selected ? '(x)' : '( )'));
                $input->parentNode->replaceChild($span, $input);
                continue;
            }

            $span = $dom->createElement('span');
            $copyAttrs($input, $span);
            $span->appendChild($dom->createTextNode((string)$val));
            $input->parentNode->replaceChild($span, $input);
        }

        foreach ($xpath->query('//textarea') as $ta) {
            $val = $getSaved($ta);
            if ($val === null) $val = (string)$ta->textContent;

            $span = $dom->createElement('span');
            $copyAttrs($ta, $span);
            $span->appendChild($dom->createTextNode((string)$val));
            $ta->parentNode->replaceChild($span, $ta);
        }

        foreach ($xpath->query('//select') as $sel) {
            $val = $getSaved($sel);
            if ($val === null) $val = '';

            $selectedText = '';
            foreach ($xpath->query(".//option", $sel) as $opt) {
                if ((string)$opt->getAttribute('value') === (string)$val) {
                    $selectedText = trim($opt->textContent);
                    break;
                }
            }
            if ($selectedText === '') $selectedText = (string)$val;

            $span = $dom->createElement('span');
            $copyAttrs($sel, $span);
            $span->appendChild($dom->createTextNode($selectedText));
            $sel->parentNode->replaceChild($span, $sel);
        }

        $out = $dom->saveHTML();
        libxml_clear_errors();

        // ✅ Convert back to UTF-8 text
        $out = html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $out;
    }

    /* =========================================================
       7) RENDER PDF WITH mPDF (Gujarati)
       ✅ Fixed Gujarati shaping + UTF-8 conversion
    ==========================================================*/
    private function render_pdf_with_mpdf($html, $filename)
    {
        $autoload = APPPATH . 'vendor/autoload.php';
        if (!file_exists($autoload)) show_error('Composer autoload not found: ' . $autoload);
        require_once $autoload;

        $fontDirPath = FCPATH . 'assets/fonts/';
        $fontFile    = $fontDirPath . 'NotoSansGujarati-Regular.ttf';

        if (!is_dir($fontDirPath)) show_error('Font folder not found: ' . $fontDirPath);
        if (!file_exists($fontFile)) show_error('Font file not found: ' . $fontFile);

        // Ensure tempDir exists
        $tmp = APPPATH . 'cache/mpdf';
        if (!is_dir($tmp)) @mkdir($tmp, 0777, true);

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $fontDirs[] = $fontDirPath;

        $fontData['notogujarati'] = [
            'R' => 'NotoSansGujarati-Regular.ttf',
            // 'B' => 'NotoSansGujarati-Bold.ttf', // if exists
        ];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'notogujarati',
            'tempDir' => $tmp,
        ]);

        // ✅ Gujarati shaping fix
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;

        // ✅ UTF-8 conversion safety (reduces '?' issues)
        $mpdf->allow_charset_conversion = true;
        $mpdf->charset_in = 'UTF-8';

        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    /* =========================================================
       8) RENDER PDF WITH DOMPDF (English)
    ==========================================================*/
    private function render_pdf_with_dompdf(string $html, string $filename)
    {
        $autoload = APPPATH . 'vendor/autoload.php';
        if (!file_exists($autoload)) show_error('Composer autoload not found: ' . $autoload);
        require_once $autoload;

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
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