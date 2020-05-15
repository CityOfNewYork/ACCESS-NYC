<?php

/**
 * Path shortands for different files
 */

namespace Path;

function lib($name) {
  return get_template_directory() . "/lib/$name.php";
}

function controller($name) {
  return get_template_directory() . "/controllers/$name.php";
}

function functions() {
  return get_template_directory() . '/lib/functions.php';
}

/**
 * Gutenberg Blocks
 */

function block($name = false, $uri = false) {
  if ($name & $uri) {
    return get_template_directory_uri() . "/blocks/$name";
  } elseif ($name) {
    return get_template_directory() . "/blocks/$name.php";
  } else {
    return get_template_directory() . '/blocks/';
  }
}

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

function shortcode($name = false) {
  if ($name) {
    return get_template_directory() . "/shortcodes/$name.php";
  } else {
    return get_template_directory() . '/shortcodes/';
  }
}

function require_shortcodes($base = 'shortcode') {
  require_once shortcode($base);

  foreach (scandir(shortcode()) as $filename) {
    $path = shortcode() . $filename;

    if (is_file($path) && $filename != $base . '.php') {
      require $path;
    }
  }
}
