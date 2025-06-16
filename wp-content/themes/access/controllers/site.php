<?php

/**
 * Site Controller
 *
 * @author Blue State Digital
 */

namespace Controller;

use Timber;
use TimberSite;
use TimberMenu;

class Site extends TimberSite {
  /**
   * Constructor
   */
  public function __construct() {
    require_once __dir__ . '/alert.php';

    add_filter('timber_context', array($this, 'addToContext'));

    parent::__construct();
  }

  /**
   * Make variables available globally to twig templates
   *
   * @param   Object  $context  The timber site context variable
   *
   * @return  Object            The modified timber site context variable
   */
  public function addToContext($context) {
    /**
     * Language
     */

    $lang = $this->getLanguageCode();

    $direction = ($lang === 'ar' || $lang === 'ur') ? 'rtl' : 'ltr';

    $context['language_code'] = $lang;

    $context['direction'] = $direction;

    $context['end'] = ($direction === 'ltr') ? 'right' : 'left';

    $context['start'] = ($direction === 'ltr') ? 'left' : 'right';

    $context['google_translate_languages'] = $this->getGoogleTranslateLanguages();

    $context['stylesheets'] = $this->getStylesheets();

    $context['url_base'] = ($lang === 'en') ? '' : '/' . $lang;

    /**
     * Get the links that need to appear in the search dropdown
     */

    $context['search_links'] = Timber::get_posts(array(
      'post_type' => 'search_suggestions',
      'numberposts' => 1
    ));

    /**
     * Add Menus
     */

    $context['header_menu'] = new TimberMenu('header-menu');

    $context['footer_get_help_now_menu'] = new TimberMenu('get-help-now');

    $context['footer_for_caseworkers_menu'] = new TimberMenu('for-caseworkers');

    $context['footer_programs_menu'] = new TimberMenu('programs');

    $context['footer_about_access_nyc_menu'] = new TimberMenu('about-access-nyc');

    /**
     * Gets object containing all program categories
     */

    $context['categories'] = get_terms('programs');

    /**
     * Site Alerts
     */

    $alerts = array_map(function($post) {
      return new Alert($post);
    }, Timber::get_posts(array(
      'post_type' => 'alert',
      'posts_per_page' => -1
    )));

    // Get the first alert that is set to site wide
    $alerts = array_filter($alerts, function($p) {
      return (empty($p->custom['alert_sitewide'])) ? false : $p->custom['alert_sitewide'];
    });

    $context['alert_sitewide'] = reset($alerts);

    /**
     * Query Vars
     */

    $context['is_print'] = get_query_var('print', false); // Print view
    $context['a_b_testing_on'] = filter_var(get_field('field_6790152da121b', 'option'), FILTER_VALIDATE_BOOLEAN);

    // If A/B testing is on, add the A/B test variant to the cookies and the context
    // The variant cookie is stored for 30 days
    if ($context['a_b_testing_on']) {
      if (isset($_COOKIE['ab_test_variant']) and (
          $_COOKIE['ab_test_variant'] == 'a'
          or $_COOKIE['ab_test_variant'] == 'b'
          or $_COOKIE['ab_test_variant'] == 'c')) { // Variant cookie must be a valid value
        $context['variant'] = $_COOKIE['ab_test_variant'];
      } else {
        $rand_val = rand(0, 2);
        $variant = 'a';
        if ($rand_val == 0) {
          $variant = 'a';
        } elseif ($rand_val == 1) {
          $variant = 'b';
        } else {
          $variant = 'c';
        }
        setcookie('ab_test_variant', $variant, time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
        $context['variant'] = $variant;
      }
    }

    // Set visitor_id cookie
    if (!isset($_COOKIE['visitor_id'])) {
      setcookie('visitor_id', rand(), time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }

    /**
     * Get the default page meta description for the page
     */

    $context['page_meta_description'] = $this->getPageMetaDescription();

    /**
     * Create nonce for allowed resources
     */

    $context['csp_script_nonce'] = (defined('CSP_SCRIPT_NONCE'))
      ? CSP_SCRIPT_NONCE : false;

    /**
     * Set Schema Base
     */

    $context['schema'] = array_filter(array(
      $this->getOrganizationSchema(),
      $this->getWebsiteSchema(),
      $this->getAlertSchema($context['alert_sitewide'])
    ));

    /** Set Schema Base */
    $context['schema'] = array_filter(array(
      $this->getOrganizationSchema(),
      $this->getWebsiteSchema(),
      $this->getAlertSchema($context['alert_sitewide'])
    ));

    return $context;
  }

  /**
   * The current language code using the WPML constant. This will only change
   * if WPML is activated.
   *
   * @return  String  The current language code
   */
  public function getLanguageCode() {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $wpml = 'sitepress-multilingual-cms/sitepress.php';

    return is_plugin_active($wpml) ? ICL_LANGUAGE_CODE : 'en';
  }

  /**
   * Get the list of stylesheets for the front-end (for the Google Translate
   * Element).
   *
   * @return  Array  List of stylesheets
   */
  public function getStylesheets() {
    $files = scandir(get_stylesheet_directory() . '/assets/styles/');

    $files = array_values(array_filter($files, function($file) {
      return (pathinfo($file)['extension'] === 'css');
    }));

    $files = array_map(function($file) {
      return get_stylesheet_directory_uri() . '/assets/styles/' . $file;
    }, $files);

    return $files;
  }

  /**
   * Create a list of missing languages for Google Translate to augment.
   *
   * @return  Array  An array of missing WPML Active Languages
   */
  public function getGoogleTranslateLanguages() {
    $languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));

    $langs = array_filter($languages, function($lang) {
      return (isset($lang['missing']) && $lang['missing'] === 1);
    });

    $langs = array_map(function($lang) {
      if ($lang['code'] === 'zh-hant') {
        $lang['code'] = 'zh-CN';
      }

      return $lang;
    }, $langs);

    return $langs;
  }

