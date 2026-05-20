<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<!-- Modal Contact -->

<div class="modal fade" id="add_treatment" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" style="width: 50%;">
        <div class="modal-content">
            <?php echo form_open_multipart(admin_url('appointly/appointments/add_treatment/'.$client->userid), ['id' => 'client-attachments-upload']); ?>
            <input type="hidden" name="appointment_id" id="appointment_idt" value="">
            <input type="hidden" id="deleted_items" name="deleted_items" value="">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                        <div class="tw-flex">
                            <div>
                                <h4 class="modal-title tw-mb-0">Add Treatment</h4>
                            </div>
                        </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="xray_image_head" class="form-group">
                            

                <!-- <div class="col-md-12"> -->
                        <div class="table-responsive s_table">
                            <div id="item_error_msg"></div>
            <table id="invoiceTable" class="table invoice-items-table treatment table-main-invoice-edit has-calculations no-mtop">
    <thead>
        <tr class="table_title">
            <th></th>
            <th width="40%" align="left"><i class="fa-solid fa-circle-exclamation tw-mr-1" aria-hidden="true" data-toggle="tooltip" data-title="<?php echo _l('treatment_details'); ?>"></i><?php echo _l('treatment_details'); ?></th>
            <th width="50%" align="left"><?php echo _l('doctor'); ?></th>
            <th align="center"><i class="fa fa-cog"></i></th>
        </tr>
    </thead>
    <tbody>
        <tr class="treatment_main">
            <td></td>
            <td>
                <textarea id="treatment" name="treatment" class="form-control" rows="4" placeholder="<?php echo _l('item_description_placeholder'); ?>"></textarea>
            </td>
            <td>
                <div class="row appoinment_dr_name">
                    <?php if($staff_id != ''){?>
                    <div class="col-xl-2 col-lg-2 apoinment_img">
                        <figure>
                            <img src="<?= staff_profile_image_url($staff_id, 'small'); ?>" class="staff-profile-image-small mright5" data-original-title="">
                        </figure>
                    </div>
                    <?php } ?>
                    <div class="col-xl-7 col-lg-7 dr_name">

                        <select id="staff" class="form-control" name="staff">
                            <!-- <select class="form-control" name="staff" id="staff" onchange="update_assginee(this.value, <?php echo $value['id']?>)"> -->
                            <option value="">-Not Selected-</option>
                            <?php foreach ($staff as $key => $stf) {?>
                                <option value="<?php echo $stf['staffid'];?>" <?php echo $staff_id == $stf['staffid'] ? 'selected' : ''?>><?php echo $stf['firstname'].' '.$stf['lastname']?></option>
                            <?php }?>
                        </select>
                    </div>
                    
                </div>
            </td>
            <td>
                <?php
                $new_item = 'undefined';
                if (isset($invoice)) {
                    $new_item = true;
                }
                ?>
                <button type="button" onclick="add_item_to_tbl(<?php echo e($new_item); ?>); return false;" class="btn pull-right btn-primary"><i class="fa fa-check"></i></button>
            </td>
        </tr>

    </tbody>
</table>
        </div>
    </div> 
<div id="removed-treatment"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button   >
                    <button type="submit" onclick="return validate_form_data();" class="btn btn-primary" id="upload-button"> <?php echo _l('submit'); ?></button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<style>
thead tr.table_title {
    background-color: #f1f5f9;
    border-top: 1px solid #e2e8f0;
}
div#item_error_msg {
    background-color: #fefce8;
}
div#item_error_msg .alert {
    color: #a16207;
}
.feed-item .treatment {
    color: #000;
}
</style>


<script>

    function open_treatment_modal(id){

       
        $("#add_treatment").modal();
         document.getElementById('appointment_idt').value = id;

         $.ajax({
        url: "<?php echo admin_url('appointly/appointments/get_appointment_assign_log/');?>" + id,
        method: "GET",
        dataType: "json",
        success: function(treatmentData) {

            $.ajax({
                url: "<?php echo admin_url('appointly/appointments/get_treatment_time_log/');?>" + id,
                method: "GET",
                dataType: "json",
                success: function(assignLogData) {


                    const elements = document.querySelectorAll('.item-tret');

                    // Loop through the elements and remove each one
                    elements.forEach(element => {
                        element.remove();
                    });

                    treatmentData.forEach(function(item, index) {
                        var assignLog = assignLogData.find(log => log.staff_id == item.staff && log.appointment_id == item.appointment_id && log.treatment_id == item.id);


                        add_existing_treatment(item, index, assignLog);
                        console.log(assignLog);
                        
                    });

                }
            });
        }
    });
}

