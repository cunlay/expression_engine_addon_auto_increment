<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Auto_increment_ext {

    public $settings = array();
    public $description = 'A fieldtype for creating channel-specific auto-incrementing fields.';
    public $docs_url = 'https://david.nmd.cc/';
    public $name = 'Auto Increment';
    public $version = '1.0';
    public $settings_exist = FALSE;

    public function __construct($settings = array())
    {
        $this->settings = $settings;
    }

    public function after_channel_entry_insert($entry_data)
    {
        list($i, $entry_id) = explode('ChannelEntry:', $entry_data);
        
        $this->log_activity(__FUNCTION__, "Entry ID: $entry_id");
        $channel_id = ee()->db->select('channel_id')
                              ->from('channel_titles')
                              ->where('entry_id', $entry_id)
                              ->get()
                              ->row('channel_id');

        if ($channel_id) {
            
            // Update the last value in the custom table
            $query = ee()->db->select('last_value')
                             ->where('channel_id', $channel_id)
                             ->get('exp_auto_increment');

            if ($query->num_rows() > 0) {
                $last_value = $query->row('last_value');
                $next_value = $last_value + 1;
                
                ee()->db->where('channel_id', $channel_id)
                        ->update('exp_auto_increment', array('last_value' => $next_value));
            } else {
                $next_value = $entry_id;

                ee()->db->insert('exp_auto_increment', array(
                    'channel_id' => $channel_id,
                    'last_value' => $next_value
                ));
            }

            $this->log_activity(__FUNCTION__, "Incremented value to $next_value for channel_id $channel_id.");
        }
    }

    public function after_channel_entry_update($entry_id)
    {
        // Log activity
        $this->log_activity(__FUNCTION__, "Entry ID: $entry_id");
    }

    public function after_channel_entry_delete($entry_id)
    {
        // Log activity
        $this->log_activity(__FUNCTION__, "Entry ID: $entry_id");
    }

    public function after_channel_entry_bulk_delete($entry_ids, $channel_id = null)
    {
        $this->log_activity(__FUNCTION__, "Entry IDs: " . (is_array($entry_ids) ? implode(',', $entry_ids) : $entry_ids) . ", Channel ID: $channel_id");
    }

    /**
     * Log activity to a file
     */
    private function log_activity($function_name, $message)
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
