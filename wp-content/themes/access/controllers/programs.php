<?php

/**
 * Program Guide
 *
 * @link /wp-json/wp/v2/programs
 * @author NYC Opportunity
 */

namespace Controller;

use SMNYC;
use Timber;
use DateTime;
use DateTimeZone;

class Programs extends Timber\Post {
  /**
   * Constructor
   *
   * @return  Object  This
   */
  public function __construct($pid = false) {
    if ($pid) {
      parent::__construct($pid);
    } else {
      parent::__construct();
    }

    /**
     * Icon Slug
     */

    $this->category_slug = $this->getCategorySlug();

    $this->category = $this->getCategory();

    $this->status = $this->getStatus();

    $this->status_type = $this->custom['program_status_type'];

    $this->icon = $this->getIcon();

    /**
     * Share Properties
     */

    $this->share_action = admin_url('admin-ajax.php');

    $this->share_url = $this->get_permalink() . '?step=how-to-apply';

    $this->share_hash = SMNYC\hash($this->share_url);

    $og_title = $this->custom['og_title'];
    $web_share_text = $this->custom['web_share_text'];

    $this->web_share = array(
      'title' => ($og_title) ?
        $og_title : $this->custom['plain_language_program_name'],
      'text' => ($web_share_text) ?
        $web_share_text : strip_tags($this->custom['brief_excerpt']),
      'url' => wp_get_shortlink()
    );

    return $this;
  }

  /**
   * Items returned in this object determine what is shown in the WP REST API.
   * Called by 'rest-prepare-posts.php' must use plugin.
   *
   * @return  Array  Items to show in the WP REST API
   */
  public function showInRest() {
    return array(
      'category_slug' => $this->getCategorySlug(),
      'category' => $this->getCategory(),
      'status' => $this->getStatus(),
      'icon' => $this->getIcon()
    );
  }

  /**
   * Get the page meta description.
   *
   * @return  String
   */
  public function getPageMetaDescription() {
    return $this->custom['plain_language_program_name'];
  }

  /**
   * Return details of the main category for the program
   *
   * @return  Array  The slug and name of the category
   */
  public function getCategory() {
    return array(
      'slug' => $this->getCategorySlug(),
      'name' => $this->terms('programs')[0]->name
    );
  }

  /**
   * Get the slug of the first program category
   *
   * @return  String  The english slug of the program category
   */
  public function getCategorySlug() {
    $cat = $this->terms('programs')[0]->slug;

    $lang = (defined('ICL_LANGUAGE_CODE')) ? ICL_LANGUAGE_CODE : 'en';

    $slug = ($lang != 'en') ? str_replace("-$lang", '', $cat) : $cat;

    return $slug;
  }

  /**
   * Get the details of the program category icon
   *
   * @return  Array  Class and Icon Version
   */
  public function getIcon() {
    return array(
      'class' => $this->getIconClass(),
      'version' => self::ICON_VERSION
    );
  }

  /**
   * Calculate and get the status of the Program
   *
   * @return  Object/Boolean  Status. If cleared return false.
   */
  public function getStatus() {
    $status = array(
      'type' => $this->custom['program_status_type'],
      'text' => $this->custom['program_status']
    );

    $clear = strtotime($this->custom['program_status_clear_date']);

    $date = new DateTime('now', new DateTimeZone('America/New_York'));
    $now = $date->getTimestamp() + $date->getOffset();

    if ($this->custom['program_status'] && !$clear) {
      return $status;
    }

    if ($clear < $now) {
      return false;
    } else {
      return $status;
    }
  }

  /**
   * Set the icon class based on the program status type ACF field
   *
   * @return  String  The class name of the icon
   */
  public function getIconClass() {
    $type = $this->custom['program_status_type'];

    $key = ($type || isset($type)) ? $type : self::STATUS_DEFAULT;

    return self::ICON_COLORS[$key];
  }

  /**
   * Returns the disambiguating description for the schema.
   *
   * @return  String  disambiguating description.
   */
  public function disambiguatingDescription() {
    $description = $this->get_field('field_58912c1a8a81b') |
      add_anyc_checklist | add_anyc_table_numeric;
    return $description;
  }

  /**
   * Return the populations served names to use for Audience tag.
   *
   * @return  String  list of names used for Audience name tag
   */
  public function getAudience() {
    $terms = array_filter($this->terms, function($term) {
      if ($term->taxonomy === 'populations-served') {
        return $term;
      }
    });

    $names = array_map(function($item) {
      return $item->name;
    }, $terms);

    if (sizeof($names) > 1) {
      return implode(', ', $names);
    } else {
      return array_pop($names);
    }
  }

