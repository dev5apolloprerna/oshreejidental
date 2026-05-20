<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script src="<?php echo site_url('assets/plugins/signature-pad/signature_pad.min.js'); ?>"></script>
<style>
    .pat-sig-model.modal-lg {
    width: 350px;
}
.modal.fade.patient-confirm .modal-dialog {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) !important;
  margin: 0; 
  z-index: 1050;
}
.drawing-btn.btn-align.mt-3 button {
    margin-left: 5px;
}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<div class="modal fade patient-confirm" id="patientSignatureModal" tabindex="-1" role="dialog" aria-labelledby="notesModalLabel" aria-hidden="true">
  <div class="modal-dialog pat-sig-model modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h3 class="modal-title" id="signModalLabel">Patient Consent</h3>
      </div>

      <div class="modal-body">

        <?php echo form_open(admin_url('appointly/appointments/add_patient_signature/'), ['id' => 'signatureForm', 'class' => 'form-horizontal']); ?>

            <input type="hidden" name="appointment_id" id="appointment_sig" value="<?php echo $appointment_id; ?>">
            <input type="hidden" name="patient_id" id="cur_patientID" value="<?php echo $patientId; ?>">
            <div style="border: 1px solid #ccc; border-radius: 5px;">
          <canvas id="sig_board"></canvas>
          <input type="hidden" name="signature_value" id="signature_data">
      </div>
      <br>
        <input type="text" name="patient_name" class="form-control" placeholder="Patient Name">
        <br>
            <div class="drawing-btn btn-align mt-3">
                <button type="button" class="btn btn-primary" id="toggleEraser">Eraser</button>
                <button type="button" class="btn btn-primary" id="eraseAll">Clear</button>
                <!-- <button type="button" class="btn btn-primary" id="update_sign"  style="display: none;">Update</button> -->
                <button type="button" class="btn btn-primary" id="delete_sign"  style="display: none;">Delete</button>

                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        <?php echo form_close(); ?>
      </div>

    </div>
  </div>
</div>
<script>
  // function openSignatureModal(appointmentId, patientId) {
  //   document.getElementById('appointment_sig').value = appointmentId;
  //   document.getElementById('cur_patientID').value = patientId;
  //   $('#patientSignatureModal').modal('show');
  //   loadExistingSign(appointmentId);
  // }
    function openSignatureModal(appointmentId, patientId) {
    document.getElementById('appointment_sig').value = '';
    document.getElementById('cur_patientID').value = '';
    document.querySelector('input[name="patient_name"]').value = '';

    const canvas = document.getElementById("sig_board");
    if (canvas) {
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    document.getElementById("delete_sign").style.display = "none";

    document.getElementById('appointment_sig').value = appointmentId;
    document.getElementById('cur_patientID').value = patientId;

    $('#patientSignatureModal').modal('show');
    loadExistingSign(appointmentId);
}

</script>
<script>
  const canvas = document.getElementById("sig_board");
  const ctx = canvas.getContext("2d");
  let drawing = false;
  let isErasing = false;

  // Set initial pen settings
  ctx.lineWidth = 3;
  ctx.lineCap = "round";
  ctx.strokeStyle = "#000";

  // Toggle eraser
  document.getElementById("toggleEraser").addEventListener("click", function () {
    isErasing = !isErasing;
    ctx.strokeStyle = isErasing ? "#ffffff" : "#000"; // white = erase
    this.textContent = isErasing ? "Pen" : "Eraser";
    this.classList.toggle("btn-primary");
    this.classList.toggle("btn-primary");
  });

  canvas.addEventListener("mousedown", function (e) {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
  });

  canvas.addEventListener("mousemove", function (e) {
    if (drawing) {
      ctx.lineTo(e.offsetX, e.offsetY);
      ctx.stroke();
    }
  });

  canvas.addEventListener("mouseup", () => drawing = false);
  canvas.addEventListener("mouseleave", () => drawing = false);

  // Save to hidden input on submit
  document.getElementById("signatureForm").addEventListener("submit", function (e) {
  const signatureData = canvas.toDataURL("image/png");
  document.getElementById("signature_data").value = signatureData;
});


   document.getElementById("eraseAll").addEventListener("click", function() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.querySelector('input[name="patient_name"]').value = "";
  });

function loadExistingSign(appointmentId) {
    existApponID = appointmentId;
    $.ajax({
        url: "<?php echo admin_url('appointly/appointments/get_patient_signature/');?>" + appointmentId,
        type: "GET",
        dataType: "json",
        success: function(response) {
            
            if (response && response.signature_value && response.signature_value.length > 0) {
                document.getElementById("delete_sign").style.display = "inline-block";
                document.querySelector('input[name="patient_name"]').value = response.patient_name || '';
                var img = new Image();
                img.src = "data:image/png;base64," + response.signature_value;

                img.onload = function() {
                    const canvas = document.getElementById("sig_board");
                    const ctx = canvas.getContext("2d");

                    // Optional: Clear existing canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Draw the image on canvas
                    ctx.drawImage(img, 0, 0);
                };
            } 
        },
    });
}


document.getElementById("delete_sign").addEventListener("click", function () {
    var curAppointmentId = existApponID;

    if (!curAppointmentId) {
        alert("No appointment ID found.");
        return;
    }

    if (confirm("Are you sure you want to delete this?")) {
        $.ajax({
            url: "<?php echo admin_url('appointly/appointments/delete_patient_signature');?>",
            type: "POST",
            data: {
                appointment_id: curAppointmentId
            },
            success: function (res) {
                console.log("Delete Success:", res);
                alert("Signature deleted successfully!");

                // Optional: clear canvas or reset form after delete
                const canvas = document.getElementById("sig_board");
                if (canvas) {
                    const ctx = canvas.getContext("2d");
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }

                document.querySelector('input[name="patient_name"]').value = '';
                document.getElementById("delete_sign").style.display = "none";
                $('#patientSignatureModal').modal('hide'); 
            },
            error: function () {
                alert("Error deleting signature.");
            }
        });
    }
});

</script>






