<?php

if( !defined( 'ABSPATH' ) ) exit();

$active_tab = "dashboard";
if(!empty($_GET["tab"]) && in_array($_GET["tab"], ['dashboard', 'settings', 'debug'])) {

    $active_tab = $_GET["tab"];
}
?>

<div class="wrap limit-login-page-settings">
    <h2><?php echo __( 'Limit Login Attempts Reloaded', 'limit-login-attempts-reloaded' ); ?></h2>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo $this->get_options_page_uri(); ?>&tab=dashboard" class="nav-tab <?php if($active_tab == 'dashboard'){echo 'nav-tab-active';} ?> "><?php _e('Dashboard', 'limit-login-attempts-reloaded'); ?></a>
        <a href="<?php echo $this->get_options_page_uri(); ?>&tab=settings" class="nav-tab <?php if($active_tab == 'settings'){echo 'nav-tab-active';} ?> "><?php _e('Settings', 'limit-login-attempts-reloaded'); ?></a>
        <a href="<?php echo $this->get_options_page_uri(); ?>&tab=debug" class="nav-tab <?php if($active_tab == 'debug'){echo 'nav-tab-active';} ?>"><?php _e('Debug', 'limit-login-attempts-reloaded'); ?></a>
    </h2>

    <?php include_once(LLA_PLUGIN_DIR.'views/tab-'.$active_tab.'.php'); ?>
</div>

