<?php

function deprecated_icl_data_from_pro_translation( $translation ) {
	return apply_filters( 'icl_data_from_pro_translation', $translation );
}

add_filter( 'wpml_tm_data_from_pro_translation', 'deprecated_icl_data_from_pro_translation', PHP_INT_MAX, 1 );
