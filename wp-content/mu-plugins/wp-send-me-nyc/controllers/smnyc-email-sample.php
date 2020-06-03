<?php

/**
 * Send Me NYC Email
 *
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;

class SmnycEmail extends Timber\Post {
  /** The twig template for emails */
  const TEMPLATE = 'emails/single.twig';

  /**
   * Constructor
   * @param  number  $pid  This is the post ID Timber accepts for displaying a specific post
   */
  public function __construct($pid = false) {
    if ($pid) {
      parent::__construct($pid);
    } else {
      parent::__construct();
    }

    return $this;
  }

  /**
   * Return the template for the location controller
   * @return [array] Array including the template string
   */
  public function templates() {
    return array(self::TEMPLATE);
  }
}
