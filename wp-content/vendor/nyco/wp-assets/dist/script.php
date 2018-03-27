<?php

namespace Nyco\Enqueue;

/**
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 *
 * @param [string]  $name      The name of the script source
 * @param [boolean] $ugl       Optional, The post fix for minified files.
 *                             Default: ''
 * @param [string]  $sep       Optional, The separator between the base name
 *                             and the hash. Default: '.'
 * @param [array]   $deps      Optional, maps to wp_enqueue_script $deps.
 *                             Default: array()
 * @param [array]   $in_footer Optional, maps to wp_enqueue_script $in_footer.
 *                             Default: true
 * @param [string]  $ext       Optional, the extension of the file.
 *                             Default: '.css'
 * @return array               Collecition containing the directory, uri,
 *                             filename, source, hash, and uglified boolean.
 * @return [null]
 */
function script(
  $name = 'main',
  $ugl = '',
  $sep = '.',
  $deps = array(),
  $in_footer = true,
  $ext = '.js'
) {
  $dir = get_template_directory();
  $uri = get_template_directory_uri();

  // If the name includes a path, separate them.
  if (strpos($name, '/') !== false) {
    $path = explode('/', $name);
    $name = $path[sizeof($path) - 1];
    unset($path[sizeof($path) - 1]);
    $path = implode('/', $path);
    $dir = "$dir/$path";
    $uri = "$uri/$path";
  }

  // Scan the directory for the file with the name.
  $files = array_filter(
    scandir($dir),
    function ($var) use ($name, $sep) {
      return (strpos($var, "$name$sep") !== false);
    }
  );

  // Get the hash from the first matched file.
  $hash = str_replace(["$name$sep", $ext], '', array_values($files)[0]);
  // Set the $ugl variable if debug is on
  $ugl = (isset($_GET['debug'])) ? '' : $ugl;
  // Build the file name
  $filename = "$name$sep$hash$ugl$ext";
  // Build the source
  $src = "$uri/$filename";

  // Enqueue the script
  wp_enqueue_script($name, $src, $deps, null, $in_footer);

  // Return what we've found
  return array(
    'directory' => $dir,
    'uri' => $uri,
    'filename' => $filename,
    'source' => $src,
    'hash' => $hash,
    'uglified' => ($ugl !== '') ? true : false
  );
}
