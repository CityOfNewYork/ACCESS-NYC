<?php

namespace SMNYC;

use Timber;
use Controller;
use Aws\Ses\SesClient;
use Soundasleep\Html2Text;

class EmailMe extends ContactMe {
  protected $action = 'Email';

  protected $service = 'AWS';

  protected $account_label = 'Key';

  protected $secret_label = 'Secret';

  protected $from_label = 'From Email Address';

  protected $account_hint = 'EXAMPLEAKIAIOSFODNN7';

  protected $secret_hint = 'EXAMPLEwJalrXUtnFEMI/K7MDENG/bPxRfiCY';

  protected $from_hint = 'noreply@example.com';

  protected $prefix = 'smnyc_aws';

  protected $template_controller = 'smnyc-email.php';

  const POST_TYPE = 'smnyc-email';

  /**
   * Register post type for email content
   */
  public function registerPostType() {
    register_post_type(self::POST_TYPE, array(
      'label' => __('SMNYC Email', 'text_domain'),
      'description' => __('Email content for Send Me NYC', 'text_domain'),
      'labels' => array(
        'name' => _x('SMNYC Emails', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('SMNYC Email', 'Post Type Singular Name', 'text_domain'),
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
   * @param   String  $share_text     Full url that is being shared.
   * @param   String  $lang           Language of the template to retrieve.
   *
   * @return  Array                   Includes the subject, html, and text bodies
   */
  protected function content($url_shortened, $url, $share_text, $template, $lang) {
    $controller = get_stylesheet_directory() . '/' . $this->template_controller;

    if (file_exists($controller)) {
      require_once $controller;
    } else {
      error_log('There is no controller for the email template.');

      $this->failure(null, 'There is no controller for the email template.');

      return false;
    }

    $post = get_page_by_path($template, OBJECT, self::POST_TYPE);

    $id = $post->ID;

    // Filter ID through WPML. Need to add conditionals for WPML or admin notice
    if ($lang !== 'en') {
      $id = apply_filters('wpml_object_id', $post->ID, self::POST_TYPE, true, $lang);
    }

    // Render Timber template
    $context = Timber::get_context();
    $context['post'] = new Controller\SmnycEmail($id);
    $html = Timber::compile($context['post']->templates(), $context);

    $subject = $context['post']->title;
    $text_body = Html2Text::convert($context['post']->post_content);

    // Replace Bitly URL
    $text_body = str_replace('{{ BITLY_URL }}', $url_shortened, $text_body);
    $html = str_replace('{{ BITLY_URL }}', $url_shortened, $html);

    // Replace Standard URL
    $text_body = str_replace('{{ URL }}', $url, $text_body);
    $html = str_replace('{{ URL }}', $url, $html);

    return array(
      'subject' => $subject,
      'html' => $html,
      'body' => $text_body
    );
  }

  /**
   * Authenticate application and sent email via AWS SES.
   *
   * @param   String  $to    The recipient to send the message to.
   *
   * @param   Array   $info  The email content.
   */
  protected function send($to, $info) {
    try {
      $user = get_option('smnyc_aws_user');
      $secret = get_option('smnyc_aws_secret');
      $from = get_option('smnyc_aws_from');
      $display = get_option('smnyc_aws_display_name');
      $reply = get_option('smnyc_aws_reply');

      $user = (!empty($user)) ? $user : SMNYC_AWS_USER;
      $secret = (!empty($secret)) ? $secret : SMNYC_AWS_SECRET;
      $from = (!empty($from)) ? $from : SMNYC_AWS_FROM;
      $display = (!empty($display)) ? $display : SMNYC_AWS_DISPLAY_NAME;
      $reply = (!empty($reply)) ? $reply : SMNYC_AWS_REPLY;

      // Build display name
      $from = (!empty($display)) ? "$display<$from>" : $from;

      $client = new SesClient(array(
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => [
          'key' => $user,
          'secret' => $secret
        ]
      ));

      $config = array(
        'Source' => $from,
        'Destination' => array(
          'ToAddresses' => [$to]
        ),
        'Message' => array(
          'Subject' => array('Data' => $info['subject']),
          'Body' => array(
            'Text' => array('Data' => $info['body']),
            'Html' => array('Data' => $info['html'])
          )
        )
      );

      if (!empty($reply)) {
        $config['ReplyToAddresses'] = [$reply];
        $config['ReturnPath'] = $reply;
      }

      $result = $client->sendEmail($config);

      if (isset($result['MessageId'])) {
        return true;
      }

      return false;
    } catch (\Aws\Ses\Exception\SesException $e) {
      $this->failure(3, $e->getMessage());
    }
  }

  /**
   * Validates the email address.
   *
   * @param   String   $addr  Email address to send message to.
   *
   * @return  Boolean         Wether the email is valid or not.
   */
  protected function validRecipient($addr) {
    if (empty($addr)) {
      $this->failure(1, 'Email address is missing');

      return false;
    } elseif (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
      $this->failure(2, 'Invalid email address. Please provide a valid email');

      return false;
    }

    return true;
  }

  /**
   * Placeholder for sanitizing the email
   *
   * @param   String  $addr  Email address to send.
   *
   * @return  String         Email address to send.
   */
  protected function sanitizeRecipient($addr) {
    return $addr;
  }

  /**
   * Extend basic setting section in ContactMe Class to add secret,
   * display name and reply email.
   */
  public function createSettingsSection() {
    parent::createSettingsSection();

    $section = $this->prefix . '_section';

    $this->registerSetting(array(
      'id' => $this->prefix . '_secret',
      'title' => $this->secret_label,
      'section' => $section,
      'private' => true
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_display_name',
      'title' => 'Email Display Name <small><em>optional</em></small>',
      'section' => $section,
      'translate' => true
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_reply',
      'title' => 'Reply-To <small><em>optional</em></small>',
      'section' => $section
    ));
  }
}
