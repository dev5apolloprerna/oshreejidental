<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointly_model extends App_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointly/appointly_attendees_model', 'atm');
    }


    /**
     * Insert new appointment
     *
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function insert_appointment($data)
    {

        $attendees = [];
        $relation = $data['rel_type'];
        $external_cid = null;

        unset($data['rel_type']);

        if ($relation == 'lead_related') {
            $this->load->model('leads_model');

            $lead = $this->leads_model->get($data['rel_id']);

            $data['contact_id'] = $data['rel_id'];

            $data['name'] = $lead->name;

            if ($lead->phonenumber != '') $data['phone'] = $lead->phonenumber;

            if ($lead->address != '') $data['address'] = $lead->address;

            if ($lead->email != '') $data['email'] = $lead->email;

            $attendees = $data['attendees'];
            $data['source'] = 'lead_related';
            $data['created_by'] = get_staff_user_id();

            unset($data['rel_lead_type'], $data['rel_id'], $data['attendees']);
        } else {
            unset($data['rel_lead_type']);
        }

        if ($relation == 'internal') {
            $data['created_by'] = get_staff_user_id();
            $data['source'] = 'internal';
            $attendees = $data['attendees'];

            unset($data['attendees']);
        } else if (isset($data['attendees']) && $relation != 'lead_related') {
            /**
             * Means it is coming from inside crm form as External not internal (Contact)
             */
            $data['created_by'] = get_staff_user_id();

            if (isset($data['contact_id'])) {
                $external_cid = $data['contact_id'];
                $data['contact_id'] = null;
            }

            /**
             * We are setting source to external because it is relation is marked as an External Contact
             */
            $data['source'] = 'external';
            $attendees = $data['attendees'];
            unset($data['attendees']);

            if (is_admin()
                || (staff_can('view_own', 'appointments')
                    || staff_can('view', 'appointments'))) {
                $data['approved'] = 1;
            }
        }

        if (
            is_admin() && $relation == 'internal'
            || is_admin() && $relation == 'lead_related'
            || (staff_can('view_own', 'appointments')
                || staff_can('view', 'appointments')) && $relation == 'internal'
        ) $data['approved'] = 1;

        /**
         * Remove white spaces from phone number
         * In case is sent from external form as internal client when logged in
         */
        if ( ! empty($data['phone'])) $data['phone'] = preg_replace('/\s+/', '', $data['phone']);

        if ($data['source'] == 'internal' && empty($data['email'])) {
            $contact_data = get_appointment_contact_details($data['contact_id']);
            $data['email'] = $contact_data['email'];
            $data['name'] = $contact_data['full_name'];
            $data['phone'] = $contact_data['phone'];
        }

        if (appointlyGoogleAuth()) {
            if (isset($data['google'])) {
                $data['external_contact_id'] = $external_cid;

                $googleEvent = insertAppointmentToGoogleCalendar($data, $attendees);
                $data['google_event_id'] = $googleEvent['google_event_id'];
                $data['google_calendar_link'] = $googleEvent['htmlLink'];

                if (isset($googleEvent['hangoutLink'])) $data['google_meet_link'] = $googleEvent['hangoutLink'];

                $data['google_added_by_id'] = get_staff_user_id();

                unset($data['google'], $data['external_contact_id']);
            }
        }

        return $this->insertHandleCustomFieldsAndNotifications($data, $attendees);
    }


    /**
     * Insert new internal appointment for staff
     *
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function insert_internal_crm_appointment($data)
    {
        $data['created_by'] = get_staff_user_id();
        $data['source'] = 'internal_staff_crm';
        $attendees = $data['attendees'];
        unset($data['attendees']);

        if (is_admin()
            || (staff_can('view_own', 'appointments')
                || staff_can('view', 'appointments'))) {
            $data['approved'] = 1;
        }

        if (appointlyGoogleAuth()) {
            if (isset($data['google'])) {

                $googleEvent = insertAppointmentToGoogleCalendar($data, $attendees);
                $data['google_event_id'] = $googleEvent['google_event_id'];
                $data['google_calendar_link'] = $googleEvent['htmlLink'];

                if (isset($googleEvent['hangoutLink'])) {
                    $data['google_meet_link'] = $googleEvent['hangoutLink'];
                }

                $data['google_added_by_id'] = get_staff_user_id();

                unset($data['google'], $data['external_contact_id']);
            }
        }

        return $this->insertHandleCustomFieldsAndNotifications($data, $attendees);
    }

    /**
     * Helper function for create appointment
     *
     * @param $data
     * @param $attendees
     *
     * @return bool
     */
    private function insertHandleCustomFieldsAndNotifications($data, $attendees)
    {
        $data = array_merge($data, convertDateForDatabase($data['date']));

        $data['hash'] = app_generate_hash();

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['rel_id'])) unset($data['rel_id']);

        $data = $this->validateInsertRecurring($data);
        
        $data['custom_recurring'] = $data['custom_recurring'] != '' ? $data['custom_recurring'] : 0;
        $data['repeat_every'] = $data['repeat_every'] != '' ? $data['repeat_every'] : 0;
        $data['reminder_before'] = $data['reminder_before'] != '' ? $data['reminder_before'] : 0;
        $data['age'] = $data['age'] != '' ? $data['age'] : '';
        $data['gender'] = $data['gender'] != '' ? $data['gender'] : '';
        
        $data['by_email'] = $data['by_email'] != '' ? (int) $data['by_email'] : 0;
        $data['by_sms'] = $data['by_sms'] != '' ? (int) $data['by_sms'] : 0;

        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        
      
        

        $appointment_id = $this->db->insert_id();

        if (isset($custom_fields)) handle_custom_fields_post($appointment_id, $custom_fields);

        $this->atm->create($appointment_id, $attendees);

        $this->appointment_approve_notification_and_sms_triggers($appointment_id);

        $responsiblePerson = get_option('appointly_responsible_person');

        if ( ! empty($responsiblePerson)) {
            add_notification([
                'description' => 'appointment_new_appointment_submitted',
                'touserid'    => $responsiblePerson,
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);
            pusher_trigger_notification(array_unique([$responsiblePerson]));
        }
        return true;
    }


    public function recurringAddGoogleNewEvent($data, $attendees)
    {
        $googleInsertData = [];

        $googleEvent = insertAppointmentToGoogleCalendar($data, $attendees);

        $googleInsertData['google_event_id'] = $googleEvent['google_event_id'];
        $googleInsertData['google_calendar_link'] = $googleEvent['htmlLink'];

        if (isset($googleEvent['hangoutLink'])) $googleInsertData['google_meet_link'] = $googleEvent['hangoutLink'];

        return $googleInsertData;
    }

    /**
     * Add appointment to google calendar
     *
     * @param array $data
     *
     * @return array|void
     * @throws \Exception
     */
    public function add_event_to_google_calendar($data)
    {
        $result = ['result' => 'error', 'message' => _l('Oops, something went wrong, please try again...')];

        if (appointlyGoogleAuth()) {

            if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == null) {

                unset($data['rel_type']);

                $googleEvent = insertAppointmentToGoogleCalendar($data, isset($data['attendees']) ? $data['attendees'] : []);

                // It means that meeting is internal and created from CRM inside
                $data['google_event_id'] = $googleEvent['google_event_id'];
                $data['google_calendar_link'] = $googleEvent['htmlLink'];

                if (isset($googleEvent['hangoutLink'])) {
                    $data['google_meet_link'] = $googleEvent['hangoutLink'];
                }

                $data['google_added_by_id'] = get_staff_user_id();

                $data['id'] = $data['appointment_id'];

                $data = array_merge($data, convertDateForDatabase($data['date']));

                if ($googleEvent) {

                    unset($data['selected_contact']);
                    unset($data['appointment_id']);
                    unset($data['attendees']);
                    unset($data['custom_fields']);
                    unset($data['repeat_type_custom']);
                    unset($data['repeat_every_custom']);

                    $this->db->where('id', $data['id']);
                    $this->db->update(db_prefix() . 'appointly_appointments', $data);

                    if ($this->db->affected_rows() !== 0) {
                        return ['result' => 'success', 'message' => _l('appointments_added_to_google_calendar')];
                    }
                }
                return $result;
            }
        }
        return $result;
    }

    /**
     * Inserts appointment submitted from external clients form
     *
     * @param array $data
     *
     * @return bool
     */
    public function insert_external_appointment($data)
    {
        $data['hash'] = app_generate_hash();

        if ($data['phone']) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        if($data['email'] != ''){

            $contact_id = $this->get_contact_id($data['email']);
            $data['contact_id'] = $contact_id;    
        }

        
        $MAIN_DB = $this->load->database('default', TRUE);
        $MAIN_DB->select('branch_db');
        $MAIN_DB->where('branchid',$data['branch']);
        $branch_data = $MAIN_DB->get(db_prefix().'branch')->row();

        unset($data['branch']);

        $data['contact_id'] = isset($data['patient_id']) ? $data['patient_id'] : '';
        unset($data['patient_id']);
        unset($data['otp']);


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

            $this->db = $this->load->database($database, TRUE);
        }


        $data = array_merge($data, convertDateForDatabase($data['date']));

        $responsiblePerson = get_option('appointly_responsible_person');
        $isAppointmentApprovedByDefault = get_option('appointly_client_meeting_approved_default');


        if ($isAppointmentApprovedByDefault) $data['approved'] = 1;

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['custom_recurring'] = $data['custom_recurring'] != '' ? $data['custom_recurring'] : 0;
         $data['contact_id'] = $data['contact_id'] != '' ? $data['contact_id'] : 0;
        $data['repeat_every'] = $data['repeat_every'] != '' ? $data['repeat_every'] : 0;
        $data['reminder_before'] = $data['reminder_before'] != '' ? $data['reminder_before'] : 0;
        $data['age'] = $data['age'] != '' ? $data['age'] : '';
        $data['gender'] = $data['gender'] != '' ? $data['gender'] : '';
        
        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        
        $appointment_id = $this->db->insert_id();
        
        if (isset($custom_fields)) handle_custom_fields_post($appointment_id, $custom_fields);

        if ($isAppointmentApprovedByDefault) {
            /**
             * If is set appointment to be automatically approved send email to contact who requested the appointment
             */
            $data['id'] = $appointment_id;
            $this->atm->send_notifications_to_appointment_contact($data);


            /**
             * If responsible person is set add as main attendee else first admin created with id 1
             */
            $this->atm->create($appointment_id, [($responsiblePerson) ? $responsiblePerson : '1']);
        }


        if ($responsiblePerson !== '') {
            $notified_users = [];
            $notified_users[] = $responsiblePerson;

            $appointment = $this->apm->get_appointment_data($appointment_id);

            $staff = appointly_get_staff($responsiblePerson);

            send_mail_template('appointly_appointment_new_appointment_submitted', 'appointly', array_to_object($staff), array_to_object($appointment));

            add_notification([
                'description' => 'appointment_new_appointment_submitted',
                'touserid'    => $responsiblePerson,
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);

            pusher_trigger_notification($notified_users);
        }

        $appointment_link = site_url() . 'appointly/appointments/view?appointment_id=' . $appointment_id;

        hooks()->do_action('send_sms_after_external_appointment_submitted', $appointment_link);

        return true;
    }

    public function get_contact_id($email)
{
        
    $this->db->select('id');
    $this->db->where('email', $email);
    $query = $this->db->get(db_prefix() . 'contacts');

    if ($query->num_rows() > 0) {
        $row = $query->row();
        return $row->id;
    }

    return null;
}

    /**
     * Update existing appointment
     *
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function update_appointment($data)
    {
        unset($data['rel_type']);

        if (isset($data['email'])) {
            $contact_form_email = $data['email']; // Current contact email sent from form
        }

        $originalAppointment = $this->get_appointment_data($data['appointment_id']);

        $current_contact = $this->atm->get_contact_email($data); // Current contact email saved in database

        $current_attendees = $this->atm->attendees($data['appointment_id']);

        // Remove white spaces 
        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        $data = handleDataReminderFields($data);

        if ($data['contact_id'] == 0) unset($data['contact_id']);

        if (appointlyGoogleAuth()) {
            // If appointments is in google calendar then -> update 
            if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == get_staff_user_id()) {
                if (isset($data['google_event_id'])) {
                    updateAppointmentToGoogleCalendar($data);
                    // update then unset
                    unset($data['google_event_id']);
                    unset($data['selected_contact']);
                }
                // Insert appointment in google calendar
            } else if (isset($data['google']) && ! isset($data['created_by']) && ! isset($data['google_event_id']) && $data['approved'] == '1') {
                $googleEvent = insertAppointmentToGoogleCalendar($data, $data['attendees']);
                $data['google_event_id'] = $googleEvent['google_event_id'];
                $data['google_calendar_link'] = $googleEvent['htmlLink'];
                $data['google_added_by_id'] = $googleEvent['google_added_by_id'];
            }
        }

        unset($data['google']);

        $data = array_merge($data, convertDateForDatabase($data['date']));

        $attendees = $data['attendees'];

        $attendee_difference = array_diff($attendees, $current_attendees);

        $new_attendees = [];

        if ( ! empty($attendee_difference) && $data['approved'] == '1') {
            foreach ($attendee_difference as $new_attendee) {
                $new_attendees[] = appointly_get_staff($new_attendee);
            }

            $data['id'] = $data['appointment_id'];
            $this->atm->send_notifications_to_new_attenddees($new_attendees, $data);
            unset($data['id']);
        }

        $appointment_id = $data['appointment_id'];

        unset($data['appointment_id']);
        unset($data['attendees']);

        if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == 0) {
            unset($data['google_added_by_id']);
        }

        unset($data['selected_contact']);

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            handle_custom_fields_post($appointment_id, $custom_fields);
            unset($data['custom_fields']);
        }
        
         $data['custom_recurring'] = $data['custom_recurring'] != '' ? $data['custom_recurring'] : 0;
        $data['repeat_every'] = $data['repeat_every'] != '' ? $data['repeat_every'] : 0;
        $data['reminder_before'] = $data['reminder_before'] != '' ? $data['reminder_before'] : 0;
        $data['age'] = $data['age'] != '' ? $data['age'] : '';
        $data['gender'] = $data['gender'] != '' ? $data['gender'] : '';
        
        $data['by_email'] = $data['by_email'] != '' ? (int) $data['by_email'] : 0;
        $data['by_sms'] = $data['by_sms'] != '' ? (int) $data['by_sms'] : 0;

        /** @var array Original Appointment $originalAppointment */
        $data = $this->validateRecurringData($originalAppointment, $data);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);

        $this->atm->update($appointment_id, $attendees);

        // Check if contact emails are different, if yes send notifications for newly added (edited) contact
        // Must wait to update the contact then do the checking if they are the same 
        // We have all values on top of function
        if ($data['source'] == 'external' && $data['approved']) {
            if ($current_contact['email']) {
                if ($current_contact['email'] !== $contact_form_email) {
                    $new__updated_appointment = $this->get_appointment_data($appointment_id);

                    $data['id'] = $appointment_id;
                    $this->atm->send_notifications_to_appointment_contact($new__updated_appointment);
                }
            }
        }

        return true;
    }

    /**
     * Update internal staff appointment
     *
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function update_internal_crm_appointment($data)
    {

        $current_attendees = $this->atm->attendees($data['appointment_id']);
        $originalAppointment = $this->get_appointment_data($data['appointment_id']);

        $data = handleDataReminderFields($data);

        if (appointlyGoogleAuth()) {
            // If appointments is in google calendar then -> update 
            if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == get_staff_user_id()) {
                if (isset($data['google_event_id'])) {
                    updateAppointmentToGoogleCalendar($data);
                    unset($data['google_event_id']);
                    unset($data['selected_contact']);
                }
                // Insert appointment in google calendar
            } else if (isset($data['google']) && ! isset($data['created_by']) && ! isset($data['google_event_id']) && $data['approved'] == '1') {
                $googleEvent = insertAppointmentToGoogleCalendar($data, $data['attendees']);
                $data['google_event_id'] = $googleEvent['google_event_id'];
                $data['google_calendar_link'] = $googleEvent['htmlLink'];
                $data['google_added_by_id'] = $googleEvent['google_added_by_id'];
            }
        }

        unset($data['google']);

        $data = array_merge($data, convertDateForDatabase($data['date']));

        $attendees = $data['attendees'];

        $attendee_difference = array_diff($attendees, $current_attendees);

        $new_attendees = [];

        if ( ! empty($attendee_difference) && $data['approved'] == '1') {
            foreach ($attendee_difference as $new_attendee) {
                $new_attendees[] = appointly_get_staff($new_attendee);
            }

            $data['id'] = $data['appointment_id'];
            $this->atm->send_notifications_to_new_attenddees($new_attendees, $data);
            unset($data['id']);
        }

        $appointment_id = $data['appointment_id'];

        unset($data['appointment_id']);
        unset($data['attendees']);

        if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == 0) {
            unset($data['google_added_by_id']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            handle_custom_fields_post($appointment_id, $custom_fields);
            unset($data['custom_fields']);
        }
        
         $data['custom_recurring'] = $data['custom_recurring'] != '' ? $data['custom_recurring'] : 0;
        $data['repeat_every'] = $data['repeat_every'] != '' ? $data['repeat_every'] : 0;
        $data['reminder_before'] = $data['reminder_before'] != '' ? $data['reminder_before'] : 0;
        $data['age'] = $data['age'] != '' ? $data['age'] : '';
        $data['gender'] = $data['gender'] != '' ? $data['gender'] : '';
        
        $data['by_email'] = $data['by_email'] != '' ? (int) $data['by_email'] : 0;
        $data['by_sms'] = $data['by_sms'] != '' ? (int) $data['by_sms'] : 0;

        /** @var array Original Appointment Data $originalAppointment */
        $data = $this->validateRecurringData($originalAppointment, $data);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);

        $this->atm->update($appointment_id, $attendees);

        return true;
    }

    /**
     * Delete appointment
     *
     * @param string $appointment_id
     *
     * @return bool
     */
    public function delete_appointment($appointment_id)
    {
        $_appointment = $this->get_appointment_data($appointment_id);

        if ($_appointment['created_by'] != get_staff_user_id() && ! is_admin() && ! staff_appointments_responsible()) {
            set_alert('danger', _l('appointments_no_delete_permissions'));

            if (isset($_SERVER['HTTP_REFERER']) && ! empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }

        if (get_option('appointly_also_delete_in_google_calendar') == 1) {
            if ($_appointment['google_event_id'] && $_appointment['google_added_by_id'] == get_staff_user_id()) {
                $this->load->model('googlecalendar');
                if (appointlyGoogleAuth()) {
                    $this->googlecalendar->deleteEvent($_appointment['google_event_id']);
                }
            }
        }

        $this->atm->deleteAll($appointment_id);

        $this->db->where('id', $appointment_id);

        if ( ! is_admin() && ! staff_appointments_responsible()) {
            $this->db->where('created_by', get_staff_user_id());
        }

        $this->db->delete(db_prefix() . 'appointly_appointments');

        if ($this->db->affected_rows() !== 0) return true;

        return false;
    }


    /**
     * Get today's appointments
     *
     * @return array
     */
    public function fetch_todays_appointments()
    {
        $date = new DateTime();
        $today = $date->format('Y-m-d');

        $staff_has_permissions = ! staff_can('view', 'appointments') || ! staff_can('view_own', 'appointments');

        if ($staff_has_permissions) {
            $this->db->where('id IN (SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $this->db->where('date', $today);
        $this->db->where('approved', 1);

        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }


    /**
     * Get all busy appointment dates
     *
     * @return void
     */
    public function getBusyTimes()
    {
        $time_format = get_option('time_format');
        $format = '';
        $time = '24';

        if ($time_format === '24') {
            $format = '"%H:%i"';
        } else {
            $time = '12';
            $format = '"%h:%i %p"';
        }

        $this->db->select('TIME_FORMAT(start_hour, ' . $format . ') as start_hour, date, source, created_by', false);
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('approved', 1);

        $dates = $this->db->get()->result_array();

        if ($format == '"%h:%i %p"') {
            foreach ($dates as &$date) {
                $date['start_hour'] = substr($date['start_hour'], 1);
            }
        }


        if (appointlyGoogleAuth()) {

            $this->load->model('googlecalendar');

            $google_calendar_dates = $this->googlecalendar->getEvents();

            $convertedDates = [];

            if (count($google_calendar_dates) > 0) {
                foreach ($google_calendar_dates as &$gcdate) {

                    $gcdate['start'] = _dt($gcdate['start']);
                    $gcdate['start'] = explode(" ", $gcdate['start']);

                    if ( ! empty($gcdate['start'][0])) {
                        if ( ! in_array($gcdate['start'], $convertedDates)) {

                            if ($time_format == '24') {
                                array_push(
                                    $convertedDates,
                                    convertDateForValidation($gcdate['start'][0] . ' ' . $gcdate['start'][1], $time)
                                );
                            } else {
                                array_push(
                                    $convertedDates,
                                    convertDateForValidation($gcdate['start'][0] . ' ' . $gcdate['start'][1] . ' ' . $gcdate['start'][2], $time)
                                );
                            }
                        }
                    }
                }
                $dates = array_merge($dates, $convertedDates);
            }
        }

        echo json_encode($dates);
    }


    /**
     * Get all appointment data for calendar event
     *
     * @param string $start
     * @param string $end
     * @param array  $data
     *
     * @return array
     */
    public function getCalendarData($start, $end, $data)
    {
        $this->db->select('subject as title, date, hash, start_hour, id, type_id');
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('finished = 0 AND cancelled = 0');


        if ( ! is_client_logged_in()) {
            if ( ! staff_appointments_responsible()) {
                $this->db->where('id IN (SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees WHERE staff_id=' . get_staff_user_id() . ')');
            }
        } else {
            $this->db->where('id IN (SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees WHERE contact_id=' . get_contact_user_id() . ')');
        }


        $this->db->where('(CONCAT(date, " ", start_hour) BETWEEN "' . $start . '" AND "' . $end . '")');

        $appointments = $this->db->get()->result_array();

        foreach ($appointments as $key => $appointment) {

            $appointment['url'] = admin_url('appointly/appointments/view?appointment_id=' . $appointment['id']);

            if (is_client_logged_in()) {
                $appointment['url'] = admin_url('appointly/appointments_public/client_hash?hash=' . $appointment['hash']);
                $appointment['_tooltip'] = $appointment['title'];
            } else {
                $appointment['_tooltip'] = (get_appointment_type($appointment['type_id']))
                    ? _l('appointments_type_heading') . ": " . get_appointment_type($appointment['type_id'])
                    : $appointment['title'];
            }

            $appointment['date'] = $appointment['date'] . ' ' . $appointment['start_hour'] . ':00';
            $appointment['color'] = get_appointment_color_type($appointment['type_id']);
            $data[] = $appointment;
        }

        return $data;
    }

    /**
     * Fetch contact data and apply to fields in modal
     *
     * @param string $contact_id
     *
     * @param        $is_lead
     *
     * @return mixed
     */
    public function apply_contact_data($contact_id, $is_lead)
    {
        if ($is_lead == 'false' || $is_lead == false) {
            return $this->clients_model->get_contact($contact_id);
        } else {
            $this->load->model('leads_model');
            return $this->leads_model->get($contact_id);
        }
    }


    /**
     * Get single appointment data
     *
     * @param string $appointment_id
     *
     * @return array|bool
     */
    public function get_appointment_data($appointment_id)
    {
        $this->db->where('id', $appointment_id);
        $appointment = $this->db->get(db_prefix() . 'appointly_appointments')->row_array();

        if ($this->db->affected_rows() > 0) {
            $appointment['attendees'] = $this->atm->get($appointment_id);
            return $appointment;
        }
        return false;
    }


    /**
     * Cancel appointment
     *
     * @param string $appointment_id
     *
     * @return void
     */
    public function cancel_appointment($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        $notified_users = [];

        $attendees = $appointment['attendees'];

        foreach ($attendees as $staff) {

            if ($staff['staffid'] === get_staff_user_id()) {
                continue;
            }

            add_notification([
                'description' => 'appointment_is_cancelled',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment['id'],
            ]);

            $notified_users[] = $staff['staffid'];
            send_mail_template('appointly_appointment_notification_cancelled_to_staff', 'appointly', array_to_object($appointment), array_to_object($staff));
        }

        pusher_trigger_notification(array_unique($notified_users));

        $template = mail_template('appointly_appointment_notification_cancelled_to_contact', 'appointly', array_to_object($appointment));

        if ( ! empty($appointment['phone'])) {
            $merge_fields = $template->get_merge_fields();
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_CANCELLED_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        $template->send();

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancelled' => 1]);

        header('Content-Type: application/json');

        if ($this->db->affected_rows() !== 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => true]);
        }
    }

    /**
     * Approve appointment
     *
     * @param string $appointment_id
     *
     * @return bool
     */
    public function approve_appointment($appointment_id)
    {
        $this->appointment_approve_notification_and_sms_triggers($appointment_id);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['finished' => 0, 'approved' => 1, 'external_notification_date' => date('Y-m-d')]);

        return true;
    }


    /**
     * Check for external client hash token
     *
     * @param string $hash
     *
     * @return bool|void
     */
    public function getByHash($hash)
    {
        $this->db->where('hash', $hash);
        $appointment = $this->db->get('appointly_appointments')->row_array();
        if ($appointment) {
            $appointment['feedbacks'] = json_decode(get_option('appointly_default_feedbacks'));

            $appointment['selected_contact'] = $appointment['contact_id'];

            if ( ! empty($appointment['selected_contact'])) {
                $appointment['details'] = get_appointment_contact_details($appointment['selected_contact']);
            }
            $appointment['attendees'] = $this->atm->get($appointment['id']);
            return $appointment;
        }
        return false;
    }


    /**
     * Marks appointment as finished
     *
     * @param $id
     *
     * @return void
     */
    public function mark_as_finished($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['finished' => 1]);

        header('Content-Type: application/json');
        if ($this->db->affected_rows() !== 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }


    /**
     * Marks appointment as ongoing
     *
     * @param $appointment
     *
     * @return void
     */
    public function mark_as_ongoing($appointment)
    {
        $this->appointment_approve_notification_and_sms_triggers($appointment['id']);

        $this->db->where('id', $appointment['id']);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancelled' => 0, 'finished' => 0, 'cancel_notes' => null]);

        header('Content-Type: application/json');

        if ($this->db->affected_rows() !== 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }


    /**
     * Send email and SMS notifications
     *
     * @param string $appointment_id
     *
     * @return void
     */
    private function appointment_approve_notification_and_sms_triggers($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        $notified_users = [];

        $attendees = $appointment['attendees'];

        if (count($attendees) == 0) {
            $this->atm->create($appointment_id, [get_staff_user_id()]);
            $attendees = $this->atm->get($appointment_id);
        }

        foreach ($attendees as $staff) {
            if ($staff['staffid'] === get_staff_user_id()) {
                continue;
            }

            add_notification([
                'description' => 'appointment_is_approved',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment['id'],
            ]);


            $notified_users[] = $staff['staffid'];
            send_mail_template('appointly_appointment_approved_to_staff_attendees', 'appointly', array_to_object($appointment), array_to_object($staff));
        }

        pusher_trigger_notification(array_unique($notified_users));

        $template = mail_template('appointly_appointment_approved_to_contact', 'appointly', array_to_object($appointment));

        if ( ! empty($appointment['phone'])) {
            $merge_fields = $template->get_merge_fields();
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_APPROVED_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        $template->send();
    }

    /**
     * External appointment cancellation handler
     *
     * @param string $hash
     * @param string $notes
     *
     * @return array
     */
    public function applyForAppointmentCancellation($hash, $notes)
    {
        $this->db->where('hash', $hash);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancel_notes' => $notes]);

        if ($this->db->affected_rows() !== 0) {
            return [
                'response' => [
                    'message' => _l('appointments_thank_you_cancel_request'),
                    'success' => true
                ]
            ];
        }
    }


    /**
     * Check if cancellation is in progress already
     *
     * @param [appointment hash] $hash
     *
     * @return array
     */
    public function checkIfCancellationIsInProgress($hash)
    {
        $this->db->select('cancel_notes');
        $this->db->where('hash', $hash);
        return $this->db->get(db_prefix() . 'appointly_appointments')->row_array();
    }


    /**
     * Send appointment early reminders
     *
     * @param string|int $appointment_id
     *
     * @return bool
     */
    public function send_appointment_early_reminders($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        if ($appointment['cancelled'] == 1 || $appointment['finished'] == 1) {
            return false;
        }

        foreach ($appointment['attendees'] as $staff) {
            add_notification([
                'description' => 'appointment_you_have_new_appointment',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);

            $notified_users[] = $staff['staffid'];

            send_mail_template('appointly_appointment_cron_reminder_to_staff', 'appointly', array_to_object($appointment), array_to_object($staff));
        }

        $template = mail_template('appointly_appointment_cron_reminder_to_contact', 'appointly', array_to_object($appointment));

        $merge_fields = $template->get_merge_fields();

        $template->send();

        pusher_trigger_notification(array_unique($notified_users));

        if ($appointment['by_sms']) {
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_APPOINTMENT_REMINDER_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        return true;
    }


    /**
     * Add new appointment type
     *
     * @param string $type
     * @param string $color
     *
     * @return bool
     */
    public function new_appointment_type($type, $color)
    {
        return $this->db->insert(db_prefix() . 'appointly_appointment_types', ['type' => $type, 'color' => $color]);
    }


    /**
     * Delete appointment type
     *
     * @param string $id
     *
     * @return void
     */
    public function delete_appointment_type($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'appointly_appointment_types');

        header('Content-Type: application/json');
        if ($this->db->affected_rows() !== 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }


    /**
     * Update appointment types
     *
     * @param array $data
     *
     * @return void
     */
    public function update_appointment_types($data, $meta)
    {
        $data_color_types = [];

        foreach ($data as $key => $color) {
            if (strpos($key, 'type_id') === 0) {
                $key = substr($key, 8, 9);
                $data_color_types[$key]['id'] = $key;
                $data_color_types[$key]['color'] = $color;
            }
        }

        foreach ($data_color_types as $new_types) {
            $this->db->where('id', $new_types['id']);
            $this->db->update(db_prefix() . 'appointly_appointment_types', ['color' => $new_types['color']]);
        }
        handleAppointlyUserMeta($meta);
    }

    /**
     * Handles the request for new appointment feedback
     *
     * @param string $appointment_id
     *
     * @return void
     */
    public function request_appointment_feedback($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        if (is_array($appointment) && ! empty($appointment)) {
            send_mail_template('appointly_appointment_request_feedback', 'appointly', array_to_object($appointment));
            echo json_encode(['success' => true]);
            return;
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Handles new feedback
     *
     * @param string $id
     * @param string $feedback
     * @param string $comment
     *
     * @return bool
     */
    public function handle_feedback_post($id, $feedback, $comment = null)
    {

        $data = ['feedback' => $feedback];

        $responsiblePerson = get_option('appointly_responsible_person');

        $notified_users = [];

        ($responsiblePerson !== '') ? $notified_users[] = $responsiblePerson : $notified_users[] = '1';

        ($responsiblePerson !== '') ? $staff = appointly_get_staff($responsiblePerson) : $staff = appointly_get_staff('1');

        $appointment = $this->apm->get_appointment_data($id);

        $tmp_name = 'appointly_appointment_feedback_received';
        $tmp_lang = 'appointment_new_feedback_added';

        if ($appointment['feedback'] !== null) {
            $tmp_name = 'appointly_appointment_feedback_updated';
            $tmp_lang = 'appointly_feedback_updated';
        }

        send_mail_template($tmp_name, 'appointly', array_to_object($staff), array_to_object($appointment));

        add_notification([
            'description' => $tmp_lang,
            'touserid'    => ($responsiblePerson) ? $responsiblePerson : 1,
            'fromcompany' => true,
            'link'        => 'appointly/appointments/view?appointment_id=' . $id,
        ]);

        pusher_trigger_notification($notified_users);

        if ($comment !== null) {
            $data['feedback_comment'] = $comment;
        }

        $this->db->where('id', $id);

        $this->db->update(db_prefix() . 'appointly_appointments', $data);

        if ($this->db->affected_rows() !== 0) return true;

        return false;
    }


    /**
     * Inserts new event to outlook calendar in database
     *
     * @param array $data
     *
     * @return bool
     */
    public function insertNewOutlookEvent($data)
    {
        $last_appointment_id = $this->db->get(db_prefix() . 'appointly_appointments')->last_row()->id;

        $this->db->where('id', $last_appointment_id);

        $this->db->update(
            db_prefix() . 'appointly_appointments',
            [
                'outlook_event_id'      => $data['outlook_event_id'],
                'outlook_calendar_link' => $data['outlook_calendar_link'],
                'outlook_added_by_id'   => get_staff_user_id(),
            ]
        );

        return true;
    }

    /**
     * Inserts new event to outlook calendar
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateAndAddExistingOutlookEvent($data)
    {
        $this->db->where('id', $data['appointment_id']);

        $this->db->update(
            db_prefix() . 'appointly_appointments',
            [
                'outlook_event_id'      => $data['outlook_event_id'],
                'outlook_calendar_link' => $data['outlook_calendar_link'],
                'outlook_added_by_id'   => get_staff_user_id(),
            ]
        );

        if ($this->db->affected_rows() !== 0) return true;

        return false;
    }

    /**
     * Handles sending custom email to client
     *
     * @param array $data
     *
     * @return bool
     */
    public function sendGoogleMeetRequestEmail($data)
    {
        $this->load->model('emails_model');
        $attendees = json_decode($data['attendees']);
        $message = $data['message'];

        if (is_array($attendees) && count($attendees) > 1) {
            $staff = appointly_get_staff($this->session->userdata('staff_user_id'));
            foreach ($attendees as $attendee) {
                // dont sent to own email
                if ($staff['email'] !== $attendee) {
                    // send to attendees
                    $this->emails_model->send_simple_email($attendee, _l('appointment_connect_via_google_meet'), $message);
                }
            }
        }
        // client email
        return $this->emails_model->send_simple_email($data['to'], _l('appointment_connect_via_google_meet'), $message);
    }

    /**
     * Recurring update appointment data validation
     *
     * @param array $original
     * @param array $data
     *
     * @return array
     */
    private function validateRecurringData(array $original, array $data)
    {
        // Recurring appointment set to NO, Cancelled
        if (isset($original['repeat_every']) && $original['repeat_every'] != '' && isset($data['repeat_every']) && $data['repeat_every'] == '') {
            $data['cycles'] = 0;
            $data['total_cycles'] = 0;
            $data['last_recurring_date'] = null;
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every'] = $data['repeat_every_custom'];
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp = explode('-', $data['repeat_every']);
                $data['recurring_type'] = $_temp[1];
                $data['repeat_every'] = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        $data['cycles'] = ! isset($data['cycles']) || $data['recurring'] == 0 ? 0 : $data['cycles'];

        unset($data['repeat_type_custom']);
        unset($data['repeat_every_custom']);
        return $data;
    }

    /**
     * Recurring appointment insert data validation
     *
     * @param array $data
     *
     * @return array
     */
    private function validateInsertRecurring(array $data)
    {
        if (isset($data['repeat_every']) && ! empty($data['repeat_every'])) {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every'] = $data['repeat_every_custom'];
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp = explode('-', $data['repeat_every']);
                $data['recurring_type'] = $_temp[1];
                $data['repeat_every'] = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }
        unset($data['repeat_type_custom']);
        unset($data['repeat_every_custom']);
        return $data;
    }

   public function increment_next_number()
    {
        // Update next patent number in settings
        $this->db->where('name', 'next_patient_number');
        $this->db->set('value', 'value+1', false);
        $this->db->update(db_prefix() . 'options');

    }

    public function patient_number_format($prefix, $number) 
    {   
        $paddingLength = 6 - strlen($number);
        $formattedNumber = $prefix .str_repeat('0', $paddingLength) . $number;
    
        return $formattedNumber;
    }


    public function create_customer($id){

        $this->db->where('id',$id);
        $appointment = $this->db->get(db_prefix(). 'appointly_appointments')->row();

        
        if(!empty($appointment)){


            $name = $appointment->name;
            $email = $appointment->email;
            $phone = $appointment->phone;
            $gender = $appointment->gender;
            $age = $appointment->age;
            

            $client_array = array(
                'company' => $name,
                'phonenumber' => $phone,
                'datecreated' => date('Y-m-d H:i:s'),
            );

             $this->db->insert(db_prefix() . 'clients',$client_array);
             $insert_id = $this->db->insert_id();

            $data['prefix'] = get_option('patient_prefix');
            $data['uid'] = get_option('next_patient_number');

            $contact_array = array(
                'userid' => $insert_id,
                'is_primary' => 1,
                'firstname' => $name,
                'lastname' => $data['lastname'] != '' ? $data['lastname'] : '',
                'email' => $email,
                'phonenumber' => $phone,
                'gender' => $data['gender'] != '' ? $data['gender'] : '',
                'dob' => $data['dob'] != '' ? $data['dob'] : '0000-00-00',
                'blood_group' => $data['blood_group'] != '' ? $data['blood_group'] : '',
                'datecreated' => date('Y-m-d H:i:s'),
                'rx_str_date' => $data['rx_str_date'] != '' ? $data['rx_str_date'] : '0000-00-00',
                'rx_end_date' => $data['rx_end_date'] != '' ? $data['rx_end_date'] : '0000-00-00',
                'otp' => $data['otp'] != '' ? $data['otp'] : '0',
            );

            $contact_array['uid'] = $this->patient_number_format($data['prefix'],$data['uid']);

            $this->db->insert(db_prefix() . 'contacts',$contact_array);
            
            $contact_id = $this->db->insert_id();
            
             $medical_history = [
            'occupation' => $data['occupation'] != '' ? $data['occupation'] : '',
            'allergies' => $data['allergies'] != '' ? $data['allergies'] : '',
            'medication' => $data['medication'] != '' ? $data['medication'] : '',
            'tobaco_past' => $data['tobaco_past'] != '' ? $data['tobaco_past'] : '',
            'tobaco_present' => $data['tobaco_present'] != '' ? $data['tobaco_present'] : '',
            'alcohol_past' => $data['alcohol_past'] != '' ? $data['alcohol_past'] : '',
            'alcohol_present' => $data['alcohol_present'] != '' ? $data['alcohol_present'] : '',
            'marital_status' => $data['marital_status'] != '' ? $data['marital_status'] : '',
            'surgical_history' => $data['surgical_history'] != '' ? $data['surgical_history'] : '',
            'enviro_factors' =>$data['enviro_factors'] != '' ? $data['enviro_factors'] : '',
            'risk_factors' => $data['risk_factors'] != '' ? $data['risk_factors'] : '',
            'history_comment' => $data['history_comment'] != '' ? $data['history_comment'] : '',
            'chief_complaint' => $data['chief_complaint'] != '' ? $data['chief_complaint'] : '',
            'dental_history' => $data['dental_history'] != '' ? $data['dental_history'] : '',
            'diagnosis' => $data['diagnosis'] != '' ? $data['diagnosis'] : '',
            'disease' => $data['disease'] != '' ? $data['disease'] : '',
            'clinical_findings' => $data['clinical_findings'] != '' ? $data['clinical_findings'] : '',
            'current_treatment' => $data['current_treatment'] != '' ? $data['current_treatment'] : '',
            'current_medication' => $data['current_medication'] != '' ? $data['current_medication'] : '',
            'previous_medication' => $data['previous_medication'] != '' ? $data['previous_medication'] : '',
        ];
        if (isset($data['medical_history']) && is_array($data['medical_history'])) {
            $medical_history['medical_history'] = implode(', ', $data['medical_history']);
        }else if(isset($data['medical_history']) && !is_array($data['medical_history'])){
            $medical_history['medical_history'] = $data['medical_history'];
        }
        else{
            $medical_history['medical_history'] = '';
        }
        if (isset($data['treatment_plan'])) {
            $medical_history['treatment_plan'] = $data['treatment_plan'] != '' ? $data['treatment_plan'] : '';
        }else{
            $medical_history['treatment_plan'] = '';
        }

      
        
        $this->db->insert(db_prefix() . 'medical_history', array_merge($medical_history, [
            'userid' => $insert_id,
            'datecreated' => date('Y-m-d H:i:s'),
        ]));

            // Increment the next patient number after data insertion
            $this->increment_next_number();


            $this->db->where('id',$id);
            $this->db->update(db_prefix().'appointly_appointments',['contact_id' => $contact_id]);

            return $insert_id;

        }
    }

}
