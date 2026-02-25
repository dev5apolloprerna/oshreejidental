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
            echo json_encode(['status' => false, 'data' => []]); exit;
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
        $lang                = ($this->input->get('lang') === 'en') ? 'en' : 'gu';

        $pdf = $this->db->where('pdf_id', $pdf_id)
                        ->get('tblnabh_master')
                        ->row_array();

        if (!$pdf) show_error('Invalid PDF');

        $fileName = ($lang === 'en')
            ? trim((string)($pdf['english_file_name'] ?? ''))
            : trim((string)($pdf['gujarati_file_name'] ?? ''));

        if ($fileName === '') {
            // fallback: other lang file
            $fileName = trim((string)($pdf['english_file_name'] ?? '')) ?: trim((string)($pdf['gujarati_file_name'] ?? ''));
        }

        if ($fileName === '') show_error('Template file missing');

        $langFolder = ($lang === 'en') ? 'english' : 'gujarati';
        $path = FCPATH . 'uploads/nabh/' . $langFolder . '/' . basename($fileName);

        if (!file_exists($path)) {
            show_error('Template file not found: ' . $path);
            return;
        }

        $html = file_get_contents($path);

        // saved submission
        $this->db->where('nabh_pdf_id', $pdf_id);
        $this->db->where('patient_id', $patient_id);
        $this->db->where('appointment_id', $appointment_id);
        $this->db->where('lang', $lang);

        $row = $this->db->order_by('id', 'DESC')
                        ->get(db_prefix() . 'nabh_form_submissions')
                        ->row_array();

        $saved = [];
        if ($row && !empty($row['form_data_json'])) {
            $saved = json_decode($row['form_data_json'], true);
            if (!is_array($saved)) $saved = [];
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
            echo json_encode(['status' => false, 'message' => 'Missing payload']); exit;
        }

        $payload = json_decode($payloadStr, true);
        if (!is_array($payload)) {
            echo json_encode(['status' => false, 'message' => 'Invalid JSON']); exit;
        }

        $patient_name = trim((string)($payload['patient_name'] ?? ''));
        $doctor_name  = trim((string)($payload['doctor_name'] ?? ''));

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

        $this->db->where('nabh_pdf_id', $pdf_id);
        $this->db->where('patient_id', $patient_id);
        $this->db->where('appointment_id', $appointment_id);
        $this->db->where('lang', $lang);

        $existing = $this->db->get($table)->row_array();

        $data = [
            'nabh_pdf_id'         => $pdf_id,
            'appointment_id'      => $appointment_id,
            'appointment_type_id' => $appointment_type_id,
            'patient_id'          => $patient_id,
            'doctor_id'           => $doctor_id,
            'lang'                => $lang,
            'patient_name'        => $patient_name,
            'doctor_name'         => $doctor_name,
            'form_data_json'      => json_encode($payload['form_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'updated_at'          => date('Y-m-d H:i:s'),
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
       4) GLOBAL JS INJECTION
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
       5) PDF: FAST TEMPLATE CACHE (HTML + CSS)
       - creates:   template.pdfcache.html
       - creates:   template.pdfcache.css
    ==========================================================*/
    private function get_pdf_cached_template($path)
    {
        $cacheHtml = $path . '.pdfcache.html';
        $cacheCss  = $path . '.pdfcache.css';

        if (file_exists($cacheHtml) && file_exists($cacheCss) && filemtime($cacheHtml) >= filemtime($path)) {
            return [$cacheHtml, $cacheCss];
        }

        $html = file_get_contents($path);
        $css  = '';

        // A) extract proper <style> blocks
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $html, $m)) {
            foreach ($m[1] as $block) $css .= "\n" . $block;
            $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);
        }

        // B) extract leaked CSS chunk if present in body text (your screenshot issue)
        if (preg_match('#/\*\s*Reset.*?\*/.*?(?=ADDRESSOGRAPH|<body|<div|<p|<h1|<h2|<table)#is', $html, $m2)) {
            $css .= "\n" . $m2[0];
            $html = str_replace($m2[0], '', $html);
        } else {
            if (preg_match('#@page\s*\{.*?\}.*?(?=ADDRESSOGRAPH|<body|<div|<p|<h1|<h2|<table)#is', $html, $m3)) {
                $css .= "\n" . $m3[0];
                $html = str_replace($m3[0], '', $html);
            }
        }

        // C) remove any remaining "/* Reset..." lines
        $html = preg_replace('#(/\*\s*Reset.*)$#im', '', $html);

        // D) save cache
        file_put_contents($cacheHtml, $html);
        file_put_contents($cacheCss, $css);

        return [$cacheHtml, $cacheCss];
    }

    /* =========================================================
       6) FAST APPLY SAVED VALUES (NO DOMDocument)
    ==========================================================*/
    private function apply_saved_to_html_fast(string $html, array $saved): string
    {
        if (empty($saved)) return $html;

        foreach ($saved as $key => $val) {
            $key = (string)$key;
            $v   = is_array($val) ? '' : (string)$val;

            $k = preg_quote($key, '#');
            $vEsc = htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // INPUT by name
            $html = preg_replace_callback(
                '#<input\b([^>]*\bname\s*=\s*([\'"])' . $k . '\2[^>]*)>#i',
                function ($m) use ($v) {
                    $tag = $m[0];
                    $attrs = $m[1];

                    $type = '';
                    if (preg_match('#\btype\s*=\s*([\'"])(.*?)\1#i', $attrs, $tm)) $type = strtolower($tm[2]);

                    if ($type === 'checkbox') {
                        $checked = ($v == '1' || $v === 1 || $v === true || strtolower((string)$v) === 'on');
                        $tag = preg_replace('#\schecked(\s*=\s*([\'"]).*?\2)?#i', '', $tag);
                        if ($checked) $tag = rtrim($tag, '>') . ' checked="checked">';
                        return $tag;
                    }

                    if ($type === 'radio') {
                        return $tag; // handled below
                    }

                    if (preg_match('#\bvalue\s*=\s*([\'"]).*?\1#i', $tag)) {
                        $tag = preg_replace(
                            '#\bvalue\s*=\s*([\'"]).*?\1#i',
                            'value="' . htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"',
                            $tag
                        );
                    } else {
                        $tag = rtrim($tag, '>') . ' value="' . htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                    }
                    return $tag;
                },
                $html
            );

            // RADIO by name: check match
            $html = preg_replace_callback(
                '#<input\b([^>]*\btype\s*=\s*([\'"])radio\2[^>]*\bname\s*=\s*([\'"])' . $k . '\3[^>]*)>#i',
                function ($m) use ($v) {
                    $tag = $m[0];
                    $tag = preg_replace('#\schecked(\s*=\s*([\'"]).*?\2)?#i', '', $tag);

                    $radioVal = '';
                    if (preg_match('#\bvalue\s*=\s*([\'"])(.*?)\1#i', $tag, $vm)) $radioVal = (string)$vm[2];

                    if ((string)$radioVal === (string)$v) {
                        $tag = rtrim($tag, '>') . ' checked="checked">';
                    }
                    return $tag;
                },
                $html
            );

            // TEXTAREA by name
            $html = preg_replace(
                '#(<textarea\b[^>]*\bname\s*=\s*([\'"])' . $k . '\2[^>]*>)(.*?)(</textarea>)#is',
                '$1' . $vEsc . '$4',
                $html
            );

            // SELECT by name
            $html = preg_replace_callback(
                '#<select\b[^>]*\bname\s*=\s*([\'"])' . $k . '\1[^>]*>.*?</select>#is',
                function ($m) use ($v) {
                    $block = $m[0];
                    $block = preg_replace('#\sselected(\s*=\s*([\'"]).*?\2)?#i', '', $block);

                    $block = preg_replace_callback(
                        '#<option\b([^>]*)>#i',
                        function ($o) use ($v) {
                            $optTag = $o[0];
                            $optVal = null;

                            if (preg_match('#\bvalue\s*=\s*([\'"])(.*?)\1#i', $optTag, $vm)) {
                                $optVal = (string)$vm[2];
                            }

                            if ($optVal !== null && (string)$optVal === (string)$v) {
                                $optTag = rtrim($optTag, '>') . ' selected="selected">';
                            }
                            return $optTag;
                        },
                        $block
                    );

                    return $block;
                },
                $html
            );

            // Fallback by id
            $html = preg_replace_callback(
                '#<input\b([^>]*\bid\s*=\s*([\'"])' . $k . '\2[^>]*)>#i',
                function ($m) use ($v) {
                    $tag = $m[0];
                    $attrs = $m[1];

                    $type = '';
                    if (preg_match('#\btype\s*=\s*([\'"])(.*?)\1#i', $attrs, $tm)) $type = strtolower($tm[2]);

                    if ($type === 'checkbox') {
                        $checked = ($v == '1' || $v === 1 || $v === true || strtolower((string)$v) === 'on');
                        $tag = preg_replace('#\schecked(\s*=\s*([\'"]).*?\2)?#i', '', $tag);
                        if ($checked) $tag = rtrim($tag, '>') . ' checked="checked">';
                        return $tag;
                    }

                    if ($type === 'radio') return $tag;

                    if (preg_match('#\bvalue\s*=\s*([\'"]).*?\1#i', $tag)) {
                        $tag = preg_replace(
                            '#\bvalue\s*=\s*([\'"]).*?\1#i',
                            'value="' . htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"',
                            $tag
                        );
                    } else {
                        $tag = rtrim($tag, '>') . ' value="' . htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                    }
                    return $tag;
                },
                $html
            );

            $html = preg_replace(
                '#(<textarea\b[^>]*\bid\s*=\s*([\'"])' . $k . '\2[^>]*>)(.*?)(</textarea>)#is',
                '$1' . $vEsc . '$4',
                $html
            );
        }

        // Hide submit bar in PDF
        $html = preg_replace(
            '#(<[^>]+class\s*=\s*([\'"])[^\'"]*\bsubmit-bar\b[^\'"]*\2[^>]*)(>)#i',
            '$1 style="display:none!important"$3',
            $html
        );

        return $html;
    }

    /* =========================================================
       7) PRINT PDF (FAST + FILLED DATA) - mPDF
    ==========================================================*/
    public function print_pdf()
{
    // ✅ avoid timeout for big PDFs (still keep it reasonable)
    @set_time_limit(300);
    @ini_set('memory_limit', '512M');

    $nabh_pdf_id    = (int)$this->input->get('nabh_pdf_id');
    $appointment_id = (int)$this->input->get('appointment_id');
    $patient_id     = (int)$this->input->get('patient_id');
    $lang           = ($this->input->get('lang') === 'gu') ? 'gu' : 'en';

    $patient_name = urldecode((string)$this->input->get('patient_name', true));
    $doctor_name  = urldecode((string)$this->input->get('doctor_name', true));

    if (!$nabh_pdf_id || !$appointment_id) {
        show_error('Missing nabh_pdf_id / appointment_id', 422);
    }

    // 1) master
    $master = $this->db->where('pdf_id', $nabh_pdf_id)->get('tblnabh_master')->row_array();
    if (!$master) show_error('NABH master record not found', 404);

    $enFile = trim((string)($master['english_file_name'] ?? ''));
    $guFile = trim((string)($master['gujarati_file_name'] ?? ''));

    $file = ($lang === 'gu') ? $guFile : $enFile;
    $langFolder = ($lang === 'gu') ? 'gujarati' : 'english';

    // fallback if selected missing
    if ($file === '') {
        if ($enFile !== '') { $file = $enFile; $langFolder = 'english'; $lang = 'en'; }
        elseif ($guFile !== '') { $file = $guFile; $langFolder = 'gujarati'; $lang = 'gu'; }
        else show_error('No template uploaded', 404);
    }

    $path = FCPATH . 'uploads/nabh/' . $langFolder . '/' . basename($file);
    if (!file_exists($path)) show_error('Template missing: ' . $path, 404);

    $html = file_get_contents($path);

    // 2) Load saved JSON from DB
    $saved = [];
    $row = $this->db->where('nabh_pdf_id', $nabh_pdf_id)
        ->where('appointment_id', $appointment_id)
        ->where('lang', $lang)
        ->order_by('id', 'DESC')
        ->get(db_prefix() . 'nabh_form_submissions')
        ->row_array();

    if ($row && !empty($row['form_data_json'])) {
        $decoded = json_decode($row['form_data_json'], true);
        if (is_array($decoded)) $saved = $decoded;
    }

    // patient/doctor fallback
    if ($patient_name === '' && !empty($row['patient_name'])) $patient_name = (string)$row['patient_name'];
    if ($doctor_name  === '' && !empty($row['doctor_name']))  $doctor_name  = (string)$row['doctor_name'];

    // ensure these also fill
    if ($patient_name !== '') $saved['patient_name'] = $patient_name;
    if ($doctor_name  !== '') $saved['doctor_name']  = $doctor_name;

    // 3) ✅ FAST FILL (no DOM)
    $html = $this->apply_saved_to_html_fast($html, $saved);

    // 4) ✅ IMPORTANT: Convert form controls to printable spans (huge speed boost)
    $html = $this->convert_form_controls_to_printable($html);

    // 5) CSS (keep it light)
    $css = '';
    $cssPath = FCPATH . 'uploads/nabh/pdf.css';
    if (file_exists($cssPath)) $css = file_get_contents($cssPath);

    // 6) Dynamic filename
    $title = ($lang === 'gu')
        ? (trim((string)($master['pdf_name_gu'] ?? '')) ?: trim((string)($master['pdf_name'] ?? 'NABH')))
        : (trim((string)($master['pdf_name'] ?? 'NABH')));

    $safeTitle = preg_replace('/[^A-Za-z0-9\-_]+/u', '_', $title);
    $filename  = $safeTitle . '_' . date('Ymd_His') . '.pdf';

    // 7) mPDF options focused on SPEED
    $mpdfOpts = [
        'autoScriptToLang' => true,
        'autoLangToFont'   => true,
        'default_font'     => ($lang === 'gu' || $langFolder === 'gujarati') ? 'notosansgujarati' : 'dejavusans',
    ];

    $this->load->library('pdf', $mpdfOpts);

    // ✅ speed knobs (in Pdf.php after $this->mpdf created, these help a lot)
    $this->pdf->mpdf->useSubstitutions = false;
    $this->pdf->mpdf->simpleTables = true;
    $this->pdf->mpdf->packTableData = true;
    $this->pdf->mpdf->shrink_tables_to_fit = 1;

    
    $binary = $this->pdf->html_to_pdf_binary($html, $css);

    return $this->output
        ->set_content_type('application/pdf')
        ->set_header('Content-Disposition: inline; filename="' . $filename . '"')
        ->set_output($binary);
}


