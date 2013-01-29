<?php
/**
 * Project: Xcelerate WP Plugin
 * @author Eric
 */

// Build URLS
$current_domain = $_SERVER['HTTP_HOST'];
$root_domain = substr($curr_domain,0,4) == "www." ? substr($current_domain,4) : $current_domain;
$re_current_domain = "http://(?:www\\.)?".preg_quote($root_domain);
$re_current_domains = array( "(?=/[^/])", $re_current_domain );
$current_domains = array($root_domain,"www.$root_domain");

define( 'XCEL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once( XCEL_PLUGIN_DIR . '/xcelerate-abstract.php');
require_once( XCEL_PLUGIN_DIR . '/utils.inc.php' );
require_once( ABSPATH . "wp-admin/includes/plugin.php" );

if( defined('WP_ALLOW_MULTISITE')  ) {
	require_once(XCEL_PLUGIN_DIR.'/network.php');
}

if ( ! function_exists( 'username_exists' ) ) {
    require_once( ABSPATH . "wp-includes/registration.php" );
}
if ( ! function_exists( 'home_url' ) ){
    function home_url() {
        return get_bloginfo( 'url' );
    }
}

?>
