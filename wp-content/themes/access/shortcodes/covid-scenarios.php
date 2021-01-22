<?php

/**
 * COVID Scenarios Shortcode Handler.
 *
 * @source https://accesspatterns.cityofnewyork.us/covid-scenarios
 *
 * @author NYC Opportunity
 */

namespace Shortcode;

use Timber;

/**
 * Class
 */
class CovidScenarios extends Shortcode {
  /** The shortcode tag */
  public $tag = 'covid-scenarios';

  /** The path to the Timber Component */
  public $template = 'objects/covid-scenarios.twig';

  /**
   * Shortcode Callback
   *
   * @param   Array   $atts           Attributes added to the shortcode
   * @param   String  $content        Content within shortcode tags
   * @param   String  $shortcode_tag  The full shortcode tag
   *
   * @return  String                  A compiled component string
   */
  public function shortcode($atts, $content, $shortcode_tag) {
    $id = sanitize_title($atts['header']). '-' . uniqid();

    return Timber::compile(
      $this->template,
      array(
        'this' => array(
          'id' => $id
        )
      )
    );
  }
}
