<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="panel_s">
      <div class="panel-body">
        <h4 class="no-margin">Patient Consent Signature</h4>
        <hr class="hr-panel-heading" />

        <?php echo form_open(admin_url('appointly/appointments/add_patient_signature/'), ['id' => 'signatureForm', 'class' => 'form-horizontal']); ?>
          <input type="hidden" name="appointment_id" id="appointment_sig" value="<?php echo (int) ($appointment_id ?? 0); ?>">
          <input type="hidden" name="patient_id" id="cur_patientID" value="<?php echo (int) ($patient_id ?? 0); ?>">

          <div style="border:1px solid #ccc;border-radius:5px;max-width:700px;">
            <canvas id="sig_board" width="700" height="220" style="width:100%;height:220px;"></canvas>
            <input type="hidden" name="signature_value" id="signature_data">
          </div>
          <div id="existingSignatureWrap" style="display:none;max-width:700px;margin-top:10px;">
            <label style="font-weight:600;">Existing Signature</label>
            <div style="border:1px dashed #ccc;border-radius:5px;padding:8px;background:#fafafa;">
              <img id="existingSignaturePreview" src="" alt="Existing Signature" style="max-width:100%;height:120px;object-fit:contain;">
            </div>
          </div>
          <br>
          <div style="max-width:700px;">
            <input type="text" name="patient_name" class="form-control" placeholder="Patient Name">
          </div>
          <br>
          <div class="drawing-btn">
            <button type="button" class="btn btn-default" id="toggleEraser">Eraser</button>
            <button type="button" class="btn btn-default" id="eraseAll">Clear</button>
            <button type="button" class="btn btn-danger" id="delete_sign" style="display:none;">Delete</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
<script>
  var existApponID = <?php echo (int) ($appointment_id ?? 0); ?>;
    var initialSignaturePayload = <?php echo json_encode($existing_signature ?? null); ?>;
  const canvas = document.getElementById('sig_board');
  const ctx = canvas.getContext('2d');
  let drawing = false;
  let isErasing = false;

  ctx.lineWidth = 3;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#000';

  document.getElementById('toggleEraser').addEventListener('click', function () {
    isErasing = !isErasing;
    ctx.strokeStyle = isErasing ? '#ffffff' : '#000';
    this.textContent = isErasing ? 'Pen' : 'Eraser';
  });

  canvas.addEventListener('mousedown', function (e) {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
  });
  canvas.addEventListener('mousemove', function (e) {
    if (!drawing) return;
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.stroke();
  });
  canvas.addEventListener('mouseup', () => drawing = false);
  canvas.addEventListener('mouseleave', () => drawing = false);

  canvas.addEventListener('touchstart', function (e) {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
  }, { passive: false });

  canvas.addEventListener('touchmove', function (e) {
    e.preventDefault();
    if (!drawing) return;
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
    ctx.stroke();
  }, { passive: false });

  canvas.addEventListener('touchend', function (e) {
    e.preventDefault();
    drawing = false;
  }, { passive: false });


  document.getElementById('signatureForm').addEventListener('submit', function () {
    document.getElementById('signature_data').value = canvas.toDataURL('image/png');
  });

  document.getElementById('eraseAll').addEventListener('click', function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.querySelector('input[name="patient_name"]').value = '';
        document.getElementById('existingSignatureWrap').style.display = 'none';
    document.getElementById('existingSignaturePreview').src = '';
  });

    function renderSignature(response) {
    if (!(response && response.signature_value && response.signature_value.length > 0)) return;

    document.getElementById('delete_sign').style.display = 'inline-block';
    document.querySelector('input[name="patient_name"]').value = response.patient_name || '';

    var img = new Image();
    var rawSignature = (response.signature_value || '').toString().replace('[removed]', '').trim();
    var normalizedSignature = rawSignature;

    if (normalizedSignature.indexOf('data:image') !== 0 && normalizedSignature.indexOf(',') !== -1) {
      normalizedSignature = normalizedSignature.split(',').pop().trim();
    }

    if (normalizedSignature.indexOf('data:image') !== 0) {
      normalizedSignature = 'data:image/png;base64,' + normalizedSignature;
    }

    img.src = normalizedSignature;
    document.getElementById('existingSignaturePreview').src = normalizedSignature;
    document.getElementById('existingSignatureWrap').style.display = 'block';
    img.onload = function () {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };
  }

    function loadExistingSign(appointmentId) {
    if (!appointmentId) return;
    $.ajax({
      url: "<?php echo admin_url('appointly/appointments/get_patient_signature/'); ?>" + appointmentId,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
      
        renderSignature(response);
      }
    });
  }

  document.getElementById('delete_sign').addEventListener('click', function () {
    if (!existApponID) {
      alert('No appointment ID found.');
      return;
    }

    if (!confirm('Are you sure you want to delete this?')) return;

    $.ajax({
      url: "<?php echo admin_url('appointly/appointments/delete_patient_signature'); ?>",
      type: 'POST',
      data: { appointment_id: existApponID },
      success: function () {
        alert('Signature deleted successfully!');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.querySelector('input[name="patient_name"]').value = '';
        document.getElementById('delete_sign').style.display = 'none';
                document.getElementById('existingSignatureWrap').style.display = 'none';
        document.getElementById('existingSignaturePreview').src = '';
      },
      error: function () {
        alert('Error deleting signature.');
      }
    });
  });

  if (initialSignaturePayload) {
    renderSignature(initialSignaturePayload);
  } else {
    loadExistingSign(existApponID);
  }
</script>
<?php init_tail(); ?>
