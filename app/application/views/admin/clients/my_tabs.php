<style>
.col-md-3 {
    width: 100%;
}

ul.clearfix.nav.navbar-pills.navbar-pills-flat.nav-tabs.nav-stacked.customer-tabs {
    display: flex;
    flex-direction: row;
}


ul.clearfix.nav.navbar-pills.navbar-pills-flat.nav-tabs.nav-stacked.customer-tabs li {
    width: 100%; 
}
ul#slider-menu {
    overflow-x: auto;
    overflow-y: hidden;
}

ul#slider-menu::-webkit-scrollbar {
    width: 3px;
    height: 3px;
}
ul#slider-menu::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
ul#slider-menu::-webkit-scrollbar-thumb {
    background: #bacdf1; 
    border-radius: 0px; 
}
ul#slider-menu::-webkit-scrollbar-thumb:hover {
    background: #bacdf1; 
}
ul#slider-menu li a {
    width: max-content;
}
ul#slider-menu li {
    padding: 15px 5px;
}
ul#slider-menu li:hover {
    background-color: #f1f5f9;
}
ul#slider-menu li .edit_profile {
    display: flex;
}
ul#slider-menu li .edit_profile .edit_title {
    padding: 0 0 0 15px;
    margin: 0;
}
.profile_img {
    height: 16px;
    width: 16px;
}
#slider-menu .customer_tab_profile {
    display: flex;
}
#slider-menu .customer_tab_patient_profile {
    display: none;
}
.patient-profile-tab {
    margin-bottom: 0px;
}
.customer-profile-group-heading{
    display: none;
}
.navbar-pills-flat>li>a{
        padding: 5px 5px 5px 5px;
}
</style>
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="patient-profile-tab">
<ul id="slider-menu" class="clearfix nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist">
<!-- <ul class="clearfix nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist"> -->
  <?php
  
  foreach ($customer_tabs as $key => $tab) {
    if ($key === 'proposals' || $key === 'credit_notes' || $key === 'estimates' || $key === 'subscriptions' || $key === 'expenses' || $key === 'contracts' || $key === 'projects' || $key === 'tickets' || $key === 'vault' || $key === 'reminders' || $key === 'map' ) {
        unset($customer_tabs[$key]);
    }
}

  foreach(filter_client_visible_tabs($customer_tabs, $client->userid) as $key => $tab){

    if($key == 'profile'){
        $n_group = 'patient_profile';
    }else{
        $n_group = $key;
    }


      ?>
    <li class="<?php if($key == 'profile' || $n_group == 'patient_profile'){echo 'active ';} ?>customer_tab_<?php echo e($key); ?>">
      <a data-group="<?php echo e($key); ?>" href="<?php echo admin_url('clients/client/'.$client->userid.'?group='.$n_group); ?>">

      
      <?php if(!empty($tab['icon'])){ ?>
            <i class="<?php echo e($tab['icon']); ?> menu-icon" aria-hidden="true"></i>
        <?php } ?>
        <?php echo e($tab['name']); ?>
        <?php if (isset($tab['badge'], $tab['badge']['value']) && !empty($tab['badge'])) {?>
            <span class="badge pull-right 
            <?=isset($tab['badge']['type']) &&  $tab['badge']['type'] != '' ? "bg-{$tab['badge']['type']}" : 'bg-info' ?>"
              <?=(isset($tab['badge']['type']) &&  $tab['badge']['type'] == '') ||
                      isset($tab['badge']['color']) ? "style='background-color: {$tab['badge']['color']}'" : '' ?>>
              <?= e($tab['badge']['value']) ?>
          </span>
        <?php } ?>
      </a>

       <?php if($key == 'profile'){?>
        <a class="edit_profile" data-group="<?php echo e($key); ?>" href="<?php echo admin_url('clients/client/'.$client->userid.'?group='.$key); ?>">
            <img class="profile_img" src="<?php echo base_url('assets/images/edit_3.png');?>"> 
            <!-- <p class="edit_title">Edit Profile</p> -->
        </a>
      <?PHP }?>
    </li>
  <?php } ?>
  
</ul>
</div>








