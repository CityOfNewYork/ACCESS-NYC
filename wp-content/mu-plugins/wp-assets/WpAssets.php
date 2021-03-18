<?php

namespace NYCO;

use Spyc;

class WpAssets {
  /** @var String Will be set to the template directory in the constructor */
  public $templates;

  /** @var String Will be set to the template directory uri in the constructor */
  public $uri;

  /** @var String The directory within $templates for pre-compiled static assets */
  public $assets = 'assets/';

  /** @var String The directory within $assets for scripts */
  public $scripts = 'scripts/';

  /** @var String The directory within $assets for scripts */
  public $styles = 'styles/';

  /** @var String The directory within mu for scripts */
  public $config = 'config/integrations.yml';

  /** @var String Script placeholder that is de-registered for adding inline scripts */
  public $placeholder = 'donotprintthis.js';

  /** @var String Namespace for registering REST routes */
  public $namespace = 'assets';

  /** @var String Namespace for Option name in wp_options */
  public $optionNamespace = 'options_';

  /** @var String Will be set to the theme version in the constructor for REST routes namespace */
  public $version;

  /** @var Number Will be set to the WordPress constant for REST routes transient cache expiration */
  public $exp;

  /**
   * Constructor
   *
   * @return  This
   */
  public function __construct() {
    $this->templates = get_stylesheet_directory();

    $this->uri = get_stylesheet_directory_uri();

    $this->version = wp_get_theme()->version;

    $this->exp = WEEK_IN_SECONDS;

    return $this;
  }

  /**
   * Register and/or enqueue a hashed script based on it's name.  File name should
   * match the pattern "scripts.{{ hash }}.js" by default. The separator between the
   * filename and the hash can be configured.
   *
   * @param   String   $handle     The name of the file without a hashname. Default: 'scripts'
   * @param   Boolean  $enqueue    Wether to enqueue the file or not. Default: true
   * @param   Array    $deps       Maps to wp_register/enqueue_script $deps. Default: array()
   * @param   String   $ver        Maps to wp_register/enqueue_script $ver. Default: null
   * @param   Boolean  $in_footer  Maps to wp_register/enqueue_script $in_footer. Default: true
   * @param   String   $sep        The separator between the base name and the hash. Default: '.'
   *
   * @return  Array                Key/value pair including registered Boolean, enqueued Boolean,
   *                               and found source String of file.
   */
  public function addScript(
      $handle = 'scripts',
      $enqueue = true,
      $deps = array(),
      $ver = null,
      $in_footer = true,
      $sep = '.'
  ) {
    // Build the source
    $src = self::findSrc($handle, '.js', $sep);

    /**
     * Add the script
     */

    $registered = wp_register_script($handle, $src, $deps, $ver, $in_footer);

    if ($enqueue) {
      wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
    } else {
      $enqueue = false;
    }

    // Return what we've found
    return array(
      'source' => $src,
      'registered' => $registered,
      'enqueued' => $enqueue
    );
  }

  /**
   * Register and/or enqueue a hashed style based on it's name. File name should
   * match the pattern "styles.{{ hash }}.css". The separator between the filename
   * and the hash can be configured.
   *
   * @param   String   $handle   The name of the file without a hashname. Default: 'styles'
   * @param   Boolean  $enqueue  Wether to enqueue the file or not. Default: true
   * @param   Array    $deps     Maps to wp_register/enqueue_style $deps. Default: array()
   * @param   String   $ver      Maps to wp_register/enqueue_style $ver. Default: null
   * @param   String   $media    Maps to wp_register/enqueue_style $media. Default: 'all'
   * @param   String   $sep      The separator between the base name and the hash. Default: '.'
   *
   * @return  Array              Returns Key/value pair including wp_register_script response,
   *                             wp_enqueue_script response, and the source uri of the file.
   */
  public function addStyle(
      $handle = 'styles',
      $enqueue = true,
      $deps = [],
      $ver = null,
      $media = 'all',
      $sep = '.'
  ) {
    // Build the source
    $src = self::findSrc($handle, '.css', $sep);

    /**
     * Add the style
     */

    $registered = wp_register_style($handle, $src, $deps, $ver, $media);

    if ($enqueue) {
      $enqueued = wp_enqueue_style($handle, $src, $deps, $ver, $media);
    } else {
      $enqueued = false;
    }

    // Return what we've found
    return array(
      'source' => $src,
      'registered' => $registered,
      'enqueued' => $enqueued
    );
  }

