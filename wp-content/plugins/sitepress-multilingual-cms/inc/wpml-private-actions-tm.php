<?php

use WPML\TM\API\Batch;
use WPML\TM\Jobs\Dispatch\BatchBuilder;
use WPML\TM\StringTranslation\StringTranslationRequest;
use WPML\TM\Jobs\Dispatch\Strings;
use WPML\TM\Jobs\Dispatch\Messages;
use WPML\TM\API\Basket;
use \WPML\FP\Obj;
use function WPML\FP\partial;

require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-translation-job-helper.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-translation-job-helper-with-api.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-jobs-collection.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-save-translation-data-action.class.php';

function wpml_tm_save_job_fields_from_post( $job_id ) {
	$job = new WPML_Post_Translation_Job( $job_id );
	$job->update_fields_from_post();
}

add_action( 'wpml_save_job_fields_from_post', 'wpml_tm_save_job_fields_from_post', 10, 1 );

/**
 * @param array $data
 * @param bool  $redirect_after_saving
 *
 * @return bool
 */
function wpml_tm_save_data( array $data, $redirect_after_saving = true ) {
	$job_factory      = wpml_tm_load_job_factory();
	$save_factory     = new WPML_TM_Job_Action_Factory( $job_factory );
	$save_data_action = $save_factory->save_action( $data );
	$result           = $save_data_action->save_translation();
	$redirect_target  = $redirect_after_saving ? $save_data_action->get_redirect_target() : false;
	if ( (bool) $redirect_target === true ) {
		wp_redirect( $redirect_target );
	}

	return $result;
}

add_action( 'wpml_save_translation_data', 'wpml_tm_save_data', 10, 1 );

function wpml_tm_add_translation_job( $rid, $translator_id, $translation_package, $batch_options ) {

	$helper = new WPML_TM_Action_Helper();
	$helper->add_translation_job( $rid, $translator_id, $translation_package, $batch_options );
}

add_action( 'wpml_add_translation_job', 'wpml_tm_add_translation_job', 10, 4 );

require_once dirname( __FILE__ ) . '/wpml-private-filters.php';

/**
 * @param int $job_id
 */
function wpml_set_job_translated_term_values( $job_id ) {
	global $sitepress;

	$delete     = $sitepress->get_setting( 'tm_block_retranslating_terms' );
	$job_object = new WPML_Post_Translation_Job( $job_id );
	$job_object->load_terms_from_post_into_job( $delete );
}

add_action( 'wpml_added_local_translation_job', 'wpml_set_job_translated_term_values' );

function wpml_tm_assign_translation_job( $job_id, $translator_id, $service, $type ) {

	$job = $type === 'string'
		? new WPML_String_Translation_Job( $job_id )
		: wpml_tm_load_job_factory()->get_translation_job(
			$job_id,
			false,
			0,
			true
		);
	if ( $job ) {
		return $job->assign_to( $translator_id, $service );
	}

	return null;
}

add_action( 'wpml_tm_assign_translation_job', 'wpml_tm_assign_translation_job', 10, 4 );

/**
 * Potentially handles the request to add strings to the translation basket,
 * triggered by String Translation.
 */
function wpml_tm_add_strings_to_basket() {
	if (
		Obj::prop( 'icl_st_action', $_POST ) === 'send_strings'
		&& wpml_is_action_authenticated( 'icl-string-translation' )
	) {
		StringTranslationRequest::sendToTranslation( $_POST, getTranslationSendMethod() );
	}
}

function getTranslationSendMethod() {
	if ( Basket::shouldUse() ) {
		return [ TranslationProxy_Basket::class, 'add_strings_to_basket' ];
	} else {
		return partial(
			[ Strings::class, 'dispatch' ],
			Batch::class . '::sendStrings',
			new Messages(),
			BatchBuilder::buildStringsBatch()
		);
	}
}

if ( is_admin() ) {
	add_action( 'init', 'wpml_tm_add_strings_to_basket' );
}
