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
        $patient_name = $this->input->get('patient_name', true);
        $doctor_name  = $this->input->get('doctor_name', true);

        // if encoded, CI usually decodes, but safe:
        $patient_name = urldecode((string)$patient_name);
        $doctor_name  = urldecode((string)$doctor_name);


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
        $payloadStr = $this->input->post('payload');

        if (!$payloadStr) {
            echo json_encode(['status'=>false,'message'=>'Missing payload']); exit;
        }

        $payload = json_decode($payloadStr,true);

        if (!is_array($payload)) {
            echo json_encode(['status'=>false,'message'=>'Invalid JSON']); exit;
        }


        $patient_name = trim($payload['patient_name'] ?? '');
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

        $formData      = $payload['form_data'] ?? [];

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
              'form_data_json' => json_encode($payload['form_data'] ?? [], JSON_UNESCAPED_UNICODE),
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

    // 1) Load NABH master
    $master = $this->db->where('pdf_id', $nabh_pdf_id)->get('tblnabh_master')->row();
    if (!$master) return $this->output->set_status_header(404)->set_output('NABH master record not found');

    // 2) Resolve template filename by language (fallback other language)
    $file = ($lang === 'gu') ? ($master->gujarati_file_name ?? '') : ($master->english_file_name ?? '');
    $file = trim((string)$file);
    if ($file === '') {
        $file = trim((string)($master->english_file_name ?? '')) ?: trim((string)($master->gujarati_file_name ?? ''));
    }
    if ($file === '') return $this->output->set_status_header(404)->set_output('Template file missing for both languages');

    // 3) Load HTML from correct folder
    $folder = ($lang === 'gu') ? 'gujarati/' : 'english/';
    $path = FCPATH . 'uploads/nabh/' . $folder . basename($file);
    if (!file_exists($path)) return $this->output->set_status_header(404)->set_output('HTML template file missing: ' . $path);

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
    }

    // 5) Ensure common fields exist
    if (!isset($saved['patient_name']) && !empty($req['patient_name'])) $saved['patient_name'] = $req['patient_name'];
    if (!isset($saved['doctor_name'])  && !empty($req['doctor_name']))  $saved['doctor_name']  = $req['doctor_name'];
    if (!isset($saved['today_date'])) $saved['today_date'] = date('d/m/Y');

    // 6) Fill HTML server-side (NO JS in PDF)
    //    Also converts inputs into printable text so design stays stable.
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

        $fontRegUrl = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $guFontRegular);
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
    $title = trim((string)($master->pdf_name ?? 'NABH'));
    $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $title) . '_' . date('Ymd_His') . '.pdf';

    if ($lang === 'gu') {
        // ✅ GUJARATI => mPDF (proper Gujarati shaping)
        return $this->render_pdf_with_mpdf($html, $filename);
    }

    // ✅ ENGLISH => DOMPDF (fast)
    return $this->render_pdf_with_dompdf($html, $filename);
}

 
 private function apply_saved_to_html_for_pdf($html, array $saved)
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $getSaved = function($node) use ($saved) {
        $name = (string)$node->getAttribute('name');
        $id   = (string)$node->getAttribute('id');
        if ($name !== '' && array_key_exists($name, $saved)) return $saved[$name];
        if ($id !== '' && array_key_exists($id, $saved)) return $saved[$id];
        return null;
    };

    $copyAttrs = function($from, $to) {
        // copy class/style so template CSS still applies
        $cls = $from->getAttribute('class');
        $sty = $from->getAttribute('style');
        if ($cls !== '') $to->setAttribute('class', $cls);
        if ($sty !== '') $to->setAttribute('style', $sty);

        // copy common layout attributes if present
        foreach (['data-*','width','height'] as $a) {
            // data-* can't be copied with wildcard in DOMDocument, ignore here
        }
    };

    // ✅ Replace ALL inputs (not only saved keys)
    foreach ($xpath->query('//input') as $input) {
        $type = strtolower((string)$input->getAttribute('type'));
        if ($type === '') $type = 'text';

        $val = $getSaved($input);
        if ($val === null) $val = (string)$input->getAttribute('value');

        if ($type === 'checkbox') {
            // output text marker only (no CSS). If you want a box, use Option A CSS.
            $span = $dom->createElement('span', ($val == 1 || $val === "1" || $val === true || $val === "on") ? '[x]' : '[ ]');
            $copyAttrs($input, $span);
            $input->parentNode->replaceChild($span, $input);
            continue;
        }

        if ($type === 'radio') {
            $radioVal = (string)$input->getAttribute('value');
            $selected = ((string)$val === $radioVal);
            $span = $dom->createElement('span', $selected ? '(x)' : '( )');
            $copyAttrs($input, $span);
            $input->parentNode->replaceChild($span, $input);
            continue;
        }

        // text/date/time -> span with same class/style
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

    // hide submit bar
    $submitBars = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' submit-bar ')]");
    if ($submitBars && $submitBars->length) {
        foreach ($submitBars as $bar) {
            $bar->setAttribute('style', trim($bar->getAttribute('style') . ';display:none !important;'));
        }
    }

    $out = $dom->saveHTML();
    libxml_clear_errors();
    return $out;
}
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
        // 'B' => 'NotoSansGujarati-Bold.ttf',
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

    $mpdf->WriteHTML($html);

    // Inline view in browser
    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    exit;
}

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