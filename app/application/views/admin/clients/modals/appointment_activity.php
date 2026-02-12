<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Contact -->
<div class="modal fade" id="appointment_activity" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
           
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                        <div class="tw-flex">
                           
                            <div>
                                <h4 class="modal-title tw-mb-0">Appointment History</h4>
                            </div>
                        </div>
            </div>
            <div class="modal-body">
                <div class="row">
                   <div class="col-md-12">
                      <div class="activity-feed" id="activity-feed"> 
                         
                         
                      </div>
                   </div>
                </div>  
            </div>
        </div>
    </div>
</div>
<?php if (!isset($contact)) { ?>
<?php 

} ?>


<script>



    function show_activitY_modal(id){

        $('#activity-feed').html('');

         requestGetJSON("<?php echo admin_url('appointly/appointments/get_appointment_assign_log/')?>" + id).done(function (response) {




            var html = ''; // Initialize an empty string to store the HTML content

            if(response.length > 0){


                // Iterate over each item in the response using forEach loop
                response.forEach(function(item) {

                    var date = new Date(item.created_date);

                    const date1 = new Date(item.start_date_time);
                    const date2 = new Date(item.end_date_time);

                   

                    // Calculate the difference in milliseconds
                    const diffInMs = Math.abs(date2 - date1);

                    // Calculate the difference in days, hours, and minutes
                    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
                    const diffInHours = Math.floor((diffInMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

                // Format the date with AM/PM
                   var formattedDate = date.toLocaleString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });

                    if(item.start_date_time != '0000-00-00 00:00:00' && item.end_date_time != '0000-00-00 00:00:00'){



                    }


                    html += '<div class="feed-item" data-sale-activity-id="' + item.id + '">';
                    html += '<div class="date"><span class="text-has-action" data-toggle="tooltip" data-title="' + item.date + '" data-original-title="" title="">' + formattedDate + '</span></div>';


                    if(item.start_date_time != '0000-00-00 00:00:00' && item.end_date_time != '0000-00-00 00:00:00'){

                         html += '<div class="text"><img src="' + item.profile_image + '" class="staff-profile-xs-image pull-left mright5">' + item.firstname + ' ' +  item.lastname + ' - Time Logged (' + diffInHours + ' Hours '+ Math.floor(diffInMs / (1000 * 60)) + ' Minutes)' + '</div>';

                       

                    }else{

                         html += '<div class="text"><img src="' + item.profile_image + '" class="staff-profile-xs-image pull-left mright5">' + item.firstname + ' ' +  item.lastname + ' - Time Logged (' + 0 + ' Hours '+ Math.floor(0 / (1000 * 60)) + ' Minutes)' + '</div>';

                    }

                   
                    html += '</div>';
                });


            }else{

                html = '<h4 style="text-align: center;color: black;">Not found</h4>';

            }

    

        // Append the generated HTML to the desired container
        $('#activity-feed').append(html);
        
        $("#appointment_activity").modal();

    });
}

    function update_assginee(staffid,appointment_id){


         var userConfirmed = confirm("Are you sure you want to update the assignee?");
        if (userConfirmed) {

             if(staffid){
            $.ajax ({
                type: 'POST',
                url: '<?php echo admin_url("appointly/appointments/update_assignee")?>',
                data: { appointment_id:appointment_id,staffid:staffid},
                success : function(htmlresponse) {
                    window.location.reload();
                }
            });
        }

        }
     

       

    }

    function start_appointment(appointment_id,staffid,action){

         requestGetJSON("<?php echo admin_url('appointly/appointments/check_appo_assign/')?>" + appointment_id).done(function (response) {

            if(response.length > 0){

                var userConfirmed = confirm("Are you sure you want to start treatment?");
                if (userConfirmed) {

                    $.ajax ({
                        type: 'POST',
                        url: '<?php echo admin_url("appointly/appointments/update_assignee_start_end_time")?>',
                        data: { appointment_id:appointment_id,staff_id:staffid,action:action},
                        success : function(htmlresponse) {
                            window.location.reload();
                        }
                    });
                }
            }else{

                alert('Please assign staff');return;
            }

         });


    }


  
        
   
</script>
