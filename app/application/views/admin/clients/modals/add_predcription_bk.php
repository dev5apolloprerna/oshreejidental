<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<!-- Modal Contact -->
<div class="modal fade" id="add_prescription" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" style="width: 90%;">
        <div class="modal-content">
            <?php echo form_open_multipart(admin_url('appointly/appointments/add_prescription/'.$client->userid), ['id' => 'client-attachments-upload']); ?>
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
                             <?php echo render_date_input('date', 'Date', date('Y-m-d')); ?>
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
                             <?php echo render_textarea('note', 'Notes', ''); ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive s_table">
                            <div id="item-err"></div>
            <table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
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
                    <tr class="main">
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
                            <input type="number" name="quantity" min="1" value="1" class="form-control"
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
                        
                        <td><input type="number" name="days" min="0" value="" class="form-control"
                                ></td>
                        <td>
                            <?php
                        $new_item = 'undefined';
                        if (isset($invoice)) {
                            $new_item = true;
                        }
                        ?>
                            <button type="button"
                                onclick="add_item_to_table1('undefined','undefined',<?php echo e($new_item); ?>); return false;"
                                class="btn pull-right btn-primary"><i class="fa fa-check"></i></button>
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
                <button type="submit" onclick="return validate_form();" class="btn btn-primary" id="upload-button"><?php echo _l('submit'); ?></button>
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
</style>
<?php if (!isset($contact)) { ?>
<?php 

} ?>

<script>

    function validate_form() {

        document.getElementById('item-err').innerHTML = '';

        var $itemsTable = $(this).find("table.items");
        var $previewItem = $itemsTable.find(".main");

        if (
            $previewItem.find('[name="description"]').length &&
            $previewItem.find('[name="description"]').val().trim().length > 0 &&
            $previewItem.find('[name="qty"]').val().trim().length > 0 &&
            $previewItem.find('[name="days"]').val().trim().length > 0
        ){
           
            $itemsTable.before(
            '<div class="alert alert-warning mbot20" id="items-warning">' +
            app.lang.item_forgotten_in_preview +
            '<i class="fa fa-angle-double-down pointer pull-right fa-2x" style="margin-top:-4px;" onclick="add_item_to_table(\'undefined\',\'undefined\',undefined); return false;"></i></div>'
            );

            
            return false;
        
        } else {

           
            var item_pre = document.getElementsByClassName('item-pre');
           
        
            if (item_pre.length == 0) {

                document.getElementById('item-err').innerHTML = '<div class="alert alert-warning mbot20" id="items-warning">' +
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

    }


    function add_item_to_preview1() {

          var selectElement = document.getElementById('item_select1');
            
            // Get the selected option value
            var id = selectElement.value;
  requestGetJSON("invoice_items/get_item_by_id/" + id).done(function (
    response
  ) {
    clear_item_preview_values();

    $('.main textarea[name="description"]').val(response.description);
    $('.main textarea[name="long_description"]').val(
      response.long_description.replace(/(<|&lt;)br\s*\/*(>|&gt;)/g, " ")
    );

    _set_item_preview_custom_fields_array(response.custom_fields);

    $('.main input[name="quantity"]').val(1);

    var taxSelectedArray = [];
    if (response.taxname && response.taxrate) {
      taxSelectedArray.push(response.taxname + "|" + response.taxrate);
    }
    if (response.taxname_2 && response.taxrate_2) {
      taxSelectedArray.push(response.taxname_2 + "|" + response.taxrate_2);
    }

    $(".main select.tax").selectpicker("val", taxSelectedArray);
    $('.main input[name="unit"]').val(response.unit);

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


function get_item_preview_values1() {
  var response = {};
  response.description = $('.main textarea[name="description"]').val();
  response.long_description = $(
    '.main textarea[name="long_description"]'
  ).val();
  response.qty = $('.main input[name="quantity"]').val();
  response.taxname = $(".main select.tax").selectpicker("val");

  response.m = $('.main select[name="m"]').val();
  response.m_time = $('.main select[name="m_time"]').val();

  response.a = $('.main select[name="a"]').val();
  response.a_time = $('.main select[name="a_time"]').val();

  response.e = $('.main select[name="e"]').val();
  response.e_time = $('.main select[name="e_time"]').val();

  response.n = $('.main select[name="n"]').val();
  response.n_time = $('.main select[name="n_time"]').val();

  response.days = $('.main input[name="days"]').val();
  response.unit = $('.main input[name="unit"]').val();
  return response;
}


function clear_item_preview_values1() {
  // Get the last taxes applied to be available for the next item
  var last_taxes_applied = $("table.items tbody")
    .find("tr:last-child")
    .find("select")
    .selectpicker("val");
  var previewArea = $(".main");

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
  $('.main select[name="m_time"]').val('');
   $("#m_time").selectpicker("val", "0");
  

 $("#a").selectpicker("val", "0");
  $('.main select[name="a_time"]').val('');
   $("#a_time").selectpicker("val", "0");

  $("#e").selectpicker("val", "0");
  $('.main select[name="e_time"]').val('');
  $("#e_time").selectpicker("val", "0");

 $("#n").selectpicker("val", "0");
  $('.main select[name="n_time"]').val('');
   $("#n_time").selectpicker("val", "0");

  previewArea.find('input[name="unit"]').val("");

  $('input[name="task_id"]').val("");
  $('input[name="expense_id"]').val("");
}

    function add_item_to_table1(data, itemid, merge_invoice, bill_expense) {
  // If not custom data passed get from the preview
  data =
    typeof data == "undefined" || data == "undefined"
      ? get_item_preview_values1()
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
    : $("body").find("tbody .item").length + 1;
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
      '<td><input type="number" min="0"  data-quantity name="newitems[' +
      item_key +
      '][qty]" value="' +
      data.qty +
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
      '<td class="tm_slot"><div class="row"><div class="col-lg-3"><div class="tlable"><label>M</label></div> <select id="m_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][m]" class="form-control tm"><option value="0">0</option><option value="1">1</option></select><select id="m_time_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][m_time]"> <option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div><div class="col-lg-3"><div class="tlable"><label>A</label></div><select id="a_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][a]" class="form-control tm"><option value="0">0</option><option value="1">1</option></select><select id="a_time_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][a_time]"> <option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div><div class="col-lg-3"><div class="tlable"><label>E</label></div><select id="e_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][e]" class="form-control tm"><option value="0">0</option><option value="1">1</option></select><select id="e_time_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][e_time]"> <option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div><div class="col-lg-3"><div class="tlable"><label>N</label></div><select id="n_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][n]" class="form-control tm"><option value="0">0</option><option value="1">1</option></select><select id="n_time_'+item_key+'" name="newitems[' +
      item_key +
      '][slot][n_time]"> <option value="0">--</option><option value="BM">BM</option><option value="AM">AM</option></select></div></td>';

   
      table_row +=
      '<td><input type="number" name="newitems[' +
      item_key +
      '][days]" min="0" value="' +
      data.days+'" class="form-control"></div></td>';

    table_row +=
      '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' +
      itemid +
      '); return false;"><i class="fa fa-trash"></i></a></td>';

    table_row += "</tr>";

    $("table.items tbody").append(table_row);

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

   

    $("body").find("#items-warning").remove();
    $("body").find(".dt-loader").remove();

    clear_item_preview_values1();

    $("#item_select1").selectpicker("val", "");



    
   
  return false;
}
</script>
