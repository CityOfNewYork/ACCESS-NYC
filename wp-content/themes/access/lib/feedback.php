<?php

namespace FeedbackNYC;

use \TANIOS\Airtable\Airtable;

add_action('wp_ajax_feedback', 'FeedbackNYC\feedbackHandler');
add_action('wp_ajax_nopriv_feedback', 'FeedbackNYC\feedbackHandler');

function feedbackHandler() {
  $nonce = $_POST['feedback-nonce'];

  if (wp_verify_nonce($nonce, 'feedback')) {
    $client = get_airtable_client();

    $feedback_fields = get_values_from_submission($_POST);

    $airtable_record = create_record($feedback_fields, $client);
  } else {
    $message = 'Error in lib/feedback.php file.';
    error_log($message);
    die(__('Security check', 'textdomain'));
  };
}

/**
 * Return the client to interact with the Airtable API.
 */
function get_airtable_client() {
  $airtable = new Airtable(array(
    'api_key' => AIRTABLE_API_KEY,
    'base'    => AIRTABLE_BASE_KEY
  ));
  return $airtable;
}

/**
 * Creates a record in an Airtable table.
 */
function create_record($args, $client) {
  $new_record = $client->saveContent(AIRTABLE_TABLE_NAME, $args);
  return $new_record;
}

/**
 * Pass values from the submission inthe Airtable fields
 */
function get_values_from_submission($submission) {
  $feedback_fields = array(
    'helpful'        => $submission['helpful'],
    'description'    => $submission['description'],
    'program'        => $submission['program']
  );
  return $feedback_fields;
}
