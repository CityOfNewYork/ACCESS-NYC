<?php

/**
 * Newsletter Shortcode Handler. The shortcode accepts the following attributes;
 *
 * @param  String  account   The Mailchimp Account ID the form posts to.
 *                           Defaults to the constant MAILCHIMP_ACCOUNT
 * @param  String  audience  The Audience ID that signups will post to.
 *                           Defaults to the constant MAILCHIMP_AUDIENCE_ID
 *
 * @author NYC Opportunity
 */

namespace Shortcode;

use Timber;

/**
 * Class
 */
class Newsletter extends Shortcode {
  /** The shortcode tag */
  public $tag = 'newsletter';

  /** The path to the Timber Component */
  public $template = 'objects/newsletter.twig';

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
    $id = $this->tag . '-' . uniqid();

    return Timber::compile(
      $this->template, array(
        'this' => array(
          'id' => $id,
          'mailchimp_account' => ($atts['account']) ?
            $atts['account'] : MAILCHIMP_ACCOUNT,
          'mailchimp_audience_id' => ($atts['audience']) ?
            $atts['audience'] : MAILCHIMP_AUDIENCE_ID,
        )
      )
    );
  }
}
