<?php defined('BASEPATH') or exit('No direct script access allowed');

class Nabh_model extends App_Model
{
    private $table = 'tblnabh_master';

    public function get_all()
    {
        return $this->db->order_by('pdf_id', 'DESC')->get($this->table)->result_array();
    }

    public function get($id)
    {
        return $this->db->where('pdf_id', (int)$id)->get($this->table)->row_array();
    }
}
