<?php

/**
 * Plugin Name:  NYCO WP Open Data Transients
 * Description:  Manage Open Data requests with WordPress Transients.
 * Author:       NYC Opportunity
 * Requirements: The plugin doesn't include dependencies. These should be added
 *               to the root Composer file for the site (composer require ...)
 */

namespace nyco\WpOpenDataTransients;

/** Configuration for the settings page of the plugin. */
require_once 'Settings.php';

require_once 'Validations.php';

require_once 'NewTransient.php';

require_once 'Transients.php';

