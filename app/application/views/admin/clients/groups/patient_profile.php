<?php

$CI = &get_instance();

// $CI->db->select(db_prefix() . 'appointly_appointments.*,'.db_prefix().'staff.staffid,'.db_prefix().'staff.firstname,'.db_prefix().'staff.lastname,'.db_prefix() . 'staff.profile_image');
// $CI->db->where('contact_id', $contact->id);
// $CI->db->join(db_prefix() . 'appointly_attendees',db_prefix() . 'appointly_attendees.appointment_id = ' . db_prefix() . 'appointly_appointments.id','left');
// $CI->db->join(db_prefix() . 'staff',db_prefix() . 'staff.staffid = ' . db_prefix() . 'appointly_attendees.staff_id','left');
// $CI->db->group_by(db_prefix() . 'appointly_appointments.id');
// $appointments = $CI->db->get(db_prefix() . 'appointly_appointments')->result_array();


//new code start

if (!isset($client) || !$client) {
    echo '<div class="alert alert-danger">Client not found.</div>';
    return;
}

if (!isset($contact) || !$contact) {
    // ✅ Try to load primary contact
    $CI->db->where('userid', $client->userid);
    $CI->db->where('is_primary', 1);
    $contact = $CI->db->get(db_prefix() . 'contacts')->row();

    // If still not found, load any contact
    if (!$contact) {
        $CI->db->where('userid', $client->userid);
        $contact = $CI->db->get(db_prefix() . 'contacts')->row();
    }
}

// If no contact exists at all, show message and stop further contact-dependent code
if (!$contact) {
    echo '<div class="alert alert-warning">No contact found for this patient.</div>';
    // You can still show some client info, but stop appointment queries that need contact_id
    return;
}
 // new code end
$CI->db->select(db_prefix() . 'appointly_appointments.*,');
$CI->db->from(db_prefix() . 'appointly_appointments');

$CI->db->where('contact_id', $contact->id);
$CI->db->group_by(db_prefix() . 'appointly_appointments.id');
$appointments = $CI->db->get()->result_array();

$CI->db->where('rel_id', $client->userid);
$CI->db->where('rel_type', 'customer');
$CI->db->where('is_lab_task',1);
$CI->db->order_by(db_prefix() . 'tasks.id', 'desc');
$lab_work_tasks = $CI->db->get(db_prefix() . 'tasks')->result_array();


$CI->db->where('role', '1');
$CI->db->or_where('admin', '1');
$staff = $CI->db->get(db_prefix() . 'staff')->result_array();



?>
<?php if ($this->session->flashdata('success_message')): ?>
    <div class="alert alert-success">
        <?php echo $this->session->flashdata('success_message'); ?>
    </div>
<?php endif; ?>
<style>
    .patient-profile-content {
    width: 40%;
    height: auto;
    box-shadow: rgba(0, 0, 0, 0.16) 0px 3px 6px, rgba(0, 0, 0, 0.23) 0px 3px 6px;
    background-color: white;
    padding: 15px 15px 15px 15px;
}
.main-section{
  margin: 0 0 0 17px;

}
.patien-img-name {
    display: flex;
}
.patient-name-uid {
    margin: auto;
}
table#patiend-profile-details th {
    width: 60%;
    padding: 10px 0px;
    font-weight: 700;
}
.edit-profile-btn {
    display: flex;
    justify-content: center;
}
button#edit-patient-profile-btn {
    padding: 5px 35px;
}
.top_section {
    display: flex;
    /* margin: 50px 0 0 0; */
}
* {
    /* font-family: inherit; */
}
body {
    /*background-color: #f9f3f3 !important;*/
}

/* Karan Css Start */

.panel-body {
    padding: 0;
    border: none;
    box-shadow: unset;
    background-color: unset !important;
}
.panel_s {
    background-color: unset !important;
    border: none;
    box-shadow: unset ;
}

/* -*-*-*-*-*-*-*-*-*-* Profile Part Start *-*-*-*-*-*-*-*-*-*- */
.profile_section {
    width: 26%;
    padding: 0 0 0 0;
}
.profile_part {
    height: 310px;
    padding: 15px 20px;
    border-radius: 9px;
    background-color: #fff;
    margin: 0 15px 0 0;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000;    
}
.profile_part .person_img figure {
    margin: 0;
}
.profile_section .profile_top {
    display: flex;
    margin-bottom: 10px;
}
.profile_part .person_img figure img {
    border-radius: 50%;
    height: 50px;
    width: 50px;
    /* border: 1px solid black; */
}
.profile_part .profile_detail table {
    margin: 0 0 0 15px;
}
.person_detail {
    padding: 5px 0 0 20px;
}
.appointment_status_info {
    margin: 4px 0 0 0 !important;
}
.appointment_status_info .label {
    font-size: 11px;
    padding: 3px 10px 3px 10px
}
.person_detail .p_name h3 {
    margin: 0;
    padding: 0 10px 0 0;
    line-break: anywhere;
    color: rgb(71 85 105);
    font-size: 12px;
    text-transform: capitalize;
}
.person_detail .p_id p {
    font-size: 12px;
    line-height: 25px;
    font-weight: 400;
    color: rgb(71 85 105);
    margin: 0 0 7px 0;
}
.person_detail .p_id p span {
    color: #9e9e9e;
    text-transform: uppercase;
}

