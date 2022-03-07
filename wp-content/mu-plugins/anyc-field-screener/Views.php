<?php

namespace FieldScreener;

use Timber;

class Views {
  /**
   * The shortcode to render and configure the application
   *
   * @var String
   */
  const SHORTCODE = 'anyc-field-screener';

  /**
   * The assets path for for the application
   *
   * @var String
   */
  const ASSETS = '/wp-content/mu-plugins/anyc-field-screener/assets';

  /**
   * The default view for the application to land on.
   *
   * @var String
   */
  const PAGE_DEFAULT = 'screener';

  /**
   * The production URL
   *
   * @var String
   */
  const PRODUCTION_URL = 'https://access.nyc.gov/eligibility/';

  /**
   * The root path to the application
   *
   * @var String
   */
  const ROOT = '/field-screener/';

  /**
   * The path to the results view
   *
   * @var String
   */
  const RESULTS = '/field-screener/results/';

  /**
   * The path of the results page that is shared with users when they text
   * or email results to themselves. It replaces the RESULTS path above.
   *
   * @var String
   */
  const RESULTS_SHARE = '/eligibility/results/';

  /**
   * Constructor
   *
   * @return  Object  Instance of FieldScreener
   */
  public function __construct() {
    $this->addAssets();

    /** Registers the shortcode for rendering the application in page views */
    add_shortcode(self::SHORTCODE, [$this, 'shortcode']);

    return $this;
  }

  /**
   * Register and enqueue Field Screener assets. Normally, the path is resolved
   * on the fly by scanning the assets directory, assuming the project is going
   * through active development.
   */
  public function addAssets() {
    $styles = ['default', self::ASSETS . '/styles/style-default.6195470e.css', [], null, 'screen'];
    $scripts = ['field', self::ASSETS . '/js/field.9b4a2397.js', [], null, true];

    wp_register_style(...$styles);
    wp_enqueue_style(...$styles);

    wp_register_script(...$scripts);
    wp_enqueue_script(...$scripts);
  }

  /**
   * Interprets the Field Screener shortcode and pulls in the appropriate page
   * based on the "page" attribute value
   *
   * @param  Array  $atts  Attributes added to the shortcode
   */
  public function shortcode($atts) {
    $atts = shortcode_atts(array(
      'env' => Util::environmentString(),
      'assets' => self::ASSETS,
      'root' => self::ROOT,
      'results' => self::RESULTS,
      'resultsShare' => self::RESULTS_SHARE,
      'translationID' => Util::TRANSLATION_ID,
      'page' => self::PAGE_DEFAULT,
      'productionUrl' => self::PRODUCTION_URL
    ), $atts);

    Timber::$dirname = [plugin_dir_path(__FILE__) . '/timber/'];

    $context = Timber::get_context();

    $context = array_merge($context, $atts);

    $context['post'] = Timber::get_post();

    $context['adminAjax'] = admin_url('admin-ajax.php');

    switch ($context['page']) {
      case 'screener':
        $compiled = $this->renderScreener($context);

        break;

      case 'results':
        $compiled = $this->renderResults($context);

        break;
    }

    return $compiled;
  }

  /**
   * Renders the Screening portion of the application
   */
  private function renderScreener($context) {
    /**
     * Create a nonce for the client application
     */

    add_filter('nonce_life', 'FieldScreener\Auth::nonceLife');

    $context['nonce'] = wp_create_nonce(Auth::NONCE_KEY);

    remove_filter('nonce_life', 'FieldScreener\Auth::nonceLife');

    /**
     * Get the program categories
     */

    $context['categories'] = get_categories(array(
      'post_type' => 'programs',
      'taxonomy' => 'programs',
      'hide_empty' => false
    ));

    /**
     * Render the view
     */

    return Timber::compile(plugin_dir_path(__FILE__) .  '/timber/screener.twig', $context);
  }

  /**
   * Renders the results page
   */
  private function renderResults($context) {
    /**
     * Replace the Field Screener results page with the ACCESS NYC results page
     */

    $get = $_GET;

    $get['path'] = $context['resultsShare'];

    /**
     * Get the share data for Email and SMS fields
     */

    $shareData = Util::shareData($get);

    $context['url'] = $shareData['url'];

    $context['hash'] = $shareData['hash'];

    $context['guid'] = (isset($shareData['query']['guid'])) ?
      $shareData['query']['guid'] : '';

    /**
     * Query the program results
     */

    $categories = (isset($shareData['query']['categories'])) ?
      explode(',', $shareData['query']['categories']) : '';

    $programs = (isset($shareData['query']['programs'])) ?
      explode(',', $shareData['query']['programs']) : '';

    $context['programs'] = implode(',', $programs);

    $context['selectedPrograms'] = Timber::get_posts(array(
      'post_type' => 'programs',
      'tax_query' => array(
        array(
          'taxonomy'  => 'programs',
          'field'     => 'slug',
          'terms'     => $categories
        )
      ),
      'posts_per_page' => -1,
      'meta_key'       => 'program_code',
      'meta_value'     => $programs
    ));

    $context['additionalPrograms'] = Timber::get_posts(array(
      'post_type' => 'programs',
      'tax_query' => array(
        array(
          'taxonomy' => 'programs',
          'field'    => 'slug',
          'terms'    => $categories,
          'operator' => 'NOT IN'
        )
      ),
      'posts_per_page' => -1,
      'meta_key'   => 'program_code',
      'meta_value' => $programs
    ));

    /**
     * Render the view
     */

    return Timber::compile(plugin_dir_path(__FILE__) . '/timber/results.twig', $context);
  }
}
