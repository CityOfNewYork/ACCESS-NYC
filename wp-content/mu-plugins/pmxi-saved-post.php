<?php

/**
 * Plugin Name: WP All Import "Post-import" Update
 * Description: Triggers Google maps locations to properly upate in locations posts after import via WP All Import.
 * Author: NYC Opportunity
 */

add_action('pmxi_saved_post', function($id) {
  $office_loc = array(
    'post_types' => array('location'),
    'fields' => array(
      'google_map' => 'field_588003b6be759',
      'address_street' => 'field_58800318be754',
      'address_street_2' => 'field_5880032abe755',
      'city' => 'field_58acf5f524f67',
      'zip' => 'field_58acf60c24f68',
    ),
  );

  if (get_post_type($id) == 'location') {
    // Get the address field for the google map
    $location = get_field($office_loc['fields']['google_map'], $id);

    // Check to see if the address is empty - if empty, populated it with the correct fields
    if (isset($location['address']) && isset($location['lat']) && isset($location['lng'])) {
      $full_address = $location['address'];
    } else {
      // Create a location array to be populated
      $location = array(
        'address' => '',
        'lat' => '',
        'lng' => ''
      );

      // Create a full address
      $full_address = get_field(
        $office_loc['fields']['address_street'], $id) . ', ' .
        get_field($office_loc['fields']['city'], $id) . ' ' .
        get_field($office_loc['fields']['zip'], $id
      );

      $address = urlencode($full_address); // Spaces as + signs

      // will want to replace the key
      $key = $_ENV('GOOGLE_LOCATION_POST_IMPORT');
      $address_query = wp_remote_get("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=$key");
      $address_json = wp_remote_retrieve_body($address_query);
      $address_data = json_decode($address_json);

      // if the address contains info
      if (isset($address_data)) {
        echo var_dump($address_data);
        $lat = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $lng = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
      }
      // set the new address
      $location['address'] = $full_address;
      $location['lat'] = $lat;
      $location['lng'] = $lng;

      // update the address, latitude, and longitude field
      update_post_meta($id, 'address', $location['address']);
      update_post_meta($id, 'lat', $location['lat']);
      update_post_meta($id, 'lng', $location['lng']);

      // update the google map fields
      update_field($office_loc['fields']['google_map'], $location, $id);
    }
  }
}, 10, 1);
