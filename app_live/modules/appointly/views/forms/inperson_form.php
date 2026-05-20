<?php defined('BASEPATH') or exit('No direct script access allowed');
// Means module is disabled
if ( ! function_exists('get_appointment_types')) {
    access_denied();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo hooks()->apply_filters('appointments_form_title', _l('appointment_create_new_appointment')); ?></title>

    <?php app_external_form_header($form); ?>

    <link href="<?= module_dir_url('appointly', 'assets/css/appointments_external_form.css'); ?>" rel="stylesheet"
          type="text/css">
</head>

<body class="appointments-external-form" <?php if (is_rtl(true)) {
    echo ' dir="rtl"';
} ?>>
<?php
$clientUserData = $this->session->userdata();
applyAdditionalCssStyles($clientUserData);

$message = '';
if($_SESSION['message-error'] != ''){
    $type = 'danger';
    $message = $_SESSION['message-error'];
}else if($_SESSION['message-success'] != ''){
    $type = 'success';
    $message = $_SESSION['message-success'];
}



?>
<div id="wrapper">
    <div class="company-logo text-center">
            <?php get_dark_company_logo(); ?>
        </div>
    <div id="content">

        <div class="container">



            <div id="response" style="padding-left: 180px;padding-right: 180px;">

                <div class="alert alert-<?php echo $type;?> text-center" style="margin:0 auto;margin-bottom:15px;"><?php echo $message;?></div></div>



            <!-- <?php echo form_open('appointly/appointments_public/create_external_appointment', ['id' => 'appointments-form']); ?> -->
               <?php echo form_open('appointly/appointments_public/sendotp', ['id' => 'new_web']); ?>



            <input type="text" hidden name="rel_type" value="external">

            <div class="row">





                <div class="main_wrapper mbot20 <?= ($this->input->get('col')) ? $this->input->get('col') : 'col-md-12'; ?>">

                    <div class="appointment-header"><?php hooks()->do_action('appointly_form_header'); ?></div>

                    <div class="text-center">
                        <h4 class="text-center"><?= _l('appointment_create_new_appointment_inperson'); ?></h4>
                    </div>

                     <div class="col-md-6">
                         <?php if (count($branches) > 0) { ?>
                        <div class="form-group appointment_type_holder">
                             <small class="req text-danger">* </small>
                            <label for=""
                                   class="control-label"><?= _l('select_branch'); ?></label>

                            <select class="form-control selectpicker" name="branch" id="branch" data-live-search="true">
                                <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                <?php foreach ($branches as $branch) { ?>
                                    <option class="form-control" 
                                            value="<?= $branch['branchid']; ?>"><?= $branch['branch']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class=" clearfix mtop15"></div>
                    <?php } ?>
                     <br>

                         <?php $appointment_types = get_appointment_types();

                    if (count($appointment_types) > 0) { ?>
                        <div class="form-group appointment_type_holder">
                             <small class="req text-danger">* </small>
                            <label for="appointment_select_type"
                                   class="control-label"><?= _l('appointments_type_heading'); ?></label>
                            <select class="form-control selectpicker" name="type_id" id="appointment_select_type" data-live-search="true">
                              
                                
                            </select>
                        </div>
                        <div class=" clearfix mtop15"></div>
                    <?php } ?>
                    <br>

                     <div class="form-group appointment_type_holder hidden">
                          
                            <label for="appointment_select_type"
                                   class="control-label"><?= _l('Enter Pateint ID'); ?></label>
                          <!--   <select class="form-control selectpicker" name="patient_id" id="patient_id" data-live-search="true">
                              
                                
                            </select> -->
                            <input type="text" class="form-control" onkeyup="hidefields(this.value);" name="patient_id" id="patient_id">
                        </div>
                         <div class=" clearfix mtop15"></div>
                         <br>

                    <div class="pateint_data">

                     <div class="form-group">
                        <label for="name"><?= _l('appointment_full_name'); ?></label>
                        <input type="text" class="form-control"
                               value="<?= (isset($clientUserData['client_logged_in'])) ? get_contact_full_name($clientUserData['contact_user_id']) : ''; ?>"
                               name="name" id="name" onchange="setsubject();">
                    </div>
                    <!-- <div class="form-group">
                        <label for="email"><?= _l('appointment_your_email'); ?></label>
                        <input type="email" class="form-control"
                               value="<?= (isset($clientUserData['client_logged_in'])) ? get_contact_detail($clientUserData['contact_user_id'], 'email') : ''; ?>"
                               name="email" id="email">
                    </div> -->

                    <div class="form-group">
                        <label for="dob">DOB</label>
                        <input type="date" class="form-control" name="dob" id="dob">
                    </div>
                   
                 <div class="form-group">
                         <small class="req text-danger">* </small>
                                <label for="treatment-plan">Gender</label><br>
                                <input type="radio" name="gender" id="male" value="male" style="height: auto !important;">
                                <label for="immediate">Male</label>
                                <input type="radio" name="gender" id="female" value="female" style="height: auto !important; ">
                                <label for="planned">Female</label>
                            
                        </div>
                   
                </div>
                </div>
                <div class="col-md-6">

                       <div class="pateint_data">

                     <div class="form-group">
                        <label for="phone"><?= _l('appointment_phone'); ?>
                            (Ex: <?= _l('appointment_your_phone_example'); ?>)</label>
                        <input type="text" class="form-control"
                               value="<?= (isset($clientUserData['client_logged_in'])) ? get_contact_detail($clientUserData['contact_user_id'], 'phonenumber') : ''; ?>"
                               name="phone" id="phone">
                    </div>

                    
                    <div hidden>
                        <?php echo render_input('age', 'Age', '', 'text', ['id' => 'age', 'readonly' => 'readonly',     ]); ?>
                    </div>
              
                     <?php echo render_input('subject', 'appointment_subject','','',['readonly' => 'readonly']); ?>
                        </div>


                    <?php echo render_textarea('description', 'appointment_description', '', ['rows' => 3]); ?>

                  
                   <?php echo render_textarea('pt_address', 'client_address',''); ?>
                        
                   
                    <?php echo render_datetime_input('date', 'appointment_date_and_time', '', ['readonly' => "readonly"], [], '', 'appointment-date'); ?>

                     <div class="hours_wrapper" style="margin-bottom: 40px;margin-top: 27px;">
                        <span class="available_time_info hwp"><?= _l('appointment_available_hours'); ?></span>
                        <span class="busy_time_info hwp"><?= _l('appointment_busy_hours'); ?></span>
                    </div>
                    <!-- <div class="form-group">
                        <label for="address"><?= _l('appointment_meeting_location') . ' ' . _l('appointment_optional'); ?></label>
                        <input type="text" class="form-control" value="" name="address" id="address">
                    </div> -->

                </div>

                                       
                    <?php
                    $rel_cf_id = (isset($appointment) ? $appointment['apointment_id'] : false);
                    echo render_custom_fields('appointly', $rel_cf_id);
                    ?>
                    <?php if (
                        get_option('recaptcha_secret_key') != ''
                        && get_option('recaptcha_site_key') != ''
                        && get_option('appointly_appointments_recaptcha') == 1
                    ) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="g-recaptcha"
                                         data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
                                    <div id="recaptcha_response_field" class="text-danger"></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="pull-right">
                        <button type="submit" id="form_submit"
                                class="btn btn-primary"><?php echo _l('appointment_submit'); ?></button>
                    </div>
                    <div class="clearfix mtop15"></div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php
app_external_form_footer($form);
?>

<script>


   $('#branch').change(function(){
        
        var select = document.getElementById("appointment_select_type");
        var length = select.options.length;
        for (i = length-1; i >= 0; i--) {
          select.options[i] = null;
        }

        document.getElementById('subject').value = '';

        // var select1 = document.getElementById("patient_id");
        // var length1 = select1.options.length;
        // console.log(length1);
        // for (i = length1-1; i >= 0; i--) {
        //   select1.options[i] = null;
        // }
        document.getElementById('name').value = '';      
        // document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('name').readOnly = false; 
        // document.getElementById('email').readOnly = false;
        document.getElementById('phone').readOnly = false;
        document.getElementById("male").checked = false;
        document.getElementById("female").checked = false;   

        var id = $(this).val();
        var csrf_token_name = document.getElementsByName("csrf_token_name")[0].value;

        $.ajax({
            type: "POST",
            url: "<?php echo base_url('appointly/inperson/get_branch_apponmtmenttypes');?>",
            data: {branch : id,csrf_token_name:csrf_token_name},
            success: function( data ) {

                $('#appointment_select_type').append(data);
                $('#appointment_select_type').selectpicker('refresh');
            }
        });

        $.ajax({
            type: "POST",
            url: "<?php echo base_url('appointly/appointments_public/get_branch_customers');?>",
            data: {branch : id,csrf_token_name:csrf_token_name},
            success: function( data ) {

                $('#patient_id').append(data);
                $('#patient_id').selectpicker('refresh');
            }
        });
    });

   $('#appointment_select_type').change(function(){

        var id = $(this).val();
        if(id == ''){
            document.getElementById('subject').value = '';
        }else{
            var name = document.getElementById("name");
            var e = document.getElementById("appointment_select_type");
            var text = e.options[e.selectedIndex].text;
           document.getElementById('subject').value = text + ' for ' + name.value;
        }
    });

   // $('#patient_id').change(function(){

   //      document.getElementById('subject').value = '';

   //      document.getElementById('email').readOnly = false;
   //      document.getElementById('phone').readOnly = false;

   //      var id = $(this).val();
   //      var e = document.getElementById("patient_id");
   //      var text = e.options[e.selectedIndex].text;
   //      document.getElementById('name').value = text;
   //      document.getElementById('name').readOnly = true;

   //      document.getElementById('email').value = $(this).find(':selected').data('email');
   //      document.getElementById('email').readOnly = true;

   //      document.getElementById('phone').value = $(this).find(':selected').data('phone');
   //      document.getElementById('phone').readOnly = true;

   //      if($(this).find(':selected').data('gender') == 'Male'){
   //          document.getElementById("male").checked = true;    
   //      }else if($(this).find(':selected').data('gender') == 'Female'){
   //          document.getElementById("female").checked = true;
   //      }
            
   //      setsubject();

   //  });




   function setsubject(){

        var e = document.getElementById("appointment_select_type");
        var name = document.getElementById("name");

        var text = e.options[e.selectedIndex].text;

         if( name.value != '' ){
               document.getElementById('subject').value = text + ' for ' + name.value;
         }
    }

     $('#new_web').appFormValidator({
            rules: {
                type_id: "required",
                gender: "required",
                age: "required",
                branch: "required",
                subject: "required",
                name: "required",
                // email: "required",
                // description: "required",
                date: "required",
                phone: 'required',
                dob: 'required',
                description: 'required',
                pt_address: 'required',

            },
        });

    function  hidefields(val) {
     

        if(val != ''){

         let elements = document.getElementsByClassName("pateint_data");
        Array.from(elements).forEach(function (element) {
          element.style = 'display:none;';
        });

         $('#new_web').appFormValidator({
            rules: {
                type_id: "required",
                // gender: "required",
                // age: "required",
                branch: "required",
                // subject: "required",
                // name: "required",
                // email: "required",
                // description: "required",
                date: "required",
                // phone: 'required',
            },
        });

            
        }else{
             let elements = document.getElementsByClassName("pateint_data");
        Array.from(elements).forEach(function (element) {
          element.style = 'display:block;';
        });

        $('#new_web').appFormValidator({
            rules: {
                type_id: "required",
                gender: "required",
                age: "required",
                branch: "required",
                subject: "required",
                name: "required",
                email: "required",
                // description: "required",
                date: "required",
                phone: 'required',
            },
        });
        }
    }

        
        $(document).ready(function () {
        $('#dob').change(function () {  
        var dob = new Date(this.value);
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear(); 
        $('#age').val(age);
        });
})
    
    </script>


<?php if (isset($form)): ?>
    <script>
        app.locale = "<?= get_locale_key($form->language); ?>";
    </script>
<?php endif; ?>

 
<!-- Javascript functionality -->
<?php require('modules/appointly/assets/js/appointments_external_form.php'); ?>

<!-- If callbacks is enabled load on appointments external form -->
<?php if (get_option('callbacks_mode_enabled') == 1) require('modules/appointly/views/forms/inperson_form.php'); ?>

</body>

</html>