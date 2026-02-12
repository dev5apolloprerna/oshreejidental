<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<!-- Modal Contact -->
<div class="modal fade" id="add_prescription" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" style="width: 90%;">
        <div class="modal-content">
            <?php echo form_open_multipart(admin_url('appointly/appointments/add_prescription/'.$client->userid), ['id' => 'client-attachments-upload']); ?>
            <input type="hidden" id="prescriptionId" name="prescription_id" value="">
            <input type="hidden" id="deletPrescription" name="deletPrescription" value="">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                        <div class="tw-flex">
                            <!-- <div class="tw-mr-4 tw-flex-shrink-0 tw-relative">
                                <?php if (isset($contact)) { ?>
                                    <?php if (!empty($contact->profile_image)) { ?>
                                    <a href="#" onclick="delete_contact_profile_image(<?php echo e($contact->id); ?>); return false;"
                                        class="tw-bg-neutral-500/30 tw-text-neutral-600 hover:tw-text-neutral-500 tw-h-8 tw-w-8 tw-inline-flex tw-items-center tw-justify-center tw-rounded-full tw-absolute tw-inset-0"
                                        id="contact-remove-img"><i class="fa fa-remove tw-mt-1"></i></a>
                                    <?php } ?>
                                <?php } ?>
                            </div> -->
                            <div>
                                <h4 class="modal-title tw-mb-0"> Add Prescription</h4>
                            </div>
                        </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div id="xray_image_head" class="form-group">
                           <!--  <label for="xray_image_title">Title</label>
                            <input type="text" name="xray_title" class="form-control" id="xray_image_title" required> -->
                             <?php echo render_date_input('date', 'Date', date('Y-m-d'), ['id' => 'preDate']);?>
                             <div class="items-select-wrapper">
            <select name="item_select1"
                class="selectpicker no-margin<?php echo $ajaxItems == true ? ' ajax-search' : ''; ?><?php echo staff_can('create', 'items') ? ' _select_input_group' : ''; ?>"
                data-width="false" id="item_select1" data-none-selected-text="<?php echo _l('Add Medicine'); ?>"
                data-live-search="true" onchange="add_item_to_preview1();">
                <option value=""></option>
                <?php foreach ($items as $group_id => $_items) { ?>
                <optgroup data-group-id="<?php echo e($group_id); ?>" label="<?php echo $_items[0]['group_name']; ?>">
                    <?php foreach ($_items as $item) { ?>
                    <option value="<?php echo e($item['id']); ?>"
                        data-subtext="<?php echo strip_tags(mb_substr($item['long_description'], 0, 200)) . '...'; ?>">
                        (<?php echo e(app_format_number($item['rate'])); ?>) <?php echo e($item['description']); ?></option>
                    <?php } ?>
                </optgroup>
                <?php } ?>
            </select>
        </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="xray_image_head" class="form-group">
                            <input type="hidden" name="appointment_id"  id="appointment_id" value="">
                           <!--  <label for="xray_image_title">Title</label>
                            <input type="text" name="xray_title" class="form-control" id="xray_image_title" required> -->
                             <?php echo render_textarea('note', 'Notes', '',['id' => 'preNotes']); ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive s_table">
                            <div id="pres-err"></div>
            <table class="table invoice-items-table prescriptionitems table-main-invoice-edit has-calculations no-mtop">
                <thead>
                    <tr>
                        <th></th>
                        <th width="20%" align="left"><i class="fa-solid fa-circle-exclamation tw-mr-1"
                                aria-hidden="true" data-toggle="tooltip"
                                data-title="<?php echo _l('Medicine'); ?>"></i>
                            <?php echo _l('Medicine'); ?></th>
                        <th width="25%" align="left"><?php echo _l('invoice_table_item_description'); ?></th>
                        <?php
                  $custom_fields = get_custom_fields('items');
                  foreach ($custom_fields as $cf) {
                      echo '<th width="15%" align="left" class="custom_field">' . e($cf['name']) . '</th>';
                  }
                     $qty_heading = _l('invoice_table_quantity_heading');
                     if (isset($invoice) && $invoice->show_quantity_as == 2 || isset($hours_quantity)) {
                         $qty_heading = _l('invoice_table_hours_heading');
                     } elseif (isset($invoice) && $invoice->show_quantity_as == 3) {
                         $qty_heading = _l('invoice_table_quantity_heading') . '/' . _l('invoice_table_hours_heading');
                     }
                     ?>
                        <th width="10%" align="right" class="qty"><?php echo e($qty_heading); ?></th>
                        <th width="30%" align="right"><?php echo _l('Time Slot'); ?></th>
                        <th width="10%" align="right"><?php echo _l('Days'); ?></th>
                        
                        <th align="center"><i class="fa fa-cog"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="mainprescription">
                        <td></td>
                        <td>
                            <textarea name="description" class="form-control" rows="4"
                                placeholder="<?php echo _l('item_description_placeholder'); ?>"></textarea>
                        </td>
                        <td>
                            <textarea name="long_description" rows="4" class="form-control"
                                placeholder="<?php echo _l('item_long_description_placeholder'); ?>"></textarea>
                        </td>
                        <?php echo render_custom_fields_items_table_add_edit_preview(); ?>
                        <td>
                            <input type="number" name="quantity" min="1" value="1" class="form-control" id="med_qty"
                                placeholder="<?php echo _l('item_quantity_placeholder'); ?>">
                            <input type="text" placeholder="<?php echo _l('unit'); ?>" data-toggle="tooltip"
                                data-title="e.q kg, lots, packs" name="unit"
                                class="form-control input-transparent text-right">
                        </td>
                        <td class="tm_slot">
                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="tlable">
                                        <label>M</label>
                                        </div> 
                          

                           <select name="m" id="m" class="form-control tm">
                            
                               <option value="0">0</option>
                               <option value="1">1</option>
                           </select>


                           <select name="m_time" id="m_time" class="form-control">
                            <option value="0">--</option>
                               <option value="BM">BM</option>
                               <option value="AM">AM</option>
                           </select>
                                </div>
                            
                       <div class="col-lg-3">
                                
                                 <div class="tlable">
                                        <label>A</label>
                                        </div> 
                             

                                <select name="a" id="a" class="form-control tm" >
                            
                               <option value="0">0</option>
                               <option value="1">1</option>
                           </select>
                                <select name="a_time" id="a_time" class="form-control">
                                     <option value="0">--</option>
                               <option value="BM">BM</option>
                               <option value="AM">AM</option>
                           </select>
                       </div>

                       <div class="col-lg-3">
                                
                                 <div class="tlable">
                                        <label>E</label>
                                        </div> 
                              
                                 <select name="e" id="e" class="form-control tm">
                            
                               <option value="0">0</option>
                               <option value="1">1</option>
                           </select>
                                <select name="e_time" id="e_time" class="form-control"> 
                                     <option value="0">--</option>
                               <option value="BM">BM</option>
                               <option value="AM">AM</option>
                           </select>

                       </div>
                       <div class="col-lg-3">
                                
                                <div class="tlable">
                                        <label>N</label>
                                        </div> 
                               

                                 <select name="n" id="n" class="form-control tm">
                            
                               <option value="0">0</option>
                               <option value="1">1</option>
                           </select>
                                <select name="n_time" id="n_time" class="form-control">
                                     <option value="0">--</option>
                               <option value="BM">BM</option>
                               <option value="AM">AM</option>
                           </select>

                       </div>

                           </div>

                        </td>
                        
                        <td><input type="number" name="days" min="0" value="" class="form-control" id="days"
                                ></td>
                        <td>
                            <?php
                        $new_item = 'undefined';
                        if (isset($invoice)) {
                            $new_item = true;
                        }
                        ?>
                            <button type="button"
                                onclick="add_prescription_to_table('undefined','undefined',<?php echo e($new_item); ?>); return false;"
                                class="btn pull-right btn-primary" id="add_preTable"><i class="fa fa-check"></i></button>
                        </td>
                    </tr>
                   
                </tbody>
            </table>
        </div>

                        </div>
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" onclick="return validate_prescription_form();" class="btn btn-primary" id="upload-button"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<style>
    .tm_slot input{
        width: 50px;
        float: left;
        margin-right: 5px;

    }
    .tm_slot .tlable{
        text-align: center;
    }
    .tm_slot .dropdown{
        width: 60px !important;
    }
    /*.tm_slot input::-webkit-outer-spin-button, .tm_slot input::-webkit-inner-spin-button {
    -webkit-appearance: none;
}*/
.tm{
    margin-bottom: 5px !important;
}
#med_qty {
    margin-top: 24px;
}
#days {
    margin-top: 24px;
}
#add_preTable {
    margin-top: 24px;
}
</style>
<?php if (!isset($contact)) { ?>
<?php 

} ?>

