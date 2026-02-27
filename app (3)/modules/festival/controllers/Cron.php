<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends App_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('festival_model');
        $this->load->library('email');
    }

    public function send_festival_emails() {
        $current_date = date('Y-m-d'); // Get current date

        // Fetch festival data for the current date
        $festival = $this->festival_model->get_festivals_by_date($current_date);

        $subject =  $festival->title;
        $message =  $festival->message;
        $header = get_option('email_header');
        $footer = get_option('email_footer');
        $company_name = get_option('companyname'); 
        $footer = str_replace('{companyname}', $company_name, $footer);
        $full_message = $header . $message . $footer;
       
        
        if (!empty($festival)) {
            // Fetch all users
            $users = $this->festival_model->get_all_users_to_send_email();
            $email_addresses = [];

                foreach ($users as $email) {

                    if (!empty($email['email'])) {
                            $email_addresses[] = $email['email'];
                        } else {
                            log_message('error', 'Email address not found');
                    }
                }

                   
                    
                    // Configure email settings
                    $this->email->from(get_option('smtp_email'), get_option('companyname'));
                    $this->email->to($email_addresses);
                    $this->email->subject($subject);
                    $this->email->message($full_message);



                    
                    // Send email
                    if ($this->email->send()) {
                        log_message('info', 'Email sent to ' . $email);
                    } else {
                        echo  $this->email->print_debugger();
                        log_message('error', 'Failed to send email to ' . $email);
                    }
                 
            }
        }

        public function send_birthday_email() {
            $current_date = date('Y-m-d'); // Get current date
    
            // Fetch festival data for the current date
            $birthday = $this->festival_model->get_birthday_date($current_date);
            
            $header = get_option('email_header');
            $footer = get_option('email_footer');
            $company_name = get_option('companyname'); 
            $footer = str_replace('{companyname}', $company_name, $footer);
            
           
                
                foreach ($birthday as $dob) {
                
                    if (!empty($dob['email'])) {

                        // Prepare email content
                        $subject =  'Happy Birthday';
                        $message = <<<EOD
                            <div class="container">
                            <div class="header">
                                 <h3>Happy Birthday, {$dob['firstname']}!</h3>
                            </div>
                                <p>Wishing you a wonderful day filled with joy, love, and happiness. May your year ahead be as fantastic as you are!</p>
                                <p>Enjoy your special day!</p>
                            </div>
                            EOD;  

                        $full_message = $header . $message . $footer; 
                        // Configure email settings
                        $this->email->from(get_option('smtp_email'), get_option('companyname'));
                        $this->email->to($dob['email']);
                        $this->email->subject($subject);
                        $this->email->message($full_message);
    
    
    
                        
                        // Send email
                        if ($this->email->send()) {
                            log_message('info', 'Email sent to ' . $dob['email']);
                        } else {
                           echo  $this->email->print_debugger();
                            log_message('error', 'Failed to send email to ' . $dob['firstname']);
                        }
                    } else {
                        log_message('error', 'Email address not found' . $dob['firstname']);
                    }
                }
                     
                
            }
    
}