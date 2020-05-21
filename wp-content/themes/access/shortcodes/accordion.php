<?php

/**
 * Accordion Shortcode Handler. The shortcode accepts the following attributes;
 *
 * @param  String/HTML  header    The header text/html to display. This is
 *                                always visible.
 * @param  Blank        active    Presence determines wether the accordion is
 *                                open or not. Does not need to be set to value.
 * @param  String       cta-href  The url for the call-to-action.
 * @param  String       cta-text  The text for the call-to-action.
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
          'cta' => ($atts['cta-href']) ?
            array(
              'href' => $atts['cta-href'],
              'text' => ($atts['cta-text']) ?
                $atts['cta-text'] : __('Learn More', 'accessnyc')
            ) : false
        )
      )
    );
  }
}
