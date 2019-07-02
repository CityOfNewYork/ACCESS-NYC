<?php

namespace SMNYC;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException as TwilioErr;

class SmsMe extends ContactMe {
  protected $action = 'SMS'; // nonce and ajax key for what this class provides
  protected $service = 'Twilio'; // name used in settings/options keys

  protected $account_label = 'SID';
  protected $secret_label = 'Token';
  protected $from_label = 'Sender Phone Number';

  protected $account_hint = 'EXAMPLEAC43fceec9fb0836fff217f4b4f';
  protected $secret_hint = 'EXAMPLE2674d4ec2a325a63cbcc63d25';
  protected $from_hint = '+15551230789';

  protected $prefix = 'smnyc_twilio';

  /**
   * Register post type for email content
   */
  public function registerPostType() {
    register_post_type('smnyc-sms', array(
      'label' => __('SMNYC SMS', 'text_domain'),
      'description' => __('SMS content for Send Me NYC', 'text_domain'),
      'labels' => array(
        'name' => _x('SMNYC SMS', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('SMNYC SMS', 'Post Type Singular Name', 'text_domain'),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_rest' => true,
      'has_archive' => false,
      'exclude_from_search' => true
    ));
  }

  /**
   * [content description]
   * @param   [type]  $url       [$url description]
   * @param   [type]  $page      [$page description]
   * @param   [type]  $orig_url  [$orig_url description]
   * @return  [type]             [return description]
   */
  protected function content($url, $page, $orig_url) {
    if ($page == self::RESULTS_PAGE) {
      $slug = 'screener-results';
    } else {
      $slug = 'programs';
    }

    // Get post and filter ID through WPML
    $post = get_page_by_path($slug, OBJECT, 'smnyc-sms');
    $id = apply_filters('wpml_object_id', $post->ID, 'smnyc-sms', true);

    // Get content and replace template tag with bitly url
    $text = trim(strip_tags(get_post($id)->post_content));
    $text = str_replace('{{ BITLY_URL }}', $url, $text);

    return $text;
  }

  /**
   * [send description]
   * @param   [type]  $to   [$to description]
   * @param   [type]  $msg  [$msg description]
   * @return  [type]        [return description]
   */
  protected function send($to, $msg) {
    try {
      $user = get_option('smnyc_twilio_user');
      $secret = get_option('smnyc_twilio_secret');
      $from = get_option('smnyc_twilio_from');

      $user = (!empty($user)) ? $user : $_ENV['SMNYC_TWILIO_USER'];
      $secret = (!empty($secret)) ? $secret : $_ENV['SMNYC_TWILIO_SECRET'];
      $from = (!empty($from)) ? $from : $_ENV['SMNYC_TWILIO_FROM'];

      $client = new Client($user, $secret);
      $sms = $client->messages->create($to, ['from' => $from, 'body' => $msg]);
    } catch (TwilioErr $e) {
      return $this->parseError($e->getCode());
    }

    if ($sms->status == 'failed' || $sms->status == 'undelivered' || $sms->errorCode || $sms->errorMessage) {
      return $this->parseError($sms->errorCode, $sms->errorMessage);
    }

    /**
     * $sms properties:
     * sid,dateCreated,dateUpdated,dateSent,accountSid,from,to,body,
     * numMedia,numSegments,status,errorCode,errorMessage,direction,price,
     * priceUnit,apiVersion,uri,subresourceUris
     */
  }

  /**
   * [validRecipient description]
   * @param   [type]  $to  [$to description]
   * @return  [type]       [return description]
   */
  protected function validRecipient($to) {
    $to = preg_replace('/\D/', '', $to); // strip all non-numbers

    // grab user's number
    if (empty($to)) {
      $this->failure(1, 'Phone number must be supplied');
    } elseif (strlen($to) < 10) {
      $this->failure(2, 'Invalid phone number. Please provide 10-digit number with area code');
    } elseif (strlen($to) === 10) {
      $to = '1' . $to; // US country code left off
    }

    // assume longer numbers have country code specified
    return '+' . $to;
  }

  /**
   * [parseError description]
   * @param   [type]  $code     [$code description]
   * @param   [type]  $message  [$message description]
   * @return  [type]            [return description]
   */
  protected function parseError($code, $message = 'An error occurred while sending') {
    $retry = false;

    switch ($code) {
      case 14101:
      case 21211:
      case 21612:
      case 21408:
      case 21610:
      case 21614:
      case 30003:
      case 30004:
      case 30005:
      case 30006: // ^ Something wrong/invalid with 'To' number
        $message = 'Unable to send to number provided. Please use another number';
        break;
      case 21611: // our outbox queue is full
        $retry = true;
        $message = 'Please try again later';
        break;
      case 14103:
      case 21602:
      case 21617:
      case 21618:
      case 21619:
      case 30007:
        $message = 'Invalid message body';
        break;
      case 30001:
      case 30009: // ephemeral errors that a retry might solve https://www.twilio.com/docs/api/rest/message#error-values
        $retry = true;
        break;
      default:
        $message = 'An error occurred during delivery';
        break;
    }

    $this->failure($code, $message, $retry);
  }
}
