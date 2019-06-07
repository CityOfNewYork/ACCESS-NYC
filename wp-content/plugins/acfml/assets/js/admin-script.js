jQuery( document ).ready( function( $ ) {
    $('.wpml_acf_annotation').prev().prop('disabled', true);
    $('.wpml_acf_annotation.relationship').prev().find('input').prop('disabled', true);
    $('.wpml_acf_annotation.taxonomy').prev().find('input').prop('disabled', true);
} );
