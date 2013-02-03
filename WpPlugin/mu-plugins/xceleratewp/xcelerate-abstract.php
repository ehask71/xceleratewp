<?php

/**
 * Xcelerate WP Plugin
 *
 * @author
 *
 *
 */
class XceleratePlugin_Abstract {

    protected $options;

    public function __construct() {
        $this->options = FALSE;
        add_action('init', array($this, 'wp_hook_init'));
    }

    public function get_plugin_title() {
        return "XcelerateWP Plugin";
    }

    public function get_plugin_token() {
        return preg_replace("!\\W!", "_", $this->get_plugin_title());
    }

    public function wp_hook_init() {
        if (is_admin()) {
            $plugin_name = $this->get_plugin_token();
            add_action('admin_menu', array($this, 'wp_hook_admin_menu'));
            add_filter("plugin_action_links_$plugin_name", array($this, 'wp_hook_add_settings_link'));
        }
    }

    public function wp_hook_admin_menu() {
        add_options_page($this->get_plugin_title() . " Options", $this->get_plugin_title(), 'manage_options', dirname(__FILE__) . '/admin.php');
    }

    public function wp_hook_add_settings_link($links) {
        $settings = '<a href="options-general.php?page=' . basename(dirname(__FILE__)) . '/admin.php">Settings</a>';
        array_unshift($links, $settings);
        return $links;
    }

    public function set_option($name, $value) {
        // Make sure options are loaded and updated in memory.
        // If the option value isn't different, don't update Wordpress.
        $this->get_options();
        if ($this->options[$name] == $value)
            return TRUE;  // no error
        $this->options[$name] = $value;
        // Set the option value in Wordpress
        add_option($name, $value, FALSE, 'no');  // add option if not already exist
        update_option($name, $value);     // update option value if does exist

        return TRUE;
    }

    public function get_default_options() {
        return array();
    }

    public function get_options() {
        // Return cached values if available
        if ($this->options)
            return $this->options;

        // List of all options with their default values
        $option_defaults = $this->get_default_options();

        // Load options from database
        $this->options = array();
        foreach ($option_defaults as $key => $default_value) {
            $this->options[$key] = get_option($key, $default_value);
        }
        return $this->options;
    }

    public function get_option($name) {
        $options = $this->get_options();
        return @$options[$name];
    }

    public function restore_default_options() {
        foreach ($this->get_default_options() as $key => $value)
            $this->set_option($key, $value);
    }

    public function validate_options($options) {
        return FALSE;
    }

    protected function increase_php_limits() {
        // Don't abort script if the client connection is lost/closed
        @ignore_user_abort(true);
        // 2 hour execution time limits
        @ini_set('default_socket_timeout', 60 * 60 * 2);
        @set_time_limit(60 * 60 * 2);

        // Increase the memory limit
        $current_memory_limit = trim(@ini_get('memory_limit'));

        if (preg_match('/(\d+)(\w*)/', $current_memory_limit, $matches)) {
            $current_memory_limit = $matches[1];
            $unit = $matches[2];
            // Up memory limit if currently lower than 256M
            if ('g' !== strtolower($unit)) {
                if (( $current_memory_limit < 256 ) || ( 'm' !== strtolower($unit) ))
                    @ini_set('memory_limit', '256M');
            }
        }
        else {
            // Couldn't determine current limit, set to 256M to be safe
            @ini_set('memory_limit', '256M');
        }
    }
    
    public function apiRequest($method,$args=false){
        $args = json_encode($args);
        $url = "http://api.xceleratewp.com/?method=".$method."&instance=" . XCELWP_INSTANCE . "&apikey=" . XCELWP_APIKEY.'&args='.urlencode($args);

        $http = new WP_Http;
        $msg = $http->get($url);
        if (is_a($msg, 'WP_Error'))
            return false;
        if (!isset($msg['body']))
            return false;
        $data = json_decode($msg['body'], true);
        return $data;
    }

}
