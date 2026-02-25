<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inperson extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointly_model', 'apm');
        $this->load->model('staff_model');
        $this->load->library('session');
    }

    /**
     * Clients hash view.
     *
     * @return void
     */
    public function client_hash()
    {
        $hash = $this->input->get('hash');

        if (!$hash) show_404();

        $appointment = $this->apm->getByHash($hash);

        if (!$appointment) show_404();

        $appointment['url'] = site_url('appointly/appointments_public/cancel_appointment');

        $appointment['feedback_url'] = site_url('appointly/appointments_public/handleFeedbackPost');

        if ($appointment['feedback_comment'] !== null) $appointment['feedback_comment'] = true;

        $this->load->view('clients/clients_hash', ['appointment' => $appointment]);
    }

    /**
     * Fetches contact data if client who requested meeting is already in the system.
     *
     * @return void
     */
    public function external_fetch_contact_data()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = $this->input->post('contact_id');

        header('Content-Type: application/json');
        echo json_encode($this->apm->apply_contact_data($id, false));
    }

    /**
     * Handles clients external public form.
     *
     * @return void
     */
    public function form()
    {
        $form = new stdClass();

        $form->language = get_option('active_language');

        $MAIN_DB = $this->load->database('default', TRUE);

        $MAIN_DB->select('branchid,branch,branch_db');
        $Branches = $MAIN_DB->get(db_prefix().'branch')->result_array();


        $this->lang->load($form->language . '_lang', $form->language);

        if (file_exists(APPPATH . 'language/' . $form->language . '/custom_lang.php')) {
            $this->lang->load('custom_lang', $form->language);
        }

        if ($this->input->post() && $this->input->is_ajax_request()) {

            $post_data = $this->input->post();

            $required = ['subject', 'description', 'name', 'email'];

            foreach ($required as $field) {
                if (!isset($post_data[$field]) || isset($post_data[$field]) && empty($post_data[$field])) {
                    $this->output->set_status_header(422);
                    die;
                }
            }
            die;
        }

        $data['form'] = $form;
        $data['branches'] = $Branches;
        $data['form']->recaptcha = 1;

        $this->load->view('forms/inperson_form', $data);
    }

    public function get_branch_apponmtmenttypes(){


        if($this->input->post()){


            $html = '<option value="">'._l('dropdown_non_selected_tex').'</option>';

            $branch = $this->input->post('branch');
            $MAIN_DB = $this->load->database('default', TRUE);
            $MAIN_DB->select('branch_db,branch_db_user,branch_db_pass');
            $MAIN_DB->where('branchid',$branch);
            $branch_data = $MAIN_DB->get(db_prefix().'branch')->row();

            if(!empty($branch_data)){

                    $database = array(
                    'hostname' => APP_DB_HOSTNAME,
                    'username' => $branch_data->branch_db_user,
                    'password' => $branch_data->branch_db_pass,
                    'database' => $branch_data->branch_db, /* this will be changed "on the fly" in controler */
                    'dbdriver' => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
                    'dbprefix' => db_prefix(),
                    'db_debug' => (ENVIRONMENT !== 'production'),
                    'char_set' => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
                    'dbcollat' => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
                    'pconnect' => FALSE,
                    'cache_on' => false,
                    'cachedir' => '',
                    'swap_pre' => '',
                    'encrypt' => $db_encrypt,
                    'compress' => false,
                    'failover' => [],
                    'save_queries' => true,
                );

                $BRANCH_DB = $this->load->database($database, TRUE);
                $appointment_types = $BRANCH_DB->get(db_prefix().'appointly_appointment_types')->result_array();


                if(!empty($appointment_types)){
                        foreach ($appointment_types as $key => $value) {

                        $html .= '<option value="'.$value['id'].'">'.$value['type'].'</option>';
                    }
                }

            }else{

                $appointment_types = get_appointment_types();

                  foreach ($appointment_types as $key => $value) {

                        $html .= '<option value="'.$value['id'].'">'.$value['type'].'</option>';
                    }
            }

            
            

            echo $html;exit;

        }

    }


    public function get_branch_customers(){


        if($this->input->post()){

            $html = '<option value="">'._l('dropdown_non_selected_tex').'</option>';

            $branch = $this->input->post('branch');
            $MAIN_DB = $this->load->database('default', TRUE);
            $MAIN_DB->select('branch_db,branch_db_user,branch_db_pass');
            $MAIN_DB->where('branchid',$branch);
            $branch_data = $MAIN_DB->get(db_prefix().'branch')->row();

            if(!empty($branch_data)){

                    $database = array(
                    'hostname' => APP_DB_HOSTNAME,
                     'username' => $branch_data->branch_db_user,
                    'password' => $branch_data->branch_db_pass,
                    'database' => $branch_data->branch_db, /* this will be changed "on the fly" in controler */
                    'dbdriver' => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
                    'dbprefix' => db_prefix(),
                    'db_debug' => (ENVIRONMENT !== 'production'),
                    'char_set' => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
                    'dbcollat' => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
                    'pconnect' => FALSE,
                    'cache_on' => false,
                    'cachedir' => '',
                    'swap_pre' => '',
                    'encrypt' => $db_encrypt,
                    'compress' => false,
                    'failover' => [],
                    'save_queries' => true,
                );

                $BRANCH_DB = $this->load->database($database, TRUE);
                $BRANCH_DB->select('userid');
                $BRANCH_DB->where('active',1);
                $clients = $BRANCH_DB->get(db_prefix().'clients')->result_array();

             

                if(!empty($clients)){
                        foreach ($clients as $key => $value) {

                        $BRANCH_DB->select('id,firstname,lastname,uid,email,phonenumber,gender');
                        $BRANCH_DB->where('userid',$value['userid']);
                        $BRANCH_DB->where('is_primary',1);
                        $BRANCH_DB->where('active',1);    
                        $contact = $BRANCH_DB->get(db_prefix().'contacts')->row();


                        $html .= '<option value="'.$contact->id.'" data-email="'.$contact->email.'" data-phone="'.$contact->phonenumber.'" data-gender="'.$contact->gender.'">'.$contact->firstname . ' ' . $contact->lastname. ' ('. $contact->uid .') </option>';
                    }
                }

            }else{

                $this->db->select('userid');
                $this->db->where('active',1);
                $clients = $this->db->get(db_prefix().'clients')->result_array();

              
                  foreach ($clients as $key => $value) {

                        $this->db->select('id,firstname,lastname,uid,email,phonenumber,gender');
                        $this->db->where('userid',$value['userid']);
                        $this->db->where('is_primary',1);
                        $this->db->where('active',1);    
                        $contact = $this->db->get(db_prefix().'contacts')->row();


                       $html .= '<option value="'.$contact->id.'" data-email="'.$contact->email.'" data-phone="'.$contact->phonenumber.'" data-gender="'.$contact->gender.'">'.$contact->firstname . ' ' . $contact->lastname. ' ('. $contact->uid .') </option>';
                    }
            }

            
            

            echo $html;exit;

        }

    }

    /**
     * Handles creation of an external appointment.
     *
     * @return void
     */
    public function create_external_appointment()
    {
        $data = $this->input->post();

        
        if (!$data) {
            show_404();
        }

        $data['source'] = $data['rel_type'];
        unset($data['rel_type']);

        if (isset($data['g-recaptcha-response'])) {
            if (get_option('recaptcha_secret_key') != '' && get_option('recaptcha_site_key') != '') {
                if (!do_recaptcha_validation($data['g-recaptcha-response'])) {
                    echo json_encode([
                        'success'   => false,
                        'recaptcha' => false,
                        'message'   => _l('recaptcha_error'),
                    ]);
                    die;
                }
            }
        }

        if (isset($data['g-recaptcha-response'])) unset($data['g-recaptcha-response']);

        if ($this->apm->insert_external_appointment($data)) {
            echo json_encode([
                'success' => true,
                'message' => _l('appointment_sent_successfully')
            ]);
        }
    }

    public function sendotp(){

        if($this->input->post()){

            $data = $this->input->post();

            $branch = $this->input->post('branch');
            $MAIN_DB = $this->load->database('default', TRUE);
            $MAIN_DB->select('branch_db');
            $MAIN_DB->where('branchid',$branch);
            $branch_data = $MAIN_DB->get(db_prefix().'branch')->row();

            if($data['patient_id'] != ''){


                    if(!empty($branch_data)){

                        $database = array(
                        'hostname' => APP_DB_HOSTNAME, 'username' => APP_DB_USERNAME, 'password' => APP_DB_PASSWORD,
                        'database' => $branch_data->branch_db, /* this will be changed "on the fly" in controler */
                        'dbdriver' => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
                        'dbprefix' => db_prefix(),
                        'db_debug' => (ENVIRONMENT !== 'production'),
                        'char_set' => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
                        'dbcollat' => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
                        'pconnect' => FALSE,
                        'cache_on' => false,
                        'cachedir' => '',
                        'swap_pre' => '',
                        'encrypt' => $db_encrypt,
                        'compress' => false,
                        'failover' => [],
                        'save_queries' => true,
                    );

                    $BRANCH_DB = $this->load->database($database, TRUE);
                    $BRANCH_DB->where('uid',$data['patient_id']);
                    $BRANCH_DB->where('active',1);
                    $BRANCH_DB->where('is_primary',1);
                    $contact = $BRANCH_DB->get(db_prefix().'contacts')->row();

                    $newdb = $BRANCH_DB;

                

                }else{


                    $this->db->where('uid',$data['patient_id']);
                    $this->db->where('active',1);
                    $this->db->where('is_primary',1);
                    $contact = $this->db->get(db_prefix().'contacts')->row();

                    $newdb = $this->db;
                }

                

                if(!empty($contact)){

                    $otp = rand(000000,999999);
                    $newdb->where('id',$contact->id);
                    $newdb->update(db_prefix() . 'contacts',['otp' => $otp]);

                    $this->session->set_userdata('post_appointment',$data);

                    $this->load->library('email');

                    $newdb->select('value');
                    $newdb->where('name','smtp_email');
                    $smtp_email = $newdb->get(db_prefix().'options')->row();

                    $newdb->select('value');
                    $newdb->where('name','companyname');
                    $companyname = $newdb->get(db_prefix().'options')->row();

                    $newdb->select('value');
                    $newdb->where('name','email_header');
                    $email_header = $newdb->get(db_prefix().'options')->row();

                    $newdb->select('value');
                    $newdb->where('name','email_footer');
                    $email_footer = $newdb->get(db_prefix().'options')->row();

                     $newdb->select('value');
                    $newdb->where('name','smtp_encryption');
                    $smtp_encryption = $newdb->get(db_prefix().'options')->row();

                     $newdb->select('value');
                    $newdb->where('name','smtp_host');
                    $smtp_host = $newdb->get(db_prefix().'options')->row();

                     $newdb->select('value');
                    $newdb->where('name','smtp_username');
                    $smtp_username = $newdb->get(db_prefix().'options')->row();

                     $newdb->select('value');
                    $newdb->where('name','smtp_password');
                    $smtp_password = $newdb->get(db_prefix().'options')->row();

                     $newdb->select('value');
                    $newdb->where('name','email_protocol');
                    $email_protocol = $newdb->get(db_prefix().'options')->row();


                     $newdb->select('value');
                    $newdb->where('name','smtp_port');
                    $smtp_port = $newdb->get(db_prefix().'options')->row();



                    $config['protocol'] = $email_protocol->value;
                    $config['smtp_host'] = $smtp_host->value;
                    $config['smtp_user'] = $smtp_username->value;
                    $config['smtp_pass'] = get_instance()->encryption->decrypt($smtp_password->value);
                    $config['smtp_port'] = $smtp_port->value;
                    $config['smtp_crypto'] = $smtp_encryption->value;
                    $config['charset'] = 'iso-8859-1';
                    $config['wordwrap'] = TRUE;

                    $this->email->initialize($config);

                    $this->email->from($smtp_email->value, $companyname->value);
                    $this->email->to($contact->email);
                    $this->email->subject('OTP Verification');
                    $this->email->message($email_header->value . 'Hello ' . $contact->firstname . ' ' . $contact->lastname. ',<br><br>Your OTP for verification is : ' . $otp . str_replace("{companyname}",$companyname->value,$email_footer->value));

                    $this->email->send();


                    set_alert('success', _l('OTP has been sent to your email address'));
                    redirect('appointly/appointments_public/verifyOTP?col=col-md-4+col-md-offset-4');
                }else{

                    set_alert('error', _l('Invalid Patient ID'));
                    redirect('appointly/appointments_public/form?col=col-md-8+col-md-offset-2');
                }
            }else{

                $data['source'] = $data['rel_type'];
                unset($data['rel_type']);
                unset($data['patient_id']);

                if ($this->apm->insert_external_appointment($data)) {
                     set_alert('success', _l('appointment_sent_successfully'));
                    redirect('appointly/appointments_public/form?col=col-md-8+col-md-offset-2');
                }

            }

            
        }
    }

    public function verifyOTP(){

        if($this->input->post()){
            
            $data = $this->input->post();
            $otp = $data['otp'];
            $patient_id = $_SESSION['post_appointment']['patient_id'];
            $branch = $_SESSION['post_appointment']['branch'];
            $MAIN_DB = $this->load->database('default', TRUE);
            $MAIN_DB->select('branch_db');
            $MAIN_DB->where('branchid',$branch);
            $branch_data = $MAIN_DB->get(db_prefix().'branch')->row();

            

                if(!empty($branch_data)){

                    $database = array(
                    'hostname' => APP_DB_HOSTNAME, 'username' => APP_DB_USERNAME, 'password' => APP_DB_PASSWORD,
                    'database' => $branch_data->branch_db, /* this will be changed "on the fly" in controler */
                    'dbdriver' => defined('APP_DB_DRIVER') ? APP_DB_DRIVER : 'mysqli',
                    'dbprefix' => db_prefix(),
                    'db_debug' => (ENVIRONMENT !== 'production'),
                    'char_set' => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
                    'dbcollat' => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
                    'pconnect' => FALSE,
                    'cache_on' => false,
                    'cachedir' => '',
                    'swap_pre' => '',
                    'encrypt' => $db_encrypt,
                    'compress' => false,
                    'failover' => [],
                    'save_queries' => true,
                );

                $BRANCH_DB = $this->load->database($database, TRUE);
                $BRANCH_DB->where('uid',$patient_id);
                $BRANCH_DB->where('otp',$otp);
                $BRANCH_DB->where('active',1);
                $BRANCH_DB->where('is_primary',1);
                $contact = $BRANCH_DB->get(db_prefix().'contacts')->row();

                $BRANCH_DB->select('type');
                $BRANCH_DB->where('id',$_SESSION['post_appointment']['type_id']);
                $appointment_type = $BRANCH_DB->get(db_prefix().'appointly_appointment_types')->row();

            }else{

                $this->db->where('uid',$patient_id);
                $this->db->where('otp',$otp);
                $this->db->where('active',1);
                $this->db->where('is_primary',1);
                $contact = $this->db->get(db_prefix().'contacts')->row();

                $this->db->select('type');
                $this->db->where('id',$_SESSION['post_appointment']['type_id']);
                $appointment_type = $this->db->get(db_prefix().'appointly_appointment_types')->row();
            }



            if(!empty($contact)){

                $_SESSION['post_appointment']['patient_id'] = $contact->id;
                $_SESSION['post_appointment']['name'] = $contact->firstname . ' ' . $contact->lastname;
                $_SESSION['post_appointment']['subject'] = $appointment_type->type . ' for ' . $contact->firstname . ' ' . $contact->lastname;
                $_SESSION['post_appointment']['email'] = $contact->email;
                $_SESSION['post_appointment']['phone'] = $contact->phonenumber;
                $_SESSION['post_appointment']['gender'] = strtolower($contact->gender);
                $_SESSION['post_appointment']['age'] = $contact->age != '' ? $contact->age : '';
                $_SESSION['post_appointment']['source'] = $_SESSION['post_appointment']['rel_type'];
                unset($_SESSION['post_appointment']['rel_type']);

                if ($this->apm->insert_external_appointment($_SESSION['post_appointment'])) {

                    unset($_SESSION['post_appointment']);
                    set_alert('success', _l('appointment_sent_successfully'));
                    redirect('appointly/appointments_public/form?col=col-md-8+col-md-offset-2');
                }

            }else{

                set_alert('error', _l('Invalid OTP'));
                redirect('appointly/appointments_public/verifyOTP?col=col-md-4+col-md-offset-4');
            }
        }
        $this->load->view('forms/otp', $data);
    }

    /**
     * Handles appointment cancelling.
     *
     * @return bool|void
     */
    public function cancel_appointment()
    {
        if ($this->input->get('hash')) {

            $hash = $this->input->get('hash');
            $notes = $this->input->get('notes');

            if ($notes == '') return false;

            if (!$hash) show_404();

            $appointment = $this->apm->getByHash($hash);

            if (!$appointment) {
                show_404();
            } else {
                $cancellation_in_progress = $this->apm->checkIfCancellationIsInProgress($hash);

                header('Content-Type: application/json');
                if ($cancellation_in_progress['cancel_notes'] === null) {
                    $responsible_person = get_option('appointly_responsible_person');
                    $touserid = '';

                    if ($responsible_person != '') {
                        $touserid = $responsible_person;
                    } else if ($responsible_person == '' && $appointment['created_by'] !== null) {
                        $touserid = $appointment['created_by'];
                    } else {
                        /** If none of above conditions are true
                         * Goes to default eg. first admin created with id of 1.
                         */
                        $touserid = 1;
                    }

                    add_notification([
                        'description' => 'appointment_cancel_notification',
                        'touserid'    => $touserid,
                        'fromcompany' => true,
                        'link'        => 'appointly/appointments/view?appointment_id=' . $appointment['id'],
                    ]);

                    pusher_trigger_notification([$touserid]);
                    echo json_encode($this->apm->applyForAppointmentCancellation($hash, $notes));
                } else {
                    echo json_encode(['response' => [
                        'message' => _l('appointments_already_applied_for_cancelling'),
                        'success' => false
                    ]]);
                }
            }
        } else {
            show_404();
        }
    }

    /**
     * Get busy appointment times.
     *
     * @return void
     */
    public function busyDates()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        return $this->apm->getBusyTimes();
    }

    /**
     * Handles external callback post.
     */
    public function request_callback_external()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $data = $this->input->post();

        if (!$data) show_404();

        /*
         * Init callbacks model
         */
        $this->load->model('callbacks_model', 'callbackm');

        echo json_encode(['success' => $this->callbackm->handle_callback_request_data($data)]);
    }

    public function handleFeedbackPost()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = $this->input->get('id');

        if (!$id) show_404();

        $rating = $this->input->get('rating');

        $comment = ($this->input->get('feedback_comment')) ? $this->input->get('feedback_comment') : null;

        if ($this->apm->handle_feedback_post($id, $rating, $comment)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

}
