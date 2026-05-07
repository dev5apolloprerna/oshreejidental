<script>
     $(document).ready(function() {

          if (isOutlookLoggedIn()) {
               acquireTokenPopupAndCallMSGraph();
          }

          var div_name = $('#div_name');
          var div_email = $('#div_email');
          var div_phone = $('#div_phone');

            function getSelectedAppointmentTypeText() {
               var typeSelect = document.getElementById('appointment_select_type');
               if (!typeSelect || !typeSelect.value) {
                    return '';
               }

               var selectedText = typeSelect.options[typeSelect.selectedIndex]
                    ? typeSelect.options[typeSelect.selectedIndex].text
                    : '';

               return selectedText ? selectedText.trim() : '';
          }

          function buildSubjectFromTypeAndName(fullName) {
               var cleanName = (fullName || '').trim();
               if (!cleanName) {
                    return '';
               }

               var appointmentTypeText = getSelectedAppointmentTypeText();
               return appointmentTypeText ? (appointmentTypeText + ' for ' + cleanName) : cleanName;
          }


          init_editor('textarea[name="notes"]', {
               menubar: false,
          });

          init_selectpicker();
          initAppointmentScheduledDates();

          $('.modal').on('hidden.bs.modal', function(e) {
               let accessToken = document.getElementById('ms-access-token');
               if (accessToken != null) accessToken.value = '';
               $('.xdsoft_datetimepicker').remove();
          });

          appValidateForm($("#appointment-form"), {
                appointment_select_type:"required",
               subject: "required",
               // description: "required",
               date: "required",
               name: "required",
               //email: "required",
               dob: "required",
              // pt_address: "required",
               'attendees[]': {
                    required: true,
                    minlength: 1
               }
          }, apply_appointments_form_data, {
               'attendees[]': "Please select at least 1 staff member"
          });
            $('body').on('change', '#appointment_select_type', function() {
               var fullName = $('#name').val() || '';
               if (fullName.trim() !== '') {
                    $('#subject').val(buildSubjectFromTypeAndName(fullName));
               }
          });

     });

     function apply_appointments_form_data(form) {
          $('button[type="submit"], button.close_btn, #addToOutlookBtn').prop('disabled', true);
          $('#appointment-form .modal-body').addClass('filterBlur');
          $('.modal-title').html(
               "<?= _l('appointment_please_wait'); ?>");
          $('button[type="submit"]').html('<i class="fa fa-refresh fa-spin fa-fw"></i>');

          var formSerializedData = $(form).serializeArray();

          var isOutlookChecked = document.getElementById('outlook') || null;

          var data = $(form).serialize();
          var url = form.action;

          $.post(url, data).done(function(response) {
               if (response.result) {
                    if (isOutlookLoggedIn() && isOutlookChecked != null) {
                         outlookAddOrUpdateEvent(formSerializedData);
                    } else {
                         alert_float('success',
                              "<?= _l("appointment_updated"); ?>"
                         );
                         setTimeout(() => {
                              window.location.reload();
                         }, 500);
                    }
               }
          });
          return false;
     }

     function addEventToOutlookCalendar(button, appointment_id) {
          var formSerializedData = $('#appointment-form').serializeArray();
          addToOutlookNewEventFromUpdate(formSerializedData, appointment_id);
     }

     function addEventToGoogleCalendar(button) {
          var form = $('#appointment-form').serialize();
          var url =
               "<?= admin_url('appointly/appointments/addEventToGoogleCalendar'); ?>";
          var modalBody = $('#appointment-form .modal-body');
          $.ajax({
               url: url,
               type: "POST",
               data: form,
               beforeSend: function() {
                    $(button).attr('disabled', true);
                    $('.modal .btn').attr('disabled', true);
                    modalBody.addClass('filterBlur');
                    $(button).html('' + appointly_please_wait +
                         '<i class="fa fa-refresh fa-spin fa-fw"></i>');
               },
               success: function(r) {
                    if (r.result == 'success') {
                         alert_float('success', r.message);
                         $('.modal').modal('hide');
                         $('.table-appointments').DataTable().ajax.reload();
                         modalBody.removeClass('filterBlur');
                    } else if (r.result == 'error') {
                         alert_float('danger', r.message);
                         modalBody.removeClass('filterBlur');
                    }
               }
          });
     }
</script>