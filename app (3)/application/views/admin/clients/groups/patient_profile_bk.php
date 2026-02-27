<?php

$CI = &get_instance();

$CI->db->select(db_prefix() . 'appointly_appointments.*,'.db_prefix().'staff.staffid,'.db_prefix().'staff.firstname,'.db_prefix().'staff.lastname,'.db_prefix() . 'staff.profile_image');
$CI->db->where('contact_id', $contact->id);
$CI->db->join(db_prefix() . 'appointly_attendees',db_prefix() . 'appointly_attendees.appointment_id = ' . db_prefix() . 'appointly_appointments.id','left');
$CI->db->join(db_prefix() . 'staff',db_prefix() . 'staff.staffid = ' . db_prefix() . 'appointly_attendees.staff_id','left');
$appointments = $CI->db->get(db_prefix() . 'appointly_appointments')->result_array();

// echo '<pre>';
// print_r($appointments);exit;



?>
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
    width: 23.5%;
    padding: 0 0 0 0;
}
.profile_part {
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
    padding: 0 0 0 10px;
}
.person_detail .p_name h2 {
    margin: 0;
    color: rgb(71 85 105);
    font-size: 15px;
    text-transform: capitalize;
}
.person_detail .p_id p {
    font-size: 13px;
    line-height: 30px;
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
    color: rgb(71 85 105);
    font-weight: 500;
    font-size: 14px;
    text-transform: capitalize
}
.profile_detail .data {
    font-size: 13px;
    color: rgb(71 85 105);
    padding: 0 0 0 8px;
    line-height: 20px;
}
.profile_part .profile_one {
    margin: 0 0 0 0;
}
.x_ray_part {
    padding: 20px 15px 20px 15px;
    border-radius: 9px;
    background-color: #fff;
    margin: 20px 15px 0 0;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000;
}
.x_ray_part .xray_head {
    margin: 0 0 0 0;
}
.x_ray_part .xray_head h2 {
    font-size: 15px;
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
    padding: 5px 50px 5px 50px;
    background-color: #2563eb;
    color: #fff;
    text-transform: uppercase;
    border: none;
    border-radius: 7px;
}

/* -*-*-*-*-*-*-*-*-*-* Medical Part Start *-*-*-*-*-*-*-*-*-*- */
.medical_part {
    height: 540px;
    background-color: #fff;
    border-radius: 7px;
    padding: 20px 10px 20px 20px;
    width: 36.6%;
    margin: 0 0 0 20px;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
    color: #000;
}
.medical_part .medical_head {
    margin: 0 0 15px 15px;
    font-size: 15px;
    color: rgb(71 85 105);
    text-align: left;
}
.history_box {
    /* background-color: #e0e9fa; */
    height: auto;
    width: 100%;
    margin: 0px 10px 15px 0;
    border: 2px solid #f2eff2;
    padding: 15px 15px 5px 15px;
    border-radius: 10px;
    border-radius: 7px;
}

