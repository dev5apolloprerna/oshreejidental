<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * patient_files_helper.php
 *
 * WHY THIS EXISTS
 * -----------------
 * Before the database merger, patients were uploaded with one ID and their
 * x-ray/image files were saved to disk at:  uploads/clients/{ID}/{file}
 *
 * During the merger, many patients were reassigned a new (current) ID.
 * tblcontacts.old_userid was correctly populated with the pre-merger ID, and
 * tblfiles.rel_id was correctly updated to point at the new ID. BUT the
 * physical folder on disk was never renamed - it is still sitting under the
 * OLD id. So code that builds "uploads/clients/{current_id}/{file}" 404s,
 * even though the database record is perfectly correct.
 *
 * resolve_patient_file_url() checks the current-id folder first (the normal
 * case for anyone uploaded after the merge, or never merged at all), and
 * transparently falls back to the pre-merge folder if the file lives there
 * instead. This is a safety net - the real, permanent fix is to run
 * migrate_patient_files.php once to rename the folders on disk so the
 * fallback is rarely needed going forward.
 */

if (!function_exists('resolve_patient_file_url')) {
    function resolve_patient_file_url($patient_id, $file_name)
    {
        $CI = &get_instance();
        $patient_id = (int) $patient_id;
        $file_name  = (string) $file_name;

        if ($patient_id <= 0 || $file_name === '') {
            return base_url('uploads/clients/' . $patient_id . '/' . $file_name);
        }

        // 1) Normal case: file lives under the patient's current id.
        $current_path = FCPATH . 'uploads/clients/' . $patient_id . '/' . $file_name;
        if (is_file($current_path)) {
            return base_url('uploads/clients/' . $patient_id . '/' . $file_name);
        }

        // 2) Fallback: this patient may have been renumbered during the merge.
        //    Look up their pre-merger id from tblcontacts.old_userid and check
        //    whether the file is sitting there instead.
        static $old_id_cache = [];
        if (!array_key_exists($patient_id, $old_id_cache)) {
            $CI->db->select('old_userid');
            $CI->db->where('userid', $patient_id);
            $CI->db->where('old_userid >', 0);
            $CI->db->limit(1);
            $row = $CI->db->get(db_prefix() . 'contacts')->row();
            $old_id_cache[$patient_id] = $row ? (int) $row->old_userid : 0;
        }

        $old_id = $old_id_cache[$patient_id];
        if ($old_id > 0) {
            $old_path = FCPATH . 'uploads/clients/' . $old_id . '/' . $file_name;
            if (is_file($old_path)) {
                return base_url('uploads/clients/' . $old_id . '/' . $file_name);
            }
        }

        // 3) Nothing found anywhere - return the current-id path so behaviour
        //    for genuinely missing files is unchanged (broken image, not a fatal error).
        return base_url('uploads/clients/' . $patient_id . '/' . $file_name);
    }
}
