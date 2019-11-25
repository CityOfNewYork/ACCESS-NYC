<?php

namespace Controller;

use Timber;

class SingleSmnycEmail extends Timber\Post {
  /** The twig template for emails */
  const TEMPLATE = 'emails/single.twig';

  /**
   * Constructor
   *
   * @param  Number  $pid  This is the post ID Timber accepts for displaying a specific post.
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
   * Return the template for the location controller.
   *
   * @return  Array  Array including the template string.
   */
  public function templates() {
    return array(self::TEMPLATE);
  }

  /**
   * Add additional content to the post.

   * @return  Class  SingleSmnycEmail
   */
  public function addToPost() {
    /**
     * Any additional post content manipulation should be added here
     */

    // $this->variable = 'something';

    return $this;
  }
}
