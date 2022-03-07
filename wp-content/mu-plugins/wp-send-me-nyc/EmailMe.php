<?php

namespace SMNYC;

use Timber;
use Controller;
use Aws\Ses\SesClient;
use Soundasleep\Html2Text;

class EmailMe extends ContactMe {
  public $action = 'Email';

  public $action_label = 'Email';

  public $service = 'AWS';

  public $type = 'Email';

  public $account_label = 'Key';

  public $secret_label = 'Secret';

  public $from_label = 'From Email Address';

  public $template_controller = 'smnyc-email.php';

  public $post_type = 'smnyc-email';

  public $post_type_label = 'SMNYC Email';

  public $post_type_description = 'Email content for Send Me NYC';

  public $post_type_name = 'SMNYC Emails';

  public $post_type_name_singular = 'SMNYC Email';

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
      $user = get_option($this->info()['option_prefix'] . 'user');
      $secret = get_option($this->info()['option_prefix'] . 'secret');
      $from = get_option($this->info()['option_prefix'] . 'from');
      $display = get_option($this->info()['option_prefix'] . 'display_name');
      $reply = get_option($this->info()['option_prefix'] . 'reply');

      $user = (!empty($user))
        ? $user : constant($this->info()['constant_prefix'] . 'USER');

      $secret = (!empty($secret))
        ? $secret : constant($this->info()['constant_prefix'] . 'SECRET');

      $from = (!empty($from))
        ? $from : constant($this->info()['constant_prefix'] . 'FROM');

      $display = (!empty($display))
        ? $display : constant($this->info()['constant_prefix'] . 'DISPLAY_NAME');

      $reply = (!empty($reply))
        ? $reply : constant($this->info()['constant_prefix'] . 'DISPLAY_REPLY');

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

    $this->registerSetting(array(
      'id' => $this->info()['option_prefix'] . 'secret',
      'title' => $this->secret_label,
      'section' => $this->info()['settings_section'],
      'private' => true
    ));

    $this->registerSetting(array(
      'id' => $this->info()['option_prefix'] . 'display_name',
      'title' => 'Email Display Name <small><em>optional</em></small>',
      'section' => $this->info()['settings_section'],
      'translate' => true
    ));

    $this->registerSetting(array(
      'id' => $this->info()['option_prefix'] . 'reply',
      'title' => 'Reply-To <small><em>optional</em></small>',
      'section' => $this->info()['settings_section']
    ));
  }
}
