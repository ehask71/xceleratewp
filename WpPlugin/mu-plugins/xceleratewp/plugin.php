<?php

/**
 * Project: Xcelerate WP Plugin
 * @author Eric
 */
require_once 'requires.php';

class XceleratePlugin extends XceleratePlugin_Abstract {

    public $is_widget = false;
    
    public function instance() {
        static $self = false;
        if (!$self) {
            $self = new XceleratePlugin();
            ob_start(array($self, 'filter_html_output'));
        }
        return $self;
    }

    // Init WP hooks
    public function wp_hook_init() {

        parent::wp_hook_init();
        $this->set_xcel_auth_cookie();
        if (is_admin()) {
            add_action('admin_init', create_function('', 'remove_action("admin_notices","update_nag",3);'));
            add_action('admin_head', array($this, 'remove_upgrade_nags'));
            add_filter('site_transient_update_plugins', array($this, 'disable_indiv_plugin_update_notices'));
            // Load Styles
	    wp_enqueue_style('xceleratewp', XCEL_PLUGIN_URL . '/css/xceleratewp.css');
            //admin menu hooks
            if (is_multisite()) {
                $this->upload_space_load();
                add_action('network_admin_menu', array($this, 'wp_hook_admin_menu'));
            } else {
                add_action('admin_menu', array($this, 'wp_hook_admin_menu'));
            }
        }

        //add_action('template_redirect',array($this,'is_404'),0);
        add_action('admin_bar_menu', array($this, 'xcel_admin_bar'), 80);
        //add_filter( 'site_url', array($this,'wp_hook_site_url') );
        add_filter('use_http_extension_transport', '__return_false');
        add_action('wp_footer', array($this, 'xcel_powered_by'));
        remove_action('wp_head', 'wp_generator');
        if (!function_exists('httphead')) {
            add_filter('template_include', array($this, 'httphead'));
        }
        //add_filter('query',array($this,'query_filter'));
        add_action('twentyeleven_credits', array($this, 'xcel_shoutout'));

        if (defined('WP_TURN_OFF_ADMIN_BAR') && true === WP_TURN_OFF_ADMIN_BAR) {
            global $show_admin_bar;
            $show_admin_bar = false;
        }

        // Disable Headway theme gzip -- it blocks us from being able to CDN-replace and isn't necessary anyway.
        add_filter('headway_gzip', '__return_false');

        // Emit debug message, maybe
        if (isset($_REQUEST['DEBUGGING_TRUE'])) {
            global $wp_object_cache;
            if (isset($wp_object_cache) && is_object($wp_object_cache) && $wp_object_cache) {
                add_action("wp_footer", array($wp_object_cache, 'stats'));
            }
        }
    }

    public function wp_hook_admin_menu() {
        // Variations due to type of site
        if (is_multisite()) {
            $capability = 'manage_network';
            $position = -1;
        } else {
            $capability = 'manage_options';
            $position = 0;
        }

        // The main page
        add_menu_page('XcelerateWP', 'XcelerateWP', $capability, dirname(__FILE__), array($this, 'xcel_admin_page'), XCEL_PLUGIN_URL . '/images/xcelerate-icon-16.png', $position);

        // Direct link to user portal
        //add_submenu_page( 'wpengine-common', 'User Portal', 'User Portal', $capability, 'wpe-user-portal', array( $this, 'redirect_to_user_portal' ) );
        // Direct link to Zendesk
        // add_submenu_page( 'wpengine-common', 'Support System', 'Support System', $capability, 'wpe-support-portal', array( $this, 'redirect_to_zendesk' ) );
    }

    public function xcel_adminbar() {
        global $wp_admin_bar;

        // Make sure we're supposed to do this.
        if (!$this->is_xcel_admin_bar_enabled())
            return;

        $user = wp_get_current_user();

        $wp_admin_bar->add_menu(array('id' => 'xcel_adminbar', 'title' => 'XcelerateWP Quick Links'));
        //$wp_admin_bar->add_menu( array( 'id'	=> 'wpengine_adminbar_status','parent' => 'wpengine_adminbar', 'title'  => 'Status Blog', 'href'   => 'http://wpengine.wordpress.com' ) );
        //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_faq','parent' => 'wpengine_adminbar', 'title'  => 'Support FAQ', 'href'   => 'http://support.wpengine.com/' ) );
        //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_support','parent' => 'wpengine_adminbar', 'title'  => 'Get Support', 'href'   => 'http://wpengine.zendesk.com' ) );
        // Leave these for admins only by checking for the 'manage_options' capability
        if ($user->has_cap('manage_options')) {
            //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_errors','parent' => 'wpengine_adminbar', 'title'  => 'Blog Error Log', 'href'   => $this->get_error_log_url() ) );
            $wp_admin_bar->add_menu(array('id' => 'xcel_adminbar_cache', 'parent' => 'xcel_adminbar', 'title' => 'Clear Cache', 'href' => $this->get_plugin_admin_url('admin.php?page=xceleratewp&purge-cache=1')));
        }
    }

