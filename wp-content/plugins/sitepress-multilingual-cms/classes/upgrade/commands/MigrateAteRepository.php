<?php

namespace WPML\TM\Upgrade\Commands;

class MigrateAteRepository implements \IWPML_Upgrade_Command {

	const TABLE_NAME            = 'icl_translate_job';
	const COLUMN_EDITOR_JOB_ID  = 'editor_job_id';
	const COLUMN_EDIT_TIMESTAMP = 'edit_timestamp';

	const OPTION_NAME_REPO = 'WPML_TM_ATE_JOBS';

	/** @var \WPML_Upgrade_Schema $schema */
	private $schema;

	/** @var bool $result */
	private $result = false;

	public function __construct( array $args ) {
		$this->schema = $args[0];
	}

	/**
	 * @return bool
	 */
	public function run() {
		$this->result = $this->addColumnsToJobsTable();

		if ( $this->result ) {
			$this->migrateOldRepository();
		}

		return $this->result;
	}

	private function addColumnsToJobsTable() {
		$result = true;

		if ( ! $this->schema->does_column_exist( self::TABLE_NAME, self::COLUMN_EDITOR_JOB_ID ) ) {
			$result = $this->schema->add_column( self::TABLE_NAME, self::COLUMN_EDITOR_JOB_ID, 'bigint(20) unsigned NULL' );
		}

		return $result;
	}

	private function migrateOldRepository() {
		$records = get_option( self::OPTION_NAME_REPO );

		if ( is_array( $records ) && $records ) {
			$wpdb           = $this->schema->get_wpdb();
			$recordPairs    = wpml_collect( array_keys( $records ) )->zip( $records );
			$ateJobIdCases  = $recordPairs->reduce( $this->getCasesReducer(), '' ) . "ELSE 0\n";

			$sql = "
				UPDATE {$wpdb->prefix}" . self::TABLE_NAME . "
				SET
					" . self::COLUMN_EDITOR_JOB_ID . " = (
						CASE job_id
							" . $ateJobIdCases . "
					    END
					)
				WHERE " . self::COLUMN_EDITOR_JOB_ID . " IS NULL
				    AND job_id IN(" . wpml_prepare_in( array_keys( $records ), '%d' ) . ")
			";

			$wpdb->query( $sql );
		}

		$this->disableAutoloadOnOldOption();
	}

	/**
	 * @param string $field
	 *
	 * @return \Closure
	 */
	private function getCasesReducer() {
		$wpdb  = $this->schema->get_wpdb();

		return function( $cases, $data ) use ( $wpdb ) {
			$cases .= isset( $data[1]['ate_job_id'] )
				? $wpdb->prepare( "WHEN %d THEN %d\n", $data[0], $data[1]['ate_job_id'] ) : '';

			return $cases;
		};
	}

	private function disableAutoloadOnOldOption() {
		$wpdb = $this->schema->get_wpdb();

		$wpdb->update(
			$wpdb->options,
			[ 'autoload' => 'no' ],
			[ 'option_name' => self::OPTION_NAME_REPO ]
		);
	}

	/**
	 * Runs in admin pages.
	 *
	 * @return bool
	 */
	public function run_admin() {
		return $this->run();
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_ajax() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_frontend() {
		return null;
	}

	/**
	 * @return bool
	 */
	public function get_results() {
		return $this->result;
	}
}