  /**
   * Get the default meta of the page if it exists.
   *
   * @return  String  The view description
   */
  public function getPageMetaDescription() {
    /**
     * Homepage
     */

    if (is_home()) {
      return get_bloginfo('description');
    }

    /**
     * Posts
     */

    if (is_single() || is_page()) {
      $id = get_post()->ID;

      /**
       * Page Meta Description Field
       */

      $description = get_field('page_meta_description', $id);

      if (isset($description)) {
        return $description;
      }
    }

    return '';
  }

  /**
   * Get the organization schema
   *
   * @return  Array  Schema object
   */
  public function getOrganizationSchema() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      '@id' => get_site_url() . '/#organization',
      'name' => get_bloginfo('title'),
      'url' => get_site_url(),
      'logo' => array(
        '@type' => 'ImageObject',
        '@id' => get_site_url() . '/#logo',
        'url' => get_stylesheet_directory_uri() . '/assets/img/apple-icon-precomposed.png',
        'width' => 192,
        'height' => 192,
        'caption' => get_bloginfo('title')
      ),
    );
  }

  /**
   * Get the Website Schema
   *
   * @return  Array  Schema object
   */
  public function getWebsiteSchema() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      '@id' => get_site_url() . '/#website',
      'url' => get_site_url(),
      'name' => get_bloginfo('title'),
      'description' => get_bloginfo('description'),
      'publisher' => array(
        '@id' => get_site_url() . '/#organization'
      ),
      // TODO: [AC-2996] Rich results Sitelinks Search Box
      // @url https://developers.google.com/search/docs/data-types/sitelinks-searchbox
      // 'potentialAction' => [
      //   array(
      //     '@type' => 'SearchAction',
      //     'target' => get_site_url() . '/?s={search_term_string}',
      //     'query-input' => 'required name=search_term_string'
      //   )
      // ],
      'inLanguage' => $this->getLanguageCode()
    );
  }

  /**
   * Get the sitewide alert schema
   *
   * @param   Object         $alert  The alert object
   *
   * @return  Array/Boolean          If the alert is a special announcement
   */
  public function getAlertSchema($alert) {
    return ($alert !== false and $alert->item_scope === 'SpecialAnnouncement') ? array(
      '@context' => 'https://schema.org',
      '@type' => 'SpecialAnnouncement',
      'name' => $alert->post_title,
      'datePosted' => $alert->post_modified,
      'text' => $alert->alert_content,
      'category' => 'https://www.wikidata.org/wiki/Q81068910',
      'spatialCoverage' => array(
        'type' => 'City',
        'name' => 'New York'
      )
    ) : false;
  }
}
