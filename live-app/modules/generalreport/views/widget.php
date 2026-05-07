<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$membershipplan = [];
if (is_admin()) {
   $this->load->model('membershipplan/Membershipplan_model');
   $membershipplan = $this->Membershipplan_model->get_all_membershipplan();
}
?>
<div class="widget<?php if(count($membershipplan) == 0 || !is_staff_member()){echo ' hide';} ?>" id="widget-<?php echo create_widget_id('membershipplan'); ?>">
   <?php if(is_staff_member()){ ?>
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body padding-10">
                  <div class="widget-dragger"></div>
                  <p class="padding-5">
                     <?php echo _l('membershipplan'); ?>
                  </p>
                  <hr class="hr-panel-heading-dashboard">
                  <?php foreach($membershipplan as $membership){
                     ?>
                     <div class="purpose padding-5 no-padding-top">
                        <h4 class="pull-left font-medium no-mtop">
                           <?php echo $membership['membershipplan']; ?>
                           <br />
                           <small><?php echo $membership['name']; ?></small>
                        </h4>
                        <div class="clearfix"></div>
                     </div>
                  <?php } ?>
               </div>
            </div>
         </div>
      </div>
   <?php } ?>
</div>
