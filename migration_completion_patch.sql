-- ============================================================================
-- OSHREEJI DENTAL — MIGRATION COMPLETION PATCH
-- Purpose: fix gaps found in main_db_migrated.sql after checking against
--          u614622744_maninagar_db / satellite_db / iskon_ambli_db and the
--          3 migration docx files.
--
-- branch_id mapping used throughout the original migration (kept same here):
--   1 = Maninagar   (apolloin_u614622744_maninagar_db)
--   2 = Satellite    (apolloin_u614622744_satellite_db)
--   3 = Iskon-Ambli  (apolloin_u614622744_iskon_ambli_db)
--   0 = original main clinic data
--
-- !! IMPORTANT !!
-- 1. TAKE A FULL BACKUP of apolloin_u614622744_main_db before running this.
-- 2. Run this on the LIVE main_db (phpMyAdmin), same as original migration —
--    not on this downloaded dump file.
-- 3. Run section by section (each section is self-contained). If something
--    errors midway, ROLLBACK and tell me the error instead of re-running blindly.
-- 4. Script is anti-join guarded (WHERE NOT EXISTS ...) so re-running it a
--    second time by mistake will NOT create duplicates.
-- 5. The 3 test invoices/payments you created live on 26-27 July (id 2,3,4)
--    are left untouched — they keep branch_id 2/3/0 as they already have.
-- ============================================================================

SET AUTOCOMMIT=0;
START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 0. STRUCTURE: add missing tracking columns
-- ----------------------------------------------------------------------------

-- tblinvoices already has branch_id (default 0) but no tbl_uniq_id / old_clientid
ALTER TABLE `tblinvoices`
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`,
  ADD `old_clientid` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

-- tblinvoicepaymentrecords already has branch_id but no tbl_uniq_id / old_invoiceid
ALTER TABLE `tblinvoicepaymentrecords`
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`,
  ADD `old_invoiceid` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

-- tblitemable has no branch tracking at all yet (shared by invoices/estimates/credit notes)
ALTER TABLE `tblitemable`
  ADD `branch_id` INT NOT NULL DEFAULT 0 AFTER `item_order`,
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`,
  ADD `old_rel_id` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

-- tblexpenses: migration was never started for this table
ALTER TABLE `tblexpenses`
  ADD `branch_id` INT NOT NULL DEFAULT 0 AFTER `addedfrom`,
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`,
  ADD `old_clientid` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

-- tblestimates: migration was never started for this table
ALTER TABLE `tblestimates`
  ADD `branch_id` INT NOT NULL DEFAULT 0 AFTER `status`,
  ADD `tbl_uniq_id` INT NOT NULL DEFAULT 0 AFTER `branch_id`,
  ADD `old_clientid` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

-- tblappointment_treatment: add old_appointment_id so we can safely remap
-- appointment_id for ONLY the rows we insert below (existing rows untouched)
ALTER TABLE `tblappointment_treatment`
  ADD `old_appointment_id` INT NOT NULL DEFAULT 0 AFTER `tbl_uniq_id`;

COMMIT;

-- ============================================================================
-- 1. INVOICES (per branch)
-- ============================================================================
START TRANSACTION;

-- --- Maninagar (branch 1) ---
INSERT INTO `tblinvoices`
(`sent`,`datesend`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,1,`id`,`clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`
FROM `apolloin_u614622744_maninagar_db`.`tblinvoices` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoices` m WHERE m.branch_id=1 AND m.tbl_uniq_id = src.id);

-- --- Satellite (branch 2) ---
INSERT INTO `tblinvoices`
(`sent`,`datesend`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,2,`id`,`clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`
FROM `apolloin_u614622744_satellite_db`.`tblinvoices` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoices` m WHERE m.branch_id=2 AND m.tbl_uniq_id = src.id);

-- --- Iskon-Ambli (branch 3) ---
INSERT INTO `tblinvoices`
(`sent`,`datesend`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,3,`id`,`clientid`,`deleted_customer_name`,`number`,`prefix`,`number_format`,`datecreated`,`date`,`duedate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`hash`,`status`,`clientnote`,`adminnote`,`last_overdue_reminder`,`last_due_reminder`,`cancel_overdue_reminders`,`allowed_payment_modes`,`token`,`discount_percent`,`discount_total`,`discount_type`,`recurring`,`recurring_type`,`custom_recurring`,`cycles`,`total_cycles`,`is_recurring_from`,`last_recurring_date`,`terms`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_invoice`,`show_quantity_as`,`project_id`,`subscription_id`,`short_link`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblinvoices` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoices` m WHERE m.branch_id=3 AND m.tbl_uniq_id = src.id);

