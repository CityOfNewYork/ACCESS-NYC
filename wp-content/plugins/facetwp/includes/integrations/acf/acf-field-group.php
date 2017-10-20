<?php

/**
 * ACF4 workaround
 *
 * We need to access some acf_field_group() methods. We're extending it
 * to override the constructor, preventing hooks from firing twice.
 */
class facetwp_acf_field_group extends acf_field_group
{
    function __construct() {
        // Nothing.
    }
}
