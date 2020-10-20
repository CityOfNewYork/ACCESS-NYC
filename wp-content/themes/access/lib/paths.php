<?php

/**
 * Path helpers shorthands for different files
 */

namespace ACCESS;

/**
 * Returns the path of a library file in the /lib directory.
 *
 * @param   String  $name  The name of the library file to get
 *
 * @return  String         The path of a library file.
 */
function lib($name) {
  return get_template_directory() . "/lib/$name.php";
}

/**
 * Returns the path of a controller file in the /controllers directory.
 *
 * @param   String  $name  The name of the controller to retrieve.
 *
 * @return  String         The full path of the controller file.
 */
function controller($name) {
  return get_template_directory() . "/controllers/$name.php";
}

/**
 * Returns the path of the functions file in the /lib directory where theme
 * function are stored.
 */
function functions() {
  return get_template_directory() . '/lib/functions.php';
}

/**
 * Gutenberg Blocks
 */

/**
 * Return the path of a Gutenberg Block.
 *
 * @param   String   $name  The name of the block to retrieve.
 * @param   Boolean  $uri   Wether to return the uri (including https://).
 *
 * @return  String          The path to the block.
 */
function block($name = false, $uri = false) {
  if ($name && $uri) {
    return get_template_directory_uri() . "/blocks/$name";
  } elseif ($name) {
    return get_template_directory() . "/blocks/$name.php";
  } else {
    return get_template_directory() . '/blocks/';
  }
}

/**
 * Requires all Gutenberg Blocks in the /blocks directory.
 */
function require_blocks() {
  foreach (scandir(block()) as $filename) {
    $path = block() . $filename;

    if (is_file($path)) {
      require $path;
    }
  }
}

/**
 * Shortcodes
 */

 /**
  * Returns the path of a shortcode or the shortcode directory.
  *
  * @param   String $name  The name of the shortcode to return.
  *
  * @return  String        The path of the shortcode or shortcode directory.
  */
function shortcode($name = false) {
  if ($name) {
    return get_template_directory() . "/shortcodes/$name.php";
  } else {
    return get_template_directory() . '/shortcodes/';
  }
}

/**
 * Require all shortcodes in the /shortcode directory.
 *
 * @param  String  $base  The base shortcode class for all shortcodes to extend.
 */
function require_shortcodes($base = 'shortcode') {
  require_once shortcode($base);

  foreach (scandir(shortcode()) as $filename) {
    $path = shortcode() . $filename;

    if (is_file($path) && $filename != $base . '.php') {
      require $path;
    }
  }
}