-- remap clientid -> new tblclients.userid, for all 3 branches
UPDATE `tblinvoices` SET clientid = (
  SELECT userid FROM `tblclients` WHERE branch_id = tblinvoices.branch_id AND tbl_uniq_id = tblinvoices.old_clientid LIMIT 1
) WHERE branch_id IN (1,2,3) AND old_clientid > 0;

COMMIT;

-- ============================================================================
-- 2. INVOICE LINE ITEMS (tblitemable, rel_type='invoice')
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,1,`id`,`rel_id`
FROM `apolloin_u614622744_maninagar_db`.`tblitemable` src
WHERE src.rel_type='invoice'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=1 AND m.rel_type='invoice' AND m.tbl_uniq_id = src.id);

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,2,`id`,`rel_id`
FROM `apolloin_u614622744_satellite_db`.`tblitemable` src
WHERE src.rel_type='invoice'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=2 AND m.rel_type='invoice' AND m.tbl_uniq_id = src.id);

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,3,`id`,`rel_id`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblitemable` src
WHERE src.rel_type='invoice'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=3 AND m.rel_type='invoice' AND m.tbl_uniq_id = src.id);

-- remap rel_id -> new tblinvoices.id
UPDATE `tblitemable` SET rel_id = (
  SELECT id FROM `tblinvoices` WHERE branch_id = tblitemable.branch_id AND tbl_uniq_id = tblitemable.old_rel_id LIMIT 1
) WHERE branch_id IN (1,2,3) AND rel_type='invoice';

COMMIT;

-- ============================================================================
-- 3. INVOICE PAYMENT RECORDS (per branch)
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblinvoicepaymentrecords`
(`invoiceid`,`branch_id`,`tbl_uniq_id`,`old_invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`)
SELECT `invoiceid`,1,`id`,`invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`
FROM `apolloin_u614622744_maninagar_db`.`tblinvoicepaymentrecords` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoicepaymentrecords` m WHERE m.branch_id=1 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblinvoicepaymentrecords`
(`invoiceid`,`branch_id`,`tbl_uniq_id`,`old_invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`)
SELECT `invoiceid`,2,`id`,`invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`
FROM `apolloin_u614622744_satellite_db`.`tblinvoicepaymentrecords` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoicepaymentrecords` m WHERE m.branch_id=2 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblinvoicepaymentrecords`
(`invoiceid`,`branch_id`,`tbl_uniq_id`,`old_invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`)
SELECT `invoiceid`,3,`id`,`invoiceid`,`amount`,`paymentmode`,`paymentmethod`,`date`,`daterecorded`,`note`,`transactionid`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblinvoicepaymentrecords` src
WHERE NOT EXISTS (SELECT 1 FROM `tblinvoicepaymentrecords` m WHERE m.branch_id=3 AND m.tbl_uniq_id = src.id);

-- remap invoiceid -> new tblinvoices.id
UPDATE `tblinvoicepaymentrecords` SET invoiceid = (
  SELECT id FROM `tblinvoices` WHERE branch_id = tblinvoicepaymentrecords.branch_id AND tbl_uniq_id = tblinvoicepaymentrecords.old_invoiceid LIMIT 1
) WHERE branch_id IN (1,2,3) AND old_invoiceid > 0;

COMMIT;

-- ============================================================================
-- 4. EXPENSES (only Satellite had 1 row; harmless to run for all 3)
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblexpenses`
(`category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`)
SELECT `category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,1,`id`,`clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`
FROM `apolloin_u614622744_maninagar_db`.`tblexpenses` src
WHERE NOT EXISTS (SELECT 1 FROM `tblexpenses` m WHERE m.branch_id=1 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblexpenses`
(`category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`)
SELECT `category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,2,`id`,`clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`
FROM `apolloin_u614622744_satellite_db`.`tblexpenses` src
WHERE NOT EXISTS (SELECT 1 FROM `tblexpenses` m WHERE m.branch_id=2 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblexpenses`
(`category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`)
SELECT `category`,`currency`,`amount`,`tax`,`tax2`,`reference_no`,`note`,`expense_name`,`clientid`,3,`id`,`clientid`,`project_id`,`billable`,`invoiceid`,`paymentmode`,`date`,`recurring_type`,`repeat_every`,`recurring`,`cycles`,`total_cycles`,`custom_recurring`,`last_recurring_date`,`create_invoice_billable`,`send_invoice_to_customer`,`recurring_from`,`dateadded`,`addedfrom`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblexpenses` src
WHERE NOT EXISTS (SELECT 1 FROM `tblexpenses` m WHERE m.branch_id=3 AND m.tbl_uniq_id = src.id);

UPDATE `tblexpenses` SET clientid = (
  SELECT userid FROM `tblclients` WHERE branch_id = tblexpenses.branch_id AND tbl_uniq_id = tblexpenses.old_clientid LIMIT 1
) WHERE branch_id IN (1,2,3) AND old_clientid > 0 AND clientid > 0;

COMMIT;

-- ============================================================================
-- 5. ESTIMATES + their line items (only Satellite had 3 estimates; harmless for all 3)
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblestimates`
(`sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,1,`id`,`clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`
FROM `apolloin_u614622744_maninagar_db`.`tblestimates` src
WHERE NOT EXISTS (SELECT 1 FROM `tblestimates` m WHERE m.branch_id=1 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblestimates`
(`sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,2,`id`,`clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`
FROM `apolloin_u614622744_satellite_db`.`tblestimates` src
WHERE NOT EXISTS (SELECT 1 FROM `tblestimates` m WHERE m.branch_id=2 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblestimates`
(`sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,`branch_id`,`tbl_uniq_id`,`old_clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`)
SELECT `sent`,`datesend`,`clientid`,`deleted_customer_name`,`project_id`,`number`,`prefix`,`number_format`,`hash`,`datecreated`,`date`,`expirydate`,`currency`,`subtotal`,`total_tax`,`total`,`adjustment`,`addedfrom`,`status`,3,`id`,`clientid`,`clientnote`,`adminnote`,`discount_percent`,`discount_total`,`discount_type`,`invoiceid`,`invoiced_date`,`terms`,`reference_no`,`sale_agent`,`billing_street`,`billing_city`,`billing_state`,`billing_zip`,`billing_country`,`shipping_street`,`shipping_city`,`shipping_state`,`shipping_zip`,`shipping_country`,`include_shipping`,`show_shipping_on_estimate`,`show_quantity_as`,`pipeline_order`,`is_expiry_notified`,`acceptance_firstname`,`acceptance_lastname`,`acceptance_email`,`acceptance_date`,`acceptance_ip`,`signature`,`short_link`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblestimates` src
WHERE NOT EXISTS (SELECT 1 FROM `tblestimates` m WHERE m.branch_id=3 AND m.tbl_uniq_id = src.id);

UPDATE `tblestimates` SET clientid = (
  SELECT userid FROM `tblclients` WHERE branch_id = tblestimates.branch_id AND tbl_uniq_id = tblestimates.old_clientid LIMIT 1
) WHERE branch_id IN (1,2,3) AND old_clientid > 0;

-- estimate line items
INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,1,`id`,`rel_id`
FROM `apolloin_u614622744_maninagar_db`.`tblitemable` src
WHERE src.rel_type='estimate'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=1 AND m.rel_type='estimate' AND m.tbl_uniq_id = src.id);

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,2,`id`,`rel_id`
FROM `apolloin_u614622744_satellite_db`.`tblitemable` src
WHERE src.rel_type='estimate'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=2 AND m.rel_type='estimate' AND m.tbl_uniq_id = src.id);

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT `rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,3,`id`,`rel_id`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblitemable` src
WHERE src.rel_type='estimate'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` m WHERE m.branch_id=3 AND m.rel_type='estimate' AND m.tbl_uniq_id = src.id);

UPDATE `tblitemable` SET rel_id = (
  SELECT id FROM `tblestimates` WHERE branch_id = tblitemable.branch_id AND tbl_uniq_id = tblitemable.old_rel_id LIMIT 1
) WHERE branch_id IN (1,2,3) AND rel_type='estimate';

COMMIT;

-- ============================================================================
-- 6. CREDIT NOTE LINE ITEMS
--    (Only Maninagar had credit notes: 3 rows, already migrated correctly.
--     Matched by number+date+total+clientid since tblcreditnotes has no
--     tbl_uniq_id tracking column — safe because these 3 are unique.)
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblitemable`
(`rel_id`,`rel_type`,`description`,`long_description`,`qty`,`rate`,`unit`,`item_order`,`branch_id`,`tbl_uniq_id`,`old_rel_id`)
SELECT m.id, src.rel_type, src.description, src.long_description, src.qty, src.rate, src.unit, src.item_order, 1, src.id, src.rel_id
FROM `apolloin_u614622744_maninagar_db`.`tblitemable` src
JOIN `apolloin_u614622744_maninagar_db`.`tblcreditnotes` s_cn ON s_cn.id = src.rel_id
JOIN `tblcreditnotes` m ON m.branch_id = 1
  AND m.number = s_cn.number AND m.date = s_cn.date AND m.total = s_cn.total AND m.clientid = s_cn.clientid
WHERE src.rel_type='credit_note'
  AND NOT EXISTS (SELECT 1 FROM `tblitemable` x WHERE x.branch_id=1 AND x.rel_type='credit_note' AND x.tbl_uniq_id = src.id);

COMMIT;

-- ============================================================================
-- 7. tblappointment_treatment — fill in ONLY the missing rows for
--    Satellite (branch 2) and Iskon-Ambli (branch 3). Existing rows are
--    left untouched so nothing already-linked in the app breaks.
-- ============================================================================
START TRANSACTION;

INSERT INTO `tblappointment_treatment`
(`appointment_id`,`staff`,`treatment`,`created_date`,`branch_id`,`tbl_uniq_id`,`old_appointment_id`)
SELECT `appointment_id`,`staff`,`treatment`,`created_date`,2,`id`,`appointment_id`
FROM `apolloin_u614622744_satellite_db`.`tblappointment_treatment` src
WHERE NOT EXISTS (SELECT 1 FROM `tblappointment_treatment` m WHERE m.branch_id=2 AND m.tbl_uniq_id = src.id);

INSERT INTO `tblappointment_treatment`
(`appointment_id`,`staff`,`treatment`,`created_date`,`branch_id`,`tbl_uniq_id`,`old_appointment_id`)
SELECT `appointment_id`,`staff`,`treatment`,`created_date`,3,`id`,`appointment_id`
FROM `apolloin_u614622744_iskon_ambli_db`.`tblappointment_treatment` src
WHERE NOT EXISTS (SELECT 1 FROM `tblappointment_treatment` m WHERE m.branch_id=3 AND m.tbl_uniq_id = src.id);

-- remap appointment_id -> new tblappointly_appointments.id, only for rows
-- carrying old_appointment_id (pre-existing rows default to 0, so they're
-- automatically skipped and left untouched)
UPDATE `tblappointment_treatment` t
SET appointment_id = (
  SELECT id FROM `tblappointly_appointments` a
  WHERE a.branch_id = t.branch_id AND a.tbl_uniq_id = t.old_appointment_id LIMIT 1
)
WHERE t.branch_id IN (2,3) AND t.old_appointment_id > 0;

COMMIT;

-- ============================================================================
-- 8. VERIFY — run these and compare with counts below
-- ============================================================================
SELECT 'tblinvoices' tbl, branch_id, COUNT(*) FROM tblinvoices GROUP BY branch_id
UNION ALL
SELECT 'tblinvoicepaymentrecords', branch_id, COUNT(*) FROM tblinvoicepaymentrecords GROUP BY branch_id
UNION ALL
SELECT 'tblitemable', branch_id, COUNT(*) FROM tblitemable GROUP BY branch_id
UNION ALL
SELECT 'tblexpenses', branch_id, COUNT(*) FROM tblexpenses GROUP BY branch_id
UNION ALL
SELECT 'tblestimates', branch_id, COUNT(*) FROM tblestimates GROUP BY branch_id
UNION ALL
SELECT 'tblappointment_treatment', branch_id, COUNT(*) FROM tblappointment_treatment GROUP BY branch_id;

-- Expected after this patch:
--   tblinvoices:               branch1 ~1890, branch2 ~347(346+1 test), branch3 ~163(162+1 test)
--   tblinvoicepaymentrecords:  branch1 ~2444, branch2 ~355, branch3 ~169
--   tblitemable:               branch1 ~4772(4769 inv+3 CN), branch2 ~746(742 inv+4 est), branch3 ~407
--   tblexpenses:                branch2 = 1
--   tblestimates:                branch2 = 3
--   tblappointment_treatment:  branch2 = 119 (was 1), branch3 = 465 (was 210)
-- ============================================================================
