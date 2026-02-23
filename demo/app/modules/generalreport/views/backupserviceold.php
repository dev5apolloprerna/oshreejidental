<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="service_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
       <?php echo form_open($this->uri->uri_string(), array('autocomplete'=>'off') ); ?>
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalLabel"><?php echo $title;?></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -28px;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <ul style="list-style: none;">
            <div class="col-12 col-sm-12" style="
    margin-bottom: 29px;
">
            <div class="col-2 col-sm-2" style="margin-top:8px;">
                    <b style="font-size:15px;">Date</b>
                 </div>
                   <div class="col-4 col-sm-4" style="margin-top:8px;">
                     <b style="font-size:15px;">Assign Mechanic </b>
                 </div>
                   <div class="col-2 col-sm-2" style="margin-top:8px;">
                    <b style="font-size:15px;"> Status </b>
                 </div>
                 <div class="col-2 col-sm-2" style="margin-top:8px;">
                    <b style="font-size:15px;"> Extra Charge </b>
                 </div>
                 <div class="col-2 col-sm-2" style="margin-top:8px;">
                    <b style="font-size:15px;"> Remark </b>
                 </div>
                 </div>
          <?php 
                  $date=date('Y-m-d');
                  $get_staffs  = $this->staff_model->getstaff();
                  
                   
                  if(isset($services))
                  {
                    foreach ($services as  $servicedetails) {
                    $customer_service_schedule_id=explode(',',$servicedetails['customer_service_schedule_id']);
                    $confirmed_by_admin=explode(',',$servicedetails['confirmed_by_admin']);

                    $staff_id = explode(',', $servicedetails['staff_id']);
                    
                    if($servicedetails['customer_prefered_datetime'] != '' && $servicedetails['customer_prefered_datetime'] != '0000-00-00'){
                        $service_dt = $servicedetails['customer_prefered_datetime'];
                    }else{
                        $service_dt = $servicedetails['service_assign_date_staff'];
                    }
                    
                     //$servicesdetails=explode(',', $servicedetails['service_assign_date_staff']);
                     
                    

                     //foreach ($servicesdetails as $key => $value) {
                    
                         $schedule= (strtotime($service_dt) < strtotime($date)) || ($servicedetails['end_time'] != '' && $servicedetails['end_time'] != '0000-00-00') ? '<div class="col-12 col-sm-12"><div class="col-2 col-sm-2" style="margin-top:8px;"><li style="font-weight: 500;"><del>'.date('d-M-Y',strtotime($service_dt)).'</li></del></div>' : '<div class="col-12 col-sm-12"><div class="col-2 col-sm-2" style="margin-top:8px;"><li style="font-weight: 500;">'.date('d-M-Y',strtotime($service_dt)).'</li></div>';
                        echo $schedule;  
                        
                        $drop_disabled = (strtotime($service_dt) < strtotime($date)) || ($servicedetails['end_time'] != '' && $servicedetails['end_time'] != '0000-00-00') ? 'disabled' : '';
                      ?>
                      
                      <div class="col-4 col-sm-4">
                          

                        <select name="staff_name[<?php echo $servicedetails['service_schedule_id'];?>]" id="service_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true" <?php echo $drop_disabled;?>>
                        <option value="" selected>Select Mechanic</option>

                      <?php 
                     
                      foreach($get_staffs as $staffs) 
                      {
                       
                        ?>

                       <option value="<?php echo $staffs['staffid']; ?>" 
                        <?=(($staffs['staffid'] == $servicedetails['staff_id'])) ? 'selected':''; ?>>
                          <?php echo $staffs['firstname'].' '.$staffs['lastname']; ?> 
                       </option>
                       <?php 
                      }
                  ?>     
                  </select><br><br>
                </div>
                
                 <div class="col-2 col-sm-2" style="margin-top:8px;">
                    <?php
                    
                    $status = '<p style="font-weight: 500;">Pending</p>';
                    
                    if($servicedetails['staff_id'] != '0'){
                        $status = '<p style="font-weight: 500;color: blue;">Assigned</p>';
                    }
                    
                    if($servicedetails['start_time'] != '' && $servicedetails['start_time'] != '0000-00-00'){
                        $status = '<p style="font-weight: 500;color: orange;">Inprogress</p>';
                    }
                    if($servicedetails['end_time'] != '' && $servicedetails['end_time'] != '0000-00-00'){
                        $status = '<p style="font-weight: 500;color: green;">Completed</p>';
                    }
                    
                    echo $status;
                    
                    ?>
                 </div>
                 <div class="col-2 col-sm-2" style="margin-top:8px;">
                     <?php echo $servicedetails['extra_amount'];?>
                 </div>
                 <div class="col-2 col-sm-2" style="margin-top:8px;">
                     <?php echo $servicedetails['remark'];?>
                 </div>
                 
                 
                     
                 </div>
               
            <?php
            //}
            }
          }
          ?>
        </ul>
        </div>
        <!--<div class="row">-->
        <!--  <div class="col-4 col-sm-4">-->
        <!--    <h5>Complete</h5>-->
        <!--  </div>-->
        <!--  <div class="col-5 col-sm-5">-->
        <!--    <h5>Complete Status</h5>-->
        <!--  </div>-->
        <!--  <div class="col-3 col-sm-3">-->
        <!--    <h5>Upcoming</h5>-->
        <!--  </div>-->
        <!--</div>-->
          
        <!--<div class="row">-->
        <!--        <ul style="list-style: none;">-->
                <?php
                // foreach ($completed_service as $key => $value) {
                  
                //     $complete = '<div class="col-4 col-sm-4"><li>'.date('d-M-Y',strtotime($value['service_assign_date_staff'])).'</li></div>';
                //     echo $complete;
                         
                //   if($value['customer_service_schedule_id'] != '')
                //   {
                //     $checked = '';
                //     if ($value['confirmed_by_admin'] == 1) {
                //         $checked = 'checked';
                //     }
                  ?>
                  <!--<div class="col-5 col-sm-5">-->
                  <!--  <div class="onoffswitch">-->
                  <!--    <input type="checkbox" name="onoffswitch" onclick="confirm_status(<?php echo $value['customer_service_schedule_id'];?>);" class="onoffswitch-checkbox" id="c_<?php echo $value['customer_service_schedule_id'];?>" data-id="" <?php echo $checked; ?>  >-->
                  <!--    <label class="onoffswitch-label" for="c_<?php echo $value['customer_service_schedule_id'];?>" ></label>-->
                  <!--</div>-->
                  <!--</div>-->
                <?php
                //} 
                ?>
                <!--<div class="col-3 col-sm-3">              -->
                <?php
                // foreach ($upcoming as $key => $values) 
                // {        
                //     $upcoming='<li>'.date('d-M-Y',strtotime($values['service_assign_date_staff'])).'</li>';
                //     echo $upcoming;
                // } 
              ?>                
              <!--</div>-->
              <?php
              //}
              ?>
              
              
              
              
      <!--        </ul>-->
      <!--  </div>-->
      <!--</div>-->
      <div class="modal-footer">
       <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
        <button type="submit" class="btn btn-info" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="#schedules-form"><?php echo _l('submit'); ?></button> 
      </div>
       <?php echo form_close(); ?>

    </div>

  </div>
</div>              
<script>
  $(".selectpicker").selectpicker();
</script>

 
                    
                