<?php

namespace FeedbackNYC;

use \TANIOS\Airtable\Airtable;

add_action('wp_ajax_feedback', 'FeedbackNYC\feedbackHandler');
add_action('wp_ajax_nopriv_feedback', 'FeedbackNYC\feedbackHandler');

/**
 * Creates a record on an Airtable based on the feedback form submission.
 *
 * @return Array - If successful, returns a response from Airtable client.
 */
function feedbackHandler() {
  $nonce = $_POST['feedback-nonce'];

  if (wp_verify_nonce($nonce, 'feedback')) {
    try {
      $client = get_airtable_client();
      $feedback_fields = get_values_from_submission($_POST);
      $airtable_record = create_record($feedback_fields, $client);

      wp_send_json([
        'success' => true,
        'error' => 200,
        'message' => __('Thank you for your feedback.'),
        'retry' => false
      ]);
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
 * Creates a record in an Airtable table.
 *
 * @param Array $args - The form fields and values from the submission.
 * @param Array $client - The Airtable PHP client.
 *
 * @return Array Response from Airtable regarding creation of a table record.
 */
function create_record($args, $client) {
  $new_record = $client->saveContent(AIRTABLE_FEEDBACK_TABLE_NAME, $args);
  $client_response = (array) $new_record;

  foreach ($client_response as $key => $value) {
    if (isset($value->error)) {
      failure(400, "{$value->error->message}");
    }
  }

  return $new_record;
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
    'helpful'        => $submission['helpful'],
    'description'    => $submission['description'],
    'program'        => $submission['program'],
    'browser'        => $_SERVER['HTTP_USER_AGENT']
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
