(function () {
  function hasText(val) {
    return val !== undefined && val !== null && String(val).trim() !== "";
  }

  function todayDDMMYYYY() {
    var now = new Date();
    var dd = String(now.getDate()).padStart(2, "0");
    var mm = String(now.getMonth() + 1).padStart(2, "0");
    var yy = now.getFullYear();
    return dd + "/" + mm + "/" + yy;
  }

  function setOne(el, val) {
    if (!el) return;
    if (el.type === "checkbox") {
      el.checked = (val == 1 || val === true || val === "1");
    } else if (el.type === "radio") {
      el.checked = (String(el.value) === String(val));
    } else if (el.value !== undefined) {
      el.value = (val ?? "") + "";
    } else {
      el.textContent = (val ?? "") + "";
    }
  }

  function normalizeImageSrc(src) {
    src = String(src || "").trim();
    if (!src) return "";

    if (src.indexOf("data:image/") === 0) return src;
    if (src.indexOf("http://") === 0 || src.indexOf("https://") === 0) return src;

    if (src.charAt(0) === "/") {
      return window.location.origin + src;
    }

    return window.location.origin + "/" + src.replace(/^\/+/, "");
  }

  function extractSignatureImageValue(saved, party) {
    var keys = Object.keys(saved || {});
    for (var i = 0; i < keys.length; i++) {
      var originalKey = keys[i];
      var key = String(originalKey || "").toLowerCase();
      var val = String(saved[originalKey] || "").trim();
      if (!val) continue;

      var isSignatureKey = /\b(sign|signature|signator|signatory)\b/i.test(key);
      if (!isSignatureKey) continue;

      var isDoctor = /\b(doctor|consultant|dr\.?|surgeon|surgen)\b/i.test(key);
      var isPatient = /patient|relative|guardian|attendant/i.test(key);

      if (party === "doctor" && isDoctor) return val;
      if (party === "patient" && isPatient) return val;
    }
    return "";
  }

  function applySavedAndDefaults() {
    var CTX = window.__NABH_CTX || {};
    var SAVED = window.__NABH_SAVED || {};

    Object.keys(SAVED).forEach(function (k) {
      document.querySelectorAll('[name="' + k + '"]').forEach(function (el) {
        setOne(el, SAVED[k]);
      });
      setOne(document.getElementById(k), SAVED[k]);
    });

    if (!hasText(SAVED.patient_name) && hasText(CTX.patient_name)) {
      setOne(document.getElementById("patient_name"), CTX.patient_name);
    }

    if (!hasText(SAVED.doctor_name) && hasText(CTX.doctor_name)) {
      setOne(document.getElementById("doctor_name"), CTX.doctor_name);
    }

    if (!hasText(SAVED.today_date)) {
      setOne(document.getElementById("today_date"), todayDDMMYYYY());
    }

    var patientImgVal =
      extractSignatureImageValue(SAVED, "patient") ||
      String(SAVED.patient_signature_image || CTX.patient_signature_image || "").trim();

    var doctorImgVal =
      extractSignatureImageValue(SAVED, "doctor") ||
      String(SAVED.doctor_signature_image || CTX.doctor_signature_image || "").trim();

    var patientImg = document.getElementById("patient_signature_image");
    var doctorImg = document.getElementById("doctor_signature_image");

    if (patientImg && hasText(patientImgVal)) {
      patientImg.setAttribute("src", normalizeImageSrc(patientImgVal));
    }

    if (doctorImg && hasText(doctorImgVal)) {
      doctorImg.setAttribute("src", normalizeImageSrc(doctorImgVal));
    }

    var s = document.getElementById("saveStatus");
    if (s && Object.keys(SAVED).length) {
      s.textContent = "Loaded from DB";
    }
  }

  function collectFormData() {
    var data = {};

    document.querySelectorAll("input[name], textarea[name], select[name]").forEach(function (el) {
      if (el.type === "checkbox") {
        data[el.name] = el.checked ? 1 : 0;
      } else if (el.type === "radio") {
        if (el.checked) data[el.name] = el.value;
      } else {
        data[el.name] = el.value;
      }
    });

    var CTX = window.__NABH_CTX || {};

    if (!data.patient_name && CTX.patient_name) data.patient_name = CTX.patient_name;
    if (!data.doctor_name && CTX.doctor_name) data.doctor_name = CTX.doctor_name;
    if (!data.today_date) data.today_date = todayDDMMYYYY();

    var patientSignatureImg = document.getElementById("patient_signature_image");
    var doctorSignatureImg = document.getElementById("doctor_signature_image");

    if (patientSignatureImg && hasText(patientSignatureImg.getAttribute("src"))) {
      data.patient_signature_image = patientSignatureImg.getAttribute("src").trim();
    }

    if (doctorSignatureImg && hasText(doctorSignatureImg.getAttribute("src"))) {
      data.doctor_signature_image = doctorSignatureImg.getAttribute("src").trim();
    }

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
      lang: CTX.lang || "en",
      form_data: collectFormData()
    };

    var fd = new FormData();
    fd.append(CTX.csrf_name, CTX.csrf_hash);
    fd.append("payload", JSON.stringify(payload));

    var res = await fetch(CTX.admin_base + "nabh/save_submission", {
      method: "POST",
      body: fd
    });

    var json = await res.json();

    if (json.csrf_hash) {
      window.__NABH_CTX.csrf_hash = json.csrf_hash;
    }

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
    if (e.target && e.target.id === "submitBtn") {
      saveNow();
    }
  });
})();