function add_existing_treatment(item, index, assignLog) {
    var staffs = <?php echo json_encode($staff); ?>;
    var table_row = '<tr class="item-tret">';

    table_row += '<td></td>';
    table_row += '<td class="bold descriptions"><textarea name="existing_items[' + item.id + '][treatment]" class="form-control" rows="5">' + item.treatment + '</textarea></td>';
    table_row += '<td><div class="row appoinment_dr_name"><div class="col-xl-7 col-lg-7 dr_name"><select name="existing_items[' + item.id + '][staff]" class="form-control" data-appointment-id="' + item.appointment_id + '" data-item-id="' + assignLog.id + '" onchange="alert(\'Are you sure you want to update the assignee?\')">';

    $.each(staffs, function(i, stf) {
        var selected = (stf.staffid == item.staff) ? ' selected' : '';
        table_row += '<option value="' + stf.staffid + '"' + selected + '>' + stf.firstname + ' ' + stf.lastname + '</option>';
    });

    table_row += '</select></div></div></td>';
    table_row += '<td><div class="col-xl-2 col-lg-2 appo-div">';

    if (assignLog) {
     // if (assignLog.start_date_time === null || assignLog.start_date_time === '0000-00-00 00:00:00') {
     //        table_row += '<button id="startAppointmentBtn_' + assignLog.id +'" class="btn btn-success appo-btn start-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ', ' + assignLog.id + ',\'start\')">Start</button>';
     //    } else if (assignLog.end_date_time === null || assignLog.end_date_time === '0000-00-00 00:00:00') {
     //        table_row += '<button id="endAppointmentBtn_' + assignLog.id +'" class="btn btn-danger appo-btn end-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ',' + assignLog.id + ', \'end\')" style="display:none;">End</button>';
     //    }

    if (assignLog.start_date_time === null || assignLog.start_date_time === '0000-00-00 00:00:00') {
            table_row += '<button id="startAppointmentBtn_' + assignLog.id +'" class="btn btn-success appo-btn start-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ', ' + assignLog.id + ',\'start\')">Start</button>';


        
            table_row += '<button id="endAppointmentBtn_' + assignLog.id +'" class="btn btn-danger appo-btn end-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ',' + assignLog.id + ', \'end\')"style="display:none;">End</button>';
        }

        else if (assignLog.end_date_time === null || assignLog.end_date_time === '0000-00-00 00:00:00') {
            table_row += '<button id="endAppointmentBtn_' + assignLog.id +'" class="btn btn-danger appo-btn end-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ',' + assignLog.id + ', \'end\')">End</button>';
        }


} else {
    table_row += '<button id="startAppointmentBtn" class="btn btn-success appo-btn start-btn" onclick="start_appointment(event, ' + item.appointment_id + ', ' + item.staff + ', ' + item.id + ', \'start\')">Start</button>';
}

    table_row += '</div></td>';
    table_row += '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_treatment(this, ' + item.id + '); return false;"><i class="fa fa-trash"></i></a></td>';
    table_row += '</tr>';
                

    $("table.treatment tbody").append(table_row);


}

    function validate_form_data() {

        document.getElementById('item_error_msg').innerHTML = '';

        var $itemsTable = $(this).find("table.treatment");
        var $previewItem = $itemsTable.find(".treatment_main");


        if (
            $previewItem.find('[name="treatment"]').length &&
            $previewItem.find('[name="staff"]').val().trim().length > 0){
            $itemsTable.before(
            '<div class="alert alert-warnings mbot20" id="item-warnings">' +
            app.lang.item_forgotten_in_preview +
            '<i class="fa fa-angle-double-down pointer pull-right fa-2x" style="margin-top:-4px;" onclick="add_item_to_table(\',undefined); return false;"></i></div>'
            );

            
            return false;
        
        } else {

           
            var item_pre = document.getElementsByClassName('item-tret');
           
        
            if (item_pre.length == 0) {

                document.getElementById('item_error_msg').innerHTML = '<div class="alert alert-warnings mbot20" id="item-warnings">' +
                    app.lang.no_items_warning +
                    "</div>";           
                
                
                return false;
            }else{

                return true;
            }
            
        }

        return false;
    }


function get_item_preview_value() {
  var response = {};
  response.treatment = $('.treatment_main textarea[name="treatment"]').val();
  response.staff = $('.treatment_main select[name="staff"]').val();
  return response;
}


