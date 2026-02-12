<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget relative" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('quick_stats'); ?>">
   <?php if(is_admin()){?>
    <div class=""></div>
    <div class="row">
        <?php
         $initial_column = 'col-lg-3';
         if (!is_staff_member() && ((staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices') && (get_option('allow_staff_view_invoices_assigned') == 0
           || (get_option('allow_staff_view_invoices_assigned') == 1 && !staff_has_assigned_invoices()))))) {
             $initial_column = 'col-lg-6';
         } elseif (!is_staff_member() || (staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices') && (get_option('allow_staff_view_invoices_assigned') == 1 && !staff_has_assigned_invoices()) || (get_option('allow_staff_view_invoices_assigned') == 0 && (staff_cant('view', 'invoices') && staff_cant('view_own', 'invoices'))))) {
             $initial_column = 'col-lg-4';
         }
      ?>
       <div class="quick-stats-projects col-xs-12 col-md-12 col-sm-12 col-lg-12 tw-mb-2 sm:tw-mb-0" style="margin-top: -20px;" >

       <div class="row">

         <form action="" method="get">

       <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 col-12 form-group blood-group contact-direction-option">
                            <label for="blood-group"> Select Period</label>
                            <div class="dropdown bootstrap-select bs3" style="width: 100%;">
                          
                            <select class="selectpicker" data-none-selected-text="" data-width="100%" name="date_range" id="date_range">  
                                    <option value="">Nothing Selected</option>
                                    <option value="today" <?php echo $_GET['date_range'] == 'today' ? 'selected' : '';?>>Today</option>
                                    <option value="this_week" <?php echo $_GET['date_range'] == 'this_week' ? 'selected' : '';?>>This Week</option>
                                    <option value="this_month" <?php echo $_GET['date_range'] == 'this_month' ? 'selected' : '';?>>This Month</option>
                                </select>
                           
                            </div>
                        </div> 
                         <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12 uid">
                        <label for="rx-str-date" class="control-label">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo $_GET['from_date'] ?? '';?>">
                    </div>
                     <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12 uid">
                        <label for="rx-str-date" class="control-label">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo $_GET['to_date'] ?? '';?>">
                    </div>
                    <div class="col-xl-1 col-lg-1 col-md-1 col-sm-12 col-12 uid" style="padding-right: 0px;">
                       
                      <button class="btn btn-primary only-save customer-form-submiter" style="margin-top:22px;">
                            <?php echo _l('Filter'); ?>
                        </button>
                    </div>
                     <div class="col-xl-1 col-lg-1 col-md-1 col-sm-12 col-12 uid" style="padding-left: 0px;">
                       
                     <a href="<?php echo admin_url();?>" class="btn btn-primary" style="margin-top:22px;">
                            <?php echo _l('Reset'); ?>
                        </a>
                    </div>
                     </div>
                  </form>

       
       </div>
       <h4 style="margin-left: 17px;">Appointments Statistics</h4>
       <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0">
         <a href="<?php echo admin_url('appointly/appointments');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate">Total</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?php echo total_rows(db_prefix() . 'appointly_appointments',$sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
   </a>
</div>
 <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0">
   <div class="top_stats_wrapper">
         <a href="<?php echo admin_url('appointly/appointments?status=approved');?>">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate" style="color:blue;">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:blue;">Approved</span>                     
         </div>
         <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:blue;"><?php echo total_rows(db_prefix() . 'appointly_appointments','approved = 1' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>
 <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0">
   <a href="<?php echo admin_url('appointly/appointments?status=pending');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate" style="color:orange;">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:orange;">Pending Approval</span>                     
         </div>
         <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:orange;"><?php echo total_rows(db_prefix() . 'appointly_appointments','approved = 0' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>
 <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0" >
   <a href="<?php echo admin_url('appointly/appointments?status=cancelled');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate" style="color:red;">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:red;">Cancelled</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:red;"><?php echo total_rows(db_prefix() . 'appointly_appointments','cancelled = 1' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>
 <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0" style="margin-top: 20px;">
   <a href="<?php echo admin_url('appointly/appointments?status=finished');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between" style="color:green;">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:green;">Finished</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:green;"><?php echo total_rows(db_prefix() . 'appointly_appointments','finished = 1' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>

<div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0"style="margin-top: 20px;">
   <a href="<?php echo admin_url('appointly/appointments?status=upcoming');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between" style="color:blue;">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:blue;">Upcoming</span>                     
         </div>
            <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:blue;"><?php echo total_rows(db_prefix() . 'appointly_appointments','date > CURDATE()' .$sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>

<div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0"style="margin-top: 20px;">
   <a href="<?php echo admin_url('appointly/appointments?status=missed');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between" style="color:#f4032f96;">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate" style="color:#f4032f96;">Missed</span>                     
         </div>
           <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" style="color:#f4032f96;"><?php echo total_rows(db_prefix() . 'appointly_appointments','finished != 1 AND date < CURDATE()' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>

<div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0"style="margin-top: 20px;">
   <a href="<?php echo admin_url('appointly/appointments?status=recurring');?>">
   <div class="top_stats_wrapper">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between" >
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-calendar" style="margin-right: 10px;"></i>
            <span class="tw-truncate">Repeat</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? " AND date BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0" ><?php echo total_rows(db_prefix() . 'appointly_appointments','recurring = 1' . $sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>

<div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0">
    <a href="<?php echo admin_url('clients');?>">
   <div class="top_stats_wrapper" style="margin-top: 20px;">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-user" style="margin-right: 10px;"></i>
            <span class="tw-truncate">Total Patients</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? "DATE(datecreated) BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?php echo total_rows(db_prefix() . 'clients',$sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
</a>
</div>

<div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 col-lg-3 tw-mb-2 sm:tw-mb-0">
    <a href="<?php echo admin_url('leads');?>">
   <div class="top_stats_wrapper" style="margin-top: 20px;">
      <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
         <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500 tw-truncate">
           <i class="fa fa-tty" style="margin-right: 10px;"></i>
            <span class="tw-truncate">Total Leads</span>                     
         </div>
          <?php $sql = ($_GET['from_date'] != '' && $_GET['to_date'] != '') ? "DATE(dateadded) BETWEEN '" . $_GET['from_date'] ."' AND '". $_GET['to_date'] ."'": '';?>
         <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?php echo total_rows(db_prefix() . 'leads',$sql)?></span>                 
      </div>
      <!-- <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
         <div class="progress-bar no-percent-text not-dynamic" style="background: rgb(37, 99, 235); width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-percent="0">                     </div>
      </div> -->
   </div>
   </a>
</div>

    </div>
 <?php }?>
</div>

