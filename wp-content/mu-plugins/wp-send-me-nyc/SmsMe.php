<?php

namespace SMNYC;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException as TwilioErr;

class SmsMe extends ContactMe {
  protected $action = 'SMS';

  protected $service = 'Twilio';

  protected $account_label = 'Account SID';

  protected $secret_sid = 'API Key SID';

  protected $secret_label = 'API Key Secret';

  protected $from_label = 'Sender Phone Number';

  protected $account_hint = 'EXAMPLEAC43fceec9fb0836fff217f4b4f';

  protected $secret_hint = 'EXAMPLE2674d4ec2a325a63cbcc63d25';

  protected $from_hint = '+15551230789';

  protected $prefix = 'smnyc_twilio';

  const POST_TYPE = 'smnyc-sms';

  /**
   * Register post type for email content
   */
  public function registerPostType() {
    register_post_type(self::POST_TYPE, array(
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
   * Get the content of the email to send.
   *
   * @param   String  $url_shortened  Shortened url that is being shared.
   * @param   String  $url            Full url that is being shared.
   * @param   String  $template       Slug of the template to retrieve.
   * @param   String  $lang           Language of the template to retrieve.
   *
   * @return  String                  The text message being shared.
   */
  protected function content($url_shortened, $url, $share_text, $template, $lang) {
    // Get post and filter ID through WPML
    $post = get_page_by_path($template, OBJECT, self::POST_TYPE);

    $id = $post->ID;

    // Filter ID through WPML. Need to add conditionals for WPML or admin notice
    if ($lang !== 'en') {
      $id = apply_filters('wpml_object_id', $post->ID, self::POST_TYPE, true, $lang);
    }

    // Get content and replace template tag with bitly url
    $text = trim(strip_tags(get_post($id)->post_content));
    $text = str_replace('{{ SHARE_TEXT }}', $share_text, $text);
    $text = str_replace('{{ BITLY_URL }}', $url_shortened, $text);
    $text = str_replace('{{ URL }}', $url, $text);

    return $text;
  }

  /**
   * Send an SMS message via the Twilio SDK.
   *
   * @param   String  $to   The recipient of the message.
   * @param   String  $msg  The message to send.
   */
  protected function send($to, $msg) {
    try {
      $user = get_option('smnyc_twilio_user');
      $apiKeySid = get_option('smnyc_twilio_api_key_sid');
      $apiKeySecret = get_option('smnyc_twilio_api_key_secret');
      $from = get_option('smnyc_twilio_from');

      $user = (!empty($user)) ? $user : SMNYC_TWILIO_USER;
      $apiKeySid = (!empty($apiKeySid)) ? $apiKeySid : SMNYC_TWILIO_API_KEY_SID;
      $apiKeySecret = (!empty($apiKeySecret)) ? $apiKeySecret : SMNYC_TWILIO_API_KEY_SECRET;
      $from = (!empty($from)) ? $from : SMNYC_TWILIO_FROM;

      $client = new Client($apiKeySid, $apiKeySecret, $user);

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
   * Wether the recipient is valid or not.
   *
   * @param   String  $to  The recipient of the message.
   *
   * @return  Boolean      Wether the recipient is valid or not.
   */
  protected function validRecipient($to) {
    $to = preg_replace('/\D/', '', $to); // strip all non-numbers

    // grab user's number
    if (empty($to)) {
      $this->failure(1, 'Phone number must be supplied');

      return false;
    } elseif (strlen($to) < 10) {
      $this->failure(2, 'Invalid phone number. Please provide 10-digit number with area code');

      return false;
    }

    // assume longer numbers have country code specified
    return true;
  }

  /**
   * Sanitize the phone number by removing characters and adding country code.
   *
   * @param   String  $to  The phone number to send to.
   *
   * @return  String       The sanitized phone number.
   */
  protected function sanitizeRecipient($to) {
    $to = preg_replace('/\D/', '', $to); // strip all non-numbers

    if (strlen($to) === 10) {
      $to = '1' . $to; // US country code left off
    }

    // assume longer numbers have country code specified
    return '+' . $to;
  }

  /**
   * Parse the error response from Twilio
   *
   * @param   Number  $code     The number of the error code.
   * @param   String  $message  A string to pass to the failer response.
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
      case 30009:
        $retry = true;
        break;
      default:
        $message = 'An error occurred during delivery';
        break;
    }

    $this->failure($code, $message, $retry);
  }

  /**
   * Extend settings section from Contact Me to add API Key Credentials
   */
  public function createSettingsSection() {
    parent::createSettingsSection();

    $section = $this->prefix . '_section';

    $this->registerSetting(array(
      'id' => $this->prefix . '_api_key_sid',
      'title' => $this->secret_sid,
      'section' => $section
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_api_key_secret',
      'title' => $this->secret_label,
      'section' => $section,
      'private' => true
    ));
  }
}
