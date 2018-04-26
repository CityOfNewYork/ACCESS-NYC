<?php

namespace Nyco\Enqueue;

/**
 * Enqueue a hashed style based on it's name.
 *
 * @param [string]  $name  Optional, The base name of the stylesheet source.
 *                         Default: 'style'
 * @param [boolean] $min   Optional, The post fix for minified files if you have
 *                         two files. One that is minified and one that is not.
 *                         Default: ''
 * @param [string]  $sep   Optional, The separator between the base name and the
 *                         hash. Default: '.'
 * @param [array]   $deps  Optional, maps to wp_enqueue_style $deps.
 *                         Default: array()
 * @param [string]  $media Optional, maps to wp_enqueue_style $media.
 *                         Default: 'all'
 * @param [string]  $ext   Optional, the extension of the file.
 *                         Default: '.css'
 * @return array           Collecition containing the directory, uri, filename,
 *                         source, hash, and minified boolean.
 */
function style(
  $name = 'style',
  $min = '',
  $sep = '.',
  $deps = [],
  $media = 'all',
  $ext = '.css'
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
  // Set the $min variable if debug is on
  $min = (isset($_GET['debug'])) ? '' : $min;
  // Build the file name
  $filename = "$name$sep$hash$min$ext";
  // Build the source
  $src = "$uri/$filename";

  // Enqueue the style
  wp_enqueue_style($name, $src, $deps, null, $media);

  // Return what we've found
  return array(
    'directory' => $dir,
    'uri' => $uri,
    'filename' => $filename,
    'source' => $src,
    'hash' => $hash,
    'minified' => ($min !== '') ? true : false
  );
}
