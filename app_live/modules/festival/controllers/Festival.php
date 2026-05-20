<?php

class Festival extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('festival_model');
        $this->load->library('email');
    }

    /* List all visittype */
    public function index()
    {
        
        if (!is_admin()) {
            access_denied('festival');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('festival', 'table'));
        }
        $data['title'] = _l('festival_lowercase');
        $this->load->view('manage', $data);
    }

    /* Edit visittype or add new if passed id */
   public function add($id='')
    {
        
        if (!is_admin()) 
        {
            access_denied('festival');
        }

        if($this->input->post('submit'))
        {
            $data = $this->input->post();

            if ($id == '') 
            {

                $id = $this->festival_model->add($data);
                if($id)
                {
                    
                    set_alert('success', 'Festival added successfully');
                    redirect(admin_url('festival/festival'));    
                } 
                         
            } 
            else 
            {
                $success = $this->festival_model->update($data, $id);
                
                if ($success) 
                {
                    set_alert('success', 'Festival update successfully');
                }
                redirect(admin_url('festival/festival'));
            }
        } 
        if ($id == '') 
        {
            $title = _l('add_new', _l('festival_lowercase'));
        } 
        else 
        {
            $festival = $this->festival_model->get($id);
            $data['festival'] = $festival;
                        
            $title = _l('edit', _l('festival_lowercase'));
        }
        $this->load->view('festival', $data);        
    }

    
    /* Delete visittype from database */
    public function delete($id)
    {
        if (!is_admin()) {
            access_denied('festival');
        }
        if (!$id) {
            redirect(admin_url('festival/festival'));
        }
        $response = $this->festival_model->delete($id);
        if ($response == true) {
            set_alert('success', 'Festival deleted successfully');
        } else {
            set_alert('warning', _l('problem_deleting', _l('festival_lowercase')));
        }
        redirect(admin_url('festival/festival'));
    }

    
    public function send_festival_emails() {
        $current_date = date('Y-m-d'); // Get current date

        // Fetch festival data for the current date
        $festival = $this->festival_model->get_festivals_by_date($current_date);
        if (!empty($festivals)) {
            // Fetch all users
            $users = $this->festival_model->get_all_users_to_send_email();
            echo "test";
            echo "<pre>"; print_r($users); exit;
            foreach ($users as $user) {
                    // Prepare email content
                    $subject =  $festival->title;
                    $message =  $festival->message;
                    $message .= 'Best regards,<br>Your Company Name';

                    // Configure email settings
                    $this->email->from(get_option('smtp_email'),get_option('companyname'));
                    $this->email->to($user->email);
                    $this->email->subject($subject);
                    $this->email->message($message);

                    // Send email
                    if ($this->email->send()) {
                        log_message('info', 'Email sent to ' . $user->email);
                    } else {
                        log_message('error', 'Failed to send email to ' . $user->email);
                    }
            }
        }
    }
   
}
