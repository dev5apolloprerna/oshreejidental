<?php
defined('BASEPATH') or exit('No direct script access allowed');

// require_once(BRANCH_SQL_FOLDER .'install.class.php');
// include_once(BRANCH_SQL_FOLDER .'sqlparser.php');
class Branch extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('branch_model');
        $this->load->model('staff_model');
        $this->load->model('contracts_model');
        $CI = & get_instance();
    }

    public function db_clone($database ='',$company_options=array(),$staff_data = [],$super_admin=[])
    {

        // Include files for installation and creation of db
        require_once(FCPATH .'modules/branch/sql/'.'install.class.php');
        include_once(FCPATH .'modules/branch/sql/'.'sqlparser.php');

        $CI = & get_instance();

        $install = new Install();
        $parser = new SqlScriptParser();

        // set Database and settings
        // $server = isset($server) ? $server : 'localhost';
        // $user = isset($user) ? $branch_data['branch'] : 'root';
        // $password = isset($password) ? $branch_data['password'] : 'root';
        $database = isset($database) ? $database : '';

        $server = APP_DB_HOSTNAME;
        $user = APP_DB_USERNAME;
        $password = APP_DB_PASSWORD;
        // $database = '';
        
        $link = new mysqli($server, $user, $password,'');
         // Check connection
         if ($link->connect_error) {
            die("Connection failed: " . $link->connect_error);
        }

        // Database Create if not exists
        $link->sql = "CREATE DATABASE IF NOT EXISTS ".$database;
        if ($link->query($link->sql) === TRUE) {
            $sqlStatements = $parser->parse(BRANCH_SQL_FOLDER . 'database.sql');
            // Configuration
            $h = $server;
            $u =$user;
            $p = $password;
            $d = $database;

            $link = new mysqli($h, $u, $p, $d);

            foreach ($sqlStatements as $statement) {
                $distilled = $parser->removeComments($statement);
                if (!empty($distilled)) {
                    $link->query($distilled);
                }
            }

            $datecreated = date('Y-m-d H:i:s');
            $link->autocommit(true);
           
          

            // Set Timezone
            $timezone = 'UTC';
            $sql      = "UPDATE ".db_prefix()."options SET value='$timezone' WHERE name='default_timezone'";
            $link->query($sql);

            $di  = time();
            $sql = "UPDATE ".db_prefix()."options SET value='$di' WHERE name='di'";
            $link->query($sql);

            // $installMsg = '<div class="col-md-12">';
            // $installMsg .= '<div class="alert alert-success">';
            // $installMsg .= '<h4 class="bold">Congratulation on your installation!</h4>';
            // $installMsg .= '<p>Now, you can activate modules that comes with the installation in <b>Setup->Modules<b>.</p>';
            // $installMsg .= '</div>';
            // $installMsg .= '</div>';
            // $sql = "UPDATE tbloptions SET value='$installMsg' WHERE name='update_info_message'";
            // $link->query($sql);

            //Insert Main Super Admin as admin in new database

            $sql1 = "INSERT INTO tblstaff (`firstname`, `lastname`, `password`, `email`, `datecreated`, `admin`, `active`,`profile_image`) VALUES('$super_admin->firstname', '$super_admin->lastname', '$super_admin->password', '$super_admin->email', '$super_admin->datecreated', 1, 1,'$super_admin->profile_image')";


            $link->query($sql1);

            // Insert Branch User In Staff and Make ADMIN
            $sql = "INSERT INTO tblstaff (`firstname`, `lastname`, `password`, `email`, `datecreated`, `admin`, `active`,`profile_image`) VALUES('$staff_data->firstname', '$staff_data->lastname', '$staff_data->password', '$staff_data->email', '$staff_data->datecreated', 1, 1,'$staff_data->profile_image')";
            $link->query($sql);

            $CI = & get_instance();
       
            $items = array();

            //Update company information in new database

            foreach($company_options as $key => $val){

                if($val['name'] == 'branch'){

                    $sql = "INSERT INTO ".db_prefix()."options (`name`,`value`) VALUES('".$val['name']."','".$val['value']."')";
                    $link->query($sql);
                
                }else{

                    $sql = "UPDATE ".db_prefix()."options SET value='".$val['value']."' WHERE name='".$val['name']."'";
                    $link->query($sql);    
                }
            }


            $sql = "UPDATE tblcurrencies SET isdefault = '0' WHERE isdefault = 1;";
            $link->query($sql);

            $sql = "INSERT INTO tblcurrencies (`symbol`, `name`, `decimal_separator`, `thousand_separator`, `placement`, `isdefault`) VALUES('₹', 'INR', '.', ',', 'before', 1)";
            $link->query($sql);



            $sqlmedical = "CREATE TABLE `tblmedical_history` (
                  `id` int(11) NOT NULL,
                  `userid` int(11) NOT NULL,
                  `occupation` varchar(255) NOT NULL,
                  `allergies` varchar(255) NOT NULL,
                  `medication` varchar(255) NOT NULL,
                  `tobaco_past` varchar(255) NOT NULL,
                  `tobaco_present` varchar(255) NOT NULL,
                  `alcohol_past` varchar(255) NOT NULL,
                  `alcohol_present` varchar(255) NOT NULL,
                  `marital_status` varchar(255) NOT NULL,
                  `medical_history` varchar(255) NOT NULL,
                  `surgical_history` varchar(255) NOT NULL,
                  `enviro_factors` varchar(255) NOT NULL,
                  `risk_factors` varchar(255) NOT NULL,
                  `chief_complaint` varchar(255) NOT NULL,
                  `dental_history` varchar(255) NOT NULL,
                  `diagnosis` varchar(255) NOT NULL,
                  `disease` varchar(255) NOT NULL,
                  `clinical_findings` varchar(255) NOT NULL,
                  `current_treatment` varchar(255) NOT NULL,
                  `previous_medication` varchar(255) NOT NULL,
                  `current_medication` varchar(255) NOT NULL,
                  `treatment_plan` varchar(255) NOT NULL,
                  `history_comment` varchar(255) NOT NULL,
                  `datecreated` datetime NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

            $link->query($sqlmedical);

            $link->query("ALTER TABLE `tblmedical_history` ADD PRIMARY KEY (`id`);");

            $link->query("ALTER TABLE `tblmedical_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");

            $link->query("ALTER TABLE `tblcontacts` 
                ADD `gender` ENUM('Male','Female','') NOT NULL AFTER `ticket_emails`,
                ADD `dob` DATE NOT NULL AFTER `gender`,
                ADD `age` DATE NOT NULL AFTER `dob`,
                ADD `uid` TEXT NOT NULL AFTER `age`,
                ADD `blood_group` VARCHAR(100) NOT NULL AFTER `uid`,
                ADD `rx_str_date` DATE NOT NULL AFTER `blood_group`,
                ADD `rx_end_date` DATE NOT NULL AFTER `rx_str_date`,
                ADD `otp` INT NOT NULL AFTER `rx_end_date`;");

            $link->query("ALTER TABLE `tblfiles` ADD `xray_title` VARCHAR(255) NULL DEFAULT NULL AFTER `task_comment_id`;");

            $link->query("INSERT INTO `tblroles` (`roleid`, `name`, `permissions`) VALUES (NULL, 'Lab Assistant', 'a:0:{}');");

            $link->query("ALTER TABLE `tbltasks` ADD `is_lab_task` INT(1) NOT NULL DEFAULT '0' AFTER `deadline_notified`;");

            $link->query("INSERT INTO `tbloptions` (`id`, `name`, `value`, `autoload`) VALUES (NULL, 'patient_prefix', 'PT-', '1');");

            $link->query("INSERT INTO `tbloptions` (`id`, `name`, `value`, `autoload`) VALUES (NULL, 'patient_number_format', '1', '1');");

            $link->query("INSERT INTO `tbloptions` (`id`, `name`, `value`, `autoload`) VALUES (NULL, 'next_patient_number', '1', '1');");
            
            //Install active modules in new branch db

            install_modules_branch($database);

            $link->query("UPDATE ".db_prefix()."options SET value='2' WHERE name='appointly_responsible_person'");

            $link->query("UPDATE ".db_prefix()."options SET value='2' WHERE name='callbacks_responsible_person'");

            $link->query("UPDATE ".db_prefix()."leads_status SET name='Patient' WHERE id='1'");
           
        }else{
            echo "Error creating database: " . $CI->error;
        }
    }
    public function index()
    {
        // printrx($_SESSION);
        
        if (staff_cant('view', 'branch')) {
            access_denied('branch');
        }
        if ($this->input->is_ajax_request()) {
            $_POST['order'][0]['column'] = 5;
            $_POST['order'][0]['dir'] = 'DESC';
            // echo '<pre>';
            // print_r($_POST);exit;
            $this->app->get_table_data(module_views_path('branch', 'table'));
        }
        $data['title']                 = _l('branch_tracking');
        $this->load->view('manage', $data);
    }

    public function add($id = '')
    {   

        if (staff_cant('view', 'branch')) {
            access_denied('branch');
        }


        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'branch')) {
                    access_denied('branch');
                }
                $data['branch'] = $this->input->post('branch');
                $data['branch_code'] = $this->input->post('branch_code');
                $data['email'] = $this->input->post('email');
                $data['vat'] = $this->input->post('vat');
                $data['phonenumber'] = $this->input->post('phonenumber');
                $data['staff_id'] = $this->input->post('staff_id');
                $data['website'] = $this->input->post('website');
                $data['address'] = $this->input->post('address');
                $data['city'] = $this->input->post('city');
                $data['state'] = $this->input->post('state');
                $data['zip'] = $this->input->post('zip');

                
                if($data['staff_id'] == '' || $data['branch'] == '' || $data['phonenumber'] == ''){
                    set_alert('warning', _l('fill_all_required_details', _l('branch_lowercase')));
                    redirect(admin_url('branch/add/'));
                }
                $branch_name = str_replace(' ','_',$data['branch']);
                $randomid = mt_rand(100000,999999); 
                $db_name_by_branch = 'db_'.$randomid;
                $data['branch_db'] =$db_name_by_branch;

                $data['image']=$_FILES['image']['name'] ?? '';
                $id = $this->branch_model->add($data);
                $image=$this->profile_images($id);
                $CI = & get_instance();
                hooks()->do_action('before_remove_branch_icon_image');
                $CI->db->where('branchid', $id);
                $CI->db->update(db_prefix() . 'branch', ['image' => $image]);
                if ($id) {
                    // Set Configuration Params
                  
                    // $data_branch['branch'] = str_replace(' ','_',$data['branch']);
                    // $data_branch['email'] = $data['email'];
                    // $data_branch['phonenumber'] = $data['phonenumber'];
                    // $data_branch['vat'] = $data['vat'];
                    // $data_branch['password'] = $data['password'];
                    // $data_branch['staff_id'] = $data['staff_id'];


                    $CI = get_instance();
                    // get Company Options

                    $CI->db->where('staffid',$data['staff_id']);
                    $branch_admin = $CI->db->get(db_prefix() . 'staff')->row();

                    $CI->db->select('name,value');
                    $CI->db->where_in('name',['companyname','company_info_format','default_timezone','smtp_email','smtp_password','smtp_port','smtp_host','smtp_email_charset','invoice_company_name','company_logo']);
                
                   
                    // 'companyname,company_info_format,default_timezone,smtp_email,smtp_password,smtp_port,smtp_host,smtp_email_charset,invoice_company_name,company_logo');
                    $options = $CI->db->get(db_prefix() . 'options')->result_array();

                    $branch_data_options = [
                        [
                            'name' => 'branch',
                            'value' => $this->input->post('branch')
                        ], 
                        [
                            'name' => 'invoice_company_address',
                            'value' => $this->input->post('address')
                        ], 
                        [
                            'name' => 'invoice_company_city',
                            'value' => $this->input->post('city')
                        ], 
                        [
                            'name' => 'invoice_company_postal_code',
                            'value' => $this->input->post('zip')
                        ], 
                        [
                            'name' => 'invoice_company_phonenumber',
                            'value' => $this->input->post('phonenumber')
                        ],
                        [
                            'name' => 'invoice_company_country_code',
                            'value' => '+91'
                        ], 
                    ];

                    $merged_options = array_merge($options,$branch_data_options);


                    $CI->db->where('admin',1);
                    $CI->db->order_by('staffid','asc');
                    $super_admin = $CI->db->get(db_prefix() . 'staff')->row();
                   
                    $db =  $this->db_clone($db_name_by_branch, $merged_options,$branch_admin,$super_admin);

                    set_alert('success', _l('added_successfully', _l('branch')));
                    redirect(admin_url('branch'));
                }
            } else {
                if (staff_cant('edit', 'branch')) {
                    access_denied('branch');
                }
                $branch = $this->input->post('branch');
                $email = $this->input->post('email');
                $vat = $this->input->post('vat');
                $phonenumber = $this->input->post('phonenumber');

                if($branch == '' || $email == '' || $vat == '' || $phonenumber == ''){
                    set_alert('warning', _l('fill_all_required_details', _l('branch_lowercase')));
                    redirect(admin_url('branch/add/' . $id));
                }
                $success = $this->branch_model->update($this->input->post(), $id);
                // $image=$this->profile_images($id);
                // $CI = & get_instance();
                // hooks()->do_action('before_remove_branch_icon_image');
                // $CI->db->where('branchid', $id);
                // $CI->db->update(db_prefix() . 'branch', ['image' => $image]);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('branch')));
                }
                redirect(admin_url('branch/'));
            }
        }else{
            if ($id == '') {
                $title = _l('add_new', _l('branch_lowercase'));
            } else {
                $data['branch']        = $this->branch_model->get($id);
                $title = _l('edit', _l('branch_lowercase'));
            }
    
            // $data['members'] = $this->branch_model->get();
            $this->load->model('staff_model');
            $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active'=>1]);
            $data['contract_types']        = $this->contracts_model->get_contract_types();
            $data['title']                 = $title;
            // $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
            $this->load->view('branch', $data);
        }
        
    }

    public function branch($id = '')
    {
       
        if (staff_cant('view', 'branch')) {
            access_denied('branch');
        }
        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'branch')) {
                    access_denied('branch');
                }
                $id = $this->branch_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('branch')));
                    redirect(admin_url('branch/branch/' . $id));
                }
            } else {
                if (staff_cant('edit', 'branch')) {
                    access_denied('branch');
                }
                $success = $this->branch_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('branch')));
                }
                redirect(admin_url('branch/branch/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('branch_lowercase'));
        } else {
            $data['branch']        = $this->branch_model->get($id);
            $title = _l('edit', _l('branch_lowercase'));
        }

        $this->load->model('staff_model');
        // $data['members'] = $this->branch_model->get();
        $this->load->model('staff_model');
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active'=>1]);
        $this->load->model('contracts_model');
        $data['contract_types']        = $this->contracts_model->get_contract_types();
        $data['title']                 = $title;
        // $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $this->load->view('branch', $data);
    }

    /* Delete announcement from database */
    public function delete($id)
    {
        if (staff_cant('delete', 'branch')) {
            access_denied('branch');
        }
        if (!$id) {
            redirect(admin_url('branch'));
        }
        $response = $this->branch_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('branch')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('branch_lowercase')));
        }
        redirect(admin_url('branch'));
    }

    public function notify($id, $notify_type)
    {
        if (staff_cant('edit', 'branch') && staff_cant('create', 'branch')) {
            access_denied('branch');
        }
        if (!$id) {
            redirect(admin_url('branch'));
        }
        $success = $this->branch_model->notify_staff_members($id, $notify_type);
        if ($success) {
            set_alert('success', _l('branch_notify_staff_notified_manually_success'));
        } else {
            set_alert('warning', _l('branch_notify_staff_notified_manually_fail'));
        }
        redirect(admin_url('branch/branch/' . $id));
    }

    public function profile_images($id='') 
    {
    
       $data['image'] =$this->input->post($_FILES['image'] ?? '');
       
        if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') 
        {
            hooks()->do_action('before_upload_branch_image');
            $path = $this->get_upload_path_by_type('branch') . $id.'/';
            $tmpFilePath = $_FILES['image']['tmp_name'];
           
            
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    // Getting file extension
                    $extension= strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = [
                        'jpg',
                        'jpeg',
                        'png',
                    ];
                    $allowed_extensions = hooks()->apply_filters('branch_image_upload_allowed_extensions', $allowed_extensions);
                    if (!in_array($extension, $allowed_extensions)) {
                        set_alert('warning', _l('Please Select jpg, jpeg and png formate'));
                        return false;
                    }
                    
                    $this->_maybe_create_upload_path($path);
                    $filename = uniqid() . '.' . $extension;
                    //echo $filename;exit;
                    $newFilePath = $path . '/' . $filename;
                    //echo $newFilePath;exit;
                    
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $CI                       = & get_instance();
                        $config                   = [];
                        $config['image_library']  = 'gd2';
                        $config['source_image']   = $newFilePath;
                        $config['new_image']      = 'IMG_' . $filename;
                        $config['maintain_ratio'] = true;
                        $config['width']          = hooks()->apply_filters('branch_icon_image_img_width', 320);
                        $config['height']         = hooks()->apply_filters('branch_icon_image_img_height', 320);
                        $CI->image_lib->initialize($config);
                        $CI->image_lib->resize();
                        $CI->image_lib->clear();
                        // Remove original image
                        unlink($newFilePath);
                    }
                } 

                return $filename;    
        }
    }
    public function get_upload_path_by_type($type)
    {
        $path = '';
        switch ($type) {
            case 'branch':
            $path = BRANCH_DOC_FOLDER;
            break;
        }
        return hooks()->apply_filters('get_upload_path_by_type', $path, $type);
    }
    public function _maybe_create_upload_path($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            fopen(rtrim($path, '/') . '/' . 'index.html', 'w');
        }
    }
    /* Delete visittype from database */
}
