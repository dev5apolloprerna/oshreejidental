<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet" />

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if(is_admin()) { ?>
                                
                            <div class="clearfix" style="color: #415165;"><?php echo '<h3 class="no-margin bold">'._l('General Report').'</h3>';?></div>
                            <hr class="hr-panel-heading" />
                            <?php }  ?>
                            <form id="install_data" name="install_data" action="" method="get">
                            <div class="row">
                                <div class="col-md-3">
                                  <div class="select-placeholder">
                                    <label><?php echo _l('filter_by_g'); ?>
                                        
                                    </label>
                                    
                                  <select name="repo_type" id="repo_type" class="selectpicker" data-width="100%" data-live-search="true">
                                     <option value="appointments">Appointments</option>
                                    <option value="leads">Leads</option>
                                   
                                    <option value="patients" <?php echo  $_GET['repo_type'] == 'patients' ? 'selected' : ''?>>Patients</option>
                                    <option value="task">Task</option>
                                     <option value="lab_work">Lab Work</option>
                                    <option value="payment_receipts">Payment Receipts</option>
                                   
                                   </select>
                                </div>
                               </div>
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
                                      <option value="today" selected><?php echo _l('today');?>
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

                                 <div class="col-md-3">
                                  <?php 

                                  $st_val = $_GET['range'] == '' ? $this->input->get('start_date') : ''; 

                                  echo render_date_input('start_date', 'from_date', $st_val); ?>
                                    
                               </div>

                                <div class="col-md-3">
                                  <?php 

                                  $en_val = $_GET['range'] == '' ? $this->input->get('end_date') : '';

                                  echo render_date_input('end_date', 'to_date', $en_val); ?>
                                    
                               </div>

                               
                              <!--  <div class="col-md-3">
                                 <?php echo render_input('area','Filter By Area',$_GET['area'],'text',[]); ?>
                               </div> -->
                                <!-- <a href="#" id="apply_filters_timesheets" class="btn btn-info p7 pull-left"><?php echo _l('apply'); ?></a> -->
                                <br>
                                <!-- <div class="col-md-2">
                                <button type="submit" class="btn btn-info p7 pull-left" style="margin-right: 10px;margin-top: 7px;"><?php echo _l('apply'); ?></button>
                                <a href="<?php echo admin_url('report'); ?>" id="apply_filters_timesheets" class="btn btn-info p7 pull-left" style="margin-top: 7px;"><?php echo _l('reset'); ?></a>
                              </div> -->
                            
                          </div>
                          <div class="" style="margin-bottom: 71px;margin-top: 10px;">
                                <button type="submit" class="btn btn-primary p7 pull-left" style="margin-right: 10px;margin-top: 7px;"><?php echo _l('apply'); ?></button>
                                <a href="<?php echo admin_url('generalreport'); ?>" id="apply_filters_timesheets" class="btn btn-primary p7 pull-left" style="margin-top: 7px;"><?php echo _l('reset'); ?></a>
                              </div>
                              </form>
                          <hr class="hr-panel-heading" />
                        
                      
                        <div class="clearfix"></div>
                       <!--  <?php render_datatable(array(
                          '<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="service_details"><label></label></div>',
                          // _l('service_id'),
                          // _l('Type'),
                           _l('Company'),
                          _l('customer_name'),
                          _l('Service Card'),
                          // _l('installation_date'),
                          
                          _l('Schedule'),
                           _l('service_type'),
                          _l('assign_mechanic'),
                        //   _l('Complete'),
                        //   _l('Upcoming'),
                          //_l('Status')
                          ),
                          'service_details'); ?>  -->

                           <?php

                    $table_data  = [];
                     $_table_data = [
                      
                       [
                         'name'     => _l('the_number_sign'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-number'],
                        ],
                        [
                         'name'     => _l('Number'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-number'],
                        ],
                         [
                         'name'     => _l('clients_list_company'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-company'],
                        ],
                         [
                         'name'     => _l('company_primary_email'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-primary-contact-email'],
                        ],
                        [
                         'name'     => _l('clients_list_phone'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-phone'],
                        ],
                        //  [
                        //  'name'     => _l('customer_active'),
                        //  'th_attrs' => ['class' => 'toggleable', 'id' => 'th-active'],
                        // ],
                        [
                         'name'     => _l('customer_groups'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-groups'],
                        ],
                        [
                         'name'     => _l('date_created'),
                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-date-created'],
                        ],
                      ];
                     foreach ($_table_data as $_t) {
                         array_push($table_data, $_t);
                     }

                     $custom_fields = get_custom_fields('customers', ['show_on_table' => 1]);

                     // foreach ($custom_fields as $field) {
                     //     array_push($table_data, [
                     //       'name'     => $field['name'],
                     //       'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
                     //     ]);
                     // }
                     $table_data = hooks()->apply_filters('customers_table_columns', $table_data);



                    render_datatable($table_data, 'service_details');
                    ?>
                        <div id="service"></div>
                    </div>
                    
                </div>
                
            </div>
            
        </div>

    </div>
</div>

<?php init_tail(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>
<script>
  $(function(){
      initDataTable('.table-service_details', window.location.href,[], [0]);
  });

   $("body").on('change', '#mass_select_all', function() {
        var to, rows, checked;

        to = $(this).data('to-table');

        rows = $('.table-' + to).find('tbody tr');

        checked = $(this).prop('checked');
        $.each(rows, function() {
            $($(this).find('td').eq(0)).find('input').prop('checked', checked);
        });
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

  function bulkaction_status() {



        var mechanic_satff = $('#mechanic_satff').find(":selected").val();

        if(mechanic_satff == ''){

          alert("Please select Mechanic");
          return false;

        }else{

          if (confirm('Do you sure want to action perform ?') == true) {

            // var selectname = $('#range1').find(":selected").val();
            // var selectname1 = $('#range2').find(":selected").val();
            
            // if ((selectname == '' && selectname1 != '') || (selectname != '' && selectname1 == '') || (selectname != '' && selectname1 != '')) {

                var selected = [];
                
                check = document.getElementsByName('check');

                 var shedule_ids = [];
                for (var i = 0; i < check.length; i++) {

                    if (check[i].checked == true) {
                     
                        selected.push(check[i].value);
                        
                        shedule_ids_check = document.getElementsByName('cust_' + check[i].value);
                        //console.log(shedule_ids_check);
                       
                        for (var s = 0; s < shedule_ids_check.length; s++) {
                          
                          shedule_ids.push(shedule_ids_check[s].value);
                        }
                    }
                }
                if (selected == '') {

                    alert("Please select checkbox from below list");
                    return false;
                }

                // var shedule_ids = [];
                // shedule_ids_check = document.getElementsByName('schedule_ids');
                // for (var i = 0; i < shedule_ids_check.length; i++) {
                //   shedule_ids.push(shedule_ids_check[i].value);
                // }
                
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url() . 'report/change_statuses'; ?>',
                    dataType: 'json',
                    data: {
                        shedule_ids: shedule_ids,
                        mechanic_satff: mechanic_satff
                    },
                    success: function(data) {
                        //alert(data);
                        toastr.success("Bulk mechanic assigned successfully");
                        if (data == true) {
                            var installationTable = $('.table-service_details');
                            installationTable.DataTable().ajax.reload();
                        } else {
                            var installationTable = $('.table-service_details');
                            installationTable.DataTable().ajax.reload();
                        }

                    }
                });
            // } else {
            //     toastr.warning("Please select bulk action status or Mechanic name.");
            //     return false;
            // }

            } else {
                var installationTable = $('.table-service_details');
                installationTable.DataTable().ajax.reload();
            }

        }
    }
</script>

