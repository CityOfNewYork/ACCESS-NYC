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

  /**
   * Build the email using content in the admin settings.
   * @param   [type]  $url       [$url description]
   * @param   [type]  $page      [$page description]
   * @param   [type]  $orig_url  [$orig_url description]
   * @return  array              Key > value object containing subject, body, and email
   */
  protected function content($url, $page, $orig_url) {
    if (file_exists(get_template_directory() . '/controllers/single-smnyc-email.php')) {
      require get_template_directory() . '/controllers/single-smnyc-email.php';
    } else {
      error_log(print_r('There is no controller for the email template', true));
      return false;
    }

    if ($page == self::RESULTS_PAGE) {
      $slug = 'screener-results';
    } else {
      $slug = 'programs';
    }

    $post = get_page_by_path($slug, OBJECT, 'smnyc-email');

    // Filter ID through WPML
    $id = apply_filters('wpml_object_id', $post->ID, 'smnyc-email', true);

    // Render Timber template
    $context = Timber::get_context();
    $context['post'] = new Controller\SingleSmnycEmail($id);
    $html = Timber::compile($context['post']->templates(), $context);

    $subject = $context['post']->title;
    $text_body = Html2Text::convert($context['post']->post_content);

    // Replace URL
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
   * @param   [type]  $to    [$to description]
   * @param   [type]  $info  [$info description]
   */
  protected function send($to, $info) {
    try {
      $user = get_option('smnyc_aws_user');
      $secret = get_option('smnyc_aws_secret');
      $from = get_option('smnyc_aws_from');
      $display = get_option('smnyc_aws_display_name');
      $reply = get_option('smnyc_aws_reply');

      $user = (!empty($user)) ? $user : $_ENV['SMNYC_AWS_USER'];
      $secret = (!empty($secret)) ? $secret : $_ENV['SMNYC_AWS_SECRET'];
      $from = (!empty($from)) ? $from : $_ENV['SMNYC_AWS_FROM'];
      $display = (!empty($display)) ? $display : $_ENV['SMNYC_AWS_DISPLAY_NAME'];
      $reply = (!empty($reply)) ? $reply : $_ENV['SMNYC_AWS_REPLY'];

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
    } catch (\Aws\Ses\Exception\SesException $e) {
      $this->failure(3, $e->getMessage());
    }
  }

  /**
   * [validRecipient description]
   * @param   [type]  $addr  [$addr description]
   * @return  [type]         [return description]
   */
  protected function validRecipient($addr) {
    if (empty($addr)) {
      $this->failure(1, 'Email address must be supplied');
    } elseif (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
      $this->failure(2, 'Invalid email address. Please provide a valid email');
    }

    return $addr;
  }

  /**
   * Extend basic setting section in ContactMe Class to add display name and reply email.
   */
  public function createSettingsSection() {
    parent::createSettingsSection();

    $section = $this->prefix . '_section';

    $this->registerSetting(array(
      'id' => $this->prefix . '_display_name',
      'title' => 'Email Display Name <small><em>optional</em></small>',
      'section' => $section,
    ));

    $this->registerSetting(array(
      'id' => $this->prefix . '_reply',
      'title' => 'Reply-To <small><em>optional</em></small>',
      'section' => $section
    ));
  }
}
