<?php

use \WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\PB\Shortcode\StringCleanUp;
use function WPML\FP\invoke;
use function WPML\Container\make;

/**
 * Class WPML_PB_Integration
 */
class WPML_PB_Integration {

	const MIGRATION_DONE_POST_META = '_wpml_location_migration_done';

	private $sitepress;
	private $factory;
	private $new_translations_recieved = false;
	private $save_post_queue = array();
	private $is_registering_string = false;

	private $strategies = array();

	/** @var StringCleanUp[]  */
	private $stringCleanUp = [];

	/**
	 * @var WPML_PB_Integration_Rescan
	 */
	private $rescan;

	/** @var IWPML_PB_Media_Update[]|null $media_updaters */
	private $media_updaters;

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

	public function resave_post_translation_in_shutdown( WPML_Post_Element $post_element, $disallowed_in_shutdown = true ) {
		if ( ! $post_element->get_source_element()
			 || ( did_action( 'shutdown' ) && $disallowed_in_shutdown )
			 || array_key_exists( $post_element->get_id(), $this->save_post_queue )
		) {
			return;
		}

		if ( WPML_PB_Last_Translation_Edit_Mode::is_native_editor( $post_element->get_id() ) ) {
			return;
		}

		$updated_packages = $this->factory->get_package_strings_resave()->from_element( $post_element );

		if ( ! $updated_packages ) {
			$this->factory->get_handle_post_body()->copy(
				$post_element->get_id(),
				$post_element->get_source_element()->get_id(),
				array()
			);
		}

		$this->with_strategies( function( IWPML_PB_Strategy $strategy ) use ( $updated_packages, $post_element ) {
			foreach ( $updated_packages as $package ) {
				$this->factory->get_string_translations( $strategy )
					->add_package_to_update_list( $package, $post_element->get_language_code() );
			}
		} );

		$this->new_translations_recieved = true;
		$this->queue_save_post_actions( $post_element->get_id(), $post_element->get_wp_object() );
	}

	/**
	 * @param int|string $post_id
	 * @param \WP_Post   $post
	 */
	public function queue_save_post_actions( $post_id, $post ) {
		$this->update_last_editor_mode( (int) $post_id );
		$this->save_post_queue[ $post_id ] = $post;
	}

	/**
	 * @return \WP_Post[]
	 */
	public function get_save_post_queue() {
		return $this->save_post_queue;
	}

	/** @param int $post_id */
	private function update_last_editor_mode( $post_id ) {
		if ( ! $this->is_translation( $post_id ) ) {
			return;
		}

		if ( $this->is_editing_translation_with_native_editor( $post_id ) ) {
			WPML_PB_Last_Translation_Edit_Mode::set_native_editor( $post_id );
		} else {
			WPML_PB_Last_Translation_Edit_Mode::set_translation_editor( $post_id );
		}
	}

	/**
	 * Due to the "translation auto-update" feature, an original update
	 * can also trigger an update on the translations.
	 * We need to make sure the globally edited post is matching with
	 * the local one.
	 *
	 * @param int $translatedPostId
	 *
	 * @return bool
	 */
	private function is_editing_translation_with_native_editor( $translatedPostId ) {
		// $getPOST :: string -> mixed
		$getPOST = Obj::prop( Fns::__, $_POST );

		$isTranslationWithNativeEditor = 'editpost' === $getPOST( 'action' )
		                                 && (int) $getPOST( 'ID' ) === $translatedPostId;

		/**
		 * This filter allows to override the result if a translation
		 * is edited with a native editor, but not the WP one.
		 *
		 * @since WPML 4.5.0
		 *
		 * @param bool $isTranslationWithNativeEditor
		 * @param int  $translatedPostId
		 */
		return apply_filters( 'wpml_pb_is_editing_translation_with_native_editor', $isTranslationWithNativeEditor, $translatedPostId );
	}

	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	private function is_translation( $postId ) {
		return (bool) $this->factory->get_post_element( $postId )->get_source_language_code();
	}

	/**
	 * @param WP_Post $post
	 */
	public function register_all_strings_for_translation( $post ) {
		if ( $post instanceof \WP_Post && $this->is_post_status_ok( $post ) && $this->is_original_post( $post ) ) {
			$this->is_registering_string = true;
			$this->with_strategies( invoke( 'register_strings' )->with( $post ) );
			$this->is_registering_string = false;
		}
	}

	/**
	 * @param \WP_Post|\stdClass $post
	 *
	 * @return bool
	 */
	private function is_original_post( $post ) {
		return $post->ID == $this->sitepress->get_original_element_id( $post->ID, 'post_' . $post->post_type );
	}

