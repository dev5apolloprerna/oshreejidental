<?php defined('BASEPATH') or exit('No direct script access allowed');

class Nabh_model extends App_Model
{
    private $master = 'tblnabh_master';
    private $map    = 'tblappointment_type_pdf_master';

    // ✅ Only mapped pdf/html for appointment type
    public function get_mapped_forms($appointment_type_id)
    {
        $appointment_type_id = (int)$appointment_type_id;

        return $this->db
            ->select('m.id, m.title, m.english_title, m.gujarati_title, m.english_file_name, m.gujarati_file_name')
            ->from($this->master . ' m')
            ->join($this->map . ' mp', 'mp.appointment_pdf_id = m.id', 'inner')
            ->where('mp.appointment_type_id', $appointment_type_id)
            ->order_by('mp.id', 'DESC')
            ->get()
            ->result_array();
    }

    public function get($id)
    {
        return $this->db->where('id', (int)$id)->get($this->master)->row_array();
    }
}
