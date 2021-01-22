<?php

if( !defined( 'ABSPATH' ) ) exit();

$active_tab = "settings";
$active_app = $this->get_option( 'active_app' );
if( !empty($_GET["tab"]) && in_array( $_GET["tab"], array( 'logs-local', 'logs-custom', 'settings', 'debug' ) ) ) {

	if(!$this->app && $_GET['tab'] === 'logs-custom') {

		$active_tab = 'logs-local';
	} else {

		$active_tab = sanitize_text_field( $_GET["tab"] );
	}
}
?>

<?php if( $active_app === 'local' ) : ?>
<div id="llar-header-upgrade-message">
    <p><span class="dashicons dashicons-info"></span>
        <?php echo sprintf( __( 'Thank you for using the free version of <b>Limit Login Attempts Reloaded</b>. <a href="%s" target="_blank">Upgrade to our cloud app</a> for enhanced protection, visual metrics & premium support.', 'limit-login-attempts-reloaded' ),
            'https://www.limitloginattempts.com/info.php?from=plugin-'.( ( substr( $active_tab, 0, 4 ) === 'logs' ) ? 'logs' : $active_tab )
        ); ?></p>
</div>
<?php endif; ?>

<div class="wrap limit-login-page-settings">
    <h2><?php echo __( 'Limit Login Attempts Reloaded', 'limit-login-attempts-reloaded' ); ?></h2>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo $this->get_options_page_uri('settings'); ?>" class="nav-tab <?php if($active_tab == 'settings'){echo 'nav-tab-active';} ?> "><?php _e('Settings', 'limit-login-attempts-reloaded'); ?></a>
        <?php if( $active_app === 'custom' ) : ?>
            <a href="<?php echo $this->get_options_page_uri('logs-custom'); ?>" class="nav-tab <?php if($active_tab == 'logs-custom'){echo 'nav-tab-active';} ?> "><?php _e('Logs', 'limit-login-attempts-reloaded'); ?></a>
        <?php else : ?>
            <a href="<?php echo $this->get_options_page_uri('logs-local'); ?>" class="nav-tab <?php if($active_tab == 'logs-local'){echo 'nav-tab-active';} ?> "><?php _e('Logs', 'limit-login-attempts-reloaded'); ?></a>
		<?php endif; ?>
        <a href="<?php echo $this->get_options_page_uri('debug'); ?>" class="nav-tab <?php if($active_tab == 'debug'){echo 'nav-tab-active';} ?>"><?php _e('Debug', 'limit-login-attempts-reloaded'); ?></a>

        <?php if($active_tab == 'logs-custom') : ?>
        <a class="llar-failover-link" href="<?php echo $this->get_options_page_uri('logs-local'); ?>"><?php _e( 'Failover', 'limit-login-attempts-reloaded' ); ?></a>
        <?php endif; ?>
    </h2>

    <?php include_once(LLA_PLUGIN_DIR.'views/tab-'.$active_tab.'.php'); ?>
</div>

