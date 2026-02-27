<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dbclone extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Load default database configuration
        $this->load->dbforge();  // Load database forge
        // $DB1 = $this->load->database('default', TRUE);
        // $DB2 = $this->load->database('default2', TRUE);
    }

    public function clone_database() {
        // Load source and destination database configurations
        $sourceDB = $this->load->database('default', TRUE);
        $destinationDB = $this->load->database('default2', TRUE);

        // Clone database
        $this->clone_tables($sourceDB, $destinationDB);
        printrx($this->db->last_query());
        echo "Database cloned successfully!";
    }

    private function clone_tables($sourceDB, $destinationDB) {
        // Get list of tables in source database
        $tables = $sourceDB->list_tables();
        
        foreach ($tables as $table) {
            // Retrieve table structure from source database
            $createTableQuery = $sourceDB->query("SHOW CREATE TABLE $table")->row_array();
            // printr($createTableQuery);
            // Extract the table creation query
            $createQuery = $createTableQuery['Create Table'];

            // Create table in destination database
            $destinationDB->query($createQuery);
            
            // Copy data from source to destination table
            $data = $sourceDB->get($table)->result_array();
            if (!empty($data)) {
                $destinationDB->insert_batch($table, $data);
            }
        }
        exit;
       
    }
}