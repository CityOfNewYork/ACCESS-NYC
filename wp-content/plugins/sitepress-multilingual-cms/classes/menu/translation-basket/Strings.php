<?php

namespace WPML\TM\Menu\TranslationBasket;

use WPML\FP\Obj;
use WPML\Notices\DismissNotices;
use WPML\TM\API\ATE\Account;
use WPML\WP\OptionManager;

class Strings {

	const ATE_AUTOMATIC_TRANSLATION_SUGGESTION = 'wpml-ate-automatic-translation-suggestion';

	/** @var Utility */
	private $utility;

	/** @var DismissNotices */
	private $dismissNotices;

	/**
	 * @param Utility        $utility
	 * @param DismissNotices $dismissNotices
	 */
	public function __construct( Utility $utility, DismissNotices $dismissNotices ) {
		$this->utility        = $utility;
		$this->dismissNotices = $dismissNotices;
	}


	public function getAll() {
		$isCurrentUserOnlyTranslator = $this->utility->isTheOnlyAvailableTranslator();

		return [
			'jobs_sent_to_local_translator'  => $this->jobsSentToLocalTranslator(),
			'jobs_emails_local_did_not_sent' => $this->emailNotSentError(),
			'jobs_committed'                 => $isCurrentUserOnlyTranslator ? $this->jobsSentToCurrentUserWhoIsTheOnlyTranslator() : $this->jobsSentDefaultMessage(),
			'jobs_committing'                => __( 'Working...', 'wpml-translation-management' ),
			'error_occurred'                 => __( 'An error occurred:', 'wpml-translation-management' ),
			'error_not_allowed'              => __(
				'You are not allowed to run this action.',
				'wpml-translation-management'
			),
			'batch'                          => __( 'Batch', 'wpml-translation-management' ),
			'error_no_translators'           => __( 'No selected translators!', 'wpml-translation-management' ),
			'rollbacks'                      => __( 'Rollback jobs...', 'wpml-translation-management' ),
			'rolled'                         => __( 'Batch rolled back', 'wpml-translation-management' ),
			'errors'                         => __( 'Errors:', 'wpml-translation-management' ),
			'sending_batch'                  => $isCurrentUserOnlyTranslator ?
				__( 'Preparing your content for translation', 'wpml-translation-management' )
				: __( 'Sending your jobs to translation', 'wpml-translation-management' ),
			'sending_batch_to_ts'            => __(
				'Sending your jobs to professional translation',
				'wpml-translation-management'
			),
		];
	}


	/**
	 * @return string
	 */
	public function duplicatePostTranslationWarning() {
		$message = esc_html_x(
			'You are about to translate duplicated posts.',
			'1/2 Confirm to disconnect duplicates',
			'wpml-translation-management'
		);

		$message .= "\n";

		$message .= esc_html_x(
			'These items will be automatically disconnected from originals, so translation is not lost when you update the originals.',
			'2/2 Confirm to disconnect duplicates',
			'wpml-translation-management'
		);

		return $message;
	}

	/**
	 * @return string
	 */
	public function jobsSentToLocalTranslator() {
		$translation_dashboard_url  = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' );
		$translation_dashboard_text = esc_html__( 'Translation Dashboard', 'wpml-translation-management' );
		$translation_dashboard_link = '<a href="' . esc_url( $translation_dashboard_url ) . '">' . $translation_dashboard_text . '</a>';

		$translation_notifications_url  = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . \WPML_Translation_Management::PAGE_SLUG_SETTINGS . '&sm=notifications' );
		$translation_notifications_text = esc_html__(
			'WPML → Settings → Translation notifications',
			'wpml-translation-management'
		);
		$translation_notifications_link = '<a href="' . esc_url( $translation_notifications_url ) . '">' . $translation_notifications_text . '</a>';

		$template = '
			<p>%1$s</p>
			<ul>
				<li>%2$s</li>
				<li>%3$s</li>
				<li>%4$s</li>
				<li>%5$s</li>
			</ul>
		';

