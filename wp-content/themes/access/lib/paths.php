<?php

/**
 * Path organization for different scripts
 */

namespace Config\Paths;

function controller($name) {
  return get_template_directory() . "/controllers/$name.php";
}

function functions() {
  return get_template_directory() . '/lib/functions.php';
}

function blocks($uri = false) {
  if ($uri) {
    return get_template_directory_uri() . '/blocks/';
  } else {
    return get_template_directory() . '/blocks/';
  }
}

function require_blocks() {
  foreach (scandir(blocks()) as $filename) {
    $path = blocks() . $filename;
    if (is_file($path)) {
      require $path;
    }
  }
}