    public function xcel_admin_page() {
        include(dirname(__FILE__) . "/admingui.php");
    }

    public function xcel_admin_bar() {
        global $wp_admin_bar;

        // Make sure we're supposed to do this.
        //if (!$this->is_xcel_admin_bar_enabled())
        //return;

        $user = wp_get_current_user();

        $wp_admin_bar->add_menu(array('id' => 'xcel_adminbar', 'title' => 'XcelerateWP Quick Links'));
        //$wp_admin_bar->add_menu( array( 'id'	=> 'wpengine_adminbar_status','parent' => 'wpengine_adminbar', 'title'  => 'Status Blog', 'href'   => 'http://wpengine.wordpress.com' ) );
        //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_faq','parent' => 'wpengine_adminbar', 'title'  => 'Support FAQ', 'href'   => 'http://support.wpengine.com/' ) );
        //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_support','parent' => 'wpengine_adminbar', 'title'  => 'Get Support', 'href'   => 'http://wpengine.zendesk.com' ) );
        // Leave these for admins only by checking for the 'manage_options' capability
        if ($user->has_cap('manage_options')) {
            //$wp_admin_bar->add_menu( array( 'id'    => 'wpengine_adminbar_errors','parent' => 'wpengine_adminbar', 'title'  => 'Blog Error Log', 'href'   => $this->get_error_log_url() ) );
            $wp_admin_bar->add_menu(array('id' => 'xcel_adminbar_cache', 'parent' => 'xcel_adminbar', 'title' => 'Clear Cache', 'href' => $this->get_plugin_admin_url('admin.php?page=xceleratewp&purge-cache=1')));
        }
    }

    public function xcel_powered_by($affiliate_code = null) {
        if (!isset($this->already_emitted_powered_by) || $this->already_emitted_powered_by != true) {
            echo($this->get_powered_by_html($affiliate_code));
            $this->already_emitted_powered_by = true;
        }
    }

