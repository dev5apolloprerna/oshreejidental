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
            ->set_output(json_encode(['status'=>false,'message'=>'Missing nabh_pdf_id / appointment_id']));
    }

    // master
    $this->db->where('pdf_id', $nabh_pdf_id);
    $master = $this->db->get(db_prefix().'nabh_master')->row();
    if (!$master) return $this->output->set_status_header(404)->set_output('NABH master record not found');

    $file = ($lang === 'gu') ? ($master->gujarati_file_name ?? '') : ($master->english_file_name ?? '');
    if (empty($file)) return $this->output->set_status_header(404)->set_output('Template file not uploaded for selected language');

    $baseUploads = FCPATH . 'uploads/nabh/';
    $path = $baseUploads . ($lang === 'gu' ? 'gujarati/' : 'english/') . $file;
    if (!file_exists($path)) return $this->output->set_status_header(404)->set_output('HTML template file missing: ' . $path);

    $html = file_get_contents($path);

    // saved json
    $saved = $req['form_data_json'] ?? [];
    if (!is_array($saved)) $saved = [];

    if (empty($saved)) {
        $this->db->where('nabh_pdf_id', $nabh_pdf_id);
        $this->db->where('appointment_id', $appointment_id);
        $this->db->where('lang', $lang);
        $this->db->order_by('id', 'DESC');
        $row = $this->db->get(db_prefix().'nabh_form_submissions')->row();

        if ($row && !empty($row->form_data_json)) {
            $decoded = json_decode($row->form_data_json, true);
            if (is_array($decoded)) $saved = $decoded;
        }

        if (empty($req['patient_name']) && $row && !empty($row->patient_name)) $req['patient_name'] = $row->patient_name;
        if (empty($req['doctor_name'])  && $row && !empty($row->doctor_name))  $req['doctor_name']  = $row->doctor_name;
    }

    $ctx = [
        'patient_name' => $req['patient_name'] ?? '',
        'doctor_name'  => $req['doctor_name'] ?? '',
        'today_date'   => date('d/m/Y'),
    ];

    // inject global placeholders (if you use it)
    if (method_exists($this, 'inject_global')) {
        $html = $this->inject_global($html, $ctx, $saved);
    }

    // ✅ THE IMPORTANT LINE (server-side fill, because dompdf won't run JS)
    $html = $this->apply_saved_to_html($html, $saved);

    // render
    $this->load->library('pdf');
    $binary = $this->pdf->html_to_pdf_binary($html);

    $title = ($lang === 'gu')
        ? (!empty($master->pdf_name_gu) ? $master->pdf_name_gu : ($master->pdf_name ?? 'NABH'))
        : ($master->pdf_name ?? 'NABH');

    $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $title) . '_' . date('Ymd_His') . '.pdf';

    return $this->output
        ->set_content_type('application/pdf')
        ->set_header('Content-Disposition: inline; filename="'.$filename.'"')
        ->set_output($binary);
} 
   private function apply_saved_to_html($html, array $saved)
{
    if (empty($saved)) return $html;

    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    foreach ($saved as $key => $val) {
        $key = (string)$key;

        // by name
        foreach ($xpath->query("//input[@name='$key']") as $input) {
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
    }

    $out = $dom->saveHTML();
    libxml_clear_errors();
    return $out;
}
}
