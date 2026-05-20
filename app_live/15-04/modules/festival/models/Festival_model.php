<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Festival_model extends App_Model
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
            
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'holidays')->row();
        }
        return $this->db->get(db_prefix() . 'holidays')->result_array();
    }

    public function get_all_offer($exclude_notified = true)
    {
        $service_plan = $this->db->get(db_prefix() . 'holidays')->result_array();
        return array_values($service_plan);
    }
    
    public function get_all_items()
    {
        $this->db->select('id,description');
        $items = $this->db->get(db_prefix() . 'items')->result_array();
        return $items;
    }

    /**
     * Add new visitorspurpose
     * @param mixed $data All $_POST dat
     * @return mixed
     */
    public function add($data)
    {
        unset($data['submit']);
        
        $insert_id = $this->db->insert(db_prefix() . 'holidays', $data);
        if ($insert_id) {

            return true;;
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
        unset($data['submit']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'holidays', $data);

        if ($this->db->affected_rows() > 0) {
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
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'holidays');
        if ($this->db->affected_rows() > 0) {

            return true;
        }

        return false;
    }

    /**
     * Change visitorspurpose status / active / inactive
     * @param  mixed $id     id
     * @param  integer $status active or inactive
     */
    public function change_offer_status($id, $status)
    {
        //echo $id;exit;
        $this->db->where('offer_id', $id);
        $this->db->update(db_prefix().'offer', [
            'status' => $status,
        ]);
        log_activity('Offer Status Changed [ID: ' . $id . ' - Active: ' . $status . ']');
    }

    public function get_festivals_by_date($date){

        $this->db->where('date', $date);
        return $this->db->get(db_prefix().'holidays')->row();
    }

    public function get_all_users_to_send_email(){
        $this->db->select('email');
        $this->db->where('active', 1);
        // $this->db->where('email', 'addon.renish003@gmail.com');
        return $this->db->get(db_prefix().'contacts')->result_array();

    }

    public function get_birthday_date($date){
        $this->db->select('firstname, email');
        $this->db->where('dob', $date);
        // $this->db->where('email', 'addon.renish003@gmail.com');
        return $this->db->get(db_prefix().'contacts')->result_array();

    }

}