.medical_part .history_box h3 {
    font-size: 15px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.medical_part .history_box p {
    font-size: 13px;
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
    height: 245px;
    background-color: #fff;
    border-radius: 7px;
    padding: 20px 10px 20px 20px;
    width: 37%;
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
    height: 175px;
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
    font-size: 15px;
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
    font-size: 15px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.appoinment_part .apponment_detail p {
    font-size: 13px;
    margin: 10px 0 10px 0;
    line-height: 16px;
    color: rgb(71 85 105);
}
.appoinment_part .dr_name {
    padding: 7px 0 0 25px;
}
.appoinment_part .dr_name span {
    padding: 0 0 0 0px;
    font-size: 13px;
    color: #475569;
}
.appoinment_part .appoinment_data .appoinment_date {
    padding: 0px 0 0 0;
    font-size: 13px;
    color: #b8b2b3;
    float: right;
    text-align: right;
}
.appoinment_data {
    padding: 15px 30px 15px 30px;
    border: 2px solid #f2eff2;
    border-radius: 10px;
    margin: 0px 10px 15px 0px;
}
.treatment_part {
    width: 106%;
    height: 275px;
    padding: 20px 20px;
    border-radius: 9px;
    background-color: #fff;
    margin: 45px 15px 0 -20px;
    transform: translateY(-0.4rem);
    box-shadow: 0 0.25rem 1.25rem rgba(200,200,200,0.9);
}
.treatment_part .treatment_head h2 {
    font-size: 15px;
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
    font-size: 15px;
    margin: 0 0 0px 0;
    text-transform: capitalize;
    font-weight: 600;
    color: rgb(71 85 105);
}
.treatment_part .treatment_data .treatment_detail p {
    font-size: 13px;
    text-align: justify;
    margin: 10px 0 10px 0;
    line-height: 16px;
    color: rgb(71 85 105);
}

 .label-info {
  background: #2663eb;
  border: 1px solid #2663eb;
  text-transform: uppercase !important;
  color: #ffffff !important;
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
            <div class="row top_section">
                <div class="col-xl-4 col-lg-4 profile_section">
                    <div class="row profile_part">
                        <div class="row profile_top">
                            <div class="col-lg-3 person_img">
                                <figure>
                                    <img src="<?php echo base_url('assets/images/user.jpg');?>" alt="Person Image">
                                </figure>
                            </div>
                            <div class="col-lg-9 person_detail">
                                <div class="p_name">
                                    <h2><?php echo $client->company;?></h2>
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
                                   <!--  <tr>
                                        <td class="header">Current Date:</td>
                                        <td class="data"><?php echo $contact->datecreated;?></td>
                                    </tr> -->
                                    <tr>
                                        <td class="header">Current RX Start Date:</td>
                                        <td class="data"><?php echo $contact->rx_str_date;?></td>
                                    </tr>
                                    <tr>
                                        <td class="header">Current RX End Date:</td>
                                        <td class="data"><?php echo $contact->rx_end_date;?></td>
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
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x1.png');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x1.png');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x2.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x2.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x3.jpg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x3.jpg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x4.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x4.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x5.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x5.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x2.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x2.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x3.jpg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x3.jpg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x4.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x4.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x5.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x5.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x6.jpg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x6.jpg');?>" alt="Person Image">
                                    </figure>
                                </a>                                
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x2.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x2.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>                    
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x4.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x4.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                            <div class="col-lg-4 xray_img">
                                <a href="<?php echo base_url('assets/images/x4.jpeg');?>" target="_blank">
                                    <figure>
                                        <img src="<?php echo base_url('assets/images/x4.jpeg');?>" alt="Person Image">
                                    </figure>
                                </a>
                            </div>
                        </div>
                        <div class="row upload_btn">
                            <button class="bg-primary">Upload</button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 appoinment_part">
                    <div class="row text-center">
                        <h2 class="appoinment_head">Appoinment</h2>
                    </div>
                    <div class="appoinment appoinment_data_scroll">
                        <?php foreach ($appointments as $key => $value) {?>

                        <div class="row appoinment_data">
                            <div class="row">
                                <div class="col-xl-9 col-lg-8 apponment_detail">
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
                                            echo '<span class="label label-warning">' . strtoupper(_l('appointment_pending_approval')) . '</span>';
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
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-1 col-lg-1 apoinment_img">
                                    <figure>
                                         <img src="<?= staff_profile_image_url($value['staffid'], 'small'); ?>"
                                                         data-toggle="tooltip"
                                                         data-title="<?= $value['firstname'] . ' ' . $value['lastname']; ?>"
                                                         class="staff-profile-image-small mright5"
                                                         data-original-title=""
                                                         title="<?= $value['firstname'] . ' ' . $value['lastname'] ?>">
                                    </figure>
                                </div>
                                <div class="col-xl-9 col-lg-9 dr_name">
                                    <span>Dr.<?php echo $value['firstname'] . ' ' .$value['lastname'];?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php }?>
                       
                    </div>
                    <div class="row treatment_part">
                        <div class="row treatment_head">
                            <h2>Curent Treatment</h2>
                        </div>
                        <div class="row treatment_data appoinment_data_scroll">
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail">
                                <div>
                                    <h3>Lorem, ipsum.</h3>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ducimus.</p>
                                </div>
                            </div>
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail">
                                <div>
                                    <h3>Lorem, ipsum.</h3>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ducimus.</p>
                                </div>
                            </div>
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail">
                                <div>
                                    <h3>Lorem, ipsum.</h3>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ducimus.</p>
                                </div>
                            </div>
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail">
                                <div>
                                    <h3>Lorem, ipsum.</h3>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ducimus.</p>
                                </div>
                            </div>
                            <div class="col-xl-8 col-lg-8 history_box treatment_detail">
                                <div>
                                    <h3>Lorem, ipsum.</h3>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cum, ducimus.</p>
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
                                <h3>Occupation</h3>
                                <p><?php 
                                if(!empty($medical_history->occupation)){
                                    echo $medical_history->occupation; 
                                }else{
                                    echo "No Data";
                                };
                                ?></p>
                            </div>
                            <div class="history_box">
                                <h3>Marital Status</h3>
                                <p><?php
                                if(!empty($medical_history->marital_status)){ 
                                    echo $medical_history->marital_status;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Allergies</h3>
                                <p><?php
                                if(!empty($medical_history->allergies)){ 
                                    echo $medical_history->allergies;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Medication</h3>
                                <p><?php
                                if(!empty($medical_history->medication)){ 
                                    echo $medical_history->medication;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Medical History</h3>
                                <p><?php
                                if(!empty($medical_history->medical_history)){ 
                                    echo $medical_history->medical_history;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Surgical History</h3>
                                <p><?php
                                if(!empty($medical_history->surgical_history)){ 
                                    echo $medical_history->surgical_history;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Tobacco Consumption (Past)</h3>
                                <p><?php
                                if(!empty($medical_history->tobaco_past)){ 
                                    echo $medical_history->tobaco_past;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Tobacco Consumption (Present)</h3>
                                <p><?php
                                if(!empty($medical_history->tobaco_present)){ 
                                    echo $medical_history->tobaco_present;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Alcohol Consumption (Past)</h3>
                                <p><?php
                                if(!empty($medical_history->alcohol_past)){ 
                                    echo $medical_history->alcohol_past;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Alcohol Consumption (Present)</h3>
                                <p><?php
                                if(!empty($medical_history->alcohol_present)){ 
                                    echo $medical_history->alcohol_present;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Occupational Hazards and Environmental Factors</h3>
                                <p><?php
                                if(!empty($medical_history->enviro_factors)){ 
                                    echo $medical_history->enviro_factors;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Other Risk Factors</h3>
                                <p><?php
                                if(!empty($medical_history->risk_factors)){ 
                                    echo $medical_history->risk_factors;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            <div class="history_box">
                                <h3>Comment</h3>
                                <p><?php
                                if(!empty($medical_history->history_comment)){ 
                                    echo $medical_history->history_comment;
                                 }else{ 
                                    echo "No Data";
                                };?></p>
                            </div>
                            
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
    </section>
