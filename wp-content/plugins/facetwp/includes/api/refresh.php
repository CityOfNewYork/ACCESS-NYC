<?php

add_action( 'rest_api_init', function() {
    register_rest_route( 'facetwp/v1/', '/refresh', array(
        'methods' => 'POST',
        'callback' => 'facetwp_api_refresh'
    ) );
});

function facetwp_api_refresh( $request ) {
    $action = isset( $_POST['action'] ) ? $_POST['action'] : '';

    $valid_actions = array(
        'facetwp_refresh',
        'facetwp_autocomplete_load'
    );

    $valid_actions = apply_filters( 'facetwp_api_valid_actions', $valid_actions );

    if ( in_array( $action, $valid_actions ) ) {
        do_action( $action );
    }

    return array();
}