.profile_detail .header {
    padding: 0 8px 0 0;
    width: 50%;
    color: rgb(71 85 105);
    font-weight: 500;
    font-size: 12px;
    text-transform: capitalize
}
.profile_detail .data {
    font-size: 12px;
    color: rgb(71 85 105);
    padding: 0 10px 0 0px;
    line-height: 20px;
    line-break: anywhere;
}
.profile_part .profile_one {
    margin: 0 0 0 0;
}
.x_ray_part {
    height: 275px;
    padding: 20px 15px 20px 15px;
    border-radius: 9px;
    background-color: #fff;
    margin: 22px 15px 0 0;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000;
}
.x_ray_part .xray_head {
    margin: 0 0 0 0;
}
.x_ray_part .xray_head h2 {
    font-size: 13px;
    color: rgb(71 85 105);
    margin: 0 0 15px 0px;
    text-align: left;
}
.x_ray_part .xray_images {
    height: 155px;
    margin: 0 0 20px 0;
    text-align: center;
}
.x_ray_part .xray_images .xray_img {
    margin: 0 0 15px 0;
    width: 33%;
    padding: 0 0px 0 0px;
}
.x_ray_part .xray_images .xray_img figure img {
    height: 60px;
    width: 60px;
    border-radius: 6px;
    /* border: 1px solid #000; */
}
.x_ray_part .upload_btn {
    text-align: left;
    margin: 0;
}
.x_ray_part .upload_btn button {
    padding: 7px 50px 7px 50px;
    background-color: #2563eb;
    font-size: 11px;
    color: #fff;
    text-transform: uppercase;
    border: none;
    border-radius: 7px;
}

/* -*-*-*-*-*-*-*-*-*-* Medical Part Start *-*-*-*-*-*-*-*-*-*- */
.medical_part {
    height: 557px;
    background-color: #fff;
    border-radius: 7px;
    padding: 20px 10px 20px 20px;
    width: 33.8%;
    margin: 0 0 0 20px;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000;
}
.medical_part .medical_head {
    margin: 0 0 15px 15px;
    font-size: 13px;
    color: rgb(71 85 105);
    text-align: left;
}
.history_box {
    /* background-color: #e0e9fa; */
    height: auto;
    width: 100%;
    margin: 0px 10px 15px 0;
    border: 2px solid #f2eff2;
    padding: 10px 15px 0px 15px;
    border-radius: 10px;
    border-radius: 7px;
}