  /**
   * Builds the source based on the contents of the assets directory
   *
   * @param   String  $handle  The main prefix of the filename
   * @param   String  $ext     The extension of the filename. Default '.js'
   * @param   String  $sep     The seperator between the hash and the filename. Default '.'
   *
   * @return  String           The URI source of the script.
   */
  private function findSrc($handle, $ext = '.js', $sep = '.') {
    // Scan the proper directory of the file.
    $sub = ($ext === '.js') ? $this->scripts : $this->styles;
    $files = self::find($sub, $handle, $sep);

    // Get the hash from the first matched file.
    $hash = str_replace(["$handle$sep", $ext], '', array_values($files)[0]);

    // Build the file name
    $filename = "$handle$sep$hash$ext";

    // Build the source
    return $this->uri . '/' . $this->assets . $sub . $filename;
  }

  /**
   * Uses the script_loader_tag filter to add attributes to a specific script.
   * For example to add crossorigin="use-credentials" to a script.
   *
   * If the value of the the attribute is a boolean, only the attribute will be
   * set for example if async: true only async will be added to the script.
   *
   * @param  String  $name  The name of the script.
   * @param  String  $attr  The name of the attribute ex: crossorigin.
   * @param  String or Boolean $value The value of the attribute ex: "anonymous"
   */
  public function addAttr($name, $attr, $value) {
    add_filter('script_loader_tag', function ($tag, $handle) use ($name, $attr, $value) {
      if ($name === $handle) {
        $script = '<script ' . (is_bool($value) ? "$attr " : "$attr=$value " ) . '$1';
        return preg_replace('/<script( )*/', $script, $tag);
      } else {
        return $tag;
      }
    }, 10, 2);
  }

  /**
   * This retrieves an integration configuration file from the MU Plugins directory.
   * By default it will look for a YAML file at config/integrations.yml that contains
   * an array of individual configuration objects then it converts the YAML file to
   * a PHP Array and returns it. This is meant to be used with the ->enqueueInline( ...args )
   * method.
   *
   * @param   String  $path  Accepts a custom to the integrations file within the
   *                         Must Use Plugins directory.
   *
   * @return  Array          An array of individual configuration objects.
   */
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
   * @param   Array  $script  Accepts a single key/value array of a configuration.
   *                          Refer to the `->loadIntegrations( ...args )` method.
   *                          PHP example below;
   * [
   *   array(
   *     'handle' => 'my-script-handle',
   *     'path' => 'https://remote/url/to/integration/source.js',
   *     'dep' => 'CONSTANT_MY_SCRIPT_IS_DEPENDENT_ON',
   *     'localize' => [
   *        'ARRAY_OF_CONSTANTS_TO_LOCALIZE_IN_SCRIPT',
   *        'CONSTANT_MY_SCRIPT_IS_DEPENDENT_ON'
   *      ], // in the script they should be written as '{{ MY_CONSTANT }}'
   *     'in_footer' => true/false, // where the script should go
   *     'inline' => array( // the inline script
   *       'path' => WPMU_PLUGIN_DIR . '/path/to/my-script.js',
   *       'position' => 'before' // wether it comes before or after the script
   *     )
   *     'attrs' => array(
   *        'crossorigin' => 'anonymous'
   *     ),
   *     'style' => array(
   *       'path' => WPMU_PLUGIN_DIR . '/path/to/css.css'
   *     )
   *    'body_open' => array(
   *       'path' => 'config/integrations/body/a-html-tag-to-include-in-the-body.html'
   *    )
   *   ),
   *   ... additional key value paired arrays ...
   * ]
   *
   * @return  Array  The same array with additional inline script contents
   */
  public function addInline($script) {
    $option = $this->getOptionValue($script['handle']);

    if ($option === false) {
      return $script;
    }

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
        wp_add_inline_script($s['handle'], $s['inline']['contents'], $s['inline']['position']);
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
      wp_add_inline_script($s['handle'] . '-inline', $s['inline']['contents'], $s['inline']['position']);

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

    /**
     * Add attributes to script tag
     */

    if ($s['attrs']) {
      foreach ($s['attrs'] as $attr => $value) {
        self::addAttr($s['handle'], $attr, $value);
      }
    }

    return $s;
  }

