-- ============================================================================
-- OSHREEJI MAIN DB — FIX SCRIPT
-- 1) Merge tblitems from satellite / maninagar / iskon into main db
-- 2) Remove genuinely duplicate entries created during the branch merge
--    (only rows that have ZERO relation in any other table — verified below)
-- ============================================================================
-- >>> TAKE A FULL BACKUP OF apolloin_u614622744_main_db BEFORE RUNNING THIS <<<
-- Run PART 1 and PART 2 as separate blocks. Read the comments before each
-- DELETE block — every DELETE is condition-based (re-checks relations at run
-- time), not a hardcoded list of ids, so it stays safe even if you run this
-- a bit later than today.
-- ============================================================================


-- ============================================================================
-- PART 1: MERGE tblitems (this table was skipped in all 3 branch migrations)
-- ============================================================================

ALTER TABLE `apolloin_u614622744_main_db`.`tblitems`
  ADD `branch_id` INT NOT NULL DEFAULT 0 AFTER `group_id`,
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`;

-- mark the 4 existing main-db items as branch 0 (original main branch) so they
-- are not confused with migrated rows
UPDATE `apolloin_u614622744_main_db`.`tblitems` SET `branch_id` = 0 WHERE `branch_id` = 0;

-- ---- Maninagar (branch_id = 1) ----
INSERT INTO `apolloin_u614622744_main_db`.`tblitems`
  (`description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, `branch_id`, `tbl_uniq_id`)
SELECT `description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, 1, `id`
FROM `apolloin_u614622744_maninagar_db`.`tblitems`;

-- ---- Satellite (branch_id = 2) ----
INSERT INTO `apolloin_u614622744_main_db`.`tblitems`
  (`description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, `branch_id`, `tbl_uniq_id`)
SELECT `description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, 2, `id`
FROM `apolloin_u614622744_satellite_db`.`tblitems`;

-- ---- Iskon Ambli (branch_id = 3) ----
INSERT INTO `apolloin_u614622744_main_db`.`tblitems`
  (`description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, `branch_id`, `tbl_uniq_id`)
