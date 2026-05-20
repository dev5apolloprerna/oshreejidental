<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Generalreport extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Generalreport_model');
         $this->load->library("pagination");
    }

    /* List all visittype */
    public function index($id='')
    {
        if (!is_admin()) {
            access_denied('generalreport');
        }

        $request = $_GET['repo_type'];

        if($request == 'appointments' || $request == ''){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=0;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table'));
            }

            $data['client_name'] = '';

            if($_GET['client_id'] != ''){

                $data['client_name'] = $this->Generalreport_model->get_client_by_id($_GET['client_id']);
            }

            //$data['service_card'] = $this->Generalreport_model->get_customers_service_card();
            $data['staff'] = $this->Generalreport_model->get_staff();
            $data['title'] = _l('generalreport');
            $this->load->view('manage', $data);

        }else if($request == 'leads'){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=1;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table_leads'));
            }

            //$data['service_card'] = $this->Generalreport_model->get_customers_service_card();
            //$data['mechanics'] = $this->Generalreport_model->get_mechanic();
            $data['staff'] = $this->Generalreport_model->get_staff();
            $data['statuses'] = $this->leads_model->get_status();
            $data['title'] = _l('generalreport');
            $this->load->view('manage_leads', $data);

        }
        else if($request == 'patients'){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=0;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table_clients'));
            }

           // $data['service_card'] = $this->Generalreport_model->get_customers_service_card();
            // $data['deliverymans'] = $this->Generalreport_model->get_delviery_man();
            $data['title'] = _l('generalreport');
            $this->load->view('manage_clients', $data);

        }
        else if($request == 'task'){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=1;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table_task'));
            }

            $data['client_name'] = '';

            if($_GET['client_id'] != ''){

                $data['client_name'] = $this->Generalreport_model->get_client_by_id($_GET['client_id']);
            }

           
           // $data['service_card'] = $this->Generalreport_model->get_customers_service_card();
            $data['staff'] = $this->Generalreport_model->get_staff();
             $data['statuses'] = $this->tasks_model->get_statuses();
            $data['title'] = _l('generalreport');
            $this->load->view('manage_task', $data);

        }
         else if($request == 'lab_work'){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=1;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table_labtask'));
            }

            $data['client_name'] = '';

            if($_GET['client_id'] != ''){

                $data['client_name'] = $this->Generalreport_model->get_client_by_id($_GET['client_id']);
            }

           // $data['service_card'] = $this->Generalreport_model->get_customers_service_card();
             $data['staff'] = $this->Generalreport_model->get_staff_lab();
             $data['statuses'] = $this->tasks_model->get_statuses();
            $data['title'] = _l('generalreport');
            $this->load->view('manage_labtask', $data);

        }
        else if($request == 'payment_receipts'){

            if ($this->input->is_ajax_request()) {
                $_POST['order'][0]['column']=0;
                $_POST['order'][0]['dir']='desc';
                //echo '<pre>'; print_r($_POST);exit;
                $this->app->get_table_data(module_views_path('generalreport', 'table_invoices'));
            }

            $data['client_name'] = '';

            if($_GET['client_id'] != ''){

                $data['client_name'] = $this->Generalreport_model->get_client_by_id($_GET['client_id']);
            }

            //$data['service_card'] = $this->Generalreport_model->get_customers_service_card();
           $data['staff'] = $this->Generalreport_model->get_staff();
             $data['invoices_statuses']    = $this->invoices_model->get_statuses();
            $data['title'] = _l('generalreport');
            $this->load->view('manage_invoices', $data);

        }

        
    }

    public function delete($id)
    {
        if(!is_admin()){
            access_denied('report');
        }
        if(!$id){
            redirect('report');
        }
        $response=$this->Generalreport_model->delete($id);
        if($response == true)
        {
            set_alert('success',_l('Deleted',_l('Report')));
        }
        else {
            set_alert('warning', _l('problem_deleting', _l('sessiontype_lowercase')));
        }
        redirect(admin_url('report'));
    }

    public function assign_mechanic($id)
    {
        $staff = $this->input->post('staff_name');
        if($id)
        {   
            //$datas['services']=$this->Generalreport_model->get_service($id);
            $datas['services']=$this->Generalreport_model->get_service_new($id);
            $datas['completed_service']=$this->Generalreport_model->get_complete_service($id);
            $datas['upcoming']=$this->Generalreport_model->get_upcoming_service($id);
            $title = _l('Service Schedule');
            $datas['title'] = $title;
            
            $this->load->view('report/service', $datas);
        }
        
        if($this->input->post('staff_name'))
        {
            $staff = $this->input->post('staff_name');
            
           
            $guarddata = $this->Generalreport_model->get_user_data($id);
            $admin_staff = $this->Generalreport_model->admin_staff();

            foreach ($staff as $staffKey => $value) 
            {
                foreach ($guarddata as $keys => $values) 
                {
                    if($staffKey == $keys && $value != $values['staff_id'])
                    {
                        //Send notification to owner when guard fill check in Emergency.
                        $notificationDataPayload = [
                            'notification_type' => 'service',
                            'sound' => 'default',
                            'from_type' => 'ADMIN',
                            'to_type' => 'MECHANIC',
                            'from_userid' => $admin_staff->staffid, 
                            'to_userid' => $value,
                            'title' => $values['service_name'] . ' service assigned to you',
                            'body' => $values['service_name'].' service on date: '.date('d-M-Y',strtotime($values['service_assign_date_staff']))
                        ];

                        // echo '<pre>'; print_r($notificationDataPayload);
                        $getStaffData = $this->Generalreport_model->get_fcm_token_by_staffid($value);
                        $this->Generalreport_model->send_notification($getStaffData->fcm_token, $notificationDataPayload, $getStaffData->device_type);
                        // $this->Generalreport_model->send_notification($values['fcm_token'], $notificationDataPayload, $values['device_type']);
                        
                        // Insert data into database for notifications
                        $notificationDataPayload = [
                            'notification_type' => 'service',
                            'from_type' => 'ADMIN',
                            'to_type' => 'MECHANIC',
                            'from_userid' => $admin_staff->staffid,
                            'to_userid' => $value,
                            'title' => $values['service_name'] . ' service assigned to you',
                            'body' => $values['service_name'].' service on date: '.date('d-M-Y',strtotime($values['service_assign_date_staff'])),
                            'additional_data' => $id,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                         $customerNotificationDataPayload = [
                        'notification_type' => 'service',
                        'from_type' => 'ADMIN',
                        'to_type' => 'CUSTOMER',
                        'from_userid' => $admin_staff->staffid,
                        'to_userid' => $values['customer_id'],
                        'title' => $values['service_name'] . ' service scheduled',
                        'body' => $values['service_name'].' scheduled on date: '.date('d-M-Y',strtotime($values['service_assign_date_staff'])),
                    ];
        
                    $getCustomerData = $this->Generalreport_model->get_fcmtoken_by_clientid($values['customer_id']);
        
                    $this->Generalreport_model->send_notification($getCustomerData->fcm_token, $customerNotificationDataPayload, $getCustomerData->device_type);

                        if($value != 0)
                        {
                            $this->Generalreport_model->add_notificationdata($notificationDataPayload);
                            $this->Generalreport_model->add_notificationdata($customerNotificationDataPayload);
                        }
                    }
                }
            }
            
            //$data = implode(',', $staff);
            $success = $this->Generalreport_model->update_mechanic($staff, $id);
            if($success == true)
            {
                set_alert('success',_l('Service assigned to mechanic successfully'));
            }
            redirect(admin_url('report'));
        }
    }

    public function confirm_status($id)
    {
        $data=$this->Generalreport_model->confirm_status($id);
        echo json_encode($data);
    }

    public function change_statuses()
    {
        $shedule_ids=$this->input->post('shedule_ids');
        $mechanic_satff=$this->input->post('mechanic_satff');
        $success=$this->Generalreport_model->change_statuses($shedule_ids,$mechanic_satff);
        //print_r($success);exit;
        echo json_encode($success);
    }
}
