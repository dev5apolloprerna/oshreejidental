<script>
  // ✅ DB saved data injected server-side (works always)
  window.__DB_FORM__ = <?php echo json_encode($form_data, JSON_UNESCAPED_UNICODE); ?>;

  // ✅ fallback values from URL/query
  window.__BASE_FIELDS__ = {
    patient_name: <?php echo json_encode($patient_name); ?>,
    doctor_name:  <?php echo json_encode($doctor_name); ?>
  };
</script>

<script>
  function setField(name, value) {
    // supports: <input name="x">, <textarea name="x">, <select name="x">
    var nodes = document.querySelectorAll('[name="' + name + '"]');
    if (!nodes.length) return;

    nodes.forEach(function (el) {
      if (el.type === "checkbox") {
        el.checked = (value == 1 || value === true || value === "1");
      } else if (el.type === "radio") {
        el.checked = (String(el.value) === String(value));
      } else {
        el.value = (value ?? "") + "";
      }
    });
  }

  function fillFromDbDynamic(dbData, maxInputs) {
    dbData = dbData || {};

    // ✅ fixed fields first
    setField("patient_name", dbData.patient_name ?? window.__BASE_FIELDS__.patient_name ?? "");
    setField("doctor_name",  dbData.doctor_name  ?? window.__BASE_FIELDS__.doctor_name  ?? "");

    // ✅ dynamic inputs: input1..inputN
    for (var i = 1; i <= maxInputs; i++) {
      var key = "input" + i;
      if (dbData.hasOwnProperty(key)) {
        setField(key, dbData[key]);
      }
    }

    // ✅ if you also have checkbox1..checkboxN
    for (var j = 1; j <= maxInputs; j++) {
      var ck = "checkbox" + j;
      if (dbData.hasOwnProperty(ck)) {
        setField(ck, dbData[ck]);
      }
    }

    // ✅ if you also have radio groups like radio1, radio2 etc (value stored)
    for (var r = 1; r <= maxInputs; r++) {
      var rk = "radio" + r;
      if (dbData.hasOwnProperty(rk)) {
        setField(rk, dbData[rk]);
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    // ✅ Set maxInputs big enough for all forms (same JS works everywhere)
    fillFromDbDynamic(window.__DB_FORM__, 300); // change 300 if needed
  });
</script>
