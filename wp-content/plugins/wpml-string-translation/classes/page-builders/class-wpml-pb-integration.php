<?php

/**
 * Class WPML_PB_Integration
 */
class WPML_PB_Integration {

	const MIGRATION_DONE_POST_META = '_wpml_location_migration_done';

	private $sitepress;
	private $factory;
	private $new_translations_recieved = false;
	private $save_post_queue = array();

	private $strategies = array();

	/**
	 * @var WPML_PB_Integration_Rescan
	 */
	private $rescan;

	/**
	 * WPML_PB_Integration constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WPML_PB_Factory $factory
	 */
	public function __construct( SitePress $sitepress, WPML_PB_Factory $factory ) {
		$this->sitepress = $sitepress;
		$this->factory   = $factory;
	}

	/**
	 * @param IWPML_PB_Strategy $strategy
	 */
	public function add_strategy( IWPML_PB_Strategy $strategy ) {
		$this->strategies[] = $strategy;
	}

	/**
	 * @return WPML_PB_Integration_Rescan
	 */
	public function get_rescan() {
		if ( null === $this->rescan ) {
			$this->rescan = new WPML_PB_Integration_Rescan( $this );
		}

		return $this->rescan;
	}

	/**
	 * @param WPML_PB_Integration_Rescan $rescan
	 */
	public function set_rescan( WPML_PB_Integration_Rescan $rescan ) {
		$this->rescan = $rescan;
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public function queue_save_post_actions( $post_id, $post ) {
		$this->save_post_queue[ $post_id ] = $post;
	}

	/**
	 * @param WP_Post $post
	 */
	public function register_all_strings_for_translation( $post ) {
		if ( $this->is_post_status_ok( $post ) && $this->is_original_post( $post ) ) {
			foreach ( $this->strategies as $strategy ) {
				$strategy->register_strings( $post );
			}
		}
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	private function is_original_post( $post ) {
		return $post->ID == $this->sitepress->get_original_element_id( $post->ID, 'post_' . $post->post_type );
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	private function is_post_status_ok( $post ) {
		return ! in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ) );
	}

	/**
	 * Add all actions filters.
	 */
	public function add_hooks() {
		add_action( 'pre_post_update', array( $this, 'migrate_location' ), 10, 2 );
		add_action( 'save_post', array( $this, 'queue_save_post_actions' ), PHP_INT_MAX, 2 );
		add_action( 'icl_st_add_string_translation', array( $this, 'new_translation' ), 10, 1 );
		add_action( 'shutdown', array( $this, 'do_shutdown_action' ) );
		add_action( 'wpml_pro_translation_completed', array( $this, 'cleanup_strings_after_translation_completed' ), 10, 3 );

		add_filter( 'wpml_tm_translation_job_data', array( $this, 'rescan' ), 9, 2 );
	}

	/**
	 * @param int      $new_post_id
	 * @param array    $fields
	 * @param stdClass $job
	 */
	public function cleanup_strings_after_translation_completed( $new_post_id, array $fields, stdClass $job ) {
		if ( 'post' === $job->element_type_prefix ) {
			$original_post = get_post( $job->original_doc_id );
			$this->register_all_strings_for_translation( $original_post );
		}
	}

	public function do_shutdown_action() {
		$this->save_translations_to_post();

		remove_action( 'icl_st_add_string_translation', array( $this, 'new_translation' ), 10, 1 );

		foreach( $this->save_post_queue as $post_id => $post ) {
			$this->register_all_strings_for_translation( $post );
		}
	}

	public function new_translation( $translated_string_id ) {
		foreach ( $this->strategies as $strategy ) {
			$this->factory->get_string_translations( $strategy )->new_translation( $translated_string_id );
		}
		$this->new_translations_recieved = true;
	}

	public function save_translations_to_post() {
		if ( $this->new_translations_recieved ) {
			foreach ( $this->strategies as $strategy ) {
				$this->factory->get_string_translations( $strategy )->save_translations_to_post();
			}
		}
	}

	/**
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-958
	 * @param array                $translation_package
	 * @param WP_Post|WPML_Package $post
	 *
	 * @return array
	 */
	public function rescan( array $translation_package, $post ) {
		if ( $post instanceof WP_Post ) {
			$translation_package = $this->get_rescan()->rescan( $translation_package, $post );
		}

		return $translation_package;
	}

	/**
	 * @param int $post_id
	 * @param object $post_data
	 */
	public function migrate_location( $post_id, $post_data ) {
		if ( $this->post_has_strings( $post_id ) && ! $this->is_migrate_location_done( $post_id ) ) {
			$wpdb = $this->sitepress->get_wpdb();
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
			if ( $this->is_post_status_ok( $post ) && $this->is_original_post( $post ) ) {
				foreach ( $this->strategies as $strategy ) {
					$strategy->migrate_location( $post_id, $post->post_content );
				}
			}

			$this->mark_migrate_location_done( $post_id );
		}
	}

	public function get_factory() {
		return $this->factory;
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function post_has_strings( $post_id ) {
		$wpdb = $this->sitepress->get_wpdb();
		$string_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->prefix}icl_string_packages WHERE post_id = %d", $post_id) );
		return $string_count > 0;
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_migrate_location_done( $post_id ) {
		return get_post_meta( $post_id, self::MIGRATION_DONE_POST_META, true );
	}

	/**
	 * @param int $post_id
	 */
	private function mark_migrate_location_done( $post_id ) {
		update_post_meta( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true );
	}

}
