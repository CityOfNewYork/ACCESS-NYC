<?php

function wp_all_import_sanitize_scheduling_times ( $times ){

	if ( !empty($times) && is_array($times) ) {

	foreach ($times as $key => $time) {
	
		if ( strtotime($time) === false && $time !== '' ) {
			unset($times[$key]);
		}
	}

	return $times;

	}
}