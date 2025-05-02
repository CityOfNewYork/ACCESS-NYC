<?php

/**
 * Class WPML_TM_Batch_Report_Email_Process
 */
class WPML_TM_Batch_Report_Email_Process {

	/**
	 * @var WPML_TM_Batch_Report
	 */
	private $batch_report;

	/**
	 * @var WPML_TM_Batch_Report_Email_Builder
	 */
	private $email_builder;

	/**
	 * WPML_TM_Batch_Report_Email_Process constructor.
	 *
	 * @param WPML_TM_Batch_Report $batch_report
	 * @param WPML_TM_Batch_Report_Email_Builder $email_builder
	 */
	public function __construct( WPML_TM_Batch_Report $batch_report, WPML_TM_Batch_Report_Email_Builder $email_builder ) {
		$this->batch_report  = $batch_report;
		$this->email_builder = $email_builder;
	}

	public function process_emails() {
		$this->batch_report->process_jobs_with_delay();
		$batch_jobs = $this->batch_report->get_jobs();

		$this->email_builder->prepare_assigned_jobs_emails( $batch_jobs );
		$this->email_builder->prepare_unassigned_jobs_emails( $batch_jobs );

		$this->send_emails();
	}

	private function send_emails() {
		$this->batch_report->clean_batch_jobs();

		$headers = array();
		$headers[] = 'Content-type: text/html; charset=UTF-8';
		$translators_ids = [ 0 ];

		foreach ( $this->email_builder->get_emails() as $email ) {
			$email['attachment'] = isset( $email['attachment'] ) ? $email['attachment'] : array();
			$email_sent = wp_mail( $email['email'], $email['subject'], $email['body'], $headers, $email['attachment'] );

			if ( $email_sent ) {
				$translators_ids[] = $email['translator_id'];
			}
		}

		$orphaned_translators_ids = $this->email_builder->get_orphaned_translators_ids();
		$dnd_translators_ids      = $this->email_builder->get_dnd_translators_ids();
		$translators_ids          = array_merge( $translators_ids, $orphaned_translators_ids, $dnd_translators_ids );

		$this->batch_report->reset_batch_report_for_translators( $translators_ids );
	}
}