.medical_part .history_box h3 {
    font-size: 13px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.medical_part .history_box p {
    font-size: 12px;
    text-align: justify;
    margin: 10px 0 10px 0;
    line-height: 16px;
    color: rgb(71 85 105);
}
.medical_part .appoinment_data_scroll .row {
    margin: 0 10px 0 0;
}

/* -*-*-*-*-*-*-*-*-*-* Appoinment Part Start *-*-*-*-*-*-*-*-*-*- */
.appoinment_part {
    height: 310px;
    background-color: #fff;
    border-radius: 7px;
    padding: 20px 10px 20px 20px;
    width: 36.1%;
    margin: 0 0 0 5px;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000; 
}
.appoinment_data_scroll {
    height: 475px;
    overflow-y: auto;
}
.appoinment {
    height: 244px;
}
.appoinment_data_scroll::-webkit-scrollbar {
    width: 3px;
}
.appoinment_data_scroll::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
.appoinment_data_scroll::-webkit-scrollbar-thumb {
    background: #e0e9fa; 
    border-radius: 10px;
}
.appoinment_data_scroll::-webkit-scrollbar-thumb:hover {
    background: #e0e9fa; 
}
.appoinment_part .appoinment_head {
    font-size: 13px;
    color: rgb(71 85 105);
    margin: 0 0 15px 15px;
    text-align: left;
}
.appoinment_part .apoinment_img { 
    padding: 0px 15px 0 0;
}
.appoinment_part .apoinment_img img {
    height: 35px;
    width: 35px;
    border-radius: 50%;
}
.appoinment_part .apponment_detail {
    padding: 0 5px 0 0px;
}
.appoinment_part .apponment_detail h3 {
    font-size: 13px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.appoinment_part .apponment_detail p {
    font-size: 12px;
    margin: 10px 0 10px 0;
    line-height: 16px;
    color: rgb(71 85 105);
}
.appoinment_part .dr_name {
/*    padding: 7px 0 0 25px;*/
padding: 0px 0 0 25px;
}
.dr_name select{
    border: 2px solid #f2eff2;
    border-radius: 50px;
    outline: none;
    font-size: 12px;

}
.dr_name select:focus{
    box-shadow :unset !important;
    outline: 0;
    --tw-ring-shadow : unset !important;
    border-color: #f2eff2;
    
}
.appoinment_part .dr_name span {
    padding: 0 0 0 0px;
    font-size: 12px;
    color: rgb(71 85 105);
}
.appoinment_part .appoinment_data .appoinment_date {
    padding: 0px 0 0 0;
    font-size: 11px;
    color: #b8b2b3;
    float: right;
    text-align: right;
}
.appoinment_data {
    padding: 0px 30px 10px 30px;
    border: 2px solid #f2eff2;
    border-radius: 10px;
    margin: 0px 10px 15px 0px;
}
.treatment_part {
    width: 108.1%;
    height: 275px;
    padding: 20px 20px;
    border-radius: 9px;
    background-color: #fff;
    margin: 45px 15px 0 -20px;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
}
.treatment_part .treatment_head h2 {
    font-size: 13px;
    color: rgb(71 85 105);
    margin: 0 0 15px 10px;
    text-align: left;
}
.treatment_part .treatment_data .treatment_detail {
    margin: 0 0 15px 10px;
    width: 95%;
}
.treatment_part .treatment_data  {
    height: 205px
}
.treatment_part .treatment_data .treatment_detail h3 {
    font-size: 13px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.treatment_part .treatment_data .treatment_detail p {
    font-size: 12px;
    text-align: justify;
    margin: 10px 0 10px 0;
    line-height: 16px;
    color: rgb(71 85 105);
}
@media (min-width: 992px) and (max-width: 1024px) {
    h2 {
        font-size: 12px !important;
    }
    h3 {
        font-size: 11px !important;
    }
    td {
        font-size: 11px !important;
    }
    span {
        font-size: 11px !important;
    }
    p {
        font-size: 11px !important;
    }
    .profile_part .person_img figure img {
        height: 40px;
        width: 40px;
    }
    .person_detail {
        padding: 2px 0px 0 0px;
    }
    .profile_section .profile_top {
        margin-bottom: 5px;
    }
    .x_ray_part .xray_images .xray_img figure img {
        height: 50px;
        width: 50px;
    }
    .appoinment_part .apoinment_img img {
        height: 30px;
        width: 30px;
    }
    .appoinment_part .dr_name {
        padding: 5px 0 0 0px;
    }
    .appoinment_part {
        height: 250px;
    } 
    .appoinment {
        height: 184px;
    }
    .treatment_part {
        width: 112.2%;
    }
    .x_ray_part {
        margin: 20px 10px 0 0;
    } 
    .profile_part {
        height: 250px;
        padding: 15px 10px;
        margin: 0 10px 0 0;
    }
    .medical_part {
        width: 30%;
        height: 543px;
        margin: 0 0 0 15px;
    }
    .profile_section {
        width: 29%;
    }
    .appoinment_part .appoinment_data .appoinment_date {
        padding: 10px 0 0 0;
    }
}

.label-info {
  background: #2663eb;
  border: 1px solid #2663eb;
  text-transform: uppercase !important;
  color: #ffffff !important;
}

.appoinment_dr_name {
    display: flex;
}

 .label-success:hover,
 .label-success:active {
  background: rgb(110, 160, 41);
  border: rgb(132, 197, 41);
  color: #ffffff !important;
  text-transform: uppercase !important;
}

body.admin_light_theme_initiated.appointments .label-warning {
  color: #fff !important;
}

_history .label-success,
 .label-success {
  background: rgb(132, 197, 41);
  border: rgb(132, 197, 41);
  color: #ffffff !important;
  text-transform: uppercase !important;
}

 .label-danger {
  background: rgba(244, 3, 47, 0.59);
  border: 1px solid rgb(248, 106, 132);
  color: #ffffff !important;
}

body .appointly_single_view_form {
  display: none;
}

body table.table-appointments th.sorting_disabled.not-export {
  width: 220px;
}

body .appointment .fit-content {
  text-align: center;
  margin: 15px;
}

.align-items-center {
  margin: 0 auto;
  width: 80%;
}

body .appointment_icon_single {
  padding-top: 0;
  padding-right: 15px;
  padding-left: 7px;
  float: left;
  font-size: 36px;
}

.spmodified.col-md-12.mright15 {
  width: 100%;
}

body.perfex_office_theme_initiated.appointments ._buttons a {
  cursor: pointer !important;
}

body.perfex_office_theme_initiated.appointments .label-warning {
  background: #f7af3d !important;
  font-size: 14px !important;
  border: 1px solid #f7af3d !important;
  color: #fff !important;
}
.app_date{
        margin-bottom: 15px;
}
.app_date i{
    margin-right: 5px;
}
strong#medical_disease {
    border-radius: 30px;
    padding: 4px 6px 4px 6px;
    border: 1px solid #d3d2d2;
}
p#medical_history_data strong {
    display: inline-block;
    margin-bottom: 10px;
    margin-right: 10px;
    font-weight: 600;
    color: rgb(71 85 105);
}
i.fa.fa-circle.text-danger-glow.blink {
    margin-right: 5px;
}
.text-danger-glow {
    color: #ff4141;
    text-shadow: 0 0 0px #f00, 0 0 0px #f00, 0 0 0px #f00, 0 0 50px #f00, 0 0 10px #f00, 0 0 0px #f00, 0 0 0px #f00;
}

.blink {
  animation: blinker 1s cubic-bezier(.5, 0, 1, 1) infinite alternate;  
}
@keyframes blinker {  
  from { opacity: 1; }
  to { opacity: 0; }
}
.clock_icon img{
    height: 20px;
    cursor: pointer;
}
.clock_icon{
    padding : 0;
    margin-left: auto;
    text-align: end;
    margin-top:15px;
}
.appo-btn{
    border-radius: 50px;
    font-size: 12px !important;
    padding: 2px 9px 2px;
}
.appo-div{
    margin: auto;
}

.loader {
    border: 16px solid #f3f3f3; / Light grey /
    border-top: 16px solid #3498db; / Blue /
    border-radius: 50%;
    width: 100px;
    height: 100px;
    animation: spin 2s linear infinite;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; / Ensure it stays on top /
    display: flex;
    margin: auto;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.blur {
    filter: blur(5px);
    pointer-events: none; / Disable interactions with blurred content /
}
</style>
<!-- <style>
    .patient-profile-content {
    width: 40%;
    height: auto;
    box-shadow: rgba(0, 0, 0, 0.16) 0px 3px 6px, rgba(0, 0, 0, 0.23) 0px 3px 6px;
    background-color: white;
    padding: 15px 15px 15px 15px;
}
.patien-img-name {
    display: flex;
}
.patient-name-uid {
    margin: auto;
}
table#patiend-profile-details th {
    width: 60%;
    padding: 10px 0px;
    font-weight: 700;
}
.edit-profile-btn {
    display: flex;
    justify-content: center;
}
button#edit-patient-profile-btn {
    padding: 5px 35px;
}
</style>
<div class="patient-info">
  <div class="patient-profile-content">
    <div class="patien-img-name">
      <div class="patient-img">
        <img src="user8-128x128.jpg">
      </div>
      <div class="patient-name-uid">
        <div class="patient-name">
          <h4><?php echo $client->company ?></h4>
        </div>
        <div class="patient-uid">
          <label>ID : </label><strong><?php echo $contact->uid ?></strong>
        </div>
      </div>
    </div>
      <table id="patiend-profile-details">
        <tr>
          <th>Gender</th>
          <td><?php echo $contact->gender ?></td>
        </tr>
        <tr>
          <th>Date of birth</th>
          <td><?php echo $contact->dob ?></td>
        </tr>
        <tr>
          <th>Bloog Group</th>
          <td><?php echo $contact->blood_group ?></td>
        </tr>
        <tr>
          <th>Email</th>
          <td><?php echo $contact->email; ?></td>
        </tr>
        <tr>
          <th>Phone</th>
          <td><?php echo $client->phonenumber ?></td>
        </tr>
      </table>
      <div class="edit-profile-btn">
      <a href="<?php echo admin_url('clients/client/' . $client->userid);?>" id="edit-patient-profile-btn" type="button" >Edit Patient Profile</a>
      </div>
