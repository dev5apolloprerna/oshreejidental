<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointments extends AdminController
{
    private $staff_no_view_permissions;

    public function __construct()
    {
        parent::__construct();

        $this->staff_no_view_permissions = !staff_can('view', 'appointments') && !staff_can('view_own', 'appointments');

        $this->load->model('appointly_model', 'apm');
    }

    /**
     * Main view
     *
     * @return void
     */
    public function index()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }          

        $data['td_appointments'] = $this->getTodaysAppointments();

        $this->load->view('index', $data);
    }

    /**
     * Single appointment view
     *
     * @return void
     */
    public function view()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }
        $this->session->unset_userdata('from_view_id');

        $appointment_id = $this->input->get('appointment_id');

        $attendees = $this->atm->attendees($appointment_id);
        /**
         * If user is assigned to a appointment but have no permissions at all eg. edit or view
         * User will be able to open the url send to mail (But only to view this specific meeting or meetings that the user is assigned to)
         */

        if (!in_array(get_staff_user_id(), $attendees)) {
            // Global view permissions required
            if (!staff_can('view', 'appointments')) {
                access_denied('Appointments');
            }
        }

        $data['appointment'] = fetch_appointment_data($appointment_id);

        
        if ($data['appointment']) {
            $data['appointment']['public_url'] = site_url('appointly/appointments_public/client_hash?hash=' . $data['appointment']['hash']);
        } else {
            appointly_redirect_after_event('warning', _l('appointment_not_exists'));
        }

        if (!$data['appointment']) {
            show_404();
        }

        $this->load->view('tables/appointment', $data);
    }

    /**
     * Render table view
     *
     * @return void
     */
    public function table()
    {
        if ($this->staff_no_view_permissions) {
            access_denied();
        }

        $_POST['order'][0]['column'] = 0;
        $_POST['order'][0]['dir'] = 'desc';

        $this->app->get_table_data(module_views_path(APPOINTLY_MODULE_NAME, 'tables/index'));
    }

    /**
     * Get contact data
     *
     * @return void
     */
    public function fetch_contact_data()
    {
        if (!$this->input->is_ajax_request() || !is_staff_logged_in()) {
            show_404();
        }

        $id = $this->input->post('contact_id');
        $is_lead = $this->input->post('lead');

        if ($id) {
            header('Content-Type: application/json');
            echo json_encode($this->apm->apply_contact_data($id, $is_lead));
        }
    }

    /**
     * Modal edit and modal update trigger views with data
     *
     * @return void
     */
    public function modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);

        $data['contacts'] = appointly_get_staff_customers();

        if ($this->input->post('slug') === 'create') {

            $this->load->view('modals/create', $data);
        } else if ($this->input->post('slug') === 'update') {

            $data['appointment_id'] = $this->input->post('appointment_id');

            $data['history'] = fetch_appointment_data($data['appointment_id']);

            if (isset($data['notes'])) {
                $data['notes'] = htmlentities($data['notes']);
            }

            $this->load->view('modals/update', $data);
        }
    }

    /**
     * Modal edit and modal update trigger views with data
     *
     * @return void
     */
    public function modal_internal_crm()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);

        if ($this->input->post('slug') === 'create') {
            $this->load->view('modals/create_internal_crm', $data);
        } else {
            $data['appointment_id'] = $this->input->post('appointment_id');
            $data['history'] = fetch_appointment_data($data['appointment_id']);
            if (isset($data['notes'])) {
                $data['notes'] = htmlentities($data['notes']);
            }
            $this->load->view('modals/update_internal_crm', $data);
        }
    }

    /**
     * Modal edit and modal update trigger views with data
     *
     * @return void
     */
    public function modal_leads_contacts_crm()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $data['staff'] = $this->staff_model->get('', ['active' => 1]);

        $user_id = $this->input->post('user_id');
        $type = $this->input->post('type');

        if ($data['staff']) {
            if ($user_id && $type == 'lead') {
                $this->load->model('leads_model');
                $data['user'] = $this->leads_model->get($user_id);
                $data['user']->type = _l('lead');
            } else {
                $this->load->model('clients_model');
                $data['user'] = $this->clients_model->get_contact($user_id);
                $data['user']->type = _l('contact');
            }
        }

        $this->load->view('modals/create_leads_contacts_crm', $data);
    }

    /**
     * Update appointment
     *
     * @return void
     */
    public function update()
    {

        $appointment = $this->input->post();
        $appointment['notes'] = $this->input->post('notes', false);

        if (staff_can('edit', 'appointments') || staff_appointments_responsible()) {
            if ($appointment) {
                if ($this->apm->update_appointment($appointment)) {
                    header('Content-Type: application/json');
                    echo json_encode(['result' => true]);
                }
            }
        }
    }


    /**
     * Update appointment
     *
     * @return void
     */
    public function update_internal_crm()
    {

        $appointment = $this->input->post();
        $appointment['notes'] = $this->input->post('notes', false);

        if (staff_can('edit', 'appointments') || staff_appointments_responsible()) {
            if ($appointment) {
                if ($this->apm->update_internal_crm_appointment($appointment)) {
                    header('Content-Type: application/json');
                    echo json_encode(['result' => true]);
                }
            }
        }
    }

    /**
     * Create appointment
     *
     * @return void
     */
    public function create()
    {
        if (!staff_can('create', 'appointments') && !staff_appointments_responsible()) {
            access_denied();
        }

        $data = $this->input->post();

        if (!empty($data)) {
            $data['rel_type'] = 'internal';
            if ($this->apm->insert_appointment($data)) {
                header('Content-Type: application/json');
                echo json_encode(['result' => true]);
            }
        }
    }


    /**
     * Create internal appointment for staff
     *
     * @return void
     */
    public function create_internal_crm()
    {
        if (!staff_can('create', 'appointments') && !staff_appointments_responsible()) {
            access_denied();
        }

        $data = $this->input->post();

        if (!empty($data)) {
            if ($this->apm->insert_internal_crm_appointment($data)) {
                header('Content-Type: application/json');
                echo json_encode(['result' => true]);
            }
        }
    }

    /**
     * Delete appointment
     *
     * @param [type] appointment $id
     *
     * @return mixed
     */
    public function delete($id)
    {
        $appointment = $this->apm->get_appointment_data($id);

        if (staff_can('delete', 'appointments') && $appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible()) {
            if (!$this->input->is_ajax_request() && isset($id)) {
                if ($this->apm->delete_appointment($id)) {
                    appointly_redirect_after_event('success', _l('appointment_deleted'));
                }
            }

            if (isset($id)) {
                if ($this->apm->delete_appointment($id)) {
                    echo json_encode(['success' => true, 'message' => _l('appointment_deleted')]);
                    return;
                }
            } else {
                show_404();
            }
        }
    }


    /**
     * Approve new appointment
     *
     * @return void
     */
    public function approve()
    {
        if (!is_admin() && !staff_appointments_responsible()) {
            access_denied();
        }

        if ($this->input->is_ajax_request()) {
            echo json_encode(['result' => $this->apm->approve_appointment($this->input->post('appointment_id'))]);
            die;
        }

        if ($this->apm->approve_appointment($this->input->get('appointment_id'))) {
            appointly_redirect_after_event('success', _l('appointment_appointment_approved'));
        }
    }

    /**
     * Mark appointment as finished
     *
     * @return bool
     */
    public function finished()
    {
        $id = $this->input->post('id');

        $appointment = $this->apm->get_appointment_data($id);

        if (staff_can('edit', 'appointments') && $appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible()) {
            return $this->apm->mark_as_finished($id);
        }

        return false;
    }

    /**
     * Mark appointment as ongoing
     *
     * @return void|boolean
     */
    public function mark_as_ongoing_appointment()
    {
        $id = $this->input->post('id');

        $appointment = $this->apm->get_appointment_data($id);

        if (staff_can('edit', 'appointments') && $appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible()) {
            return $this->apm->mark_as_ongoing($appointment);
        }

        return false;
    }

    /**
     * Mark appointment as cancelled
     *
     * @return void|boolean
     */
    public function cancel_appointment()
    {
        $id = $this->input->post('id');

        $appointment = $this->apm->get_appointment_data($id);

        if (staff_can('edit', 'appointments') && $appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible()) {
            return $this->apm->cancel_appointment($id);
        }

        return false;
    }

    /**
     * Get today's appointments
     *
     * @return array
     */
    public function getTodaysAppointments()
    {
        return $this->apm->fetch_todays_appointments();
    }

    /**
     * Send appointment early reminders
     *
     * @return void
     */
    public function send_appointment_early_reminders()
    {
        if ($this->staff_no_view_permissions || !staff_appointments_responsible()) {
            access_denied();
        }

        if ($this->apm->send_appointment_early_reminders($this->input->post('id'))) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }


    /**
     * Load user settings view
     *
     * @return void Returns view
     */
    public function user_settings_view()
    {
        if ($this->staff_no_view_permissions) {
            access_denied();
        }

        $data = getAppointlyUserMeta();

        $data['filters'] = get_appointments_table_filters();

        $this->load->view('users/index', $data);
    }


    /**
     * User settings request for updating options in meta table
     *
     * @return void
     */
    public function user_settings()
    {
        $data = $this->input->post();

        if ($data) {

            $meta = [
                'appointly_show_summary'         => $this->input->post('appointly_show_summary'),
                'appointly_default_table_filter' => $this->input->post('appointly_default_table_filter'),
            ];

            $this->apm->update_appointment_types($data, $meta);

            appointly_redirect_after_event('success', _l('settings_updated'), 'appointments/user_settings_view/settings');
        }
    }

    /**
     * Add new appointment type
     *
     * @return bool
     */
    public function new_appointment_type()
    {
        if (!staff_appointments_responsible() && !staff_can('create', 'appointments')) {
            access_denied();
        }

        if ($this->input->post()) {
            if ($this->apm->new_appointment_type(
                $this->input->post('type'),
                $this->input->post('color')
            )) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            }
        }
        return false;
    }


    /**
     * Delete appointment type
     *
     * @param [string] id
     *
     * @return boolean
     */
    public function delete_appointment_type()
    {
        if (!staff_can('delete', 'appointments') && !staff_appointments_responsible()) {
            access_denied();
        }
        return $this->apm->delete_appointment_type($this->input->post('id'));
    }

    /**
     * Add event to google calendar
     *
     * @return void
     */
    public function addEventToGoogleCalendar()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!staff_can('edit', 'appointments') && !staff_appointments_responsible()) {
            access_denied();
        }

        $data = [];

        $data = $this->input->post();
        if ($data && !empty($data)) {
            header('Content-Type: application/json');
            $result = $this->apm->add_event_to_google_calendar($data);
            if ($result) {
                echo json_encode($result);
            }
        }
    }

    /**
     * Request new appointment feedback
     *
     * @param string $id
     *
     * @return void
     */
    public function requestAppointmentFeedback($id)
    {
        if ($id && !empty($id)) {
            header('Content-Type: application/json');
            $result = $this->apm->request_appointment_feedback($id);
            if ($result) {
                echo json_encode($result);
            }
        }
    }

    /**
     * Get attendee details
     *
     * @return void
     */
    public function getAttendeeData()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if ($this->input->post('ids')) {
            header('Content-Type: application/json');
            echo json_encode($this->atm->details($this->input->post('ids')));
        }
    }

    /**
     * Add new outlook event to calendar
     *
     * @return void
     */
    public function newOutlookEvent()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $data = [];

        $data = $this->input->post();

        if ($data && !empty($data)) {
            header('Content-Type: application/json');
            echo json_encode(['result' => $this->apm->insertNewOutlookEvent($data)]);
        }
    }

    /**
     * Add new outlook event to calendar from existing appointment
     *
     * @return void
     */
    public function updateAndAddExistingOutlookEvent()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $data = [];
        $data = $this->input->post();

        if ($data && !empty($data)) {
            header('Content-Type: application/json');
            echo json_encode(['result' => $this->apm->updateAndAddExistingOutlookEvent($data)]);
        }
    }

    /**
     * Send custom email to request meet via Google Meet
     *
     * @return void
     */
    public function sendCustomEmail()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $data = [];

        $data = $this->input->post();

        if ($data && !empty($data)) {
            header('Content-Type: application/json');
            echo json_encode($this->apm->sendGoogleMeetRequestEmail($data));
        }
    }

    /**
     * Edit appointment from view directly
     */
    public function edit_from_view()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        $from_view_id = $this->input->get('from_view_id');

        if ($from_view_id) {
            $this->session->set_userdata(['from_view_id' => $from_view_id]);
            echo json_encode(['success' => true]);
            return;
        }

        echo json_encode(['success' => false]);
    }

    public function convert_customrer($id){

        $create = $this->apm->create_customer($id);

        if($create){
            set_alert('success', _l('Patient Created Successfully', _l('client')));
            redirect(admin_url('clients/client/'.$create.'?group=patient_profile'));
        }

    }
    
    public function add_prescription($clientid)
    {

        $data = $this->input->post();


        if (!empty($data)) {
            
            if ($this->apm->add_prescription($data)) {

                set_alert('success', _l('Prescription Created Successfully'));
                redirect(admin_url('clients/client/'.$clientid.'?group=patient_profile'));
               
            }
        }
    }

    public function print_prescription($id)
    {
        if (!$id) {
            redirect(admin_url('clients'));
        }


        $invoice        = $this->apm->get_prescription($id);

        try {
            $pdf = $this->prescription_pdf($invoice);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }
        
        $pdf->Output($id . '.pdf', $type);
    }


    function prescription_pdf($invoice, $tag = '')
    {
        return $this->app_pdf('invoice', FCPATH .'modules/'.APPOINTLY_MODULE_NAME.'/libraries/Invoice_pdf', $invoice, $tag);
    }

    function app_pdf($type, $path, ...$params)
    {
        $basename = ucfirst(basename(strbefore($path, EXT)));

        if (!endsWith($path, EXT)) {
            $path .= EXT;
        }

        $path = hooks()->apply_filters("{$type}_pdf_class_path", $path, ...$params);

        include_once($path);

        return (new $basename(...$params))->prepare();
    }
    
    function update_assignee(){

        $data = $this->input->post();

        if ($data && !empty($data)) {
            header('Content-Type: application/json');
            echo json_encode($this->atm->create($data['appointment_id'],array($data['staffid'])));
        }
    }

    function get_appointment_assign_log($appointment_id){

        echo json_encode(get_appointment_assign_log($appointment_id));exit;
    }

    function check_appo_assign($appointment_id){

        echo json_encode($this->apm->check_appo_assign($appointment_id));exit;
    }


    function update_assignee_start_end_time(){

        $data = $this->input->post();

        if ($data && !empty($data)) {
            echo json_encode($this->apm->update_start_end_time($data));
        }
    }
    
    public function add_treatment($clientid)
    {
        $data = $this->input->post();
        
        if (!empty($data)) {
            
            if ($this->apm->add_treatment($data)) {

                set_alert('success', _l('Treatment Created Successfully'));
                redirect(admin_url('clients/client/'.$clientid.'?group=patient_profile'));
               
            }
        }
    }

    function get_treatment_time_log($appointment_id) {
    
    $this->db->select('*');
    $this->db->from(db_prefix() . 'appointment_assign_log');
    $this->db->where('appointment_id', $appointment_id);    
    $result = $this->db->get()->result_array();

    echo json_encode($result);
    exit();
}


    public function get_appointment_treatment_log($appointment_id){
        
        $logs = $this->apm->get_treatment_log($appointment_id);
        echo json_encode($logs); exit;
    }
    
     public function get_prescription($appointment_id){

        if(!empty($appointment_id)){

            $prescription = $this->apm->get_prescription_data($appointment_id);
            echo json_encode($prescription); exit;

        }

    }
    
    public function add_patient_signature() {

    $data = $this->input->post();
    $patient_id = $data['patient_id'];
    if (!empty($data['appointment_id']) && !empty($data['signature_value'])) {
        $data['signature_value'] = str_replace('[removed]', '', $data['signature_value']);

        $existingSignature = $this->apm->get_patient_signature_by_appointment($data['appointment_id']);
        if ($existingSignature) {
            $this->apm->update_patient_signature_by_appointmentID(
                $data['appointment_id'],
                $data['patient_name'],
                $data['signature_value']
            );
            set_alert('success', 'Signature updated successfully.');
        } else {
            $this->apm->save_patient_signature($data);
            set_alert('success', 'Signature saved successfully.');
        }

        redirect(admin_url('clients/client/' . $patient_id . '?group=patient_profile'));
    } else {
        set_alert('warning', 'Missing appointment ID or signature data.');
        redirect($_SERVER['HTTP_REFERER']);
    }
}


    public function get_patient_signature($appointmentId) {
    
        if (!empty($appointmentId)) {

            $signature = $this->apm->get_patient_signature_by_appointment($appointmentId);
    
            if ($signature) {  
                echo json_encode($signature);
            } else {
               
                echo json_encode([]);
            }
        } else {
            echo json_encode(['error' => 'Appointment ID is required.']);
        }
    }
    
    public function delete_patient_signature() {
        $appointmentId = $this->input->post('appointment_id');

        if (!empty($appointmentId)) {
            $deleted_sign = $this->apm->delete_patient_signature_by_appointmentID($appointmentId);

            if ($deleted_sign) {
                echo json_encode(['success' => true, 'message' => 'Signature deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No signature found or failed to delete.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment ID is required.']);
        }
    }

}
