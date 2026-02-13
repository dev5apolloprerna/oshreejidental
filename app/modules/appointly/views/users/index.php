<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
/* ✅ Checkbox look inside select2 dropdown */
.select2-results__option::before{
    content:"☐";
    display:inline-block;
    width:18px;
    margin-right:8px;
    color:#666;
}
.select2-results__option[aria-selected="true"]::before{
    content:"☑";
    color:#1a73e8;
}
.select2-container--default .select2-selection--multiple {
    min-height: 38px;
    border: 1px solid #d8dde3;
    border-radius: 4px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice{
    margin-top: 6px;
}
</style>

<div id="wrapper">
    <div class="content">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">

                            <?php if (staff_can('create', 'appointments') || staff_appointments_responsible()) { ?>
                                <button type="button" class="btn btn-primary pull-right" onclick="openTypeModal()">
                                    <?= _l('appointments_type_add'); ?>
                                </button>

                                <label class="control-label font-medium"><?= _l('appointments_type_heading'); ?></label>
                                <hr>

                                <div class="clearfix"></div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="appointmentTypesTable">
                                        <thead>
                                            <tr>
                                                <th style="width:60px;">#</th>
                                                <th>Appointment Type</th>
                                                <th style="width:120px;">Color</th>
                                                <th>PDFs</th>
                                                <th style="width:140px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1; foreach (($types_rows ?? []) as $row) { ?>
                                                <tr id="aptype_row_<?= (int)$row['id']; ?>">
                                                    <td><?= $i++; ?></td>
                                                    <td><?= html_escape($row['type']); ?></td>
                                                    <td>
                                                        <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= html_escape($row['color']); ?>;border:1px solid #ddd;"></span>
                                                        <span class="mleft5"><?= html_escape($row['color']); ?></span>
                                                    </td>
                                                    <td><?= $row['pdf_names'] ? html_escape($row['pdf_names']) : '<span class="text-muted">-</span>'; ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-default btn-sm js-edit-type" data-id="<?= (int)$row['id']; ?>">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>

                                                        <?php if (staff_can('delete', 'appointments') || staff_appointments_responsible()) : ?>
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteType(<?= (int)$row['id']; ?>)">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal -->
<div id="typesModal" class="modal fade" role="dialog">
    <form id="appointmentTypeForm">
        <!-- ✅ CSRF -->
        <input type="hidden"
               name="<?php echo $this->security->get_csrf_token_name(); ?>"
               value="<?php echo $this->security->get_csrf_hash(); ?>">

        <!-- ✅ Edit id -->
        <input type="hidden" name="id" id="type_id" value="">

        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="typeModalTitle"><?= _l('appointments_type_add'); ?></h4>
                </div>

                <div class="modal-body">
                    <label class="control-label"><?= _l('appointments_type_add_name_label'); ?></label>
                    <input type="text" class="form-control mbot10" name="appointment_type" id="appointment_type" required>

                    <?php echo render_color_picker('color', _l('appointments_type_add_calendar_label')); ?>

                    <div class="form-group mt-3">
                        <label class="control-label">Select PDFs</label>
                        <select name="pdf_ids[]" id="pdf_ids" class="form-control" multiple="multiple">
                            <?php foreach (($all_pdfs ?? []) as $p) { ?>
                                <option value="<?= (int)$p['pdf_id']; ?>">
                                    <?= html_escape($p['pdf_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">Search + multiple select.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('save'); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php init_tail(); ?>
<?php require('modules/appointly/assets/js/user_settings_js.php'); ?>

<script>
/** ✅ helper */
function csrfInputSelector(){
  return 'input[name="<?= $this->security->get_csrf_token_name(); ?>"]';
}

/** ✅ init select2 once */
function initPdfSelect2(){
  if (typeof $.fn.select2 !== 'function') {
    console.error('Select2 not loaded. Check path assets/plugins/select2/');
    return;
  }
  if ($('#pdf_ids').hasClass('select2-hidden-accessible')) return;

  $('#pdf_ids').select2({
    placeholder: 'Select PDFs',
    width: '100%',
    closeOnSelect: false,
    allowClear: true,
    dropdownParent: $('#typesModal') // ✅ important for modal
  });
}

function openTypeModal(){
  $('#typeModalTitle').text('<?= _l('appointments_type_add'); ?>');
  $('#type_id').val('');
  $('#appointment_type').val('');
  $('#color').val('#3D9970').trigger('change');

  initPdfSelect2();
  $('#pdf_ids').val(null).trigger('change');

  $('#typesModal').modal('show');
}

$(function () {

  initPdfSelect2();

  // ✅ edit click
  $(document).on('click', '.js-edit-type', function(e){
    e.preventDefault();

    var id = $(this).data('id');
    if (!id) return;

    $('#typeModalTitle').text('Edit Appointment Type');
    $('#type_id').val(id);
    $('#appointment_type').val('');
    $('#color').val('#3D9970').trigger('change');

    initPdfSelect2();
    $('#pdf_ids').val(null).trigger('change');

    $('#typesModal').modal('show');

    $.get('<?= admin_url('appointly/appointments/get_appointment_type/'); ?>' + id, function(resp){
      var r = resp;
      if (typeof resp === 'string') {
        try { r = JSON.parse(resp); } catch (e) { r = null; }
      }
      if (!r || !r.success) {
        alert((r && r.message) ? r.message : 'Edit load failed');
        return;
      }

      $('#appointment_type').val(r.data.type || '');
      $('#color').val(r.data.color || '#3D9970').trigger('change');

      if (Array.isArray(r.data.pdf_ids)) {
        $('#pdf_ids').val(r.data.pdf_ids.map(String)).trigger('change');
      }
    });
  });

  // ✅ save add/edit
  $('#appointmentTypeForm').on('submit', function(e){
    e.preventDefault();

    $.post('<?= admin_url('appointly/appointments/save_appointment_type'); ?>', $(this).serialize(), function(resp){
      var r = resp;
      if (typeof resp === 'string') {
        try { r = JSON.parse(resp); } catch (e) { r = null; }
      }

      // ✅ refresh csrf if controller returns csrf_hash
      if (r && r.csrf_hash) {
        $(csrfInputSelector()).val(r.csrf_hash);
      }

      if (!r || !r.success) {
        alert((r && r.message) ? r.message : 'Save failed');
        return;
      }
      window.location.reload();
    });
  });

});

// ✅ delete (same as before)
function deleteType(id){
  if (!confirm('Are you sure?')) return;

  $.post('<?= admin_url('appointly/appointments/delete_appointment_type/'); ?>' + id, {}, function(resp){
    var r = resp;
    if (typeof resp === 'string') {
      try { r = JSON.parse(resp); } catch (e) { r = null; }
    }
    if (!r || !r.success) {
      alert('Delete failed');
      return;
    }
    $('#aptype_row_' + id).remove();
  });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</body>
</html>
