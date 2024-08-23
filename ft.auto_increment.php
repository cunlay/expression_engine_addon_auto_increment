<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Auto_increment_ft extends EE_Fieldtype {

    public $info = array(
        'name'      => 'Auto Increment',
        'version'   => '1.0'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function install()
    {
        ee()->load->dbforge();

        // Create the custom auto increment table
        $sql = "CREATE TABLE IF NOT EXISTS `exp_auto_increment` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT,
            `channel_id` INT(11) UNSIGNED NOT NULL,
            `last_value` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `channel_id` (`channel_id`)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;";
        ee()->db->query($sql);

        // Register extension hooks
        $hooks = array(
            'after_channel_entry_insert' => 'after_channel_entry_insert',
            'after_channel_entry_update' => 'after_channel_entry_update',
            'after_channel_entry_delete' => 'after_channel_entry_delete',
            'after_channel_entry_bulk_delete' => 'after_channel_entry_bulk_delete'
        );

        foreach ($hooks as $hook => $method) {
            ee()->db->insert('exp_extensions', array(
                'class'    => 'auto_increment_ext',
                'method'   => $method,
                'hook'     => $hook,
                'settings' => serialize(array()),
                'version'  => '1.1',
                'enabled'  => 'y'
            ));
        }

        // Log activity
        $this->log_activity(__FUNCTION__, 'Addon installed and extension hooks registered.');
    }

    public function uninstall()
    {
        ee()->load->dbforge();

        // Drop the custom auto increment table
        $sql = "DROP TABLE IF EXISTS `exp_auto_increment`;";
        ee()->db->query($sql);

        // Unregister extension hooks
        ee()->db->where('class', 'Auto_increment_ext')
                ->delete('exp_extensions');

        // Log activity
        $this->log_activity(__FUNCTION__, 'Table dropped and extension hooks unregistered.');
    }
    
    public function __save_settings($data)
    {        
        
        ee()->load->dbforge();
        $field_id = isset($data['field_id']) ? $data['field_id'] : null;
        $message = "Function: ".__FUNCTION__." | Field ID: ".$field_id." | Data: ".json_encode($data);
        $this->log_activity(__FUNCTION__, $message);

        if ($field_id) {
            $fields = array(
                'field_id_'.$field_id => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                )
            );

            ee()->dbforge->add_column('exp_channel_data', $fields);
            $this->log_activity(__FUNCTION__, 'Custom field column added.');
        }
  
        return $data;
    }
    
		
	public function save_settings($data)
    {
        // You might want to perform validation here
        // For instance, checking if `channel_id` is present and is a valid integer
        $channel_id = isset($data['channel_id']) ? (int) $data['channel_id'] : 1;
        // Save settings to the database
        ee()->db->where('class', get_class($this))
                ->update('exp_extensions', array(
                    'settings' => serialize(array('channel_id' => $channel_id))
                ));
    
        // Log activity
        $this->log_activity(__FUNCTION__, "Channel ID saved: $channel_id");
        return array('channel_id' => $channel_id);
    }

    
    public function __update_settings($data)
    {
        $message = "Function: ".__FUNCTION__." | Data: ".json_encode($data);
        $this->log_activity(__FUNCTION__, $message);

        return $data;
    }

    public function __display_settings($settings)
    {
        $message = "Function: ".__FUNCTION__." | Settings: ".json_encode($settings);
        $this->log_activity(__FUNCTION__, $message);

        $data = array(
            'field_id' => isset($settings['field_id']) ? $settings['field_id'] : ''
        );

        return ee()->load->view('settings', $data, TRUE);
    }

    public function display_field($data)
    {
        // Generate the next ID only if this is a new entry
        $next_id = $this->get_next_id();
        $message = "Function: ".__FUNCTION__." | Next ID: ".$next_id;
        $this->log_activity(__FUNCTION__, $message);
        return form_input($this->field_name, $next_id, 'readonly');
    }
    
		private function get_next_id()
    {
        // Determine if this is an edit or new entry
        $current_uri = ee()->uri->uri_string();
        $is_new_entry = (strpos($current_uri, 'create') !== false);
        $channel_id = isset($this->settings['channel_id']) ? $this->settings['channel_id'] : $this->get_channel_id_from_entry();

        // If it's not a new entry, return the existing data (no increment)
        if (!$is_new_entry) {
            return $this->content_id(); // Return the existing ID for editing
        }else{
            $current_uri = ee()->uri->uri_string();
            $uri_segments = explode('/', $current_uri);
            $channel_id = isset($uri_segments[3]) ? $uri_segments[3] : null;
            $this->settings['channel_id'] = $channel_id;
            // Log the extracted channel ID
            $this->log_activity(__FUNCTION__, "Extracted Channel ID: ".$channel_id);
        }

        if (!$channel_id) {
            return 'Error: channel_id not found';
        }

        // Get the current value from the custom table
        $query = ee()->db->select('last_value')
                         ->where('channel_id', $channel_id)
                         ->get('exp_auto_increment');

        if ($query->num_rows() > 0) {
            $next_value = 1;
            $last_value1 = $query->row('last_value');
            
            $row = ee()->db->select_max('entry_id', 'maxId')
                           ->from('exp_channel_titles')
                           ->get()
                           ->row();  // Fetch the first row of the result
            
            $value1 = isset($row->maxId) ? $row->maxId : 0;
            
            ee()->load->database();
            // Execute the custom query to get the table status
            $query = ee()->db->query("SHOW TABLE STATUS LIKE 'exp_channel_titles'");
            // Check if the query returned a result
            if ($query->num_rows() > 0) {
                $row2 = $query->row();
                $value2 = $row2->Auto_increment; // Fetches the next auto-increment value
                
                //compare titles max to auto increment value
                $last_value2 = $value1 > $value2  ? $value1 : $value2;
                
                //compare last_values
                $last_value = $last_value2 > $last_value1  ? $last_value2 : $last_value1;
                
                if(isset($row->maxId)){
                    ee()->db->where('channel_id', $channel_id)
                        ->update('exp_auto_increment', array('last_value' => $last_value));
                    $next_value = $last_value + 1; 
                    $mes = "The other next ID (calculated) is: $next_value $last_value1 $last_value2" ;
                }else{
                    $next_value = $last_value;
                    $mes = "The second other next ID (calculated) is: $next_value $last_value1 $last_value2 $value1" ;
                }
                
                $this->log_activity(__FUNCTION__, $mes);
            }
            
            
        } else {           
            $row = ee()->db->select_max('entry_id', 'maxId')
                           ->from('exp_channel_titles')
                           ->get()
                           ->row();  // Fetch the first row of the result
                              
            $value1 = isset($row->maxId) ? $row->maxId : 0;
            
            ee()->load->database();
            // Execute the custom query to get the table status
            $query = ee()->db->query("SHOW TABLE STATUS LIKE 'exp_channel_titles'");
            // Check if the query returned a result
            if ($query->num_rows() > 0) {
                $row2 = $query->row();
                $value2 = $row2->Auto_increment; // Fetches the next auto-increment value
                
                if(isset($row->maxId) && $value1 > 0){
                    //compare titles max to auto increment value
                    $last_value = $value1 > $value2  ? $value1 : $value2;
                    ee()->db->where('channel_id', $channel_id)
                        ->update('exp_auto_increment', array('last_value' => $last_value));
                }else{
                    $last_value = $value1;
                    if($value1 < $value2 && !isset($row->maxId)){
                        // Load the database library
                        ee()->load->database();
                        
                        // Step 1: Make sure the table is empty
                        // This will delete all records. Be cautious!
                        ee()->db->truncate('exp_channel_titles');
                        
                        // Step 2: Reset the auto-increment value to 1
                        $reset_query = "ALTER TABLE exp_channel_titles AUTO_INCREMENT = 1";
                        ee()->db->query($reset_query);
                    }
                }
                        
                $next_value = $last_value + 1; 
                $mes = "The first next ID (calculated) is: " . $next_value;
                $this->log_activity(__FUNCTION__, $mes);
            }else{
                $last_value = 0;
                $next_value = 1;
            }
            
            // Insert the first record for this channel
            ee()->db->insert('exp_auto_increment', array(
                'channel_id' => $channel_id,
                'last_value' => $last_value
            ));
            
            $this->log_activity(__FUNCTION__, "Inserted initial value $last_value for channel_id $channel_id.");
        }

        return $next_value;
    }

    private function get_channel_id_from_entry()
    {
        
        $entry_id = $this->content_id();
        if ($entry_id) {
            return ee()->db->select('channel_id')
                           ->from('channel_titles')
                           ->where('entry_id', $entry_id)
                           ->get()
                           ->row('channel_id');
        }

        return null;
    }

    public function save($data)
    {
        $message = "Function: ".__FUNCTION__." | Data: ".json_encode($data);
        $this->log_activity(__FUNCTION__, $message);
        return $data;
    }

    public function validate($data)
    {
        if (!is_numeric($data)) {
            $error_message = "The field must contain a numeric value.";
            $this->log_activity(__FUNCTION__, $error_message);
            return $error_message;
        }
        return TRUE;
    }
    
    private function __clear_cache()
    {
        // Clear all caches related to this fieldtype
        ee()->functions->clear_caching('all', '', TRUE);
        $this->log_activity(__FUNCTION__, 'Cache cleared');
    }

    public function log_activity($function_name, $message)
    {        
        $file = SYSPATH . 'user/addons/auto_increment/custom_log.txt';
        $max_lines = 500;
        $time = date('Y-m-d H:i:s');
        $full_message = "[$time] $function_name: $message" . PHP_EOL;
        
        // Append the new log entry
        file_put_contents($file, $full_message, FILE_APPEND);
        
        // Read the file into an array of lines
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        
        // Check if the number of lines exceeds the maximum allowed
        if (count($lines) > $max_lines) {
            // Calculate the number of lines to remove
            $lines_to_remove = count($lines) - $max_lines;
        
            // Remove the oldest lines
            $lines = array_slice($lines, $lines_to_remove);
        
            // Write the remaining lines back to the file
            file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
        }
    }
}
