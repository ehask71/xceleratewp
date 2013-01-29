<?php
if ( ! current_user_can( 'manage_options' ) )
    return false;

if ( MULTISITE && ! is_super_admin() ) {
    //echo 'You do not have permission';
    ?>
    <div class="wrap">
        <h2>Error</h2>
        <p>You do not have permission to access this.</p>
    </div>
    <?php
    return false;
}

$plugin = XceleratePlugin::instance();
$options = $plugin->get_options();
$site_info = $plugin->get_site_info();
$message   = '';
$error     = '';

// Process form submissions
if ( isset( $_POST['options'] ) && isset( $_POST['submit'] ) ) {
    check_admin_referer( XCELWP_INSTANCE . '-config' );

    foreach ( $options as $key => $value ) {
        if ( isset( $_POST['options'][$key] ) ) {
            $plugin->set_option( $key, $options[$key] = stripslashes( $_POST['options'][$key] ) );
        }
    }

    $error = $plugin->validate_options( $options );
    if ( empty( $error ) ) {
        $message = __( "Settings have been successfully updated", XCELWP_INSTANCE );
    }
}

// Process purging all caches
if ( excel_req( 'purge-all' ) ) {
    XceleratePlugin::purge_server_cache();
    $message = "Caching Servers Purged!";
}
// Switch Cache State
if(excel_req('switch-cache')){
   $res = XceleratePlugin::switchCache();
   $message = "Caching Switched!";
}
?>
<div class="wrap">
    <div style="float:left;padding-right: 20px;"><img src="<?php echo XCEL_PLUGIN_URL . '/images/xcelerate-wp.png';?>"></div>
    <h2 style="padding-top: 20px;margin-bottom:40px;"><?php echo $plugin->get_plugin_title(); ?></h2>
<?php if ( ! empty( $error ) ) : ?>
        <div class="error"><p><?php echo $error; ?></p></div>
<?php endif; ?>
<?php if ( ! empty( $message ) ) : ?>
        <div class="updated fade"><p><?php echo $message; ?></p></div>
<?php endif; ?>

    <p>Caching</p>
    <form method="post" name="options" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
    <?php wp_nonce_field( XCELWP_INSTANCE . '-config' ); ?>
        <table class="form-table">
            <tr>
                <td></td>
                <td>
                    Caching Servers (Html Cache) is <?php echo $plugin->getServerCacheStatus();?>
                </td>
            </tr>
            <tr>
		<td></td>
		<td style="border-top: 1px solid #c0c0c0;">
                    <input type="submit" name="purge-all" value="Purge Page-Cache" class="button-primary" onclick="return confirm('Please be patient, this sometimes takes a while.');"/>
                    ( Purges the page-cache )
		</td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="switch-cache" value="Caching on/off" class="button-primary" onclick=""/>
                ( Switches Page Cache on/off )
                </td>
            </tr>
        </table>
    </form>
</div>
<hr/>
<p>XcelerateWP v<?= XCEL_VERSION ?></p>
