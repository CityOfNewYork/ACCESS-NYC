<?php

use Config\Paths as Path;

require_once get_template_directory() . '/lib/paths.php';

/**
 * Controllers
 */

require_once Path\controller('site');

new Controller\Site();

/**
 * Functions
 */

require_once Path\functions();

/**
 * Blocks
 */

Path\require_blocks();
