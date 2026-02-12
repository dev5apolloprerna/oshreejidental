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
    <div class="company-logo text-center" style="margin-bottom: 30px;">
            <?php get_dark_company_logo(); ?>
        </div>
    <div id="content">

        <div class="container">



            <div id="response" style="padding-left: 180px;padding-right: 180px;">

                <div class="alert alert-<?php echo $type;?> text-center" style="margin:0 auto;margin-bottom:15px;"><?php echo $message;?></div></div>

            <?php echo form_open('appointly/appointments_public/verifyOTP', ['id' => '']); ?>



            <input type="text" hidden name="rel_type" value="external">

            <div class="row">





                <div class="main_wrapper mbot20 <?= ($this->input->get('col')) ? $this->input->get('col') : 'col-md-12'; ?>">

                    <div class="appointment-header"><?php hooks()->do_action('appointly_form_header'); ?></div>

                    <div class="text-center">
                        <h4 class="text-center"><?= _l('Verify OTP'); ?></h4>
                    </div>

                    

                     <?php echo render_input('otp', 'Enter OTP'); ?>


                    
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



</body>

</html>