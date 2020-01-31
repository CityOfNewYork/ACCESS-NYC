<?php

// phpcs:disable
/**
 * Plugin Name: Stat Collector
 * Description: Adds WordPress hooks to enable the logging of data from the site to a specified MySQL database. Currently, it collects information from the Drools Request/Response, and Send Me NYC SMS and Email messages.
 * Author:      Blue State Digital, maintained by NYC Opportunity
 */
// phpcs:enable

require plugin_dir_path(__FILE__) . '/stat-collector/StatCollector.php';
