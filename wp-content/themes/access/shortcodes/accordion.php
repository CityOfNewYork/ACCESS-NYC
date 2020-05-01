<?php

/**
 * Accordion Shortcode Handler
 *
 * @author NYC Opportunity
 */

namespace Shortcode;

use Timber;

/**
 * Class
 */
class Accordion extends Shortcode {
  /** The shortcode tag */
  public $tag = 'accordion';

  /** The path to the Timber Component */
  public $template = 'components/accordion.twig';

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
          'id' => $id,
          'active' => in_array('active', $atts),
          'header' => $atts['header'],
          'body' => $content,
          'cta' => ($atts['cta_href']) ?
            array(
              'href' => $atts['cta_href'],
              'text' => ($atts['cta_text']) ?
                $atts['cta_text'] : __('Learn More', 'accessnyc')
            ) : false
        )
      )
    );
  }
}