/**
 * ✅ Converts INPUT/TEXTAREA/SELECT into plain text spans
 * This removes the slowest part of mPDF layout and prevents timeouts.
 */
private function convert_form_controls_to_printable(string $html): string
{
    // Make HTML a bit more XHTML-like (cheap safety for mPDF)
    $html = preg_replace('#<br\s*>#i', '<br />', $html);
    $html = preg_replace('#<hr\s*>#i', '<hr />', $html);
    $html = preg_replace('#&nbsp;#i', ' ', $html);

    // 1) INPUT => plain value text (NO borders, NO block styling)
    $html = preg_replace_callback('#<input\b[^>]*>#i', function ($m) {
        $tag = $m[0];

        $type = 'text';
        if (preg_match('#\btype\s*=\s*([\'"])(.*?)\1#i', $tag, $tm)) {
            $type = strtolower($tm[2]);
        }

        // hide buttons
        if (in_array($type, ['button','submit','reset','image'], true)) return '';

        // checkbox/radio
        if ($type === 'checkbox') {
            $checked = (stripos($tag, 'checked') !== false);
            return $checked ? '✓' : '';
        }
        if ($type === 'radio') {
            $checked = (stripos($tag, 'checked') !== false);
            return $checked ? '●' : '';
        }

        $value = '';
        if (preg_match('#\bvalue\s*=\s*([\'"])(.*?)\1#is', $tag, $vm)) {
            $value = html_entity_decode($vm[2], ENT_QUOTES, 'UTF-8');
        }

        $value = trim($value);
        if ($value === '') $value = ' '; // keep cell height stable
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $html);

    // 2) TEXTAREA => plain text
    $html = preg_replace_callback('#<textarea\b[^>]*>(.*?)</textarea>#is', function ($m) {
        $text = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        if ($text === '') $text = ' ';
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $html);

    // 3) SELECT => selected option label (plain)
    $html = preg_replace_callback('#<select\b[^>]*>.*?</select>#is', function ($m) {
        $block = $m[0];
        $selectedText = '';

        if (preg_match('#<option\b[^>]*selected[^>]*>(.*?)</option>#is', $block, $sm)) {
            $selectedText = trim(strip_tags($sm[1]));
        } elseif (preg_match('#<option\b[^>]*>(.*?)</option>#is', $block, $fm)) {
            $selectedText = trim(strip_tags($fm[1]));
        }

        if ($selectedText === '') $selectedText = ' ';
        return htmlspecialchars($selectedText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $html);

    // 4) Remove submit bar blocks
    $html = preg_replace('#<[^>]*class\s*=\s*([\'"])[^\'"]*\bsubmit-bar\b[^\'"]*\1[^>]*>.*?</[^>]+>#is', '', $html);

    return $html;
}

}