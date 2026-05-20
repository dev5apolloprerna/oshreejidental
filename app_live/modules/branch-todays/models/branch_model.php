<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Branch_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  integer (optional)
     * @return object
     * Get single visitorspurpose
     */
    public function get($id = '', $exclude_notified = false)
    {
        //echo $id;
        if (is_numeric($id)) {
            $this->db->where('branchid', $id);
            return $this->db->get(db_prefix() . 'branch')->row();
        }
        return $this->db->get(db_prefix() . 'branch')->result_array();
    }

    public function get_all_branch($exclude_notified = true)
    {
        $service_plan = $this->db->get(db_prefix() . 'branch')->result_array();
        return array_values($service_plan);
    }

    /**
     * Add new visitorspurpose
     * @param mixed $data All $_POST dat
     * @return mixed
     */
    public function add($data)
    {
        $data['created_at'] = date("Y-m-d H:i:s");
        $this->db->insert(db_prefix() . 'branch', $data);
        //echo $this->db->last_query();
        $insert_id = $this->db->insert_id();
        //echo $insert_id;exit;
        if ($insert_id) {
            log_activity('New Offer Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update visitorspurpose
     * @param  mixed $data All $_POST data
     * @param  mixed $id   visitorspurpose id
     * @return boolean
     */
    public function update($data, $id)
    {        
        $data['created_at'] = date("Y-m-d H:i:s");
        $this->db->where('branchid', $id);
        $this->db->update(db_prefix() . 'branch', $data);
        //echo $this->db->last_query();exit;
        if ($this->db->affected_rows() > 0) {
            log_activity('Offer Updated [ID:' . $id . ']');

            return true;
        }

        return false;
    }
    
    /**
     * Delete visitorspurpose
     * @param  mixed $id visitorspurpose id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('branchid', $id);
        $this->db->delete(db_prefix() . 'branch');
        if ($this->db->affected_rows() > 0) {
            log_activity('Offer Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Change visitorspurpose status / active / inactive
     * @param  mixed $id     id
     * @param  integer $status active or inactive
     */
    public function change_branch_status($id, $status)
    {
        //echo $id;exit;
        $this->db->where('branchid', $id);
        $this->db->update(db_prefix().'branch', [
            'status' => $status,
        ]);
        log_activity('Offer Status Changed [ID: ' . $id . ' - Active: ' . $status . ']');
    }
    
}
