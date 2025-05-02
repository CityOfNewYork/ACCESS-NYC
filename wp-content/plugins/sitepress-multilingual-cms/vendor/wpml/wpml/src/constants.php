<?php

// WPML version.
define(
  'WPML_VERSION',
  defined( 'ICL_SITEPRESS_SCRIPT_VERSION' )
    ? ICL_SITEPRESS_SCRIPT_VERSION
    : '4.7.0'             // Start of wpml/wpml.
);

// Directories.
define( 'WPML_ROOT_DIR', __DIR__ . '/..' );
define( 'WPML_PUBLIC_DIR', WPML_ROOT_DIR . '/public' );


// Capabliities.
define( 'WPML_CAP_MANAGE_OPTIONS', 'manage_options' );
define( 'WPML_CAP_MANAGE_TRANSLATIONS', 'manage_translations' );