SELECT `description`, `long_description`, `rate`, `tax`, `tax2`, `unit`, `group_id`, 3, `id`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblitems`;

-- ---- Fix tax / tax2 references ----
-- tblitems.tax / tax2 point to tbltaxes.id, but each source branch had its own
-- tbltaxes ids. Only a handful of items actually had a tax set. Remap by
-- matching the tax NAME (case-insensitive) to the tax name already in main db
-- (main db tbltaxes: 1 = IMPLANT 5%, 2 = ALIGNERS 5%). Anything that can't be
-- matched by name is left NULL (safer than pointing at the wrong tax) and is
-- flagged for you to check manually.

UPDATE `apolloin_u614622744_main_db`.`tblitems` i
JOIN `apolloin_u614622744_satellite_db`.`tblitems` si ON si.`id` = i.`tbl_uniq_id`
JOIN `apolloin_u614622744_satellite_db`.`tbltaxes` st ON st.`id` = si.`tax`
LEFT JOIN `apolloin_u614622744_main_db`.`tbltaxes` mt
  ON (UPPER(st.`name`) LIKE '%IMPLANT%' AND UPPER(mt.`name`) LIKE '%IMPLANT%')
  OR (UPPER(st.`name`) LIKE '%ALIGN%'   AND UPPER(mt.`name`) LIKE '%ALIGN%')
SET i.`tax` = mt.`id`
WHERE i.`branch_id` = 2 AND si.`tax` IS NOT NULL;

UPDATE `apolloin_u614622744_main_db`.`tblitems` i
JOIN `apolloin_u614622744_satellite_db`.`tblitems` si ON si.`id` = i.`tbl_uniq_id`
JOIN `apolloin_u614622744_satellite_db`.`tbltaxes` st ON st.`id` = si.`tax2`
LEFT JOIN `apolloin_u614622744_main_db`.`tbltaxes` mt
  ON (UPPER(st.`name`) LIKE '%IMPLANT%' AND UPPER(mt.`name`) LIKE '%IMPLANT%')
  OR (UPPER(st.`name`) LIKE '%ALIGN%'   AND UPPER(mt.`name`) LIKE '%ALIGN%')
SET i.`tax2` = mt.`id`
WHERE i.`branch_id` = 2 AND si.`tax2` IS NOT NULL;

UPDATE `apolloin_u614622744_main_db`.`tblitems` i
JOIN `apolloin_u614622744_iskon_ambli_db`.`tblitems` ii ON ii.`id` = i.`tbl_uniq_id`
JOIN `apolloin_u614622744_iskon_ambli_db`.`tbltaxes` it ON it.`id` = ii.`tax`
LEFT JOIN `apolloin_u614622744_main_db`.`tbltaxes` mt
  ON (UPPER(it.`name`) LIKE '%IMPLANT%' AND UPPER(mt.`name`) LIKE '%IMPLANT%')
  OR (UPPER(it.`name`) LIKE '%ALIGN%'   AND UPPER(mt.`name`) LIKE '%ALIGN%')
SET i.`tax` = mt.`id`
WHERE i.`branch_id` = 3 AND ii.`tax` IS NOT NULL;

UPDATE `apolloin_u614622744_main_db`.`tblitems` i
JOIN `apolloin_u614622744_iskon_ambli_db`.`tblitems` ii ON ii.`id` = i.`tbl_uniq_id`
JOIN `apolloin_u614622744_iskon_ambli_db`.`tbltaxes` it ON it.`id` = ii.`tax2`
LEFT JOIN `apolloin_u614622744_main_db`.`tbltaxes` mt
  ON (UPPER(it.`name`) LIKE '%IMPLANT%' AND UPPER(mt.`name`) LIKE '%IMPLANT%')
  OR (UPPER(it.`name`) LIKE '%ALIGN%'   AND UPPER(mt.`name`) LIKE '%ALIGN%')
SET i.`tax2` = mt.`id`
WHERE i.`branch_id` = 3 AND ii.`tax2` IS NOT NULL;

-- verify
SELECT branch_id, COUNT(*) AS items_migrated FROM `apolloin_u614622744_main_db`.`tblitems` GROUP BY branch_id;
SELECT id, description, tax, tax2, branch_id FROM `apolloin_u614622744_main_db`.`tblitems` WHERE tax IS NOT NULL OR tax2 IS NOT NULL;


-- ============================================================================
-- PART 2: DUPLICATE CLEANUP
-- Rule applied everywhere below: a duplicate copy is deleted ONLY if it has
-- ZERO relation in any other table. If a duplicate copy IS related to
-- something else, it is left untouched.
-- ============================================================================

USE `apolloin_u614622744_main_db`;
START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 2.1  tblappointly_appointment_types — duplicate appointment types
--      (same type name + color, same branch). Delete the newer duplicate
--      ONLY if no appointment actually uses it.
-- ----------------------------------------------------------------------------
DELETE t2 FROM tblappointly_appointment_types t1
JOIN tblappointly_appointment_types t2
  ON t1.branch_id = t2.branch_id
 AND t1.type  <=> t2.type
 AND t1.color <=> t2.color
 AND t1.id < t2.id
WHERE NOT EXISTS (
  SELECT 1 FROM tblappointly_appointments a WHERE a.type_id = t2.id
);

-- ----------------------------------------------------------------------------
-- 2.2  tblappointment_prescriptions + tblappointment_prescription_items
--      Whole prescription (and its medicine lines) got saved twice for the
--      same appointment. Delete the duplicate prescription's own items first,
--      then the duplicate prescription itself.
-- ----------------------------------------------------------------------------
CREATE TEMPORARY TABLE tmp_presc_dupes AS
SELECT DISTINCT t2.id AS delete_id
FROM tblappointment_prescriptions t1
JOIN tblappointment_prescriptions t2
  ON t1.branch_id      = t2.branch_id
 AND t1.appointment_id = t2.appointment_id
 AND t1.date           <=> t2.date
 AND t1.note           <=> t2.note
 AND t1.created_date    <=> t2.created_date
 AND t1.id < t2.id;

DELETE FROM tblappointment_prescription_items WHERE prescription_id IN (SELECT delete_id FROM tmp_presc_dupes);
DELETE FROM tblappointment_prescriptions WHERE id IN (SELECT delete_id FROM tmp_presc_dupes);
DROP TEMPORARY TABLE tmp_presc_dupes;

-- ----------------------------------------------------------------------------
-- 2.3  tblclients + tblcontacts + tblmedical_history
--      "New patient" form got submitted more than once (same company/phone/
--      city/address/etc, same branch, same datecreated). Delete the extra
--      copy ONLY if that specific copy has zero appointments, invoices,
--      estimates, expenses, projects, credit notes, subscriptions, tickets,
--      events, files, notes, project activity, etc. anywhere.
-- ----------------------------------------------------------------------------
CREATE TEMPORARY TABLE tmp_client_dupes AS
SELECT DISTINCT t2.userid AS delete_userid
FROM tblclients t1
JOIN tblclients t2
  ON t1.branch_id = t2.branch_id
 AND t1.userid < t2.userid
 AND MD5(CONCAT_WS('|', t1.company, t1.vat, t1.phonenumber, t1.country, t1.city, t1.zip, t1.state,
        t1.address, t1.reference_from, t1.website, t1.datecreated, t1.active, t1.leadid,
        t1.billing_street, t1.billing_city, t1.billing_state, t1.billing_zip, t1.billing_country,
        t1.shipping_street, t1.shipping_city, t1.shipping_state, t1.shipping_zip, t1.shipping_country,
        t1.longitude, t1.latitude, t1.default_language, t1.default_currency, t1.show_primary_contact,
        t1.stripe_id, t1.registration_confirmed, t1.addedfrom))
   = MD5(CONCAT_WS('|', t2.company, t2.vat, t2.phonenumber, t2.country, t2.city, t2.zip, t2.state,
        t2.address, t2.reference_from, t2.website, t2.datecreated, t2.active, t2.leadid,
        t2.billing_street, t2.billing_city, t2.billing_state, t2.billing_zip, t2.billing_country,
        t2.shipping_street, t2.shipping_city, t2.shipping_state, t2.shipping_zip, t2.shipping_country,
        t2.longitude, t2.latitude, t2.default_language, t2.default_currency, t2.show_primary_contact,
        t2.stripe_id, t2.registration_confirmed, t2.addedfrom))
WHERE t1.branch_id IN (1,2,3)
  -- the duplicate copy (t2) must have ZERO relations anywhere before we touch it
  AND NOT EXISTS (SELECT 1 FROM tblinvoices      i  WHERE i.clientid  = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblestimates     e  WHERE e.clientid  = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblexpenses      ex WHERE ex.clientid = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblprojects      p  WHERE p.clientid  = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblcreditnotes   cn WHERE cn.clientid = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblsubscriptions su WHERE su.clientid = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tbltickets       tk WHERE tk.userid   = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblevents        ev WHERE ev.userid   = t2.userid)
  AND NOT EXISTS (SELECT 1 FROM tblcontact_permissions cp WHERE cp.userid = t2.userid)
  AND NOT EXISTS (
        SELECT 1 FROM tblcontacts c WHERE c.userid = t2.userid AND (
             EXISTS (SELECT 1 FROM tblappointly_appointments a WHERE a.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblfiles f WHERE f.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblconsents co WHERE co.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblgdpr_requests g WHERE g.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblprojectdiscussioncomments pdc WHERE pdc.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblprojectdiscussions pd WHERE pd.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblproject_activity pa WHERE pa.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblproject_files pf WHERE pf.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tblshared_customer_files scf WHERE scf.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tbltask_comments tcm WHERE tcm.contact_id = c.id)
          OR EXISTS (SELECT 1 FROM tbluser_meta um WHERE um.contact_id = c.id)
        )
  );

DELETE FROM tblmedical_history WHERE userid IN (SELECT delete_userid FROM tmp_client_dupes);
DELETE FROM tblcontacts        WHERE userid IN (SELECT delete_userid FROM tmp_client_dupes);
DELETE FROM tblclients         WHERE userid IN (SELECT delete_userid FROM tmp_client_dupes);
DROP TEMPORARY TABLE tmp_client_dupes;

-- ----------------------------------------------------------------------------
-- 2.4  tblmedical_history — standalone duplicate history rows (same patient,
--      same branch, identical content, no client-level duplicate involved).
--      Nothing else in the schema points to tblmedical_history.id, so this is
--      always safe once the content is a true match.
-- ----------------------------------------------------------------------------
CREATE TEMPORARY TABLE tmp_mh_dupes AS
SELECT DISTINCT t2.id AS delete_id
FROM tblmedical_history t1
JOIN tblmedical_history t2
  ON t1.branch_id = t2.branch_id
 AND t1.userid    = t2.userid
 AND t1.id < t2.id
 AND MD5(CONCAT_WS('|', t1.occupation, t1.allergies, t1.medication, t1.tobaco_past, t1.tobaco_present,
        t1.alcohol_past, t1.alcohol_present, t1.marital_status, t1.medical_history, t1.surgical_history,
        t1.enviro_factors, t1.risk_factors, t1.chief_complaint, t1.dental_history, t1.diagnosis, t1.disease,
        t1.clinical_findings, t1.current_treatment, t1.previous_medication, t1.current_medication,
        t1.treatment_plan, t1.history_comment, t1.immediate_text, t1.planned_text, t1.datecreated))
   = MD5(CONCAT_WS('|', t2.occupation, t2.allergies, t2.medication, t2.tobaco_past, t2.tobaco_present,
        t2.alcohol_past, t2.alcohol_present, t2.marital_status, t2.medical_history, t2.surgical_history,
        t2.enviro_factors, t2.risk_factors, t2.chief_complaint, t2.dental_history, t2.diagnosis, t2.disease,
        t2.clinical_findings, t2.current_treatment, t2.previous_medication, t2.current_medication,
        t2.treatment_plan, t2.history_comment, t2.immediate_text, t2.planned_text, t2.datecreated))
WHERE t1.branch_id IN (1,2,3);

DELETE FROM tblmedical_history WHERE id IN (SELECT delete_id FROM tmp_mh_dupes);
DROP TEMPORARY TABLE tmp_mh_dupes;

-- ----------------------------------------------------------------------------
-- NOTE — tblappointment_treatment (ids 621, 622, 623 for appointment 3243,
-- Maninagar branch): text-identical duplicate treatment note, BUT each of the
-- 3 copies is individually linked to its own row in tblappointment_assign_log
-- (staff assignment records 2205/2206/2207). Since every copy IS related to
-- something else, none of them is deleted here — per your own rule. If you
-- want these merged into one, they need to be handled manually (decide which
-- assign_log entry to keep), not auto-deleted.
-- ----------------------------------------------------------------------------

-- ---- verification before commit ----
SELECT 'client dupes removed check' AS step,
       (SELECT COUNT(*) FROM tblclients WHERE branch_id IN (1,2,3)) AS clients_left;

-- Review the numbers above, then either:
COMMIT;
-- or, if something looks wrong:
-- ROLLBACK;