    public function remove_upgrade_nags() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery('#dashboard_right_now a.button').css('display','none');
            });

        </script>
        <?php

    }

    public function disable_indiv_plugin_update_notices($value) {
        $plugins_to_disable_notices_for = array();
        $basename = '';
        foreach ($plugins_to_disable_notices_for as $plugin)
            $basename = plugin_basename($plugin);
        if (isset($value->response[@$basename]))
            unset($value->response[$basename]);
        return $value;
    }

    public function purge_server_cache($pid = null) {
        global $xcel_domains;
        global $xcel_cache_servers;
        global $wpdb;

        if (isset($purge_counter) && $purge_counter > 2)
            return false;
        $blog_url = home_url();
        $blog_url_parts = @parse_url($blog_url);
        $blog_domain = $blog_url_parts['host'];

        $paths = array();  // will leave empty if we want a purge-all
        $purge_thing = false;

        // Purge All
        $paths[] = "/*";  // full blog purge
        $purge_thing = true;
        $purge_domains = $xcel_domains;
        if (isset($wpdb->dmtable)) {
            $rows = $wpdb->get_results("SELECT domain FROM {$wpdb->dmtable}");
            foreach ($rows as $row) {
                $purge_domains[] = strtolower($row->domain);
            }
            $purge_domains = array_unique($purge_domains);
        }

        if (!count($paths))
            return;  // short-circuit if there's nothing to do.
        $paths = array_unique($paths);

        if (!isset($purge_counter))
            $purge_counter = 1;
        else
            $purge_counter++;

        if (count($xcel_domains) > 8) {
            $purge_domains = array($blog_domain);
        }

        foreach ($xcel_cache_servers as $varnish) {
            foreach ($purge_domains as $hostname) {
                foreach ($paths as $path) {
                    error_log("####: $varnish: $hostname, $path");
                    XceleratePlugin::http_request_async("PURGE", $varnish, 80, $hostname, $path, array(), 0);
                }
            }
        }

        return true;
    }

    public function real_ip() {
        $this->process_internal_command();
    }

    public function filter_html_output($html) {

        return $html;
    }

    public function get_site_info() {
        static $cached_site_info = null;
        if (!$cached_site_info) {
            $r = new stdClass;
            $r->name = XCELWP_INSTANCE;
            /* $r->cluster = WPE_CLUSTER_ID;
              $r->is_pod = defined('WPE_ISP') ? WPE_ISP : FALSE;
              $r->lbmaster = $r->is_pod ? "pod-" . $r->cluster . ".wpengine.com" : "lbmaster-" . $r->cluster . ".wpengine.com";
              $r->public_ip = gethostbyname($r->lbmaster);
              $r->sftp_host = ( $r->is_pod ? $r->name : ( $r->cluster == 1 ? "sftp" : "sftp" . $r->cluster )) . ".wpengine.com";
              $r->sftp_port = ( $r->cluster == 1 ? 22000 : 22 ); */
            $cached_site_info = $r;
        }
        return $cached_site_info;
    }

    public function get_plugin_admin_url($url = 'admin.php?page=xceleratewp') {
        return is_multisite() ? network_admin_url($url) : admin_url($url);
    }

    public function upload_space_load() {
        
    }

    public static function http_request_async($method, $domain, $port, $hostname, $uri, $extra_headers = array(), $wait_ms = 100) {
        if (!$hostname)
            $hostname = $domain;
        $fp = fsockopen($domain, $port, $errno, $errstr, /* connect timeout: */ 1.0);
        if (!$fp) {
            error_log("Async Request Error: $errno, $errstr: $domain:$port");
            return false;
        }
        $headers = "Host: $hostname\r\nConnection: close\r\n";
        if (is_array($extra_headers)) {
            foreach ($extra_headers as $k => $v)
                $headers .= "$k: $v\r\n";
        }
        $send = "$method $uri HTTP/1.0\r\n$headers\r\n";
        fwrite($fp, $send);
        fflush($fp);  // make sure that request got sent
        if ($wait_ms > 0)
            usleep($wait_ms * 1000);
        else {   // actually wait for the response
            $response = "";
            while (!!($line = fgets($fp))) {
                $response .= $line . "\n";
            }  // get past the HTTP header
            //error_log("Request Response: $response");
            usleep(100);
            fgets($fp);  // more stuff
            fclose($fp);  // all done
        }
        return true;
    }

    public function process_internal_command() {
        $cmd = excel_req('xcel-cmd');
        if (!$cmd)
            return;
        mail('ehask71@gmail.com', 'Xcelerate', print_r($cmd, 1));

        exit(0);
    }

    public function set_xcel_auth_cookie() {
        $xcel_cookie = 'xcel-auth';

        // If not-authenticated, delete our cookie in case it exists.
        if (!wp_get_current_user() || !current_user_can('edit_pages')) {
            if (isset($_COOKIE[$xcel_cookie]))   // normally isn't set, so this optimization happens a lot
                setcookie($xcel_cookie, '', time() - 1000000, '/');
            return;
        }

        // Authenticated, so set the cookie properly.  No need if it's already set properly.
        $cookie_value = md5('wpe_auth_salty_dog|' . WPE_APIKEY);
        if (@$_COOKIE[$xcel_cookie] != $cookie_value)
            setcookie($xcel_cookie, $cookie_value, 0, '/');
    }

    public function httphead($template) {
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD')
            return false;

        return $template;
    }

    private static function get_path_trailing_slash($path) {
        if (substr($path, -1) != '/')
            return $path . '/';
        return $path;
    }
    
    public function get_powered_by_html($aff){
        if ( !$this->is_widget)
            return "";

        $this->already_emitted_powered_by = true;
        //$html = $this->view('general/powered-by', false, false);
        //$html = preg_replace("#( href=\".*?)(\")#", "\\1?a_aid=$affiliate_code\\2", $html);
        $html = 'XcelerateWP <a href="http://xceleratewp.com" title="'. esc_attr_e( 'Managed WordPress Hosting', 'xceleratewp' ).'">'.printf( __( '%s.', 'xceleratewp' ), 'WordPress Hosting' ).'</a>';
        return "<span class=\"promo\">$html</span>";
    }

    public function xcel_shoutout(){
        if (get_option('stylesheet') != 'twentyeleven' && get_option('template') != 'twentyeleven')
            return false;
        if ($this->already_emitted_powered_by == true)
            return false;

        //to prevent repeating
        $this->already_emitted_powered_by = true;
        ?>
        <div id="site-host">
            XcelerateWP <a href="http://xceleratewp.com" title="<?php esc_attr_e('Managed WordPress Hosting', 'xceleratewp'); ?>"><?php printf(__('%s.', 'xceleratewp'), 'WordPress Hosting'); ?></a>
        </div>
        <?php
    }
    
    public function getServerCacheStatus($live=false){
        global $xcel_domains;
        $blog_domain = $xcel_domains[0];
        $method = ($live)?'get-cache-status-live':'get-cache-status';
        $args = array('domain'=> $blog_domain);
        
        $data = $this->apiRequest($method, $args);
        if($data['cache-status'] == 'yes'){
            return '<span style="color:green;font-weight:strong;">Enabled</span>';
        }
        return '<span style="color:red;font-weight:strong;">Disabled</span>';
    }
    
    public function switchCache(){
        global $xcel_domains,$xcel_cache_servers;
        $blog_domain = $xcel_domains[0];
        $args = array('domain'=> $blog_domain,'varnish'=> $xcel_cache_servers[0]);
        $method = 'switch-cache';

        $data = $this->apiRequest($method, $args);
        return $data;
    }
    
    public function setTTL() {
        global $xcel_domains,$xcel_cache_servers;
        $blog_domain = $xcel_domains[0];
        $args = array('domain'=> $blog_domain);
        $method = 'set-base-ttl';
        
        $data = $this->apiRequest($method, $args);
        return $data;
    }

}

$xcelerate = XceleratePlugin::instance();
add_action('plugins_loaded', array($xcelerate, 'real_ip'));
?>
