<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Generalreport_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id ='')
    {
        if($id)
        {
            $this->db->where('services_id', $id);
            return $this->db->get(db_prefix().'service_details')->row();
        }
        return $this->db->get(db_prefix().'service_details')->result_array();
    }

    public function delete($id)
    {
        $this->db->where('services_id',$id);
        $this->db->delete(db_prefix().'service_details');

        if($this->db->affected_rows() > 0) 
        {
            log_activity('Report Deleted [ID:'.$id.']');
            return true;
        }
        return false;
    }

    public function get_service($id)
    {   
        $this->db->select('service_status');
        $this->db->where('services_id',$id);
        $status=$this->db->get(db_prefix().'service_details')->row();
        if($status->service_status == 'Active')
        {
            $this->db->select(db_prefix().'customer_service_schedule.*');
            $this->db->select('customer_service_schedule.service_schedule_id,'.db_prefix().'customer_service_schedule.staff_id,service_assign_date_staff,confirmed_by_admin,customer_service_schedule_id');
            $this->db->join(db_prefix().'customer_service_schedule_details',db_prefix().'customer_service_schedule_details.service_schedule_id='.db_prefix().'customer_service_schedule.service_schedule_id','left');
            $this->db->where('service_detail_id',$id);
            return $this->db->get(db_prefix().'customer_service_schedule')->result_array();
        }
        else
        {
            return false;
        }
        
        
    }

    public function get_service_new($customer_id)
    {   
        
        $this->db->select(db_prefix().'customer_service_schedule.*');
        $this->db->select(db_prefix().'service_details.challanid,'.db_prefix().'service_details.service_plan_id');
        $this->db->select('customer_service_schedule.service_schedule_id,'.db_prefix().'customer_service_schedule.staff_id,service_assign_date_staff,confirmed_by_admin,customer_service_schedule_id');
        $this->db->join(db_prefix().'service_details',db_prefix().'service_details.services_id='.db_prefix().'customer_service_schedule.service_detail_id','left');
        $this->db->join(db_prefix().'customer_service_schedule_details',db_prefix().'customer_service_schedule_details.service_schedule_id='.db_prefix().'customer_service_schedule.service_schedule_id','left');
        $this->db->where(db_prefix().'customer_service_schedule.customer_id',$customer_id);
        return $this->db->get(db_prefix().'customer_service_schedule')->result_array();
    }

    public function admin_staff()
    {
        $this->db->select('staffid');
        $this->db->where('admin',1);
        return $this->db->get(db_prefix().'staff')->row();
    }

    public function get_user_data($id)
    {
        $this->db->select('service_schedule_id,service_name,service_detail_id,staff_id,service_assign_date_staff,fcm_token,device_type,customer_service_schedule.customer_id');
        $this->db->join(db_prefix().'staff',db_prefix().'staff.staffid ='.db_prefix().'customer_service_schedule.staff_id','left');
        $this->db->join(db_prefix().'service_details',db_prefix().'service_details.services_id = '.db_prefix().'customer_service_schedule.service_detail_id','left');
        $this->db->where('service_detail_id',$id);
        return $this->db->get(db_prefix().'customer_service_schedule')->result_array();
    }

    public function confirm_status_admin($id)
    {
        $this->db->select('confirmed_by_admin,customer_service_schedule_id');
        $this->db->join(db_prefix().'customer_service_schedule_details', db_prefix().' customer_service_schedule_details.service_schedule_id ='.db_prefix().'customer_service_schedule.service_schedule_id','left'); 
        $this->db->where('service_detail_id',$id);
        return $this->db->get(db_prefix().'customer_service_schedule')->result_array();
        //echo $this->db->last_query();exit;
    }

   

    public function get_customer_service($id)
    {   
        $this->db->select('service_assign_date_staff');
        $this->db->select('end_time');
        $this->db->select('customer_prefered_datetime');
        $this->db->where('service_detail_id',$id);
        $service_datails = $this->db->get(db_prefix().'customer_service_schedule')->result_array();
        return $service_datails;
    }

    public function get_complete_service($id)
    {   
        $this->db->select('service_assign_date_staff,confirmed_by_admin,customer_service_schedule_id');
        $this->db->join(db_prefix().'customer_service_schedule_details', db_prefix().' customer_service_schedule_details.service_schedule_id ='.db_prefix().'customer_service_schedule.service_schedule_id','left'); 
        $this->db->where('service_detail_id',$id);
        $this->db->where(db_prefix().'customer_service_schedule.service_status',1);
        $service_completed=$this->db->get(db_prefix().'customer_service_schedule')->result_array();
        return $service_completed;
    }

    public function get_upcoming_service($id)
    {   
        $this->db->select('service_assign_date_staff');
        $this->db->where('service_detail_id',$id);
        $this->db->where('service_status',0);
        $service_upcoming=$this->db->get(db_prefix().'customer_service_schedule')->result_array(); 
        return $service_upcoming;
    }

    public function update_mechanic($data, $id)
    {   
        $staffid = $data;

        foreach($staffid as $key => $stf){
            
            if(!empty($stf)){
                
                $this->db->where('service_schedule_id',$key);
                $sche_row =$this->db->get(db_prefix().'customer_service_schedule')->row(); 
                
                if($sche_row->happy_code == ''){
                    
                    // $updateData['happy_code'] = random_int(1000, 9999);
                    $updateData['happy_code'] = '1234';    
                }
                
                if($sche_row->customer_prefered_datetime != '' && $sche_row->customer_prefered_datetime != '0000-00-00'){
                    $updateData['service_assign_date_staff'] = date('Y-m-d');
                }
                
                
                $this->db->set('staff_id', $stf);
                $this->db->where('service_schedule_id', $key);
                $this->db->update(db_prefix().'customer_service_schedule', $updateData);   
            }
        }
        return true;
    }

    public function monthly($start, $end, $step = '+1 months', $format = 'Y-m-d') 
    {
        $array = array();
        $current = strtotime($start);
        $last = strtotime($end);

        while( $current <= $last) {

            $array[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $array;
    }

    public function six_Month($start, $end, $step = '+6 months', $format = 'Y-m-d')
    {
        $array = array();
        $current = strtotime($start);
        $last = strtotime($end);

        while( $current <= $last) 
        {
            $array[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $array;
    }

    public function quarterly($start, $end, $step = '+3 months', $format = 'Y-m-d') 
    {
        $array = array();
        $current = strtotime($start);
        $last = strtotime($end);

        while( $current <= $last) 
        {
            $array[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $array;
    }

    public function yearly($start, $end, $step = '+12 months', $format = 'Y-m-d') 
    {
        $array = array();
        $current = strtotime($start);
        $last = strtotime($end);

        while( $current <= $last) 
        {
            $array[] = date($format, $current);
            $current = strtotime($step, $current);
        }
        return $array;
    }

    public function confirm_status($id)
    {
        $this->db->where('customer_service_schedule_id', $id);
        $this->db->update(db_prefix().'customer_service_schedule_details', ['confirmed_by_admin'=>1]);
        if($this->db->affected_rows() > 0)
        {
            return true;
        }
        return false;
    }

    public function get_fcm_token_by_staffid($id)
    {
        $this->db->select('fcm_token, device_type');
        $this->db->where('staffid', $id);
        return $this->db->get(db_prefix() . 'staff')->row();
    }

    public function get_customers_service_card(){

        $this->db->select(db_prefix() . 'clients.userid,'.db_prefix() . 'clients.company,'.db_prefix() . 'contacts.uid');
        $this->db->where(db_prefix() . 'clients.active', 1);
        $this->db->join(db_prefix().'contacts',db_prefix().'contacts.userid = ' . db_prefix() . 'clients.userid');
        return $this->db->get(db_prefix() . 'clients')->result_array();
    }

    public function get_client_by_id($id){

        $this->db->select(db_prefix() . 'clients.userid,'.db_prefix() . 'clients.company,'.db_prefix() . 'contacts.uid');
        $this->db->where(db_prefix() . 'clients.active', 1);
        $this->db->where(db_prefix() . 'clients.userid', $id);
        $this->db->join(db_prefix().'contacts',db_prefix().'contacts.userid = ' . db_prefix() . 'clients.userid');
        $result = $this->db->get(db_prefix() . 'clients')->row();

        if(!empty($result)){
            return $result->company . ' ('. $result->uid .')';
        }else{
            return '';
        }

    }

    public function change_statuses($shedule_ids,$mechanic_satff)
    {
        //$happy_code = random_int(1000, 9999);
        $happy_code = '1234';
        $this->db->where_in('service_schedule_id',$shedule_ids);
        $this->db->update(db_prefix().'customer_service_schedule',[
            'staff_id' => $mechanic_satff,
            'happy_code' => $happy_code
        ]);
        if($this->db->affected_rows() > 0)
        {
            return true;
        }
        return false;
        
    }

    public function get_mechanic(){
        $this->db->select('staffid,firstname,lastname');
        $this->db->where('active', 1);
        $this->db->where('role', 1);
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

     public function get_staff(){
        $this->db->select('staffid,firstname,lastname');
        $this->db->where('active', 1);
        $this->db->where('role !=', 2);
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

     public function get_staff_lab(){
        $this->db->select('staffid,firstname,lastname');
        $this->db->where('active', 1);
        $this->db->where('role', 2);
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    public function get_ticketstatus(){
        return $this->db->get(db_prefix() . 'tickets_status')->result_array();
    }
}
