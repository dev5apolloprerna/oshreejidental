(function () {
  function setOne(el, val) {
    if (!el) return;
    if (el.type === "checkbox") el.checked = (val == 1 || val === true || val === "1");
    else if (el.type === "radio") el.checked = (String(el.value) === String(val));
    else if (el.value !== undefined) el.value = (val ?? "") + "";
    else el.textContent = (val ?? "") + "";
  }

  function todayDDMMYYYY() {
    var now = new Date();
    var dd = String(now.getDate()).padStart(2, "0");
    var mm = String(now.getMonth() + 1).padStart(2, "0");
    var yy = now.getFullYear();
    return dd + "/" + mm + "/" + yy;
  }

  function applySavedAndDefaults() {
    var CTX = window.__NABH_CTX || {};
    var SAVED = window.__NABH_SAVED || {};

    // 1) fill everything from SAVED first
    Object.keys(SAVED).forEach(function (k) {
      document.querySelectorAll('[name="' + k + '"]').forEach(function (el) {
        setOne(el, SAVED[k]);
      });
      setOne(document.getElementById(k), SAVED[k]);
    });

    // 2) apply defaults ONLY if not present in SAVED
    var defaults = {
      patient_name: CTX.patient_name || "",
      doctor_name: CTX.doctor_name || "",
      today_date: todayDDMMYYYY()
    };

    Object.keys(defaults).forEach(function (k) {
      if (SAVED[k] !== undefined && SAVED[k] !== null && String(SAVED[k]).trim() !== "") return;

      // fill by name
      var nodes = document.querySelectorAll('[name="' + k + '"]');
      if (nodes && nodes.length) {
        nodes.forEach(function (el) {
          // don't overwrite if user already typed
          if (el.value !== undefined && String(el.value).trim() !== "") return;
          setOne(el, defaults[k]);
        });
      }

      // fill by id (spans/divs)
      var byId = document.getElementById(k);
      if (byId) {
        if (byId.value !== undefined) {
          if (String(byId.value).trim() === "") setOne(byId, defaults[k]);
        } else {
          if (String(byId.textContent || "").trim() === "") setOne(byId, defaults[k]);
        }
      }
    });

    var s = document.getElementById("saveStatus");
    if (s && Object.keys(SAVED).length) s.textContent = "Loaded from DB";
  }

  function collectFormData() {
    var data = {};
    document.querySelectorAll("input[name], textarea[name], select[name]").forEach(function (el) {
      if (el.type === "checkbox") data[el.name] = el.checked ? 1 : 0;
      else if (el.type === "radio") { if (el.checked) data[el.name] = el.value; }
      else data[el.name] = el.value;
    });

    // ensure defaults are included even if they are spans (no input)
    var CTX = window.__NABH_CTX || {};
    if (!data.patient_name && CTX.patient_name) data.patient_name = CTX.patient_name;
    if (!data.doctor_name && CTX.doctor_name) data.doctor_name = CTX.doctor_name;
    if (!data.today_date) data.today_date = todayDDMMYYYY();

    // if spans exist, take from them
    ["patient_name", "doctor_name", "today_date"].forEach(function (k) {
      if (data[k]) return;
      var el = document.getElementById(k);
      if (el && (el.textContent || "").trim()) data[k] = el.textContent.trim();
    });

    return data;
  }

  async function saveNow() {
    var CTX = window.__NABH_CTX || {};
    if (!CTX.admin_base || !CTX.csrf_name || !CTX.csrf_hash) {
      alert("Missing admin base/csrf (open form via controller URL)");
      return;
    }

    var payload = {
      nabh_pdf_id: parseInt(CTX.pdf_id || 0, 10),
      appointment_id: parseInt(CTX.appointment_id || 0, 10),
      appointment_type_id: parseInt(CTX.appointment_type_id || 0, 10),
      patient_id: parseInt(CTX.patient_id || 0, 10),
      doctor_id: parseInt(CTX.doctor_id || 0, 10),
      lang: CTX.lang || "gu",
      form_data: collectFormData()
    };

    var fd = new FormData();
    fd.append(CTX.csrf_name, CTX.csrf_hash);
    fd.append("payload", JSON.stringify(payload));

    var res = await fetch(CTX.admin_base + "nabh/save_submission", { method: "POST", body: fd });
    var json = await res.json();

    if (json.csrf_hash) window.__NABH_CTX.csrf_hash = json.csrf_hash;

    var s = document.getElementById("saveStatus");
    if (json.status) {
      if (s) s.textContent = "Saved";
      alert(json.message || "Saved");
    } else {
      if (s) s.textContent = "Save failed";
      alert(json.message || "Save failed");
    }
  }

  document.addEventListener("DOMContentLoaded", applySavedAndDefaults);
  document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "submitBtn") saveNow();
  });
})();
