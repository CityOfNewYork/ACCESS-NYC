<?php

/**
 * Plugin Name:  NYCO WordPress Composer Sync
 * Description:  Talk to Composer
 * Author:       NYC Opportunity
*/

require_once plugin_dir_path(__FILE__) . '/wp-composer-sync/WpComposerSync.php';

new NYCO\WpComposerSync();
