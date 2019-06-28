<?php

namespace Controller;

use Timber;

class SingleSmnycEmail extends Timber\Post {
  /**
   * [ description]
   */
  const TEMPLATE = 'emails/single.twig';

  /**
   * [__construct description]
   * @return  [type]  [return description]
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
    // Create the body for the text only email.
    $this->text_body = strip_tags($this->post_content, '<a>');

    return $this;
  }
}