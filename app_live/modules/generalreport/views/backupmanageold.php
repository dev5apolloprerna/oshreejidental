<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if(is_admin()) { ?>
                                
                            <div class="clearfix" style="color: #415165;"><?php echo '<h3 class="no-margin bold">'._l('service_report').'</h3>';?></div>
                            <hr class="hr-panel-heading" />
                            <?php }  ?>
                            <form id="install_data" name="install_data" action="" method="get">
                            <div class="row">
                              <div class="col-md-3">
                                  <div class="select-placeholder">
                                    <?php 
                                      if(isset($_GET['range']))
                                      {
                                        $st=$_GET['range'];
                                      }
                                      else
                                      {
                                        $st ='';
                                      }
                                    ?>
                                    <label><?php echo _l('today_and_upcomming_service'); ?>
                                        
                                    </label>
                                    
                                   <select name="range" id="range" class="selectpicker" data-width="100%" >
                                    <option value=""></option>
                                    <?php 
                                    if($st == 'today')
                                    {?>
                                      <option value="today" selected><?php echo _l('today_service');?>
                                    </option>
                                    <?php 
                                    }
                                    else
                                    {?>
                                      <option value="today" ><?php echo _l('today');?>
                                    </option>
                                   <?php
                                    }
                                    ?>
                                    <?php 
                                    if($st == 'this_week')
                                    {?>
                                      <option value="this_week" selected><?php echo _l('this_week');?>
                                    </option>
                                    <?php 
                                    }
                                    else
                                    {?>
                                      <option value="this_week" ><?php echo _l('this_week');?>
                                    </option>
                                   <?php
                                    }
                                    ?>
                                    <?php 
                                    if($st == 'this_month')
                                    {?>
                                      <option value="this_month" selected><?php echo _l('this_months');?>
                                    </option>
                                    <?php 
                                    }
                                    else
                                    {?>
                                      <option value="this_month" ><?php echo _l('this_months');?>
                                    </option>
                                   <?php
                                    }
                                    ?>
                                   </select>
                                </div>
                               </div>

                                <div class="col-md-4">
                                  <div class="select-placeholder">
                                    <label><?php echo _l('service_card'); ?>
                                        
                                    </label>
                                    
                                   <select name="service_card" id="service_card" class="selectpicker" data-width="100%" data-live-search="true">
                                    <option value=""></option>
                                    <?php
                                    foreach($service_card as $servics){

                                      $selected_cus = ($servics['userid'] == $_GET['service_card']) ? 'selected' : '';
                                      echo '<option value="'.$servics['userid'].'" '.$selected_cus.'>'.$servics['first_name'] . ' ' . $servics['last_name'] .' (SERVICE-CARD-'.$servics['service_card_no'].')</option>';
                                    }
                                    ?>
                                   </select>
                                </div>
                               </div>
                               <div class="col-md-3">
                                 <?php echo render_input('area','Filter By Area',$_GET['area'],'text',[]); ?>
                               </div>
                                <!-- <a href="#" id="apply_filters_timesheets" class="btn btn-info p7 pull-left"><?php echo _l('apply'); ?></a> -->
                                <br>
                                <button type="submit" class="btn btn-info p7 pull-left" style="margin-right: 10px;margin-top: 7px;"><?php echo _l('apply'); ?></button>
                                <a href="<?php echo admin_url('report'); ?>" id="apply_filters_timesheets" class="btn btn-info p7 pull-left" style="margin-top: 7px;"><?php echo _l('reset'); ?></a>
                            </form>
                          </div>
                          <hr class="hr-panel-heading" />
                        <div class="row">
                            <form id="install_data" name="install_data" action="" method="get">

                                <div class="col-md-3">
                                    <div class="form-group select-placeholder">
                                        <label><?php echo _l('bulk_actions_staff'); ?></label>
                                        <select name="range2" id="range2" class="selectpicker" data-live-search="true" data-width="100%">
                                            <option value=""></option>
                                            <?php
                                            $get_staffs  = $this->load->staff_model->getstaff();
                                            foreach ($get_staffs as $staffs) { ?>
                                                <option value="<?php echo $staffs['staffid']; ?>"><?php echo $staffs['firstname'] . " " . $staffs['lastname']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                            </form>
                        </div>
                        <br>
                        <button type="submit" name="active" onclick="bulkaction_status()" id="active" class="btn btn-info p7 pull-left" style="margin-top: 7px;"><?php echo _l('submit'); ?></button>
                            
                        </div>
                        
                        <hr class="hr-panel-heading" />
                        <div class="clearfix"></div>
                        <?php render_datatable(array(
                          '<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="clients"><label></label></div>',
                          _l('service_id'),
                          _l('Type'),
                          _l('Challan No'),
                          _l('customer_name'),
                          _l('installation_date'),
                          _l('service_type'),
                          _l('Schedule'),
                          _l('assign_mechanic'),
                        //   _l('Complete'),
                        //   _l('Upcoming'),
                          //_l('Status')
                          ),
                          'service_details'); ?> 
                        <div id="service"></div>
                    </div>
                    
                </div>
                
            </div>
            
        </div>

    </div>
</div>

<?php init_tail(); ?>

<script>
  $(function(){
      initDataTable('.table-service_details', window.location.href);
  });
  function getid(id)
  {
    let url= admin_url + 'report/assign_mechanic/'+id;
    // alert(url);
    $.ajax({
        type: 'POST',
        url: url,
        data: {id:id},
        success: function(data){
          //alert(data)
         $('#service').html(data);
          $('#service_modal').modal({
              show: true,
              backdrop: 'static'
          });
          $('body').off('shown.bs.modal','#service_modal');
        }
    });        
  }  
  function confirm_status(id)
  {
      
      let url = admin_url + 'report/confirm_status/'+id;
      $.ajax({
        type: 'POST',
        url: url,
        data: {id:id},
        success :function(data)
        {
           var installationTable = $('.table-service_details');
           installationTable.DataTable().ajax.reload();
        }
      }); 
  }
</script>

