<?php

/**
 * Send Me NYC Email
 *
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;

class SingleSmnycEmail extends Timber\Post {
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

    $this->addToPost();
  }

  /**
   * Return the template for the location controller
   * @return [array] Array including the template string
   */
  public function templates() {
    return array(self::TEMPLATE);
  }

  /**
   * [getContext description]
   * @param   [type]  $post     [$post description]
   * @param   [type]  $context  [$context description]
   * @return  [type]            [return description]
   */
  public function addToPost() {
    /**
     * Any additional post content manipulation should be added here
     */

    // $this->variable = 'something';

    return $this;
  }
}