  /**
   * Returns the value for a record in the wp_options table.
   *
   * @param $optionName The name of the script handle.
   *
   * @return  Boolean The option_value set on the wp_options table as Boolean.
   *
   */
  private function getOptionValue($optionName) {
    $value = get_option($this->optionNamespace . $optionName, 'ON');
    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

    return $value;
  }

  /**
   * Uses an array of configuration objects to register WP Rest Routes that act
   * as JavaScript files instead of inline scripts.
   *
   * @param   Array     $scripts  Array of integration objects (use ->loadIntegrations()
   *                              to retrieve them).
   * @param   Function  $auth     The authentication function to use for routes (passed
   *                              to register_rest_route() as the 'permission_callback'
   *                              argument).
   *
   * @return  Array               Returns the integrations configuration with all rest
   *                              route details.
   */
  public function registerRestRoutes($scripts, $auth) {
    return array_map(function($script) use ($auth) {
      if (array_key_exists('inline', $script)) {
        return self::registerRestRoute($script, $auth);
      } else {
        return $script;
      }
    }, $scripts);
  }

  /**
   * Scans the specified asset directory of the current theme
   *
   * @param   String  $sub     The sub directory within $assets to look for the file in.
   * @param   String  $handle  The main prefix of the filename.
   * @param   String  $sep     The seperator between the hash and the filename. Default '.'
   *
   * @return  Array            An array of all matched files.
   */
  private function find($sub, $handle, $sep = '.') {
    $dir = $this->templates . '/' . $this->assets . $sub;
    return array_filter(
      scandir($dir),
      function ($var) use ($handle, $sep) {
        return (strpos($var, "$handle$sep") !== false);
      }
    );
  }

  /**
   * Removes the placeholder script created for including an inline script.
   *
   * @param   String  $tag  The script code block passed to the script_loader_tag WP hook.
   *
   * @return  String        The tag with the placeholder script removed.
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
   *
   * @param   String   $handle     Name for the script
   * @param   String   $src        Path or url for the script
   * @param   Boolean  $in_footer  Wether to have the script in the footer or head
   *
   * @return  Array                Full array of arguments
   */
  private function scriptArgs($handle, $src, $in_footer) {
    return [$handle, $src, [], null, $in_footer];
  }

  /**
   * Register a WP REST route for a particular script to load from
   *
   * @param   Object    $script  A configured script with an inline object
   * @param   Function  $auth    The authentication method to use
   *
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
   * Get a WordPress REST API url for a particular script.
   *
   * @param   String  $handle  Name of the registered script.
   *
   * @return  String           Full URL of the script.
   */
  private function restUrl($handle) {
    return rest_url('/' . self::restNamespace() . self::restRoute($handle));
  }

  /**
   * Create a consistent namespace for the registered WP REST API endpoints.
   *
   * @return  String  The namespace, default will be "assets/v{{ theme version }}"
   */
  private function restNamespace() {
    return $this->namespace . '/v' . $this->version;
  }

  /**
   * Create a consistent route for each script.
   *
   * @param   String  $handle  Name of the registered script.
   *
   * @return  String           The route, default will be "/{{ handle }}.js/".
   */
  private function restRoute($handle) {
    return "/$handle.js/";
  }

  /**
   * Takes file contents from the path argument in the WPMU_PLUGIN_DIR and
   * replaces template tag contents of the script with real ENV variables.
   *
   * @param   String  $path      Path to a script file.
   * @param   Array   $localize  An array of constants to pass to the string.
   *
   * @return  String             The contents of the script file.
   */
  private function getFileContents($path, $localize) {
    $inline_path = WPMU_PLUGIN_DIR . '/' . $path;
    $contents = self::localize(file_get_contents($inline_path), $localize);

    return $contents;
  }

  /**
   * Replaces all instances of a set of constants with constant values in string.
   *
   * @param   String  $string   The string to localize.
   * @param   Array  $localize  An array of constants to pass to the string.
   *
   * @return  String            The localized string.
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
