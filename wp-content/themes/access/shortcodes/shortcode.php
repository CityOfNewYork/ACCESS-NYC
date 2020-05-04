<?php

/**
 * Shortcode Handler
 *
 * @author NYC Opportunity
 */

namespace Shortcode;

/**
 * Class
 */
class Shortcode {
  /** Prefix for all shortcodes */
  public $prefix = 'anyc-';

  /** The shortcode tag */
  public $tag = '';

  /**
   * Constructor
   */
  public function __construct() {
    $this->shortcode = $this->prefix . $this->tag;

    add_shortcode($this->shortcode, [$this, 'shortcode']);
  }

  /**
   * Add Shortcode Callback
   *
   * @param   Array   $atts           Attributes added to the shortcode
   * @param   String  $content        Content within shortcode tags
   * @param   String  $shortcode_tag  The full shortcode tag
   *
   * @return  String                  A compiled component string
   */
  public function shortcode($atts, $content, $shortcode_tag) {
    return '';
  }
}
