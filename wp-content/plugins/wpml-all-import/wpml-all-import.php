<?php

/*
	Plugin Name: WPML All Import
	Plugin URI: http://wpml.org
	Description: Import multilingual content to WordPress. Requires WP All Import & WPML.
	Version: 2.1.1
	Author: OnTheGoSystems
	Author URI: http://www.onthegosystems.com/
*/

require_once "includes/rapid-addon.php";

if ( ! class_exists('WPAI_WPML') )
{

	/**
	 * Plugin root dir with forward slashes as directory separator regardless of actuall DIRECTORY_SEPARATOR value
	 * @var string
	 */
	define('WPAI_WPML_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
	/**
	 * Plugin root url for referencing static content
	 * @var string
	 */
	define('WPAI_WPML_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));

	/**
	 * Main plugin file
	 *
	 * @singletone
	 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
	 */
	final class WPAI_WPML
	{

		/**
		 * Singletone instance
		 * @var WPAI_WPML
		 */
		protected static $instance;
		/**
		 * Plugin root dir
		 * @var string
		 */
		const ROOT_DIR = WPAI_WPML_ROOT_DIR;
		/**
		 * Plugin root URL
		 * @var string
		 */
		const ROOT_URL = WPAI_WPML_ROOT_URL;
		/**
		 * Return singletone instance
		 * @return WPAI_WPML
		 */
		static public function getInstance()
		{
			if (self::$instance == NULL)
			{
				self::$instance = new self();
			}
			return self::$instance;
		}

		public $wpml_addon;
		public $wpml;

		private $current_language;
		private $default_language;
		private $language_code;

		private function __construct()
		{
			$this->wpml_addon = new RapidAddon('WPML All Import', 'wpml_addon');

			$this->wpml_addon->set_import_function( array( &$this, 'import') );
			$this->wpml_addon->set_post_saved_function( array( &$this, 'saved') );

			$conditions = array(
				'plugins' => array(
					'sitepress-multilingual-cms/sitepress.php'
				)
			);

			$this->wpml_addon->admin_notice(
				$this->wpml_addon->name . ' requires WP All Import <a href="http://www.wpallimport.com/" target="_blank">Pro</a> or <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a> and <a href="https://wpml.org/" target="_blank">WPML Multilingual CMS.</a>',
				$conditions
			);

			$this->wpml_addon->run($conditions);

			if ( $this->wpml_addon->is_active_addon('') && apply_filters( 'wpml_setting', false, 'setup_complete' ) )
			{
				global $sitepress;

				$this->wpml = $sitepress;

				$this->default_language = $this->wpml->get_default_language();

				$this->init_addon_fields();

				add_action( 'admin_init', 			   array( &$this, 'enqueue_stylesheets' ) );
				add_action( 'pmxi_before_post_import', array( &$this, 'before_post_import' ), 10, 1 );
				add_action( 'pmxi_after_post_import',  array( &$this, 'after_post_import' ),  10, 1 );
				add_action( 'pmxi_saved_post',         array( &$this, 'saved_post' ), 10, 1 );
				add_action( 'pmxi_delete_post',        array( &$this, 'delete_post' ), 10, 1 );
				add_filter( 'pmxi_import_name', 	   array( &$this, 'import_name'), 10, 2 );
                add_filter( 'wp_all_import_term_exists', array( &$this, 'wp_all_import_term_exists' ), 10, 4);

				add_filter( 'wp_all_import_get_existing_image', array( $this, 'get_existing_image_filter' ) );

				add_filter( 'wp_all_import_get_image_from_gallery', array( $this, 'get_image_from_gallery_filter' ) );

				//add_action( 'pmxi_attachment_uploaded', array( $this, 'pmxi_attachment_uploaded_action' ), 10, 3 );

				add_action( 'wp_all_import_add_attachment', array( $this, 'add_attachment_action' ) );
			}

		}

		/**
		*
		*	Init addons' view
		*
		*/
		private function init_addon_fields()
		{
			global $wpdb;

			$table = PMXI_Plugin::getInstance()->getTablePrefix() . 'imports';

			$imports = $wpdb->get_results("SELECT * FROM $table WHERE `parent_import_id` = 0", ARRAY_A);

			if ( empty($imports) )
			{
				$this->wpml_addon->add_text("This is your first import. Default language will be choosen automatically ( " . $this->get_flag_html($this->default_language) . $this->wpml->get_display_language_name($this->default_language, 'en') ." ).");
			}

			$langs = $this->wpml->get_active_languages();

			if ( ! empty($langs) )
			{
				// prepare active languages list
				$language_list = array();

				foreach ($langs as $code => $langInfo)
				{
					$language_list[$code] = $this->get_flag_html($code) . $langInfo['display_name'];

					if ($code == $this->default_language) $language_list[$code] .= ' ( <strong>default</strong> )';
				}

				$this->wpml_addon->add_field(
					'lng',
					'Content Language',
					'radio',
					$language_list
				);

				if ( ! empty($imports) )
				{
					// prepare active imports list
					$import_list = array();

					foreach ( $imports as $import )
					{
						if ( ! empty($_GET['id']) and $_GET['id'] == $import['id'] ) continue;
						$import_options = unserialize($import['options']);
						$import_list[$import['id']]  = '[ ID: ' . $import['id'] . ' ] ' . ( ( ! empty($import_options['wpml_addon']['lng'])) ? $this->get_flag_html($import_options['wpml_addon']['lng']) : '' ) . (( ! empty($import['friendly_name']) ) ? $import['friendly_name'] : $import['name']);
					}

					$this->wpml_addon->add_options(
						null,
						'Automatic Record Matching to Translate',
						array(
							$this->wpml_addon->add_field(
								'matching_logic',
								'Records matching logic',
								'radio',
								array(
									'' => '<strong>Import data in main language (' . $this->wpml->get_display_language_name($this->default_language, 'en') . ')</strong>',
									'auto' => array(
										'Define parent import',
										$this->wpml_addon->add_field(
											'import',
											'Import to translate',
											'radio',
											$import_list
										),
										$this->wpml_addon->add_field('unique_key', 'Unique Identifier', 'text', null, 'To inform WPML that this new post is translation of another post put the same unique identifier like you did for post in main language.')
									)
								)
							)
						)
					);

					// Aditional Options [ TODO: taxonomies options, media options, etc. ]

					// $this->wpml_addon->add_options(
					// 	null,
					// 	'Advanced Settings',
					// 	array(
					// 		$this->wpml_addon->add_field('translate_taxes', 'Translate taxonomies', 'radio', array('' => 'No', '1' => 'Yes'))
					// 	)
					// );
				}
				return;
			}

			$this->wpml_addon->add_text('Please set up site languages before using \'WP All Import - WPML add-on.\'');
		}

		public function get_flag_html( $code )
		{
			return "<img width='18' height='12' src='" . $this->wpml->get_flag_url($code) . "' style='position:relative; top: 2px;'/> ";
		}

		// [filters]

			/**
			*
			*	Import's friendly name on manage imports screen
			*
			*/
			public function import_name($friendly_name, $import_id)
			{
				$import = new PMXI_Import_Record();
				$import->getById($import_id);
				if ( ! $import->isEmpty())
				{
					if ( ! empty($import->options['wpml_addon']['lng']) )
					{
						$friendly_name = $this->get_flag_html($import->options['wpml_addon']['lng']) . $friendly_name;
					}
				}
				return $friendly_name;
			}

		// [\filters]

		// [actions]

			public function enqueue_stylesheets()
			{
				wp_enqueue_style( 'wp-all-import-wpml-add-on', self::ROOT_URL . '/static/css/admin.css', false, 0.1, 'all' );
			}

			/**
			*
			*	Fires before inserting/updating post [do_action - 'pmxi_before_post_import']
			*
			*/
			public function before_post_import( $import_id )
			{
				$this->current_language = apply_filters('wpml_current_language', null);

				if ( empty($this->language_code) )
				{
					$import = new PMXI_Import_Record();
					$import->getById( $import_id );
					if ( ! $import->isEmpty() )
					{
						$this->language_code = (empty($import->options['wpml_addon']['lng'])) ? $this->default_language : $import->options['wpml_addon']['lng'];
					}
				}
				// switch language to language in which post should be created
				do_action( 'wpml_switch_language', $this->language_code );
			}

			/**
			*
			*	Fires after inserting/updating post [do_action - 'pmxi_after_post_import']
			*
			*/
			public function after_post_import( $import_id )
			{
				// switch language back to main
				do_action( 'wpml_switch_language', $this->current_language );
			}

			/**
			*
			*	Fires after saving post [do_action - 'pmxi_saved_post']
			*
			*/
			public function saved_post( $post_id )
			{
				/*
				set postmeta '_wcml_duplicate_of_variation' for translated product variations
				 */
				$post_type = get_post_type($post_id);
				$wpml_post_type = "post_".$post_type;
				if ('product_variation' == $post_type) {
					$post_language = apply_filters( 'wpml_element_language_code', null,
						array('element_id' => $post_id,
							  'element_type' => $post_type));
					if ($this->default_language != $post_language) {
						$post_meta_key = '_wcml_duplicate_of_variation';
						$current_post_meta = get_post_meta($post_id, $post_meta_key, true);
						if (!is_numeric($current_post_meta)) {
							$trid = apply_filters( 'wpml_element_trid', null, $post_id, $wpml_post_type);
							if (is_numeric($trid)) {
								$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $wpml_post_type);
								if (is_array($translations) && isset($translations[$this->default_language])) {
									$original_post_object = $translations[$this->default_language];
									if (isset($original_post_object->element_id)) {
										$original_post_id = $original_post_object->element_id;
										add_post_meta( $post_id, $post_meta_key, $original_post_id );
									}
								}
							} else {
								throw new Exception("No trid for product variation in secondary language, imposssible to set _wcml_duplicate_of_variation");
							}
						}
					}
				}
			}

			/**
			*
			*	Fires before deleting post [do_action - 'pmxi_delete_post']
			*
			*/
			public function delete_post( $post_id )
			{
				global $wpdb;
				$post_type = 'post_' . get_post_type($post_id);
				$delete_args = array($post_id, $post_type);
				$delete_sql = "DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s";
				$delete_sql_prepared = $wpdb->prepare($delete_sql, $delete_args);
				$wpdb->query( $delete_sql_prepared );
				$this->wpml->get_translations_cache()->clear();
			}

		//[\actions]


		/**
		*
		*	Fires after wp_insert_post/wp_update_post
		*
		*/
		public function import($post_id, $data, $import, $articleData, $logger)
		{
			// return if this is a basic post ( not a translation )
			if ( empty($import['options']['wpml_addon']['matching_logic']) or empty($import['options']['wpml_addon']['import']) ) return;

			// search for post to translate
			$parentImport = new PMXI_Import_Record();
			$parentImport->getById($import['options']['wpml_addon']['import']);

			if ( ! $parentImport->isEmpty())
			{
				// post is that must be translated
				$parent_post_id = false;

				$postRecord = new PMXI_Post_Record();
				$postRecord->clear();
				$postRecord->getBy(array(
					'unique_key' => $data['unique_key'],
					'import_id'  => $import['options']['wpml_addon']['import']
				));
				if ( ! $postRecord->isEmpty() )
					$parent_post_id = $postRecord->post_id;

				if ($parent_post_id)
				{

                    if (isset($articleData['post_type']) && "taxonomies" == $articleData['post_type'] && isset($articleData['taxonomy'])) {
                        $post_type = 'tax_' . $articleData['taxonomy'];

	                    // update untranslated taxonomy count
	                    global $woocommerce_wpml;
	                    if ( isset ( $woocommerce_wpml ) ) {
		                    $woocommerce_wpml->terms->update_terms_translated_status( $articleData['taxonomy'] );
	                    }
                    } else {
                        $post_type = 'post_' . get_post_type($post_id);
                    }


                    $trid = $this->wpml->get_element_trid($parent_post_id, $post_type);

					if ( $trid )
					{
						global $wpdb;

						// sync translation slug
						$parent_post = get_post($parent_post_id);

						if ( ! empty($parent_post) and $parent_post->post_title == $articleData['post_title'])
						{
							$wpdb->update( $wpdb->posts, array( 'post_name' => $parent_post->post_name ), array( 'ID' => $post_id ) );
						}

						// create a translation
						$tid = $this->wpml->set_element_language_details($post_id, $post_type, $trid, $import->options['wpml_addon']['lng'], $parentImport->options['wpml_addon']['lng'], false);

						if (is_wp_error($tid))
						{
							$logger and call_user_func($logger, __('<b>ERROR</b>', 'wpml-all-import') . ': ' . $tid->get_error_message());
						}
						else
						{
							$logger and call_user_func($logger, sprintf(__('- Created `%s` translation for `%s`', 'wpml-all-import'), $import->options['wpml_addon']['lng'], $parent_post->post_title));
						}
					}
				}
			}
		}

		/**
		*
		*	Fires after all data has been imported, e.q. images, taxonomies, custom fields etc.
		*
		*/
		public function saved( $post_id, $import, $logger )
		{
			// TODO: here we can add translations for taxonomies and ather stuff
			$logger and call_user_func($logger, __('<b>TEST</b>', 'wpml-all-import'));
		}
                
                public function wp_all_import_term_exists( $term_a, $taxonomy, $term, $parent) {
                    
                    if ( ! $this->wpml->is_translated_taxonomy( $taxonomy ) ) return $term_a;

                    if ( ! empty($term_a) and ! is_wp_error($term_a) )
                    {                    	
                    	$term_id = apply_filters('wpml_object_id', $term_a['term_id'], $taxonomy, true, $this->language_code);
                    	$term_a  = get_term_by('id', $term_id, $taxonomy, ARRAY_A);
                    }
                    return $term_a;
                }

		public function get_existing_image_filter( $attachment_id ) {

			if ( $this->language_code ) {
				$attachment_id = apply_filters( 'wpml_object_id', $attachment_id, 'attachment', true, $this->language_code );
			}

			return $attachment_id;
		}

		public function get_image_from_gallery_filter( $attachment ) {

			if ( $this->language_code && $attachment ) {

				global $wpdb;

				$localized_attachment_id = apply_filters( 'wpml_object_id', $attachment->ID, 'attachment', false, $this->language_code );

				if ( $localized_attachment_id ) {

					$localized_attachment = $wpdb->get_row(
						$wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s AND post_mime_type LIKE %s;", $localized_attachment_id, 'attachment', 'image%' )
					);

					if ( $localized_attachment ) {
						$attachment = $localized_attachment;
					}
				}
			}

			return $attachment;
		}

		public function add_attachment_action( $attachment_id ) {

			$translation_id = apply_filters( 'wpml_element_trid', false, $attachment_id, 'post_attachment' );

			if ( $this->language_code && ! $translation_id ) {

				do_action( 'wpml_set_element_language_details', array(
					'element_id'    => $attachment_id,
					'element_type'  => 'post_attachment',
					'trid'          => false,
					'language_code' => $this->language_code,
				) );

				$factory         = new WPML_Media_Attachments_Duplication_Factory();
				$media_duplicate = $factory->create();
				$media_duplicate->save_attachment_actions( $attachment_id );
			}

		}
            
	}

	WPAI_WPML::getInstance();
}
