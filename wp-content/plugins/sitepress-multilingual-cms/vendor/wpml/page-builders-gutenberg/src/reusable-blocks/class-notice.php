<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class Notice {

	/** @var \WPML_Notices $notices */
	private $notices;

	/** @var JobLinks */
	private $job_links;

	public function __construct(
		\WPML_Notices $notices,
		JobLinks $job_links
	) {
		$this->notices   = $notices;
		$this->job_links = $job_links;
	}

	public function addJobsCreatedAutomatically( array $job_ids ) {
		$job_links = $this->job_links->get( $job_ids );

		if ( $job_links->isEmpty() ) {
			return;
		}

		$text = '<p>' . _n(
			'We automatically created a translation job for the reusable block:',
			'We automatically created translation jobs for the reusable blocks:',
			$job_links->count(),
			'sitepress'
		) . '</p>';

		$text .= '<ul><li>' . implode( '</li><li>', $job_links->toArray() ) . '</li></ul>';

		$notice = $this->notices->create_notice( 'automatic-jobs', $text, __CLASS__ );
		$notice->set_flash( true );
		$notice->set_restrict_to_screen_ids( $this->getRestrictScreenIDs() );
		$notice->set_hideable( true );
		$notice->set_css_class_types( 'notice-info' );
		$this->notices->add_notice( $notice );
	}

	/**
	 * @return array
	 */
	private function getRestrictScreenIDs() {
		$screen_ids = [ 'post', 'edit-post' ];

		if ( isset( $_GET['return_url'] ) ) {
			$query  = wpml_parse_url( $_GET['return_url'], PHP_URL_QUERY );
			parse_str( $query, $params );

			if ( isset( $params['post'] ) ) {
				$post_id    = filter_var( $params['post'], FILTER_VALIDATE_INT );
				$screen_ids = [ get_post_type( $post_id ) ];
			} elseif ( isset( $params['post_type'] ) ) {
				$post_type  = filter_var( $params['post_type'], FILTER_SANITIZE_STRING );
				$screen_ids = [ 'edit-' . $post_type ];
			}
		}

		return $screen_ids;
	}
}
