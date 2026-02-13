<link href="<?= base_url('assets/plugins/select2/css/select2.min.css'); ?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/select2/js/select2.min.js'); ?>"></script>

<script>
 
    /* function delete_appointment_type(id) {
          var url = "<?= admin_url('appointly/appointments/delete_appointment_type'); ?>";
          if (confirm("<?= _l("confirm_action_prompt"); ?>")) {
               $.post(url, {
                    id: id
               }).done(function(r) {
                    if (r.success == true) {
                         alert_float('success', "<?= _l('appointments_type_deleted_successfully'); ?>");
                         $('.mright20#aptype_' + id).fadeOut();
                    }
               })
          }
     }*/

     appValidateForm($("#appointmentNewTypeForm"), {
          appointment_type: "required",
          color: "required",
     });

     $('body').on('submit', '#appointmentNewTypeForm', function() {
          var app_type = $('input[name="appointment_type"]').val();
          var color_type = $('input[name="color"]').val();

          var url = "<?= admin_url('appointly/appointments/new_appointment_type'); ?>";

          $.post(url, {
               type: app_type,
               color: color_type,
               beforeSend() {
                    $('button[type="submit"], button.close_btn').prop('disabled', true);
                    $('button[type="submit"]').html('<i class="fa fa-refresh fa-spin fa-fw"></i>');
               }
          }).done(function(r) {
               if (r.success == true) {
                    setTimeout(function() {
                         location.reload()
                    }, 1500);
                    alert_float('success', "<?= _l('appointments_type_added_successfully'); ?>");
               }
          });
          return false;
     });


function editType(id) {
    $.get('<?= admin_url('appointly/appointments/get_appointment_type/'); ?>' + id, function(resp) {
        var r = JSON.parse(resp);
        if (!r.success) {
            alert(r.message || 'Failed');
            return;
        }
        var d = r.data;

        $('#type_id').val(d.id);
        $('#appointment_type').val(d.type);
        $('#color').val(d.color).trigger('change');

        $('#pdf_ids').selectpicker('deselectAll');
        if (Array.isArray(d.pdf_ids) && d.pdf_ids.length) {
            $('#pdf_ids').selectpicker('val', d.pdf_ids.map(String));
        }

        $('#typeModalTitle').text('Edit Appointment Type');
        $('#typeModal').modal('show');
    });
}


$('#appointmentTypeForm').on('submit', function(e) {
  e.preventDefault();

  var url = '<?= admin_url('appointly/appointments/save_appointment_type'); ?>';

  $.post(url, $(this).serialize(), function(resp) {
    var r = JSON.parse(resp);

    // ✅ refresh csrf token in form
    if (r.csrf_hash) {
      $('input[name="<?= $this->security->get_csrf_token_name(); ?>"]').val(r.csrf_hash);
    }

    if (!r.success) {
      alert(r.message || 'Failed to save');
      return;
    }

    window.location.reload();
  });
});



function deleteType(id) {
    if (!confirm('Are you sure?')) return;

    $.post('<?= admin_url('appointly/appointments/delete_appointment_type/'); ?>' + id, {}, function(resp) {
        var r = JSON.parse(resp);
        if (!r.success) {
            alert('Delete failed');
            return;
        }
        $('#aptype_row_' + id).remove();
    });
}
</script>

