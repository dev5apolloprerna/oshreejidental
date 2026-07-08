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
        $superAdmin = $this->extract_super_admin_data($data);

        if ($superAdmin) {
            $this->db->where('email', $superAdmin['email']);
            if ($this->db->get(db_prefix() . 'staff')->row()) {
                die('Email already exists');
            }
        }


        // $this->db->where('email', $data['email']);
        // $email = $this->db->get(db_prefix() . 'branch')->row();

        // if ($email) {
        //     die('Email already exists');
        // }
        $send_welcome_email = true;
        $original_password  = isset($data['password']) ? $data['password'] : '';

        $data['password']    = app_hash_password(isset($data['password']) ? $data['password'] : '');
        $data['created_at'] = date("Y-m-d H:i:s");
        $this->db->insert(db_prefix() . 'branch', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            
            if ($superAdmin) {
                $staffId = $this->create_branch_super_admin($superAdmin, $insert_id);
                if ($staffId) {
                    $this->db->where('branchid', $insert_id);
                    $this->db->update(db_prefix() . 'branch', ['staff_id' => $staffId]);
                }
            }

            log_activity('New Branch Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    private function extract_super_admin_data(&$data)
    {
        $superAdmin = [
            'firstname' => trim($data['super_admin_firstname'] ?? ''),
            'lastname'  => trim($data['super_admin_lastname'] ?? ''),
            'email'     => trim($data['email'] ?? ''),
            'password'  => $data['password'] ?? '',
        ];

        unset($data['super_admin_firstname'], $data['super_admin_lastname']);

        if ($superAdmin['firstname'] === '' && !empty($data['branch'])) {
            $superAdmin['firstname'] = trim($data['branch']);
        }

        if ($superAdmin['lastname'] === '') {
            $superAdmin['lastname'] = 'Admin';
        }

        if ($superAdmin['email'] === '' || $superAdmin['password'] === '') {
            return false;
        }

        return $superAdmin;
    }

    /**
     * Create the staff user that can log in as the newly created branch super admin.
     */
    private function create_branch_super_admin($superAdmin, $branchId)
    {
        $staffData = [
            'email'       => $superAdmin['email'],
            'firstname'   => $superAdmin['firstname'],
            'lastname'    => $superAdmin['lastname'],
            'password'    => app_hash_password($superAdmin['password']),
            'datecreated' => date('Y-m-d H:i:s'),
            'admin'       => 0,
            'active'      => 1,
            'is_not_staff'=> 0,
            'branch_id'   => (int) $branchId,
        ];

        if ($this->db->field_exists('role', db_prefix() . 'staff')) {
            $staffData['role'] = null;
        }

        $this->db->insert(db_prefix() . 'staff', $staffData);
        $staffId = $this->db->insert_id();

        if (!$staffId) {
            return false;
        }

        $this->db->where('staffid', $staffId);
        $this->db->update(db_prefix() . 'staff', [
            'media_path_slug' => slug_it($superAdmin['firstname'] . ' ' . $superAdmin['lastname']),
        ]);

        if ($this->db->table_exists(db_prefix() . 'branch_admins')) {
            $this->db->insert(db_prefix() . 'branch_admins', [
                'branch_id'     => (int) $branchId,
                'staff_id'      => (int) $staffId,
                'date_assigned' => date('Y-m-d H:i:s'),
            ]);
        }

        log_activity('New Branch Super Admin Added [ID: ' . $staffId . ', Branch ID: ' . $branchId . ']');

        return $staffId;
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
            log_activity('Branch Updated [ID:' . $id . ']');

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
            log_activity('Branch Deleted [ID:' . $id . ']');

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
        log_activity('Branch Status Changed [ID: ' . $id . ' - Active: ' . $status . ']');
    }
    
}
