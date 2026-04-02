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
 
  function hasText(val) {
    return val !== undefined && val !== null && String(val).trim() !== "";
  }

  function cssEscapeSafe(value) {
    var raw = String(value || "");
    if (window.CSS && typeof window.CSS.escape === "function") return window.CSS.escape(raw);
    return raw.replace(/([\"'\\\[\]#.:>~*=\s])/g, "\\$1");
  }

  function extractSignatureValue(saved, party) {
    var keys = Object.keys(saved || {});
    for (var i = 0; i < keys.length; i++) {
      var key = String(keys[i] || "").toLowerCase();
      var val = saved[keys[i]];
      if (!hasText(val)) continue;
      if (!(key.indexOf("sign") !== -1 || key.indexOf("signature") !== -1)) continue;

      if (party === "doctor" && (key.indexOf("doctor") !== -1 || key.indexOf("consultant") !== -1 || key.indexOf("surgeon") !== -1 || key.indexOf("surgen") !== -1 || key.indexOf("dr") !== -1)) {
        return String(val).trim();
      }

      if (party === "patient" && (key.indexOf("patient") !== -1 || key.indexOf("relative") !== -1 || key.indexOf("guardian") !== -1 || key.indexOf("attendant") !== -1)) {
        return String(val).trim();
      }
    }
    return "";
  }

  function isSignatureImageValue(value) {
    var v = String(value || "").trim().toLowerCase();
    if (!v) return false;
    if (v.indexOf("data:image/") === 0) return true;
    if (/\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(v)) return true;
    return v.indexOf("/uploads/") !== -1 || v.indexOf("http://") === 0 || v.indexOf("https://") === 0;
  }

  function extractSignatureImageValue(saved, party) {
    var keys = Object.keys(saved || {});
    for (var i = 0; i < keys.length; i++) {
      var key = String(keys[i] || "").toLowerCase();
      var val = String(saved[keys[i]] || "").trim();
      if (!val) continue;
      if (!isSignatureContext(key)) continue;
      if (!isSignatureImageValue(val)) continue;

      if (party === "doctor" && isDoctorContext(key)) return val;
      if (party === "patient" && isPatientContext(key)) return val;
    }
    return "";
  }

  function getContextText(el) {
    var chunks = [];
    ["name", "id", "placeholder", "class", "alt", "title"].forEach(function (a) {
      chunks.push(String(el.getAttribute(a) || "").toLowerCase());
    });

    if (el.parentElement) chunks.push(String(el.parentElement.textContent || "").toLowerCase());
    var tr = el.closest("tr");
    if (tr) chunks.push(String(tr.textContent || "").toLowerCase());

    var prevLabel = el.closest("td") ? el.closest("td").previousElementSibling : null;
    if (prevLabel) chunks.push(String(prevLabel.textContent || "").toLowerCase());

    var id = String(el.getAttribute("id") || "").trim();
    var name = String(el.getAttribute("name") || "").trim();
    if (id) {
      var idLabel = document.querySelector('label[for="' + cssEscapeSafe(id) + '"]');
      if (idLabel) chunks.push(String(idLabel.textContent || "").toLowerCase());
    }
    if (name) {
      var nameLabel = document.querySelector('label[for="' + cssEscapeSafe(name) + '"]');
      if (nameLabel) chunks.push(String(nameLabel.textContent || "").toLowerCase());
    }

    return chunks.join(" ").trim();
  }

  function isDoctorContext(context) {
    return /\b(doctor|consultant|dr\.?|surgeon|surgen)\b/i.test(context || "");
  }

  function isPatientContext(context) {
    return context.indexOf("patient") !== -1
      || context.indexOf("relative") !== -1
      || context.indexOf("guardian") !== -1
      || context.indexOf("attendant") !== -1;
  }

  function isSignatureContext(context) {
    return /\b(sign|signature|signator|signatory)\b/i.test(context || "");
  }

  function getContextParty(context) {
    var hasDoctor = isDoctorContext(context);
    var hasPatient = isPatientContext(context);

    if (hasDoctor && hasPatient) return "";
    if (hasDoctor) return "doctor";
    if (hasPatient) return "patient";
    return "";
  }

  function fillPartyDefaultsByContext(saved, ctx) {
    var doctorName = String(ctx.doctor_name || saved.doctor_name || "").trim();
    var patientName = String(ctx.patient_name || saved.patient_name || "").trim();
    var doctorSign = extractSignatureValue(saved, "doctor") || doctorName;
    var patientSign = extractSignatureValue(saved, "patient") || patientName;
    var doctorSignImage = extractSignatureImageValue(saved, "doctor")
      || String(saved.doctor_signature_image || "").trim()
      || String(ctx.doctor_signature_image || "").trim();
    var patientSignImage = extractSignatureImageValue(saved, "patient")
      || String(saved.patient_signature_image || "").trim()
      || String(ctx.patient_signature_image || "").trim();

    if (doctorSignImage && patientSignImage && doctorSignImage === patientSignImage && !String(ctx.doctor_signature_image || "").trim()) {
      doctorSignImage = "";
    }

    if (!hasText(saved.doctor_signature) && hasText(doctorSign)) saved.doctor_signature = doctorSign;
    if (!hasText(saved.patient_signature) && hasText(patientSign)) saved.patient_signature = patientSign;
    if (!hasText(saved.doctor_signature_image) && hasText(doctorSignImage)) saved.doctor_signature_image = doctorSignImage;
    if (!hasText(saved.patient_signature_image) && hasText(patientSignImage)) saved.patient_signature_image = patientSignImage;

    document.querySelectorAll("input[name], input[id], textarea[name], textarea[id]").forEach(function (el) {
      if (el.type === "hidden" || el.type === "checkbox" || el.type === "radio") return;
      if (hasText(el.value)) return;

      var context = getContextText(el);
      if (!context) return;
      var party = getContextParty(context);
      if (!party) return;

      var fillValue = "";
      if (party === "doctor" && doctorName) {
        fillValue = isSignatureContext(context) ? (doctorSign || doctorName) : doctorName;
      } else if (party === "patient" && patientName) {
        fillValue = isSignatureContext(context) ? (patientSign || patientName) : patientName;
      }

      if (!fillValue) return;
      setOne(el, fillValue);
    });

    document.querySelectorAll("img").forEach(function (img) {
      var src = String(img.getAttribute("src") || "").trim();
      if (src) return;

      var context = getContextText(img);
      if (!context || !isSignatureContext(context)) return;
      var party = getContextParty(context);
      if (!party) return;

      if (party === "doctor" && doctorSignImage) {
        img.setAttribute("src", doctorSignImage);
        return;
      }

      if (party === "patient" && patientSignImage) {
        img.setAttribute("src", patientSignImage);
      }
    });
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
 
     var defaults = {
       patient_name: CTX.patient_name || "",
       doctor_name: CTX.doctor_name || "",
       today_date: todayDDMMYYYY()
     };
 
     Object.keys(defaults).forEach(function (k) {
      if (hasText(SAVED[k])) return;
 
      // fill by name
       var nodes = document.querySelectorAll('[name="' + k + '"]');
       if (nodes && nodes.length) {
         nodes.forEach(function (el) {
          // don't overwrite if user already typed
           if (el.value !== undefined && String(el.value).trim() !== "") return;
           setOne(el, defaults[k]);
         });
       }
 
       var byId = document.getElementById(k);
       if (byId) {
         if (byId.value !== undefined) {
           if (String(byId.value).trim() === "") setOne(byId, defaults[k]);
         } else {
           if (String(byId.textContent || "").trim() === "") setOne(byId, defaults[k]);
         }
       }
     });
 
    fillPartyDefaultsByContext(SAVED, CTX);

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
 
     var CTX = window.__NABH_CTX || {};
     if (!data.patient_name && CTX.patient_name) data.patient_name = CTX.patient_name;
     if (!data.doctor_name && CTX.doctor_name) data.doctor_name = CTX.doctor_name;
     if (!data.today_date) data.today_date = todayDDMMYYYY();
 
 var patientSignatureImg = document.querySelector('img[name="patient_signature_image"], img#patient_signature_image');
     var doctorSignatureImg = document.querySelector('img[name="doctor_signature_image"], img#doctor_signature_image');
     if (patientSignatureImg && hasText(patientSignatureImg.getAttribute('src'))) data.patient_signature_image = patientSignatureImg.getAttribute('src').trim();
     if (doctorSignatureImg && hasText(doctorSignatureImg.getAttribute('src'))) data.doctor_signature_image = doctorSignatureImg.getAttribute('src').trim();
     
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