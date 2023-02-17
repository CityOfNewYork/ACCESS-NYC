<?php

namespace WPML\TM\Editor;

class ClassicEditorActions {

	public function addHooks() {
		add_action( 'wp_ajax_wpml_save_job_ajax', [ $this, 'saveJob' ] );
	}

	public function saveJob() {
		if ( ! wpml_is_action_authenticated( 'wpml_save_job' ) ) {
			wp_send_json_error( 'Permission denied.' );

			return;
		}

		$data      = [];
		$post_data = \WPML_TM_Post_Data::strip_slashes_for_single_quote( $_POST['data'] );
		parse_str( $post_data, $data );

		/**
		 * It filters job data
		 *
		 * @param array $data
		 */
		$data = apply_filters( 'wpml_translation_editor_save_job_data', $data );

		$job = \WPML\Container\make( \WPML_TM_Editor_Job_Save::class );

		$job_details = [
			'job_type'             => $data['job_post_type'],
			'job_id'               => $data['job_post_id'],
			'target'               => $data['target_lang'],
			'translation_complete' => isset( $data['complete'] ) ? true : false,
		];
		$job         = apply_filters( 'wpml-translation-editor-fetch-job', $job, $job_details );

		$ajax_response = $job->save( $data );
		$ajax_response->send_json();

	}
}