<script>


    function validate_prescription_form() {

        document.getElementById('pres-err').innerHTML = '';

        var $preTable = $(this).find("table .prescriptionitems");
        var $previewItems = $preTable.find(".mainprescription");
        var existing_pre = document.getElementsByClassName('existing_pre');
        if (
            $previewItems.find('[name="description"]').length &&
            $previewItems.find('[name="long_description"]').val().trim().length > 0 &&
            $previewItems.find('[name="quantity"]').val().trim().length > 0 &&
            $previewItems.find('[name="days"]').val().trim().length > 0 && existing_pre.length == 0
        )

        {

            
            $preTable.before(
            '<div class="alert alert-warning mbot20" id="prescription-warning">' +
            app.lang.item_forgotten_in_preview +
            '<i class="fa fa-angle-double-down pointer pull-right fa-2x" style="margin-top:-4px;" onclick="add_item_to_table(\'undefined\',\'undefined\',undefined); return false;"></i></div>'
            );

            
            return false;
        
        }

        

         else {

            

            var item_pre = document.getElementsByClassName('item-pre');
           
        
            if (item_pre.length == 0 && existing_pre.length == 0) {

                document.getElementById('pres-err').innerHTML = '<div class="alert alert-warning mbot20" id="prescription-warning">' +
                    app.lang.no_items_warning +
                    "</div>";           
                
                
                return false;
            }else{

                return true;
            }
            
        }





        return false;
    }




    function open_prescrip_modal(id){

        document.getElementById('appointment_id').value = id;
        $("#add_prescription").modal();

        clear_prescription_preview_values();

        $.ajax({
        url: "<?php echo admin_url('appointly/appointments/get_prescription/');?>" + id,
        method: "GET",
        dataType: "json",
        success: function(prescriptionData) {

            // console.log(prescriptionData);
            
            const elements = document.querySelectorAll('.existing_pre');

                    // Loop through the elements and remove each one
                    elements.forEach(element => {
                        element.remove();
                    });
                    
              // Check if the prescriptionData is an array
            if (Array.isArray(prescriptionData)) {
                // If it's an array, iterate over it
                prescriptionData.forEach(function(item, index) {
                    appendExistingPrescriptionRow(item, index);
                });
            }  else {
                console.error("Unexpected data format:", prescriptionData);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching prescription data:", error);
        }
    });
}

function appendExistingPrescriptionRow(data, item_key) {
    var table_row = '<tr class="existing_pre">';
    document.getElementById('prescriptionId').value = data.prescription_id;
    document.getElementById('date').value = data.date;
    document.getElementById('note').value = data.note;
    // Set the description
    table_row += '<td></td>';
    table_row += '<input type="hidden" name="existingitems[' + data.id + '][prescription_id]" value="' + data.prescription_id + '">';
    table_row +=
        '<td class="bold description"><textarea name="existingitems[' +
        data.id +
        '][description]" class="form-control" rows="4">' +
        (data.description || '') +
        "</textarea></td>";

    // Set the long description
    table_row +=
        '<td><textarea name="existingitems[' +
        data.id +
        '][long_description]" class="form-control item_long_description" rows="4">' +
        (data.long_description || '').replace(/\n/g, "\n") +
        "</textarea></td>";

    // Set the quantity and unit
    table_row +=
        '<td><input type="number" id="med_qty" min="0" data-quantity name="existingitems[' +
        data.id +
        '][qty]" value="' +
        (data.qty || '') +
        '" class="form-control">';

    if (!data.unit || typeof data.unit == "undefined") {
        data.unit = "";
    }

    table_row +=
        '<input type="text" placeholder="Unit" name="existingitems[' +
        data.id +
        '][unit]" class="form-control input-transparent text-right" value="' +
        data.unit +
        '">';

    table_row += "</td>";

    // Time Slot Section
    var time_slot_keys = ['morning', 'afternoon', 'evening', 'night'];

    table_row += '<td class="tm_slot"><div class="row">';

    time_slot_keys.forEach(function(key, index) {
        var time_value = data.time_slot ? data.time_slot[key] || "0" : "0";
        var time_timing_value = data.time_slot_timing ? data.time_slot_timing[key] || "0" : "0";

        var time_labels = ['M', 'A', 'E', 'N'];
        table_row += '<div class="col-lg-3"><div class="tlable"><label>' + time_labels[index] + '</label></div>';
        
        table_row += '<select id="' + key[0] + '_' + data.id + '" name="existingitems[' +
            data.id +
            '][slot][' + key[0] + ']" class="form-control tm">';
        table_row += '<option value="0"' + (time_value == "0" ? " selected" : "") + '>0</option>';
        table_row += '<option value="1"' + (time_value == "1" ? " selected" : "") + '>1</option>';
        table_row += '</select>';

        table_row += '<select id="' + key[0] + '_time_' + data.id + '" name="existingitems[' +
            data.id +
            '][slot][' + key[0] + '_time]" class="form-control tm">';
        table_row += '<option value="0"' + (time_timing_value == "0" ? " selected" : "") + '>--</option>';
        table_row += '<option value="BM"' + (time_timing_value == "BM" ? " selected" : "") + '>BM</option>';
        table_row += '<option value="AM"' + (time_timing_value == "AM" ? " selected" : "") + '>AM</option>';
        table_row += '</select></div>';
    });

    table_row += '</div></td>';

    // Set the days
    table_row +=
        '<td><input type="number" id="days" name="existingitems[' +
        data.id +
        '][days]" min="0" value="' +
        (data.days || '') + '" class="form-control"></div></td>';

    // Add a delete button
    table_row +=
        '<td><a href="#" class="btn btn-danger pull-left" id="add_preTable" onclick="delete_prescription(this,' +
        data.id +
        '); return false;"><i class="fa fa-trash"></i></a></td>';

    table_row += "</tr>";

    // Append the row to the table
    $("table.prescriptionitems tbody").append(table_row);
}


    function add_item_to_preview1() {

          var selectElement = document.getElementById('item_select1');
            
            // Get the selected option value
            var id = selectElement.value;
  requestGetJSON("invoice_items/get_item_by_id/" + id).done(function (
    response
  ) {
    clear_item_preview_values();

    $('.mainprescription textarea[name="description"]').val(response.description);
    $('.mainprescription textarea[name="long_description"]').val(
      response.long_description.replace(/(<|&lt;)br\s*\/*(>|&gt;)/g, " ")
    );

    _set_item_preview_custom_fields_array(response.custom_fields);

    $('.mainprescription input[name="quantity"]').val(1);

    var taxSelectedArray = [];
    if (response.taxname && response.taxrate) {
      taxSelectedArray.push(response.taxname + "|" + response.taxrate);
    }
    if (response.taxname_2 && response.taxrate_2) {
      taxSelectedArray.push(response.taxname_2 + "|" + response.taxrate_2);
    }

    $(".mainprescription select.tax").selectpicker("val", taxSelectedArray);
    $('.mainprescription input[name="unit"]').val(response.unit);

    var $currency = $("body").find(
      '.accounting-template select[name="currency"]'
    );
    var baseCurency = $currency.attr("data-base");
    var selectedCurrency = $currency.find("option:selected").val();
    var $rateInputPreview = $('.main input[name="rate"]');

    if (baseCurency == selectedCurrency) {
      $rateInputPreview.val(response.rate);
    } else {
      var itemCurrencyRate = response["rate_currency_" + selectedCurrency];
      if (!itemCurrencyRate || parseFloat(itemCurrencyRate) === 0) {
        $rateInputPreview.val(response.rate);
      } else {
        $rateInputPreview.val(itemCurrencyRate);
      }
    }

    $(document).trigger({
      type: "item-added-to-preview",
      item: response,
      item_type: "item",
    });
  });
}


function get_prescription_preview_values() {
  var response = {};
  response.description = $('.mainprescription textarea[name="description"]').val();
  response.long_description = $('.mainprescription textarea[name="long_description"]').val();
  response.quantity = $('.mainprescription input[name="quantity"]').val();
  // response.taxname = $(".mainprescription select.tax").selectpicker("val");

  response.m = $('.mainprescription select[name="m"]').val();
  response.m_time = $('.mainprescription select[name="m_time"]').val();

  response.a = $('.mainprescription select[name="a"]').val();
  response.a_time = $('.mainprescription select[name="a_time"]').val();

  response.e = $('.mainprescription select[name="e"]').val();
  response.e_time = $('.mainprescription select[name="e_time"]').val();

  response.n = $('.mainprescription select[name="n"]').val();
  response.n_time = $('.mainprescription select[name="n_time"]').val();

  response.days = $('.mainprescription input[name="days"]').val();
  response.unit = $('.mainprescription input[name="unit"]').val();
  return response;
}


function clear_prescription_preview_values() {
  // Get the last taxes applied to be available for the next item
  var last_taxes_applied = $("table.prescriptionitems tbody")
    .find("tr:last-child")
    .find("select")
    .selectpicker("val");
  var previewArea = $(".mainprescription");

  previewArea.find("textarea").val(""); // includes cf
  previewArea
    .find('td.custom_field input[type="checkbox"]')
    .prop("checked", false); // cf
  previewArea.find("td.custom_field input:not(:checkbox):not(:hidden)").val(""); // cf // not hidden for chkbox hidden helpers
  previewArea.find("td.custom_field select").selectpicker("val", ""); // cf
  previewArea.find('input[name="quantity"]').val(1);
  previewArea.find("select.tax").selectpicker("val", last_taxes_applied);
  previewArea.find('input[name="days"]').val("");
  previewArea.find('input[name="rate"]').val("");
  $("#m").selectpicker("val", "0");
  $('.mainprescription select[name="m_time"]').val('');
   $("#m_time").selectpicker("val", "0");
  

 $("#a").selectpicker("val", "0");
  $('.mainprescription select[name="a_time"]').val('');
   $("#a_time").selectpicker("val", "0");

  $("#e").selectpicker("val", "0");
  $('.mainprescription select[name="e_time"]').val('');
  $("#e_time").selectpicker("val", "0");

 $("#n").selectpicker("val", "0");
  $('.mainprescription select[name="n_time"]').val('');
   $("#n_time").selectpicker("val", "0");

  previewArea.find('input[name="unit"]').val("");

  $('input[name="task_id"]').val("");
  $('input[name="expense_id"]').val("");
}

    function add_prescription_to_table(data, itemid, merge_invoice, bill_expense) {
  // If not custom data passed get from the preview
  data =
    typeof data == "undefined" || data == "undefined"
      ? get_prescription_preview_values()
      : data;

      
  if (
    data.description === "" &&
    data.long_description === "" &&
    data.days === "" && 
    data.qty === ""
  ) {
    return;
  }

  if(data.days <= 0){
        return;
  }

  if(data.qty <= 0){
        return;
  }

  if(data.m == 0 && data.a == 0 && data.e == 0 && data.n == 0){
    alert('Please select at least one time slot');return;
  }

  if(data.m_time == 0 && data.a_time == 0 && data.e_time == 0 && data.n_time == 0){
    alert('Please select at least one time slot');return;
  }

  
   var regex = /<br[^>]*>/gi;

  var table_row = "";
  var item_key = lastAddedItemKey
    ? (lastAddedItemKey += 1)
    : $("body").find("tbody .mainprescription").length + 1;
  lastAddedItemKey = item_key;

 table_row += '<tr class="item-pre">';


  table_row += '<td></td>';
  

    table_row +=
      '<td class="bold description"><textarea name="newitems[' +
      item_key +
      '][description]" class="form-control" rows="5">' +
      data.description +
      "</textarea></td>";

    table_row +=
      '<td><textarea name="newitems[' +
      item_key +
      '][long_description]" class="form-control item_long_description" rows="5">' +
      data.long_description.replace(regex, "\n") +
      "</textarea></td>";


    table_row +=
      '<td><input type="number" id="med_qty" min="0"  data-quantity name="newitems[' +
      item_key +
      '][quantity]" value="' +
      data.quantity +
      '" class="form-control">';

    if (!data.unit || typeof data.unit == "undefined") {
      data.unit = "";
    }

    table_row +=
      '<input type="text" placeholder="' +
      app.lang.unit +
      '" name="newitems[' +
      item_key +
      '][unit]" class="form-control input-transparent text-right" value="' +
      data.unit +
      '">';

    table_row += "</td>";

   

   

    table_row +=
  '<td class="tm_slot"><div class="row">' +
  '<div class="col-lg-3"><div class="tlable"><label>M</label></div>' +
  '<select id="m_' + item_key + '" name="newitems[' + item_key + '][slot][m]" class="form-control tm">' +
  '<option value="0">0</option><option value="1">1</option></select>' +
  '<select id="m_time_' + item_key + '" name="newitems[' + item_key + '][slot][m_time]" class="form-control tm">' +
  '<option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div>' +
  
  '<div class="col-lg-3"><div class="tlable"><label>A</label></div>' +
  '<select id="a_' + item_key + '" name="newitems[' + item_key + '][slot][a]" class="form-control tm">' +
  '<option value="0">0</option><option value="1">1</option></select>' +
  '<select id="a_time_' + item_key + '" name="newitems[' + item_key + '][slot][a_time]" class="form-control tm">' +
  '<option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div>' +
  
  '<div class="col-lg-3"><div class="tlable"><label>E</label></div>' +
  '<select id="e_' + item_key + '" name="newitems[' + item_key + '][slot][e]" class="form-control tm">' +
  '<option value="0">0</option><option value="1">1</option></select>' +
  '<select id="e_time_' + item_key + '" name="newitems[' + item_key + '][slot][e_time]" class="form-control tm">' +
  '<option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div>' +
  
  '<div class="col-lg-3"><div class="tlable"><label>N</label></div>' +
  '<select id="n_' + item_key + '" name="newitems[' + item_key + '][slot][n]" class="form-control tm">' +
  '<option value="0">0</option><option value="1">1</option></select>' +
  '<select id="n_time_' + item_key + '" name="newitems[' + item_key + '][slot][n_time]" class="form-control tm">' +
  '<option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div>' +
  '</td>';

   
      table_row +=
      '<td><input type="number" id="days" name="newitems[' +
      item_key +
      '][days]" min="0" value="' +
      data.days+'" class="form-control"></div></td>';

    table_row +=
      '<td><a href="#" class="btn btn-danger pull-left" id="add_preTable" onclick="delete_item(this,' +
      itemid +
      '); return false;"><i class="fa fa-trash"></i></a></td>';

    table_row += "</tr>";

    $("table.prescriptionitems tbody").append(table_row);

    document.getElementById('m_'+item_key).value = data.m;
    document.getElementById('a_'+item_key).value = data.a;
    document.getElementById('e_'+item_key).value = data.e;
    document.getElementById('n_'+item_key).value = data.n;

    document.getElementById('m_time_'+item_key).value = data.m_time;
    document.getElementById('a_time_'+item_key).value = data.a_time;
    document.getElementById('e_time_'+item_key).value = data.e_time;
    document.getElementById('n_time_'+item_key).value = data.n_time;


    $(document).trigger({
      type: "item-added-to-table",
      data: data,
      row: table_row,
    });

   

    $("body").find("#prescription-warning").remove();
    $("body").find(".dt-loader").remove();

    clear_prescription_preview_values();

    $("#item_select1").selectpicker("val", "");



    
   
  return false;
}

    var deletPres = [];
 function delete_prescription(element, id) {
     if(confirm("Are you sure you want to delete the prescription?")){
        deletPres.push(id);
        // Update the hidden input field
        document.getElementById('deletPrescription').value = JSON.stringify(deletPres);
    
        // Remove the row from the table
        $(element).closest('tr').remove();
    }
}
</script>
