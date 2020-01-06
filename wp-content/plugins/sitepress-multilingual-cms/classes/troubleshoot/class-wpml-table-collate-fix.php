<?php

class WPML_Table_Collate_Fix implements IWPML_AJAX_Action, IWPML_Backend_Action, IWPML_DIC_Action {

	const AJAX_ACTION = 'fix_tables_collation';

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/** @var WPML_Upgrade_Schema $schema */
	private $schema;

	public function __construct( wpdb $wpdb, WPML_Upgrade_Schema $schema ) {
		$this->wpdb   = $wpdb;
		$this->schema = $schema;
	}

	public function add_hooks() {
		add_action( 'wpml_troubleshooting_after_fix_element_type_collation', array(
			$this,
			'render_troubleshooting_button'
		) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'fix_collate_ajax' ) );
		add_action( 'upgrader_process_complete', array( $this, 'fix_collate' ), PHP_INT_MAX );
	}

	public function fix_collate_ajax() {
	    if ( isset( $_POST['nonce'] )
             && wp_verify_nonce( $_POST['nonce'], self::AJAX_ACTION )
        ) {
	        $this->fix_collate();
		    wp_send_json_success();
        }
    }

	public function render_troubleshooting_button() {
		?>
        <p>
            <input id="wpml_fix_tables_collation" type="button" class="button-secondary"
                   value="<?php _e( 'Fix WPML tables collation', 'sitepress' ) ?>"/><br/>
			<?php wp_nonce_field( self::AJAX_ACTION, 'wpml-fix-tables-collation-nonce' ); ?>
            <small style="margin-left:10px;"><?php esc_attr_e( 'Fixes the collation of WPML tables in order to match the collation of default WP tables.', 'sitepress' ) ?></small>
        </p>
		<?php
	}

	public function enqueue_scripts( $hook ) {
	    if ( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' === $hook ) {
		    wp_enqueue_script( 'wpml-fix-tables-collation', ICL_PLUGIN_URL . '/res/js/fix-tables-collation.js', array( 'jquery' ), ICL_SITEPRESS_VERSION );
        }
	}

	public function fix_collate() {
		if ( did_action( 'upgrader_process_complete' ) > 1 ) {
			return;
		}

		$wp_default_table_data = $this->wpdb->get_row(
			$this->wpdb->prepare( 'SHOW TABLE status LIKE %s', $this->wpdb->posts )
		);

		if ( isset( $wp_default_table_data->Collation ) ) {
		    $charset = $this->schema->get_default_charset();

			foreach ( $this->get_all_wpml_tables() as $table ) {
				$table = reset( $table );

				$table_data = $this->wpdb->get_row(
					$this->wpdb->prepare( 'SHOW TABLE status LIKE %s', $table )
				);

				if ( isset( $table_data->Collation ) && $table_data->Collation !== $wp_default_table_data->Collation ) {
					$this->wpdb->query(
						$this->wpdb->prepare(
							'ALTER TABLE ' . $table . ' CONVERT TO CHARACTER SET %s COLLATE %s',
							$charset,
							$wp_default_table_data->Collation
						)
					);
				}
			}
		}
	}

	/**
	 * @return array
	 */
	private function get_all_wpml_tables() {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->wpdb->prefix . 'icl_%' ),
			ARRAY_A
		);
	}
}