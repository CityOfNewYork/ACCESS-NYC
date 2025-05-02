<?php

namespace WPML\TM\AutomaticTranslation\Actions;

use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Option;
use WPML\UIPage;
use function WPML\FP\spreadArgs;

/**
 * When user creates post/page while automatic translation is active and ATE is temporarily unavailable,
 * WPML fails to create local translation job for this post/page after checking for languages eligible for automatic translation.
 *
 * So, here we create a notice for the user to inform him about the content that WPML failed to created translation job for,
 * and inform him how he can deal with it to overcome the problem.
 *
 * @class AutomaticTranslationJobCreationFailureNotice
 *
 * @see wpmldev-161
 */
class AutomaticTranslationJobCreationFailureNotice implements \IWPML_Action {
	const OPTION_KEY = 'auto-translation-job-creation-error';
	const NOTICE_ID = 'automatic-job-creation-failed';

	/** @var array */
	private $jobFailedElements;

	/** @var \WPML_Notices */
	private $wpmlNotices;

	/** @var \WPML_Translation_Element_Factory */
	private $wpmlTranslationElementFactory;

	public function __construct( \WPML_Translation_Element_Factory $translationElementFactory, \WPML_Notices $wpmlNotices ) {
		$optionVal                           = Option::get( self::OPTION_KEY );
		$this->jobFailedElements             = $optionVal ? json_decode( stripslashes( $optionVal ), true ) : [];
		$this->wpmlNotices                   = $wpmlNotices;
		$this->wpmlTranslationElementFactory = $translationElementFactory;

		// Update notice when class is constructed so that when user reloads a page or navigates to any other page ...
		// We check for content that has job created and remove it from 'auto-translation-job-creation-error' in options table
		$this->updateNotice();
	}

	public function add_hooks() {
		Hooks::onAction( 'wpml_update_failed_jobs_notice' )
		     ->then( spreadArgs( [ $this, 'updateNotice' ] ) );
	}

	/**
	 * Updates notice about posts that WPML failed to created local translation jobs for.
	 * First we check if any posts got jobs created, and we remove them from options table.
	 * Then if we pass a valid element we add it to the options table.
	 * And finally we update the notice with updated elements or dismiss notice if all elements had local translation job created for them.
	 *
	 * @param \WPML_Post_Element $postElement
	 *
	 * @return void
	 */
	public function updateNotice( $postElement = null ) {
		$previousJobFailedElements = $this->jobFailedElements;

		$this->deleteElementsThatHaveJobsCreated();

		if ( $postElement ) {
			$this->addFailedJobPostElement( $postElement );
		}

		if ( Lst::length( $previousJobFailedElements ) !== Lst::length( $this->jobFailedElements ) ) {
			$this->updateOrDismissNotice();
		}
	}

	/**
	 * Deletes all post elements from options table that had local translation job created for them and updates the $jobFailedElements property.
	 *
	 * @return void
	 */
	public function deleteElementsThatHaveJobsCreated() {
		foreach ( $this->jobFailedElements as $contentId => $contentInfo ) {
			$postElement = $this->wpmlTranslationElementFactory->create_post( $contentId );
			if ( Lst::length( $postElement->get_translations() ) > 1 ) {
				unset( $this->jobFailedElements[ $contentId ] );

				$encodedContent = $this->encodedContent( $this->jobFailedElements );
				Option::update( self::OPTION_KEY, $encodedContent );
			}
		}

		if ( ! Lst::length( $this->jobFailedElements ) ) {
			$this->deleteOption();
		}
	}

	/**
	 * Adds to options table and $jobFailedElements property the post element that WPML failed to create local translation job for.
	 *
	 * @param \WPML_Post_Element $postElement
	 *
	 * @return void
	 */
	public function addFailedJobPostElement( $postElement ) {
		$this->jobFailedElements[ $postElement->get_id() ] = [
			'title' => $postElement->get_wp_object()->post_title,
			'lang'  => $postElement->get_language_code()
		];

		$encodedContent = $this->encodedContent( $this->jobFailedElements );
		Option::update( self::OPTION_KEY, $encodedContent );
	}

	/**
	 * Handles updating or dismissing the notice which appears for posts that WPML failed to create local translation job for.
	 *
	 * @return void
	 */
	public function updateOrDismissNotice() {
		if ( Lst::length( $this->jobFailedElements ) ) {
			$this->displayNotice();
		} else {
			$notice = $this->wpmlNotices->get_notice( self::NOTICE_ID );
			$notice && $this->wpmlNotices->dismiss_notice( $notice );
		}
	}

	/**
	 * Displays the notice which appears for posts that WPML failed to create local translation job for.
	 *
	 * @return void
	 */
	private function displayNotice() {
		$message = $this->constructMessage();
		$notice  = $this->createNotice( $message );
		$this->wpmlNotices->add_notice( $notice, true );
	}

	/**
	 * Constructs the notice message.
	 *
	 * @return string
	 */
	private function constructMessage() {
		$message = '<h2 id="job_creation_fail_notice">' . __( 'WPML experienced an issue while trying to automatically translating some of your content:', 'sitepress' ) . '</h2>';
		$message .= '<ul>';
		foreach ( $this->jobFailedElements as $contentInfo ) {
			$message .= '<li class="job_creation_fail_element">' . $contentInfo['title'] . '</li>';
		}

		$message .= '</ul>';
		$message .= '<p id="job_creation_fail_tm_link">' . sprintf( __( 'To translate these items, please go to <a rel="noreferrer" href="%s">Translation Management</a> and send them for translation.', 'sitepress' ), UIPage::getTMDashboard() ) . '</p>';
		$message .= '<p id="job_creation_fail_support_link">' . sprintf( __( 'If the problem continues, contact <a target="_blank" rel="noreferrer" href="%s">WPML support</a> for assistance.', 'sitepress' ), 'https://wpml.org/forums/forum/english-support/?utm_source=plugin&utm_medium=gui&utm_campaign=wpml-posts' ) . '</p>';

		return $message;
	}

	/**
	 * Creates the notice which appears for posts that WPML failed to create local translation job for.
	 *
	 * @param string $message
	 *
	 * @return \WPML_Notice
	 */
	private function createNotice( $message ) {
		$notice = $this->wpmlNotices->create_notice( self::NOTICE_ID, $message );
		$notice->set_dismissible( true );
		$notice->reset_dismiss();
		$notice->set_css_class_types( [ 'error' ] );

		return $notice;
	}

	/**
	 * Deletes 'auto-translation-job-creation-error' from options table when all posts that were saved before got local translation job created for them.
	 *
	 * @return void
	 */
	private function deleteOption() {
		if ( ! Option::get( self::OPTION_KEY ) ) {
			return;
		}

		Option::delete( self::OPTION_KEY );
	}

	/**
	 * Creates a JSON encoded string to be saved in the 'auto-translation-job-creation-error' key in options table
	 *
	 * @param array $content
	 *
	 * @return string
	 */
	private function encodedContent( $content ) {
		return json_encode( $content ) ?: '';
	}
}
