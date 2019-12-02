<?php

/**
 * Plugin Name:  NYCO WP Open Data Transients
 * Description:  Interface for saving Open Data endpoints as WordPress Transients.
 * Author:       NYC Opportunity
 * Requirements: The plugin doesn't include dependencies.
 */

namespace nyco\WpOpenDataTransients;

/** Configuration for the settings page of the plugin. */
require_once 'Settings.php';

/** Validations for settings */
require_once 'Validations.php';

/** Admin action for saving transients */
require_once 'NewTransient.php';

/** API for setting/getting transients */
require_once 'Transients.php';
