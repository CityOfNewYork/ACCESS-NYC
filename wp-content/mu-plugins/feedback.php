<?php

namespace FeedbackNYC;

add_action('wp_ajax_feedback', 'FeedbackNYC\feedbackHandler');
add_action('wp_ajax_nopriv_feedback', 'FeedbackNYC\feedbackHandler');

/**
 * Creates a record on an Airtable based on the feedback form submission.
 *
 * @return Array - If successful, returns a response from Airtable client.
 */
function feedbackHandler() {
  // "constants"
  $AIRTABLE_API_URL = 'https://api.airtable.com/v0';
  $RECAPTCHA_URL = 'https://recaptchaenterprise.googleapis.com/v1/projects/access-nyc/assessments?key=' . GOOGLE_RECAPTCHA_PRIVATE_API_KEY;
  $RECAPTCHA_EXPECTED_ACTION = 'feedback_submit';
  $RECAPTCHA_MIN_SCORE = 0.5;

  $nonce = $_POST['feedback-nonce'];
  $nonce_valid = wp_verify_nonce($nonce, 'feedback');
  $recaptcha_valid = false;

  if ($nonce_valid) {
    // validate ReCAPTCHA
    $token = $_POST['g-recaptcha-response'];
      
    $body = [
      'event' => [
        'token' => $token,
        'expectedAction' => $RECAPTCHA_EXPECTED_ACTION,
        'siteKey' => GOOGLE_RECAPTCHA_SITE_KEY,
      ]
    ];

    $response = wp_remote_post($RECAPTCHA_URL, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'data_format' => 'body',
    ]);

    if (is_wp_error($response)) {
      error_log('WP error when evaluating ReCAPTCHA: ' . $response->get_error_message());
      $recaptcha_valid = false;
    } elseif (wp_remote_retrieve_response_code($response) !== 200) {
      error_log('Bad response when evaluating ReCAPTCHA: ' . wp_remote_retrieve_body($response));
      $recaptcha_valid = false;
    } else {
      $data = json_decode(wp_remote_retrieve_body($response));
      $recaptcha_valid = $data->riskAnalysis->score > $RECAPTCHA_MIN_SCORE;
    }
  }

  if ($recaptcha_valid) {
    try {
      if (defined('AIRTABLE_FEEDBACK_API_TOKEN') && defined('AIRTABLE_FEEDBACK_BASE_KEY') && defined('AIRTABLE_FEEDBACK_TABLE_KEY')) {
        $feedback_fields = get_values_from_submission($_POST);
        $api_request_url = $AIRTABLE_API_URL . '/' . AIRTABLE_FEEDBACK_BASE_KEY . '/' . AIRTABLE_FEEDBACK_TABLE_KEY;

        $response = wp_remote_post($api_request_url, [
          'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . AIRTABLE_FEEDBACK_API_TOKEN
          ],
          'body' => wp_json_encode($feedback_fields),
          'method' => 'POST',
          'data_format' => 'body',
        ]);
  
        if (is_wp_error($response)) {
          error_log('WP error when submitting feedback to Airtable: ' . $response->get_error_message());
          failure(400, 'Error when submitting feedback');
        } elseif (wp_remote_retrieve_response_code($response) !== 200) {
          error_log('Bad response submitting feedback to Airtable: ' . wp_remote_retrieve_body($response));
          failure(400, 'Error when submitting feedback');
        } else {
          wp_send_json([
            'success' => true,
            'error' => 200,
            'message' => __('Thank you for your feedback.'),
            'retry' => false
          ]);
        }
      }
    } catch (Exception $e) {
      $message = $e->getMessage();

      failure(400, $message);
    }
  } else {
    $message = 'Feedback form nonce not verified';

    failure(400, $message);
  };
}

/**
 * Return the client to interact with the Airtable API.
 *
 * @return Array - The Airtable PHP client
 */
function get_airtable_client() {
  if (defined('AIRTABLE_FEEDBACK_API_KEY') && defined('AIRTABLE_FEEDBACK_BASE_KEY')) {
    $airtable = new Airtable(array(
      'api_key' => AIRTABLE_FEEDBACK_API_KEY,
      'base'    => AIRTABLE_FEEDBACK_BASE_KEY
    ));
    return $airtable;
  } else {
    failure(400, 'Airtable API Keys are missing.');
  }
}

/**
 * Pass values from the submission inthe Airtable fields
 *
 * @param Array $submission - The POST request data from the feedback form.
 *
 * @return Array The fields and values from the feedback form submission.
 */
function get_values_from_submission($submission) {
  $feedback_fields = array(
    'fields' => array(
      'helpful'        => $submission['helpful'],
      'description'    => $submission['description'],
      'program'        => $submission['program'],
      'browser'        => $_SERVER['HTTP_USER_AGENT']
    )
  );

  return $feedback_fields;
}

/**
 * Sends a failure notice to the request.
 *
 * @param   Number   $code     The specific error code
 * @param   String   $message  The feedback message
 * @param   Boolean  $retry    Wether to retry
 */
function failure($code, $message, $retry = false) {
  wp_send_json([
    'success' => false,
    'error' => $code,
    'message' => $message,
    'retry' => $retry
  ]);
}