function clear_item_preview_value() {
    
  var previewArea = $(".treatment_main");
  previewArea.find('textarea[name="treatment"]').val("");
  previewArea.find('select[name="staff"]').val("");

}

    function add_item_to_tbl(data, itemid, merge_invoice, bill_expense) {
  // If not custom data passed get from the preview
  data =
    typeof data == "undefined" || data == "undefined"
      ? get_item_preview_value()
      : data;

  if (data.treatment === "" || data.staff === "" ) {
    alert("Please make sure all required fields are filled out.");
    return;
  }

  
  
   var regex = /<br[^>]*>/gi;

var staffs = <?php echo json_encode($staff); ?>

  var table_row = "";
  var item_key = lastAddedItemKey
    ? (lastAddedItemKey += 1)
    : $("body").find("tbody .treatment").length + 1;
  lastAddedItemKey = item_key;

 table_row += '<tr class="item-tret">';


  table_row += '<td></td>';
  

    table_row +=
      '<td class="bold descriptions"><textarea name="newitems[' + item_key +'][treatment]" class="form-control" rows="5">' + data.treatment +
      "</textarea></td>";

    table_row += '<td><div class="row appoinment_dr_name"><div class="col-xl-7 col-lg-7 dr_name"><select name="newitems[' + item_key + '][staff]" class="form-control" onchange="alert(\'Are you sure you want to update the assignee?\')">';


    $.each(staffs, function(index, stf) {
                    var selected = (stf.staffid === data.staff) ? ' selected' : '';
        
                    table_row += '<option value="' + stf.staffid + '"' + selected + '>' + stf.firstname + ' ' + stf.lastname + '</option>';
    });

table_row += '</select></div></div></td>';

    table_row +=
      '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' +
      itemid +
      '); return false;"><i class="fa fa-trash"></i></a></td>';

    table_row += "</tr>";

    $("table.treatment tbody").append(table_row);

    $(document).trigger({
      type: "item-added-to-table",
      data: data,
      row: table_row,
    });

   

    $("body").find("#item-warnings").remove();
    $("body").find(".dt-loader").remove();

    clear_item_preview_value();

    $("#items_select1").selectpicker("val", "");

  return false;
}

    var deletedItems = [];

function delete_treatment(element, id) {
    
    if(confirm("Are you sure you want to delete the treatment?")){
        deletedItems.push(id);
        // Update the hidden input field
        document.getElementById('deleted_items').value = JSON.stringify(deletedItems);
    
        // Remove the row from the table
        $(element).closest('tr').remove();
    }
}


$(document).ready(function() {
        // Event handler for the "Start" button
        $(document).on('click', '.start-btn', function(event) {
            event.preventDefault(); // Prevent default action

            // Extract data attributes from the button
            const button = $(this);
            
            // Call the start_appointment function and pass the event
            start_appointment();
        });
    });


function start_appointment(event, appointment_id, staffid, id, action) {
    // Prevent the default action of the event
    
        if (event) {
        event.preventDefault();
    }

    var startButtonId = 'startAppointmentBtn_' + id;
    var endButtonId = 'endAppointmentBtn_' + id;

    if (action === 'start') {
        // Logic to handle starting the appointment
        document.getElementById(startButtonId).style.display = 'none';
        document.getElementById(endButtonId).style.display = 'block';
    } else if (action === 'end') {
        // Logic to handle ending the appointment
        document.getElementById(endButtonId).style.display = 'none';
    }
    
    // Confirm user action
    var userConfirmed = confirm("Are you sure you want to " + action + " treatment?");
    if (userConfirmed) {
        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url("appointly/appointments/update_assignee_start_end_time")?>', // Update with correct URL
            data: {
                appointment_id: appointment_id,
                staff_id: staffid,
                id: id,
                action: action
            },
            success: function(response) {
                
            },
            error: function(xhr, status, error) {
                // Handle errors here
                console.error('AJAX request failed:', status, error);
                alert('An error occurred: ' + error);
            }
        });
    }
}
        

         

//     function update_assignee_id($appointment_id, $staff_id, $id){

//         var userConfirmed = confirm("Are you sure you want to update the assignee?");
//         if (userConfirmed) {
//         // Make an AJAX request to update the staff ID
//         $.ajax({
//             url: '<?php echo admin_url("appointly/appointments/update_assignee_id")?>', // Replace with your server endpoint
//             type: 'POST',
//             data: {
//                 appointment_id: appointment_id,
//                 staff_id: staff_id,
//                 id: id
//             },
//             success: function(response) {
//                 // Handle the successful response here
//                 alert('Assignee updated successfully.');
//             },
//             error: function(xhr, status, error) {
//                 // Handle any errors here
//                 alert('Error updating assignee: ' + error);
//             }
//         });
//     }
// }

    // function update_assignee_id(selectElement) {
    //         var staff_id = selectElement.value;  // Get the selected staff ID
    //         var appointment_id = selectElement.getAttribute('data-appointment-id');
    //         var id = selectElement.getAttribute('data-item-id');

    //         var userConfirmed = confirm("Are you sure you want to update the assignee?");

    //         if (userConfirmed) {
    //             // Make an AJAX request to update the staff ID
    //             $.ajax({
    //                 url: '<?php echo admin_url("appointly/appointments/update_assignee_id")?>', // Replace with your server endpoint
    //                 type: 'POST',
    //                 data: {
    //                     appointment_id: appointment_id,
    //                     staff_id: staff_id,
    //                     id: id
    //                 },
    //                 success: function(response) {
    //                     // Handle the successful response here
    //                     alert('Assignee updated successfully.');
    //                 },
    //                 error: function(xhr, status, error) {
    //                     // Handle any errors here
    //                     alert('Error updating assignee: ' + error);
    //                 }
    //             });
    //         } else {
    //             // Optionally, handle UI changes if needed
    //         }
    //     }


</script>