</div>
</div> -->
<section class="main-section">
        <div class="container-patient">
        <div id="loader" class="loader" style="display: none;"></div> 
            <div class="row top_section"  id="patient_profileSection">
                <div class="col-xl-4 col-lg-4 profile_section">
                    <div class="row profile_part">
                        <div class="row profile_top">
                            <div class="col-lg-3 person_img">
                                <figure>
                                    <?php $user_image_path = $contact->profile_image != '' ?base_url('uploads/client_profile_images/' . $contact->id . '/thumb_' . $contact->profile_image) : base_url('assets/images/user.jpg');
                                    ?>
                                    <img src="<?php echo $user_image_path;?>" alt="Person Image">
                                </figure>
                            </div>
                            <div class="col-lg-9 person_detail">
                                <div class="p_name">
                                    <h3><?php echo $client->company;?></h3>
                                </div>
                                <div class="p_id">
                                    <p><span>id : </span> <?php echo $contact->uid;?></p>
                                </div>
                            </div>
                        </div>
                        <div class="row profile_detail">
                            <div>
                                <table>
                                    <!-- <tr>
                                        <td class="header">status:</td>
                                        <td class="data">ICU Post Appendix OP</td>
                                    </tr> -->
                                    <tr>
                                        <td class="header">Gender:</td>
                                        <td class="data"><?php echo $contact->gender;?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Blood G:</td>
                                        <td class="data"><?php echo $contact->blood_group;?></td>
                                    </tr>
                                    <!-- <tr>
                                        <td class="header">Condition:</td>
                                        <td class="data">Stable</td>
                                    </tr>
                                    <tr>
                                        <td class="header">Room no:</td>
                                        <td class="data">75</td>
                                    </tr>
                                    <tr>
                                        <td class="header">Weight:</td>
                                        <td class="data">64KG</td>
                                    </tr>
                                    <tr>
                                        <td class="header">Height</td>
                                        <td class="data">6M</td>
                                    </tr>
                                    <tr>
                                        <td class="header">Genotype:</td>
                                        <td class="data">O+</td>
                                    </tr> -->
                                    <tr>
                                        <td class="header">Email:</td>
                                        <td class="data"><?php echo $contact->email;?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Phone:</td>
                                        <td class="data"><?php echo $client->phonenumber;?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Current RX Start Date:</td>
                                        <td class="data"><?php echo $contact->rx_str_date != '0000-00-00' ? $contact->rx_str_date :'-';?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Current RX End Date:</td>
                                        <td class="data"><?php echo $contact->rx_end_date != '0000-00-00' ? $contact->rx_end_date : '-';?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Current Treatment:</td>
                                        <td class="data"><?php echo $medical_history->current_treatment != '' ? $medical_history->current_treatment : '-';?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row x_ray_part">
                        <div class="row xray_head">
                            <h2>X-Ray</h2>
                        </div>
                        <div class="row xray_images appoinment_data_scroll">                                 
                            <?php
                            if (!empty($xray_file) && is_array($xray_file)) {
                                foreach ($xray_file as $file) {
                                    if ($file !== null && isset($file->file_name)) {
                                    $image_path = base_url('uploads/clients/' . $client->userid . '/' . $file->file_name);
                                    $file_extension = pathinfo($image_path, PATHINFO_EXTENSION);
                                    // Check if the file extension is one of the allowed image formats
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) { ?>
                                        <div class="col-lg-4 xray_img">
                                            <a href="<?php echo $image_path?>" target="_blank">
                                                <figure>
                                                    <img src="<?php echo $image_path;?>" >
                                                </figure>
                                            </a>
                                        </div>
                                    <?php }
                                }                   
                            }
                            } else {
                                echo "No image files found.";
                            }
                            ?>
                          
                            
                            
                        </div>
                        <!-- <div class="row upload_btn">
                            
                            <a href="#"  data-toggle="modal" data-target="#xray">Upload</a>  
                                               
                        </div> -->
                        <div class="row upload_btn">
                            <!-- <input type="file" class="button" id="myFile" name="Upload" value="Upload"> -->
                            <button data-toggle="modal" data-target="#xray">Upload</button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-4 appoinment_part">
                    <div class="row text-center">
                        <div>
                            <h2 class="appoinment_head">Appoinments</h2>
                        </div>
                        
                    </div>
                    <div class="appoinment appoinment_data_scroll">
                        <?php 

                        if(!empty($appointments)){

                        foreach ($appointments as $key => $value) {

                            $CI->db->where('appointment_id', $value['id']);
                            $appo_staff = $CI->db->get(db_prefix() . 'appointly_attendees')->result_array();

                            $staff_id = '';

                            $check_start_end_time = [];

                            if(!empty($appo_staff)){


                                $staff_id = end($appo_staff)['staff_id'];

                                $CI->db->where('appointment_id',$value['id']);
                                $CI->db->where('staff_id',$staff_id);
                                $check_start_end_time = $CI->db->get(db_prefix().'appointment_assign_log')->row();
                            }

                            ?>

                        <div class="row appoinment_data">
                            <div class="row">
                                <div class="col-xl-9 col-lg-8 col-md-7 apponment_detail">
                                    <div>
                                        <p class="app_date"><i class="fa-regular fa-calendar calendar-icon" aria-hidden="true"></i>
<?php echo date("d/m/y", strtotime($value['date'])) . ' ' .date("H:i A", strtotime($value['start_hour']));?></p>
                                        <h3><?php echo $value['subject'];?></h3>
                                        <p><?php echo $value['description'];?></p>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-4 text-end appoinment_date">
                                    <h3 class="appointment_status_info">
                                        <?php
                                        if ($value['cancelled']) {
                                            echo '<span class="label label-danger">' . strtoupper(_l('appointment_cancelled')) . '</span>';
                                        } else if (
                                            ! $value['finished']
                                            && ! $value['cancelled']
                                            && ! $value['approved']
                                            && date('Y-m-d H:i', strtotime($value['date'] . ' ' . $value['start_hour'])) < date('Y-m-d H:i')
                                        ) {
                                            echo '<span class="label label-danger">' . strtoupper(_l('appointment_missed_label')) . '</span>';
                                        } else if (
                                            ! $value['finished']
                                            && ! $value['cancelled']
                                            && $value['approved'] == 1
                                        ) {
                                            echo '<span class="label label-info">' . strtoupper(_l('appointment_upcoming')) . '</span>';
                                        } else if (
                                            ! $value['finished']
                                            && ! $value['cancelled']
                                            && $value['approved'] == 0
                                        ) {
                                           // echo '<span class="label label-warning">' . strtoupper(_l('appointment_pending_approval')) . '</span>';
                                            if (
                                                $value['approved'] == 0
                                                && $value['cancelled'] == 0
                                                && is_admin() || $value['approved'] == 0
                                                && $value['cancelled'] == 0
                                                && staff_can('view', 'appointments')
                                            ) {
                                                echo '<a class="label label-info mleft5 approve_appointment_single" onClick="disableButtonsAfterDelete()" href="' . admin_url('appointly/appointments/approve?appointment_id=' . $value['id']) . '">' . _l('appointment_approve') . '</a>';
                                            }
                                        } else {
                                            echo '<span class="label label-success">' . strtoupper(_l('appointment_finished')) . '</span>';
                                        }
                                        ?>
                                    </h3>

                                      <?php
// Check if a prescription exists for the given appointment
$CI->db->where('appointment_id', $value['id']);
$check_prescription_exists = $CI->db->get(db_prefix() . 'appointment_prescriptions')->row();

// Always show the "Prescription" button
?>

<button class="btn btn-primary add_pre" data-toggle="tooltip" title="<?php echo _l('Add Prescription'); ?>" onclick="open_prescrip_modal(<?php echo $value['id'] ?>)" style="border-radius: 31px;font-size: 12px;padding: 5px 5px 6px 9px;text-align: end;margin-top: 8px;">
    <i class="fa fa-prescription" style="padding-right: 5px;"></i>
</button>

<?php
// Conditionally show the "Print" button if a prescription exists
if (!empty($check_prescription_exists)) { ?>
    <a href="<?php echo admin_url('appointly/appointments/print_prescription/' . $value['id'] . '?output_type=I') ?>" target="_blank" class="btn btn-success add_pre" data-toggle="tooltip" title="<?php echo _l('Print Prescription'); ?>" style="border-radius: 31px;font-size: 12px;padding: 5px 11px 5px 11px;text-align: end;margin-top: 8px;">
        <i class="fa-regular fa-file-pdf"></i>
    </a>
<?php } ?>

<button  class="btn btn-primary add_pre" data-toggle="tooltip" title="<?php echo _l('Add Treatment'); ?>"onclick="open_treatment_modal(<?php echo $value['id']?>)" style="    border-radius: 31px;font-size: 12px;padding: 6px 5px 4px 9px;text-align: end;margin-top: 8px;">
                                        <i class="fa fa-medkit" style="padding-right: 5px;"></i>
                                    </button>

                                </div>
                                
                            </div>
                            <div>
                           
                        </div>
                            <div class="row appoinment_dr_name">
                                  <?php if($staff_id != ''){?>
                                <!--<div class="col-xl-2 col-lg-2 apoinment_img">-->
                                <!--    <figure>-->
                                <!--         <img src="<?= staff_profile_image_url($staff_id, 'small'); ?>"-->
                                                         
                                <!--                         class="staff-profile-image-small mright5"-->
                                <!--                         data-original-title=""-->
                                <!--                        >-->
                                <!--    </figure>-->
                                    
                                <!--</div>-->
                                <?php } ?>
                                <div class="col-xl-7 col-lg-7 dr_name" style="<?php echo $value['firstname'] != '' ? 'padding' : 'padding: 0 0 0 0px;'?>">

                                <!--     <select class="form-control" name="staff" id="staff" onchange="update_assginee(this.value, <?php echo $value['id']?>)">-->
                                <!--          <?php if($value['firstname'] != ''){?>-->
                                <!--    <span>Dr.<?php echo $value['firstname'] . ' ' .$value['lastname'];?></span>-->
                                <!--    <?php } else { ?>-->
                                <!--     <span style="color: orange;">- Not Assigned</span>-->
                                <!--<?php }?> -->
                                <!--    <option>-Not Selected-</option>-->
                                <!--        <?php foreach ($staff as $key => $stf) {?>-->
                                <!--            <option value="<?php echo $stf['staffid'];?>" <?php echo $staff_id == $stf['staffid'] ? 'selected' : ''?>><?php echo $stf['firstname'].' '.$stf['lastname']?></option>-->
                                <!--        <?php }?>-->
                                <!--    </select>-->
                                <!--Patient Consent Model-->
                                <button class="btn btn-primary add_free_hand_dental" 
                                    onclick="openSignatureModal(<?php echo $value['id']; ?>, <?php echo $client->userid; ?>)"  
                                    style="border-radius: 31px; font-size: 12px; padding: 5px 5px 6px 9px; text-align: end; margin-top: 8px;">Patient Consent
                                </button>

                                <button class="btn btn-primary add_free_hand_dental"
                                    onclick="openNabhFormsModal()"
                                    style="border-radius: 31px; font-size: 12px; padding: 5px 5px 6px 9px; text-align: end; margin-top: 8px;">
                                    NABH FORMS
                                </button>

                                  
                                </div>

                                <!-- <div class="col-xl-2 col-lg-2 appo-div">-->
                                <!--    <?php if(empty($check_start_end_time) || $check_start_end_time->start_date_time == '0000-00-00 00:00:00'){?>-->
                                <!--    <button class="btn btn-success appo-btn" onclick="start_appointment(<?php echo $value['id']?>,<?php echo $staff_id;?>,'start')">Start</button>-->
                                <!--<?php }else if(!empty($check_start_end_time) && $check_start_end_time->end_date_time == '0000-00-00 00:00:00'){?>-->

                                <!--      <button class="btn btn-danger appo-btn" onclick="start_appointment(<?php echo $value['id']?>,<?php echo $staff_id;?>,'end')">End</button>-->

                                <!--<?php }else{?>-->

                                     <!-- <button class="btn btn-danger appo-btn">-</button> -->


                                <!--<?php }?>-->
                                <!--</div>-->

                                <div class="col-xl-2 col-lg-2 clock_icon">
                                    <img onclick="show_activitys_modal(<?php echo $value['id'];?>);"src="<?php echo base_url('assets/images/activity_log.png');?>">
                                </div>

                            </div>
                        </div>
                        
                        <?php }}else{?>

                                <p>No Data</p>
                              
                            <?php }?>
                       
                    </div>
                    <div class="row treatment_part">
                        <div class="row treatment_head">
                            <h2>Lab Work</h2>
                        </div>
                        <div class="row treatment_data appoinment_data_scroll">
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail" style="padding: 0 0 0 0;border: none;">
                                <div>
                                <?php 
                                if(!empty($lab_work_tasks)){

                                    foreach ($lab_work_tasks as $key => $lab_work_task) {?>

                                        <a href="<?php echo admin_url('tasks/view/' . $lab_work_task['id']);?>" target="_blank">
                                        <div class="row appoinment_data">
                                            <div class="row">
                                                <div class="col-xl-9 col-lg-8 col-md-7 apponment_detail">
                                                    <div>
                                                        <p class="app_date"><i class="fa-regular fa-calendar calendar-icon" aria-hidden="true"></i>
                <?php echo date("d/m/Y", strtotime($lab_work_task['startdate'])) . ' to ' .date("d/m/Y", strtotime($lab_work_task['duedate']));?></p>
                                                        <h3><?php echo $lab_work_task['name'];?></h3>
                                                        <p><?php echo $lab_work_task['description'];?></p>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-lg-4 text-end appoinment_date">
                                                    <h3 class="appointment_status_info" style="margin-top: 10px !important;">
                                                         <span class="tw-ml-5"><?php echo format_task_status($lab_work_task['status']);?></span>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    
                                    <?php }?>

                                <?php }else{
                                    echo "No Data";
                                };
                                ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 medical_part">
                    <div class="row text-center">
                        <h2 class="medical_head">Medical History</h2>
                    </div>
                    <div class="appoinment_data_scroll">
                        <div class="row ">
                            <div class="history_box">
                                <h3></h3>
                                <p id="medical_history_data"><?php
                                if(!empty($medical_history->medical_history)){
                                     $medical_diseases = explode(',', $medical_history->medical_history); 
                                        foreach ($medical_diseases as $medical_disease) {
                                            echo "<strong id='medical_disease'><i class='fa fa-circle text-danger-glow blink'></i>$medical_disease</strong>";
                                        }
                                } else { 
                                    echo "No Data";
                                };?></p>
                            </div>

                            <?php 
                                if(!empty($medical_history->occupation)){?>
                            <div class="history_box">
                                <h3>Occupation</h3>
                                <p><?php 
                                if(!empty($medical_history->occupation)){
                                    echo $medical_history->occupation; 
                                }else{
                                    echo "No Data";
                                };
                                ?></p>
                            </div>
                        <?php }?>
                        <?php
                                if(!empty($medical_history->marital_status)){ ?>
                            <div class="history_box">
                                <h3>Marital Status</h3>
                                <p><?php
                                if(!empty($medical_history->marital_status)){ 
                                    echo $medical_history->marital_status;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                        <?php }?>
                            <?php
                                if(!empty($medical_history->chief_complaint)){ ?>
                            <div class="history_box">
                                <h3>Chief Complaint</h3>
                                <p><?php
                                if(!empty($medical_history->chief_complaint)){ 
                                    echo $medical_history->chief_complaint;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                        <?php }?>

                        <?php
                                if(!empty($medical_history->allergies)){ ?>
                            
                            <div class="history_box">
                                <h3>Allergies</h3>
                                <p><?php
                                if(!empty($medical_history->allergies)){ 
                                    echo $medical_history->allergies;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                              <?php }?>

                              <?php
                                if(!empty($medical_history->surgical_history)){ ?>

                            <div class="history_box">
                                <h3>Surgical History</h3>
                                <p><?php
                                if(!empty($medical_history->surgical_history)){ 
                                    echo $medical_history->surgical_history;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <?php }?>

                            <?php
                                if(!empty($medical_history->medication)){ ?>

                            <div class="history_box">
                                <h3>Medication</h3>
                                <p><?php
                                if(!empty($medical_history->medication)){ 
                                    echo $medical_history->medication;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                               <?php }?>

                               <?php
                                if(!empty($medical_history->disease)){ ?>

                            <div class="history_box">
                                <h3>Patient taking drug for systemmetic disease</h3>
                                <p><?php
                                if(!empty($medical_history->disease)){ 
                                    echo $medical_history->disease;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                            <?php }?>

                            <?php
                                if(!empty($medical_history->dental_history)){ ?>

                            <div class="history_box">
                                <h3>Previous dental history</h3>
                                <p><?php
                                if(!empty($medical_history->dental_history)){ 
                                    echo $medical_history->dental_history;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>


                            <?php }?>

                            <?php
                                if(!empty($medical_history->clinical_findings)){ ?>

                            <div class="history_box">
                                <h3>Clinical Findings</h3>
                                <p><?php
                                if(!empty($medical_history->clinical_findings)){ 
                                    echo $medical_history->clinical_findings;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                                <?php }?>

                                <?php
                                if(!empty($medical_history->diagnosis)){ ?>

                            <div class="history_box">
                                <h3>Provisional Diagnosis</h3>
                                <p><?php
                                if(!empty($medical_history->diagnosis)){ 
                                    echo $medical_history->diagnosis;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                            <?php }?>

                            <?php
                                if(!empty($medical_history->previous_medication)){ ?>


                            <div class="history_box">
                                <h3>Previous Medication</h3>
                                <p><?php
                                if(!empty($medical_history->previous_medication)){ 
                                    echo $medical_history->previous_medication;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                               <?php }?>

                               <?php
                                if(!empty($medical_history->current_medication)){ ?>

                            <div class="history_box">
                                <h3>Current Medication</h3>
                                <p><?php
                                if(!empty($medical_history->current_medication)){ 
                                    echo $medical_history->current_medication;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                              <?php }?>

                              <?php
                                if(!empty($medical_history->tobaco_past)){ ?>

                            <div class="history_box">
                                <h3>Tobacco Consumption (Past)</h3>
                                <p><?php
                                if(!empty($medical_history->tobaco_past)){ 
                                    echo $medical_history->tobaco_past;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                                <?php }?>

                                <?php
                                if(!empty($medical_history->tobaco_present)){?>


                            <div class="history_box">
                                <h3>Tobacco Consumption (Present)</h3>
                                <p><?php
                                if(!empty($medical_history->tobaco_present)){ 
                                    echo $medical_history->tobaco_present;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <?php }?>

                            <?php
                                if(!empty($medical_history->alcohol_past)){?>

                            <div class="history_box">
                                <h3>Alcohol Consumption (Past)</h3>
                                <p><?php
                                if(!empty($medical_history->alcohol_past)){ 
                                    echo $medical_history->alcohol_past;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                              <?php }?>

                              <?php
                                if(!empty($medical_history->alcohol_present)){ ?>

                            <div class="history_box">
                                <h3>Alcohol Consumption (Present)</h3>
                                <p><?php
                                if(!empty($medical_history->alcohol_present)){ 
                                    echo $medical_history->alcohol_present;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                              <?php }?>
                              <?php
                                if(!empty($medical_history->enviro_factors)){ ?>

                            <div class="history_box">
                                <h3>Occupational Hazards and Environmental Factors</h3>
                                <p><?php
                                if(!empty($medical_history->enviro_factors)){ 
                                    echo $medical_history->enviro_factors;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                              <?php }?>
                              <?php
                                if(!empty($medical_history->risk_factors)){ ?>
                            <div class="history_box">
                                <h3>Other Risk Factors</h3>
                                <p><?php
                                if(!empty($medical_history->risk_factors)){ 
                                    echo $medical_history->risk_factors;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                             <?php }?>

                             <?php
                                if(!empty($medical_history->treatment_plan)){ ?>

                            <div class="history_box">
                                <h3>Treatment Plan</h3>
                                <p><?php
                                if(!empty($medical_history->treatment_plan)){ 
                                    echo $medical_history->treatment_plan;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                             <?php }?>

                             <?php
                                if(!empty($medical_history->history_comment)){ ?>

                            <div class="history_box">
                                <h3>Comment</h3>
                                <p><?php
                                if(!empty($medical_history->history_comment)){ 
                                    echo $medical_history->history_comment;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>

                               <?php }?>
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="row second_row">
                <div class="col-xl-4 col-lg-4 card-body"></div>
                <div class="col-xl-4 col-lg-4 card-body"></div>
                <div class="col-xl-4 col-lg-4 card-body"></div>
            </div>

            <div class="row second_three">
                <div class="col-xl-4 col-lg-4 card-body"></div>
                <div class="col-xl-4 col-lg-4 card-body"></div>
                <div class="col-xl-4 col-lg-4 card-body"></div>
            </div> -->
        </div>

        <!-- model start -->
        <!-- NABH List Modal -->
<div class="modal fade" id="nabhListModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">NABH Forms</h4>

        <div style="display:flex; gap:10px; align-items:center;">
          <select id="nabhLang" class="form-control" style="width:160px;">
            <option value="gu">Gujarati</option>
            <option value="en">English</option>
          </select>

          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="nabhTable">
            <thead>
              <tr>
                <th style="width:70px;">#</th>
                <th>Form Name</th>
                <th style="width:140px;">Available</th>
                <th style="width:120px;">Action</th>
              </tr>
            </thead>
            <tbody id="nabhTbody">
              <tr><td colspan="4">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="nabhPdfModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document" style="width:95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="nabhPdfTitle">View PDF</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>

      <div class="modal-body" style="height:80vh;">
        <iframe id="nabhPdfFrame" src="about:blank" style="width:100%; height:100%; border:0;"></iframe>
      </div>
    </div>
  </div>
</div>

        <!-- model end -->
    </section>
    <?php $this->load->view('appointly/patient_consent'); ?>
    <?php $this->load->view('admin/clients/modals/xray'); ?>
    <?php $this->load->view('admin/clients/modals/treatment_activity'); ?>
    <?php $this->load->view('admin/clients/modals/add_predcription'); ?>
    <?php $this->load->view('admin/clients/modals/add_treatment'); ?>
    
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('paste', function(event) {
        var items = (event.clipboardData || event.originalEvent.clipboardData).items;
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item.type.indexOf("image") !== -1) {
                var file = item.getAsFile();
                showLoader();
                uploadFileWithAjax(file);
            }
        }
    });
});
function showLoader() {
    var loader = document.getElementById('loader');
    loader.style.display = 'block';
    document.getElementById('patient_profileSection').classList.add('blur');
}
function hideLoader() {
    var loader = document.getElementById('loader');
    loader.style.display = 'none';
    document.getElementById('patient_profileSection').classList.remove('blur');
}
function uploadFileWithAjax(file) {
    var formData = new FormData();
    formData.append('file', file);
    formData.append('xray_upload', 'image'); 
    var clientId = getClientIdFromUrl();
    var url = '<?= admin_url('clients/upload_attachment'); ?>/' + clientId;
    formData.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
    $.ajax({
        url: url,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'json', 
        success: function(response) {
            hideLoader();
            if (response.status === 'success') {
                var filename = response.filename;
                var imageUrl = '<?= base_url("uploads/clients/"); ?>' + clientId + '/' + filename;
                var newImageBlock = `
                    <div class="col-lg-4 xray_img">
                        <a href="${imageUrl}" target="_blank">
                            <figure>
                                <img src="${imageUrl}" alt="Uploaded Image">
                            </figure>
                        </a>
                    </div>`;
    
                $('.row.xray_images.appoinment_data_scroll').append(newImageBlock);
                alert(response.message);
            } 
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideLoader();
            console.error('File upload failed', textStatus, errorThrown);
        }
    });
}
function getClientIdFromUrl() {
    var pathname = window.location.pathname;
    var segments = pathname.split('/');
    var clientIndex = segments.indexOf('client');
    var clientId = null;
    if (clientIndex !== -1 && clientIndex + 1 < segments.length) {
        clientId = segments[clientIndex + 1];
    }
    return clientId;
}
</script>

<!-- nabh js -->
<script>
function openNabhFormsModal() {
  $('#nabhListModal').modal('show');
  loadNabhList();
}

$('#nabhLang').on('change', function () {
  loadNabhList();
});

function loadNabhList() {
  const lang = $('#nabhLang').val();

  // CSRF (Perfex/CI)
  const csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
  const csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

  $('#nabhTbody').html('<tr><td colspan="4">Loading...</td></tr>');

  $.ajax({
    url: admin_url + 'nabh/list-json',
    type: 'POST',
    dataType: 'json',
    data: { [csrfName]: csrfHash },
    success: function(res) {
      if (!res || !res.status) {
        $('#nabhTbody').html('<tr><td colspan="4">No data found</td></tr>');
        return;
      }

      const rows = res.data || [];
      if (!rows.length) {
        $('#nabhTbody').html('<tr><td colspan="4">No forms found</td></tr>');
        return;
      }

      let html = '';
      rows.forEach((r, i) => {
        const hasEn = !!r.has_en;
        const hasGu = !!r.has_gu;

        // Title rule:
        // If selected lang file exists => show that title
        // else show other language title (fallback)
        let title = '-';
        if (lang === 'gu') title = hasGu ? (r.title_gu || r.title_en) : (r.title_en || r.title_gu);
        else title = hasEn ? (r.title_en || r.title_gu) : (r.title_gu || r.title_en);

        // Availability label
        let avail = '';
        if (hasEn && hasGu) avail = 'EN + GU';
        else if (hasEn) avail = 'EN';
        else if (hasGu) avail = 'GU';
        else avail = 'Not Uploaded';

        // View: requested language; controller will fallback automatically if missing
        //const viewUrl = admin_url + 'nabh/view/' + r.pdf_id + '?lang=' + lang;
        const viewUrl = admin_url + 'nabh/view-html/' + r.pdf_id + '?lang=' + lang;

        const disabled = (!hasEn && !hasGu) ? 'disabled' : '';

        html += `
          <tr>
            <td>${i+1}</td>
            <td>${escapeHtml(title)}</td>
            <td>${escapeHtml(avail)}</td>
            <td>
              <button class="btn btn-sm btn-primary" ${disabled}
                onclick="openNabhPdf('${viewUrl}', '${escapeHtml(title)}')">
                View
              </button>
            </td>
          </tr>
        `;
      });

      $('#nabhTbody').html(html);
    },
    error: function() {
      $('#nabhTbody').html('<tr><td colspan="4">Error loading forms</td></tr>');
    }
  });
}

function openNabhPdf(url, title) {
  $('#nabhPdfTitle').text(title || 'View PDF');
  $('#nabhPdfFrame').attr('src', url);
  $('#nabhPdfModal').modal('show');
}

// optional: clear iframe when closing
$('#nabhPdfModal').on('hidden.bs.modal', function(){
  $('#nabhPdfFrame').attr('src', 'about:blank');
});

// basic html escape
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
</script>