		return sprintf(
			$template,
			esc_html__( 'All done. What happens next?', 'wpml-translation-management' ),
			esc_html__(
				'WPML sent emails to the translators, telling them about the new work from you.',
				'wpml-translation-management'
			),
			sprintf(
				esc_html__(
					'Your translators should log-in to their accounts in this site and go to %1$sWPML → Translations%2$s. There, they will see the jobs that are waiting for them.',
					'wpml-translation-management'
				),
				'<strong>',
				'</strong>'
			),
			sprintf(
				esc_html__(
					'You can always follow the progress of translation in the %1$s. For a more detailed view and to cancel jobs, visit the %2$s list.',
					'wpml-translation-management'
				),
				$translation_dashboard_link,
				$this->getJobsLink()
			),
			sprintf(
				esc_html__(
					'You can control email notifications to translators and yourself in %s.',
					'wpml-translation-management'
				),
				$translation_notifications_link
			)
		);
	}

	/**
	 * @return string
	 */
	public function jobsSentToCurrentUserWhoIsTheOnlyTranslator() {
		return sprintf(
			       '<p>%s</p><p>%s <a href="%s"><strong>%s</strong></a></p>',
			       __( 'Ready!', 'wpml-translation-management' ),
			       /* translators: This text is followed by 'Translation Queue'. eg To translate those jobs, go to the Translation Queue */
			       __( 'To translate those jobs, go to ', 'wpml-translation-management' ),
			       admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' ),
			       __( 'WPML → Translations', 'wpml-translation-management' )
		       ) . $this->automaticTranslationTip();
	}

	/**
	 * @return string
	 */
	private function automaticTranslationTip() {
		if (
			$this->dismissNotices->isDismissed( self::ATE_AUTOMATIC_TRANSLATION_SUGGESTION ) ||
			! \WPML_TM_ATE_Status::is_enabled_and_activated()
			|| Account::isAbleToTranslateAutomatically()
		) {
			return '';
		}

		$template = "
			<div id='wpml-tm-basket-automatic-translations-suggestion' >
				<h5>%s</h5>
				
				<p>%s</p>
				
				<p>%s <span>%s</span></p>
			</div>
		";

		return sprintf(
			$template,
			esc_html__( 'Want to translate your content automatically?', 'wpml-translation-management' ),
			sprintf(
				esc_html__(
					'Go to %s and click the %sAutomatic Translation%s tab to create an account and start each month with 2,000 free translation credits!',
					'wpml-translation-management'
				),
				'<strong>' . $this->getTMLink() . '</strong>',
				'<strong>',
				'</strong>'
			),
			$this->dismissNotices->renderCheckbox( self::ATE_AUTOMATIC_TRANSLATION_SUGGESTION ),
			__( 'Don’t offer this again', 'wpml-translation-management' )
		);
	}

	/**
	 * @return string
	 */
	public function jobsSentDefaultMessage() {
		$message = '<p>' . esc_html__( 'Ready!', 'wpml-translation-management' ) . '</p>';
		$message .= '<p>';
		$message .= sprintf(
			esc_html__(
				'You can check the status of these jobs in %s.',
				'wpml-translation-management'
			),
			$this->getJobsLink()
		);
		$message .= '</p>';

		return $message;
	}

	/**
	 * @return string
	 */
	private function getJobsLink() {
		$url  = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs' );
		$text = esc_html__( 'WPML → Translation Jobs', 'wpml-translation-management' );
		$link = '<a href="' . esc_url( $url ) . '">' . $text . '</a>';

		return $link;
	}

	private function getTMLink() {
		$url  = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' );
		$text = esc_html__( 'WPML → Translation Management', 'wpml-translation-management' );
		$link = '<a href="' . esc_url( $url ) . '">' . $text . '</a>';

		return $link;
	}

	/**
	 * @return string
	 */
	public function emailNotSentError() {
		return '<li><strong>' . esc_html__(
				'WPML could not send notification emails to the translators, telling them about the new work from you.',
				'wpml-translation-management'
			) . '</strong></li>';
	}
}
