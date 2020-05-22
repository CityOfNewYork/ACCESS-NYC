<?php

/**
 * Newsletter Archive Shortcode Handler. The shortcode accepts the following
 * attributes;
 *
 * @param  String  account  The Mailchimp Account ID. Defaults to the
 *                          constant MAILCHIMP_ACCOUNT
 * @param  String  archive  The Archive Folder ID that will display. Defaults
 *                          to the constant MAILCHIMP_ARCHIVE_ID
 * @param  Number  show     The amount of archives to show. Defaults to the
 *                          constant MAILCHIMP_ARCHIVE_SHOW
 *
 * @author NYC Opportunity
 */

namespace Shortcode;

use Timber;

/**
 * Class
 */
class NewsletterArchive extends Shortcode {
  /** The shortcode tag */
  public $tag = 'newsletter-archive';

  /** The path to the Timber Component */
  public $template = 'objects/newsletter-archive.twig';

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
          'mailchimp_archive_id' => ($atts['archive']) ?
            $atts['archive'] : MAILCHIMP_ARCHIVE_ID,
          'mailchimp_archive_show' => ($atts['show']) ?
            $atts['show'] : MAILCHIMP_ARCHIVE_SHOW,
          'csp_script_nonce' => (defined('CSP_SCRIPT_NONCE'))
            ? CSP_SCRIPT_NONCE : false
        )
      )
    );
  }
}