	/**
	 * @param \WP_Post|\stdClass $post
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
		add_action( 'pre_post_update', array( $this, 'migrate_location' ) );
		add_action( 'save_post', array( $this, 'queue_save_post_actions' ), PHP_INT_MAX, 2 );
		add_action( 'wpml_pb_resave_post_translation', array( $this, 'resave_post_translation_in_shutdown' ), 10, 1 );
		add_action( 'icl_st_add_string_translation', array( $this, 'new_translation' ), 10, 1 );
		add_action( 'wpml_pb_finished_adding_string_translations', array( $this, 'process_pb_content_with_hidden_strings_only' ), 9, 2 );
		add_action( 'wpml_pb_finished_adding_string_translations', array( $this, 'save_translations_to_post' ), 10 );
		add_action( 'wpml_pro_translation_completed', array( $this, 'cleanup_strings_after_translation_completed' ), 10, 3 );

		add_filter( 'wpml_tm_translation_job_data', array( $this, 'rescan' ), 9, 2 );

		add_action( 'wpml_pb_register_all_strings_for_translation', [ $this, 'register_all_strings_for_translation' ] );
		add_filter( 'wpml_pb_register_strings_in_content', [ $this, 'register_strings_in_content' ], 10, 3 );
		add_filter( 'wpml_pb_update_translations_in_content', [ $this, 'update_translations_in_content'], 10, 2 );

		add_action( 'wpml_start_GB_register_strings', [ $this, 'initialize_string_clean_up' ], 10, 1 );
		add_action( 'wpml_end_GB_register_strings', [ $this, 'clean_up_strings' ], 10, 1 );
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

	public function new_translation( $translated_string_id ) {
		if ( ! $this->is_registering_string ) {
			$this->with_strategies( function( $strategy ) use ( $translated_string_id ) {
				$this->factory->get_string_translations( $strategy )->new_translation( $translated_string_id );
			} );
			$this->new_translations_recieved = true;
		}
	}

	/**
	 * @param callable $callable
	 */
	private function with_strategies( callable $callable ) {
		Fns::each( $callable, $this->strategies );
	}

	/**
	 * When a Page Builder content has only a "LINK" string, it's won't be part
	 * of the translation job as it's automatically converted.
	 * We need to add the package to the update list (by strategies).
	 *
	 * @param int $new_post_id
	 * @param int $original_doc_id
	 */
	public function process_pb_content_with_hidden_strings_only( $new_post_id, $original_doc_id ) {
		if (
			! did_action( 'wpml_add_string_translation' )
			&& apply_filters( 'wpml_pb_is_page_builder_page', false, get_post( $new_post_id ) )
		) {
			$targetLang = $this->sitepress->get_language_for_element( $new_post_id, 'post_' . get_post_type( $new_post_id ) );

			$addPackageToUpdateList = function( WPML_Package $package ) use ( $targetLang ) {
				$this->with_strategies( function( IWPML_PB_Strategy $strategy ) use ( $package, $targetLang ) {
					$this->factory
						->get_string_translations( $strategy )
						->add_package_to_update_list( $package, $targetLang );
				} );
			};

			$this->new_translations_recieved = wpml_collect( apply_filters( 'wpml_st_get_post_string_packages', [], $original_doc_id ) )
				->each( $addPackageToUpdateList )
				->isNotEmpty();
		}
	}

	public function save_translations_to_post() {
		if ( $this->new_translations_recieved ) {
			$this->with_strategies( function( IWPML_PB_Strategy $strategy ) {
				$this->factory->get_string_translations( $strategy )->save_translations_to_post();
			} );
			$this->new_translations_recieved = false;
		}
	}

	/**
	 * @param string $content
	 * @param string $lang
	 *
	 * @return string
	 */
	public function update_translations_in_content( $content, $lang ) {
		$this->with_strategies( function ( IWPML_PB_Strategy $strategy ) use ( &$content, $lang ) {
			$content = $this->factory->get_string_translations( $strategy )->update_translations_in_content( $content, $lang );
		} );

		return $content;
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
	 */
	public function migrate_location( $post_id ) {
		if ( $this->post_has_strings( $post_id ) && ! $this->is_migrate_location_done( $post_id ) ) {
			$wpdb = $this->sitepress->get_wpdb();
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
			if ( $this->is_post_status_ok( $post ) && $this->is_original_post( $post ) ) {
				$this->with_strategies( invoke( 'migrate_location' )->with( $post_id, $post->post_content ) );
			}

			$this->mark_migrate_location_done( $post_id );
		}
	}

	/**
	 * @param bool $registered
	 * @param string|int $post_id
	 * @param string $content
	 *
	 * @return bool
	 */
	public function register_strings_in_content( $registered, $post_id, $content ) {
		foreach ( $this->strategies as $strategy ) {
			$registered = $strategy->register_strings_in_content( $post_id, $content, $this->stringCleanUp[ $post_id ] ) || $registered;
		}
		return $registered;
	}

	public function get_factory() {
		return $this->factory;
	}

	public function initialize_string_clean_up( WP_Post $post ) {
		$shortcodeStrategy = make( WPML_PB_Shortcode_Strategy::class );
		$shortcodeStrategy->set_factory( $this->factory );
		$this->stringCleanUp[ $post->ID ] = new StringCleanUp( $post->ID, $shortcodeStrategy );
	}

	public function clean_up_strings( WP_Post $post ) {
		$this->stringCleanUp[ $post->ID ]->cleanUp();
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function post_has_strings( $post_id ) {
		$wpdb = $this->sitepress->get_wpdb();
		$string_packages_table = $wpdb->prefix . 'icl_string_packages';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$string_packages_table'" ) !== $string_packages_table ) {
			return false;
		}

		$string_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$string_packages_table} WHERE post_id = %d", $post_id) );
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

	/**
	 * @param WP_Post $post
	 */
	public function translate_media( $post ) {
		if ( $this->is_post_status_ok( $post ) && ! $this->is_original_post( $post ) ) {

			foreach ( $this->get_media_updaters() as $updater ) {
				$updater->translate( $post );
			}
		}
	}

	/** @return IWPML_PB_Media_Update[] $media_updaters */
	private function get_media_updaters() {
		if ( ! $this->media_updaters ) {
			$this->media_updaters = apply_filters( 'wpml_pb_get_media_updaters', array() );
		}

		return $this->media_updaters;
	}
}