  /**
   * Returns the base schema for the program
   *
   * @return  Array  Base program schema
   */
  public function getSchema() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'GovernmentService',
      'name' => $this->name,
      'alternateName' => $this->plain_language_program_name,
      'datePosted' => $this->post_modified,
      'url' => $this->get_permalink,
      'serviceType' => $this->category['name'],
      'serviceOperator' => array(
        '@type' => 'GovernmentOrganization',
        'name' => $this->government_agency
      ),
      // TODO: [AC-2994] replace block with valid ServiceChannel using "How to apply" sections
      // @url https://schema.org/ServiceChannel
      // 'availableChannel' => array(
      //   '@type' => 'ServiceChannel',
      //   'description' => $this->get_field(accordion)
      // ),
      'spatialCoverage' => array(
        'type' => 'City',
        'name' => 'New York'
      ),
      'description' => $this->get_field('program_description'),
      'disambiguatingDescription' => $this->disambiguatingDescription()
    );
  }

  /**
   * Get a tout version of the Government Service schema
   *
   * @return  Array  The Schema
   */
  public function getSchemaTout() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'GovernmentService',
      'name' => $this->name,
      'alternateName' => $this->plain_language_program_name,
      'url' => $this->get_permalink,
      'serviceType' => $this->category['name']
    );
  }

  /**
   * Returns the schema for Special Announcement.
   *
   * @return  Array/Boolean  Special Announcement Schema,
   *                         false if no status or expired.
   */
  public function getSpecialAnnouncementSchema() {
    return ($this->status_type === 'covid-response') ?
      array(
        '@context' => 'https://schema.org',
        '@type' => 'SpecialAnnouncement',
        'name' => $this->program_name,
        'category' => 'https://www.wikidata.org/wiki/Q81068910',
        'datePosted' => $this->post_modified,
        'expires' => $this->custom['program_status_clear_date'] ?
          $this->custom['program_status_clear_date'] : '',
        'governmentBenefitsInfo' => array(
          '@type' => 'GovernmentService',
          'name' => $this->program_name,
          'url' => $this->structured_data_url,
          'provider' => array(
            '@type' => 'GovernmentOrganization',
            'name' => $this->government_agency
          ),
          'audience' => array(
            '@type' => 'Audience',
            'name' => $this->getAudience()
          ),
          'serviceType' => $this->category['name']
        ),
        'serviceOperator' => array(
          '@type' => 'GovernmentOrganization',
          'name' => $this->government_agency
        ),
        'spatialCoverage' => array(
          'type' => 'City',
          'name' => 'New York'
        )
      ) : false;
  }

  /**
   * Returns the base schema for the FAQ
   *
   * @return  Array  Base FAQ schema
   */
  public function getFaqSchema() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => $this->addQuestionsToSchemaFaq($this->getQuestions())
    );
  }

  /**
  * The array $questions has a set of elements that are the questions to
  * be added to the $faq variable which will be added to the schema as the
  * `FAQPage` section.
  *
  * @return  Array  Set of FAQ questions
  */
  private function getQuestions() {
    $questions = [
      array(
        '@type' => 'Question',
        'name' => "How does $this->program_name work?",
        'acceptedAnswer' => array(
          '@type' => 'Answer',
          'text' => $this->faqAnswer('field_58912c1a8a81b')
        )
      ),
      array(
        '@type' => 'Question',
        'name' => "Am I eligible for $this->program_name?",
        'acceptedAnswer' => array(
          '@type' => 'Answer',
          'text' => $this->faqAnswer('field_58912c1a8a82d')
        )
      ),
      array(
        '@type' => 'Question',
        'name' => "What do I need in order to apply to $this->program_name?",
        'acceptedAnswer' => array(
          '@type' => 'Answer',
          'text' => $this->faqAnswer('field_589de18fca4e0')
        )
      ),
      array(
        '@type' => 'Question',
        'name' => "How do I Apply to $this->program_name?",
        'acceptedAnswer' => array(
          '@type' => 'Answer',
          'text' => join('', [$this->faqAnswer('field_58912c1a8a850'),
            $this->faqAnswer('field_58912c1a8a885'),
            $this->faqAnswer('field_58912c1a8a900'),
            $this->faqAnswer('field_58912c1a8a8cb')
          ])
        )
      )
    ];

    return $questions;
  }

  /**
   * Returns the a description from the program's field section.
   *
   * @return  String  Answer to FAQ.
   */
  private function faqAnswer($field) {
    $answer = $this->get_field($field);

    return $answer;
  }

  /**
   * Add FAQ to schema only if sections are shown.
   *
   * @return  Array  Set of questions to be added to the schema.
   */
  private function addQuestionsToSchemaFaq($questions) {
    $schema_faq = [];
    $sections = $this->get_field('field_589e43563c471');

    foreach ($sections as &$section) {
      if ($section['value'] === 'how-it-works') {
        array_push($schema_faq, $questions[0]);
      } elseif ($section['value'] === 'determine-your-eligibility') {
        array_push($schema_faq, $questions[1]);
      } elseif ($section['value'] === 'what-you-need-to-include') {
        array_push($schema_faq, $questions[2]);
      } elseif ($section['value'] === 'how-to-apply') {
        array_push($schema_faq, $questions[3]);
      }
    }

    return $schema_faq;
  }

  /**
   * Constants
   */

  const STATUS_DEFAULT = 'info';

  /** Version of icons to use (sets the postfix) */
  const ICON_VERSION = '2';

  /** Icon color setting for each status */
  const ICON_COLORS = array(
    'info' => 'text-blue-bright fill-blue-light',
    'success' => 'text-green fill-green-light',
    'warning' => 'text-yellow-access fill-yellow-light',
    'urgent' => 'text-red fill-pink-light',
    'covid-response' => 'text-covid-response fill-covid-response-light',
  );
}
