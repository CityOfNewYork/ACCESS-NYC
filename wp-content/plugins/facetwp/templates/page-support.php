<?php

$disabled = true;
$activation = get_option( 'facetwp_activation' );

if ( ! empty( $activation ) ) {
    $activation = json_decode( $activation, true );
    if ( 'success' == $activation['status'] ) {
        $disabled = false;
    }
}

if ( $disabled ) {
    echo '<h3>Active License Required</h3>';
    echo '<p>Please activate or renew your license to access support.</p>';
    return;
}

$plugins = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );
$theme = wp_get_theme();

ob_start();

?>
Home URL:                   <?php echo home_url(); ?>

FacetWP License:            <?php echo '~' . substr( get_option( 'facetwp_license' ), -8 ); ?>

WordPress Version:          <?php echo get_bloginfo( 'version' ); ?>

Active Theme:               <?php echo $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ); ?>


PHP Version:                <?php echo phpversion(); ?>

MySQL Version:              <?php echo $wpdb->get_var( "SELECT VERSION()" ); ?>

Web Server Info:            <?php echo $_SERVER['SERVER_SOFTWARE']; ?>

PHP Memory Limit:           <?php echo ini_get( 'memory_limit' ); ?>

PHP Memory Usage:           <?php echo round( memory_get_usage( true ) / 1048576 ) . 'M'; ?>


<?php
foreach ( $plugins as $plugin_path => $plugin ) {
    if ( in_array( $plugin_path, $active_plugins ) ) {
        echo $plugin['Name'] . ' ' . $plugin['Version'] . "\n";
    }
}

$sysinfo = ob_get_clean();
$sysinfo = preg_replace( "/[ ]{2,}/", ' ', trim( $sysinfo ) );
$sysinfo = str_replace( "\n", '{n}', $sysinfo );
$sysinfo = urlencode( $sysinfo );
?>

<script>
(function($) {
    $(function() {
        $(document).on('click', '.facetwp-tab[rel="support"]', function() {
            if ( 1 > $('.fwp-iframe-wrapper iframe').length) {
                var iframe = '<iframe src="https://facetwp.com/create-ticket/?sysinfo=<?php echo $sysinfo; ?>" style="width:100%; height:600px"></iframe>';
                $('.fwp-iframe-wrapper').html(iframe);
            }
        });
    });
})(jQuery);
</script>

<div class="fwp-iframe-wrapper"></div>
