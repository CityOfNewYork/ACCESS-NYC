<?php

namespace NYCO;

use Spyc;

class WpAssets {
  public $namespace = 'assets';

  public $config = 'config/integrations.yml';

  public $placeholder = 'donotprintthis.js';

  public $version;

  public function __construct() {
    $this->version = wp_get_theme()->version;
    $this->exp = WEEK_IN_SECONDS;

    return $this;
  }

  /**
   * Enqueue a hashed script based on it's name. Enqueue the minified version based on debug mode.
   * @param [string]  $name      The name of the script source
   * @param [boolean] $ugl       Optional, The post fix for minified files. Default: ''
   * @param [string]  $sep       Optional, The separator between the base name and the hash. Default: '.'
   * @param [array]   $deps      Optional, maps to wp_enqueue_script $deps. Default: array()
   * @param [array]   $in_footer Optional, maps to wp_enqueue_script $in_footer. Default: true
   * @param [string]  $ext       Optional, the extension of the file. Default: '.css'
   * @return array               Collecition containing directory, uri, filename, source, hash, and uglified boolean.
   */
  public function enqueueScript(
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

  /**
   * Enqueue a hashed style based on it's name.
   * @param [string]  $name  Optional, The base name of the stylesheet source. Default: 'style'
   * @param [boolean] $min   Optional, The post fix for minified files if you have two files. One that is minified and
   *                         one that is not. Default: ''
   * @param [string]  $sep   Optional, The separator between the base name and the hash. Default: '.'
   * @param [array]   $deps  Optional, maps to wp_enqueue_style $deps. Default: array()
   * @param [string]  $media Optional, maps to wp_enqueue_style $media. Default: 'all'
   * @param [string]  $ext   Optional, the extension of the file. Default: '.css'
   * @return array           Collecition containing the directory, uri, filename, source, hash, and minified boolean.
   */
  public function enqueueStyle(
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

  /**
   * Helper to add cross origin anonymous attribute to a specific script.
   * @param [string] $name The name of the script.
   */
  public function addCrossoriginAttr($name) {
    $name = end(explode('/', $name));

    add_filter('script_loader_tag', function ($tag, $handle) use ($name) {
      if ($name === $handle) {
        return preg_replace('/<script( )*/', '<script crossorigin="anonymous"$1', $tag);
      }
    }, 10, 2);
  }

  public function loadIntegrations($path = false) {
    $path = ($path) ? $path : $this->config;

    if (file_exists(WPMU_PLUGIN_DIR . '/' . $path)) {
      $config = Spyc::YAMLLoad(WPMU_PLUGIN_DIR . '/' . $path);
    } else {
      return false;
    }

    return $config;
  }

  /**
   * Enqueue Inline scripts and their source using wp_enqueue_script() and wp_add_inline_script()
   * https://developer.wordpress.org/reference/functions/wp_add_inline_script/
   *
   * Useful for cloud service integrations that are configured on the
   * client-side (Google Analytics, Webtrends, Rollbar.js, etc.) what require
   * loading a remote source but are configured with an inline script code block.
   *
   * Also enqueues inline styles if required by the configuration. This is not
   * possible by default and uses a technique described in this article
   * https://www.cssigniter.com/late-enqueue-inline-css-wordpress/
   *
   * Accepts a single configuration array with multiple scripts;
   * [
   *   array(
   *     'handle' => 'my-script-handle',
   *     'path' => 'https://url/to/script.js',
   *     'dep' => 'CONSTANT_MY_SCRIPT_IS_DEPENDENT_ON',
   *     'localize' => [
   *        'ARRAY_OF_CONSTANTS_TO_LOCALIZE_IN_SCRIPT',
   *        'CONSTANT_MY_SCRIPT_IS_DEPENDENT_ON'
   *      ], // in the script they should be written as '{{ MY_CONSTANT }}'
   *     'position' => 'head'/'body'/'footer', // where the script should go
   *     'inline' => array( // the inline script
   *       'path' => WPMU_PLUGIN_DIR . '/path/to/my-script.js',
   *       'position' => 'before' // wether it comes before or after the script
   *     ),
   *     'style' => array(
   *       'path' => WPMU_PLUGIN_DIR . '/path/to/css.css'
   *     )
   *   )
   * ... additional key value paired arrays ...
   * ]
   *
   * @return Array The same array with additional inline script contents
   */
  public function enqueueInline($script) {
    if (array_key_exists('dep', $script) && !defined($script['dep'])) {
      return $script;
    }

    // Shorthand for script
    $s = $script;

    // Use version argument if no specific version exists
    $v = (array_key_exists('version', $s)) ? $s['version'] : $this->version;

    /**
     * Enqueue scripts
     */

    // Register/Enqueue the script source if it is configured
    if (array_key_exists('path', $s)) {
      // Replace constant tags with real constants
      $s['path'] = self::localize($s['path'], $s['localize']);
      $src = self::scriptArgs($s['handle'], $s['path'], $s['in_footer']);

      wp_register_script(...$src);
      wp_enqueue_script(...$src);

      // Add inline script to source script if it exists
      if (array_key_exists('inline', $s)) {
        $s['inline']['contents'] = self::getFileContents($s['inline']['path'], $s['localize']);
        wp_add_inline_script($s['handle'], $s['inline']['contents'], $s['inline']['before']);
      }
    }

    /**
     * Handler for adding inline script if there is no source script
     */

    if (array_key_exists('inline', $s) && !array_key_exists('path', $s)) {
      // create fake source script so the inline script can be modified through
      // the script loader tag filter in other parts of the site.
      $inline = self::scriptArgs(
        $s['handle'] . '-inline',
        $this->placeholder,
        $s['in_footer']
      );

      $s['inline']['contents'] = self::getFileContents($s['inline']['path'], $s['localize']);

      wp_register_script(...$inline);
      wp_enqueue_script(...$inline);
      wp_add_inline_script($s['handle'] . '-inline', $s['inline']['contents'], $s['inline']['before']);

      // Remove the fake script from the printed script tag.
      add_filter('script_loader_tag', function($tag) {
        return self::removePlaceholder($tag);
      });
    }

    /**
     * Register the style
     */

    if (array_key_exists('style', $s)) {
      $s['style']['contents'] = self::getFileContents($s['style']['path'], $s['localize']);

      wp_register_style($s['handle'], false);
      wp_enqueue_style($s['handle']);
      wp_add_inline_style($s['handle'], $s['style']['contents']);
    }

    /**
     * Add a body tag if required by the integration
     */

    if (array_key_exists('body_open', $s)) {
      add_action('wp_body_open', function() use ($s) {
        $s['body_open']['contents'] = self::getFileContents($s['body_open']['path'], $s['localize']);
        echo $s['body_open']['contents'];
      });
    }

    return $s;
  }

  private function bodyOpen() {
  }

  /**
   * Removes the placeholder script created for including an inline script
   * @param   string  $tag  The script code block passed to the script_loader_tag WP hook
   * @return  string        The tag with the placeholder script removed
   */
  private function removePlaceholder($tag) {
    if (strpos($tag, $this->placeholder)) {
      $regex = '/\n?(<script (?:.+)?(src=(.)*' . $this->placeholder . '(.)*)><\/script>)\n?/';
      $tag = preg_replace($regex, '', $tag);
    }

    return $tag;
  }

  /**
   * Shorthand function for creating register and enqueue scripts arguments.
   * Returns the full set of args for @link https://developer.wordpress.org/reference/functions/wp_enqueue_script/
   * @param   String   $handle     Name for the script
   * @param   String   $src        Path or url for the script
   * @param   Boolean  $in_footer  Wether to have the script in the footer or head
   * @return  Array                Full array of arguments
   */
  private function scriptArgs($handle, $src, $in_footer) {
    return [$handle, $src, [], null, $in_footer];
  }

  /**
   * Takes a configuration object of scripts and registers WP Rest Routes for
   * the configured inline scripts.
   * @param   Object  $scripts  [$scripts description]
   * @return  Object            [return description]
   */
  public function registerRestRoutes($scripts) {
    return array_map(function($script) use ($namespace) {
      if (array_key_exists('inline', $script)) {
        return self::registerRestRoute($script);
      } else {
        return $script;
      }
    }, $scripts);
  }

  /**
   * Register a WP REST route for a particular script to load from
   * @param   Object    $script  A configured script with an inline object
   * @param   Function  $auth    The authentication method to use
   * @return  Object             The same script with the REST route and URL
   */
  private function registerRestRoute($script, $auth) {
    $script['inline']['route'] = self::restRoute($script['handle']);
    $script['inline']['url'] = self::restUrl($script['handle']);

    register_rest_route(self::restNamespace(), $script['inline']['route'], array(
      'methods' => 'GET',
      'permission_callback' => $auth,
      'callback' => function() use ($script) {
        header('Content-Type: application/javascript; charset=UTF-8');

        $contents = get_transient($script['inline']['path']);

        if (false === $contents) {
          $contents = self::getFileContents($script['inline']['path'], $script['localize']);
          set_transient($script['inline']['path'], $contents, $this->exp);
        }

        echo $contents;

        exit();
      }
    ));

    return $script;
  }

  /**
   * Get a WordPress REST API url for a particular script
   * @param   String  $handle  Name of the registered script
   * @return  String           Full URL of the script
   */
  private function restUrl($handle) {
    return rest_url('/' . self::restNamespace() . self::restRoute($handle));
  }

  /**
   * Create a consistent namespace for the registered WP REST API endpoints
   * @return  String  The namespace, default will be "assets/v{{ theme version }}"
   */
  private function restNamespace() {
    return $this->namespace . '/v' . $this->version;
  }

  /**
   * Create a consistent route for each script
   * @param   String  $handle  Name of the registered script
   * @return  String           The route, default will be "/{{ handle }}.js/"
   */
  private function restRoute($handle) {
    return "/$handle.js/";
  }

  /**
   * Takes file contents from the path argument in the WPMU_PLUGIN_DIR and
   * replaces template tag contents of the script with real ENV variables.
   * @param   String  $path      Path to a script file
   * @param   [type]  $localize  [$localize description]
   * @return  String             The contents of the script file
   */
  private function getFileContents($path, $localize) {
    $inline_path = WPMU_PLUGIN_DIR . '/' . $path;
    $contents = self::localize(file_get_contents($inline_path), $localize);

    return $contents;
  }

  /**
   * Replaces all instances of a set of constants with constant values in string
   * @param   String  $string   The string to localize
   * @param   Array  $localize  An array of constants to pass to the string
   * @return  String            The localized string
   */
  private function localize($string, $localize) {
    foreach ($localize as $value) {
      if (defined($value)) {
        $string = str_replace("{{ $value }}", constant($value), $string);
      }
    }

    return $string;
  }
}
