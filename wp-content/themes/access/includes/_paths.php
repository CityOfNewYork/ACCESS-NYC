<?php

/**
 * Path organization for different scripts
 */

namespace Config\Paths;

/**
 * Functions
 */

function lib($name) {
  return get_template_directory() . "/includes/$name.php";
}

function controller($name) {
  return get_template_directory() . "/controllers/$name.php";
}

function config($name) {
  return get_template_directory() . "/includes/$name.php";
}
