<?php

namespace Controller;

/**
 * Dependencies
 */

use Timber;
use TimberSite;
use TimberMenu;

/**
 * Site Controller
 */
class Site extends TimberSite {
  /**
   * Constructor
   */
  public function __construct() {
    add_filter('timber_context', array($this, 'addToContext'));

    parent::__construct();
  }

  /**
   * Make variables available globally to twig templates
   * @param   object  $context  The timber site context variable
   * @return  object            The modified timber site context variable
   */
  public function addToContext($context) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    /**
     * The current language code using the WPML constant.
     * This will only change if WPML is activated.
     */

    $wpml = 'sitepress-multilingual-cms/sitepress.php';
    $lang = is_plugin_active($wpml) ? ICL_LANGUAGE_CODE : 'en';
    $direction = ($lang === 'ar' || $lang === 'ur') ? 'rtl' : 'ltr';
    $context['language_code'] = is_plugin_active($wpml) ? ICL_LANGUAGE_CODE : 'en';
    $context['direction'] = $direction;
    $context['end'] = ($direction === 'ltr') ? 'right' : 'left';
    $context['start'] = ($direction === 'ltr') ? 'left' : 'right';

    $context['url_base'] = ($lang === 'en') ? '' : '/' . $lang;

    /** Get the links that need to appear in the search dropdown */
    $context['search_links'] = Timber::get_posts(array(
      'post_type' => 'program_search_links',
      'numberposts' => 1
    ));

    /**
     * Add Menus
     */

    $context['menu'] = new TimberMenu('header-menu');
    $context['footer_get_help_now_menu'] = new TimberMenu('get-help-now');
    $context['footer_for_caseworkers_menu'] = new TimberMenu('for-caseworkers');
    $context['footer_programs_menu'] = new TimberMenu('programs');
    $context['footer_about_access_nyc_menu'] = new TimberMenu('about-access-nyc');
    $context['peu_header_menu'] = new TimberMenu('peu');

    /** Gets object containing all program categories */
    $context['categories'] = get_terms('programs');

    /** Determine if page is in print view */
    $context['is_print'] = isset($_GET['print']) ? $_GET['print'] : false;

    /** Get the meta description for the page */
    $context['page_meta_desc'] = $this->getMetaDescription($context['language_code']);

    /** Create nonce for allowed resources */
    $context['csp_script_nonce'] = (defined('CSP_SCRIPT_NONCE')) ? CSP_SCRIPT_NONCE : false;

    return $context;
  }

  /**
   * Get the meta description for the page if it exists
   * @param   string  $lang  The language of the description needed
   * @return  string         The view description
   */
  public function getMetaDescription($lang) {
    if (is_home()) {
      return get_bloginfo('description');
    }

    if ($lang != 'en') {
      $id = get_page_by_path(trim($_SERVER["REQUEST_URI"], '/' . $lang))->ID;
    } else {
      $id = get_page_by_path(trim($_SERVER["REQUEST_URI"], '/'))->ID;
    }

    $translated = apply_filters('wpml_object_id', $id, 'page', true, $lang);
    $description = get_field('page_meta_description', $translated);

    if ($description == '') {
      $description = get_field('page_meta_description', $id);
    }

    return $description;
  }
}
