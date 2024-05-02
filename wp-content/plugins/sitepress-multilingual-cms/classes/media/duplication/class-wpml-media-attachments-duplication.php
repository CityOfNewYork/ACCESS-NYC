<?php

use WPML\FP\Obj;
use WPML\LIB\WP\Nonce;
use WPML\LIB\WP\User;
use WPML\Media\Option;

class WPML_Media_Attachments_Duplication {

	const WPML_MEDIA_PROCESSED_META_KEY = 'wpml_media_processed';

	/** @var  WPML_Model_Attachments */
	private $attachments_model;

	/** @var SitePress */
	private $sitepress;

	private $wpdb;

	private $language_resolution;

	private $original_thumbnail_ids = array();

	/**
	 * WPML_Media_Attachments_Duplication constructor.
	 *
	 * @param SitePress              $sitepress
	 * @param WPML_Model_Attachments $attachments_model
	 *
	 * @internal param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( SitePress $sitepress, WPML_Model_Attachments $attachments_model, wpdb $wpdb, WPML_Language_Resolution $language_resolution ) {
		$this->sitepress           = $sitepress;
		$this->attachments_model   = $attachments_model;
		$this->wpdb                = $wpdb;
		$this->language_resolution = $language_resolution;
	}

	public function add_hooks() {
		// do not run this when user is importing posts in Tools > Import
		if ( ! isset( $_GET['import'] ) || $_GET['import'] !== 'wordpress' ) {
			add_action( 'add_attachment', array( $this, 'save_attachment_actions' ) );
			add_action( 'add_attachment', array( $this, 'save_translated_attachments' ) );
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 10, 2 );
		}

		$active_languages = $this->language_resolution->get_active_language_codes();

		if ( $this->is_admin_or_xmlrpc() && ! $this->is_uploading_plugin_or_theme() && 1 < count( $active_languages ) ) {
			add_action( 'edit_attachment', array( $this, 'save_attachment_actions' ) );
			add_action( 'icl_make_duplicate', array( $this, 'make_duplicate' ), 10, 4 );
		}

		$this->add_postmeta_hooks();

		add_action( 'save_post', array( $this, 'save_post_actions' ), 100, 2 );
		add_action( 'wpml_pro_translation_completed', array( $this, 'sync_on_translation_complete' ), 10, 3 );

		add_action( 'wp_ajax_wpml_media_set_initial_language', array( $this, 'batch_set_initial_language' ) );
		add_action( 'wp_ajax_wpml_media_translate_media', array( $this, 'ajax_batch_translate_media' ), 10, 0 );
		add_action( 'wp_ajax_wpml_media_duplicate_media', array( $this, 'ajax_batch_duplicate_media' ), 10, 0 );
		add_action( 'wp_ajax_wpml_media_duplicate_featured_images', array( $this, 'ajax_batch_duplicate_featured_images' ), 10, 0 );

		add_action( 'wp_ajax_wpml_media_mark_processed', array( $this, 'ajax_batch_mark_processed' ), 10, 0 );
		add_action( 'wp_ajax_wpml_media_scan_prepare', array( $this, 'ajax_batch_scan_prepare' ), 10, 0 );

		add_action( 'wp_ajax_wpml_media_set_content_prepare', array( $this, 'set_content_defaults_prepare' ) );
		add_action( 'wpml_loaded', array( $this, 'add_settings_hooks' ) );
	}

	public function add_settings_hooks() {
		if ( User::getCurrent() && ( User::canManageTranslations() || User::hasCap( 'wpml_manage_media_translation' ) )
		) {
			add_action('wp_ajax_wpml_media_set_content_defaults', array($this, 'wpml_media_set_content_defaults') );
		}
	}

	private function add_postmeta_hooks() {
		add_action( 'update_postmeta', [ $this, 'record_original_thumbnail_ids_and_sync' ], 10, 4 );
		add_action( 'delete_post_meta', [ $this, 'record_original_thumbnail_ids_and_sync' ], 10, 4 );
	}

	private function withPostMetaFiltersDisabled( callable $callback ) {
		$filter = [ $this, 'record_original_thumbnail_ids_and_sync' ];

		$shouldRestoreFilters = remove_action( 'update_postmeta', $filter, 10 )
								&& remove_action( 'delete_post_meta', $filter, 10 );

		$callback();

		if ( $shouldRestoreFilters ) {
			$this->add_postmeta_hooks();
		}
	}

	private function is_admin_or_xmlrpc() {
		$is_admin  = is_admin();
		$is_xmlrpc = defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
		return $is_admin || $is_xmlrpc;
	}

	public function save_attachment_actions( $post_id ) {
		if ( $this->is_uploading_media_on_wpml_media_screen() ) {
			return;
		}

		if ( $this->is_uploading_plugin_or_theme() && get_post_type( $post_id ) == 'attachment' ) {
			return;
		}

		$media_language = $this->sitepress->get_language_for_element( $post_id, 'post_attachment' );
		$trid           = false;
		if ( ! empty( $media_language ) ) {
			$trid = $this->sitepress->get_element_trid( $post_id, 'post_attachment' );
		}
		if ( empty( $media_language ) ) {
			$parent_post_sql      = "SELECT p2.ID, p2.post_type FROM {$this->wpdb->posts} p1 JOIN {$this->wpdb->posts} p2 ON p1.post_parent = p2.ID WHERE p1.ID=%d";
			$parent_post_prepared = $this->wpdb->prepare( $parent_post_sql, array( $post_id ) );
			/** @var \stdClass $parent_post */
			$parent_post = $this->wpdb->get_row( $parent_post_prepared );

			if ( $parent_post ) {
				$media_language = $this->sitepress->get_language_for_element( $parent_post->ID, 'post_' . $parent_post->post_type );
			}

			if ( empty( $media_language ) ) {
				$media_language = $this->sitepress->get_admin_language_cookie();
			}
			if ( empty( $media_language ) ) {
				$media_language = $this->sitepress->get_default_language();
			}
		}
		if ( ! empty( $media_language ) ) {
			$this->sitepress->set_element_language_details( $post_id, 'post_attachment', $trid, $media_language );

			$this->save_translated_attachments( $post_id );
			$this->update_attachment_metadata( $post_id );
		}
	}

	private function is_uploading_media_on_wpml_media_screen() {
		return isset( $_POST['action'] ) && 'wpml_media_save_translation' === $_POST['action'];
	}

	public function wp_generate_attachment_metadata( $metadata, $attachment_id ) {
		if ( $this->is_uploading_media_on_wpml_media_screen() ) {
			return $metadata;
		}

		$this->synchronize_attachment_metadata( $metadata, $attachment_id );

		return $metadata;
	}

	private function update_attachment_metadata( $source_attachment_id ) {
		$original_element_id = $this->sitepress->get_original_element_id( $source_attachment_id, 'post_attachment', false, false, true );
		if ( $original_element_id ) {
			$metadata = wp_get_attachment_metadata( $original_element_id );
			$this->synchronize_attachment_metadata( $metadata, $original_element_id );
		}
	}

	private function synchronize_attachment_metadata( $metadata, $attachment_id ) {
		// Update _wp_attachment_metadata to all translations (excluding the current one)
		$trid = $this->sitepress->get_element_trid( $attachment_id, 'post_attachment' );

		if ( $trid ) {
			$translations = $this->sitepress->get_element_translations( $trid, 'post_attachment', true, true, true );
			foreach ( $translations as $translation ) {
				if ( $translation->element_id != $attachment_id ) {
					$this->update_attachment_texts( $translation );

					/**
					 * Action to allow synchronise additional attachment data with translation.
					 *
					 * @param int    $attachment_id The ID of original attachment.
					 * @param object $translation   The translated attachment.
					 */
					do_action( 'wpml_after_update_attachment_texts', $attachment_id, $translation );

					$attachment_meta_data = get_post_meta( $translation->element_id, '_wp_attachment_metadata' );
					if ( isset( $attachment_meta_data[0]['file'] ) ) {
						continue;
					}

					update_post_meta( $translation->element_id, '_wp_attachment_metadata', $metadata );
					$mime_type = get_post_mime_type( $attachment_id );
					if ( $mime_type ) {
						$this->wpdb->update( $this->wpdb->posts, array( 'post_mime_type' => $mime_type ), array( 'ID' => $translation->element_id ) );
					}
				}
			}
		}
	}

	private function update_attachment_texts( $translation ) {
		if ( ! isset( $_POST['changes'] ) ) {
			return;
		}

		$changes = array( 'ID' => $translation->element_id );

		foreach ( $_POST['changes'] as $key => $value ) {
			switch ( $key ) {
				case 'caption':
					$post = get_post( $translation->element_id );
					if ( ! $post->post_excerpt ) {
						$changes['post_excerpt'] = $value;
					}

					break;

				case 'description':
					$translated_attachment = get_post( $translation->element_id );
					if ( ! $translated_attachment->post_content ) {
						$changes['post_content'] = $value;
					}

					break;

				case 'alt':
					if ( ! get_post_meta( $translation->element_id, '_wp_attachment_image_alt', true ) ) {
						update_post_meta( $translation->element_id, '_wp_attachment_image_alt', $value );
					}

					break;
			}
		}

		remove_action( 'edit_attachment', array( $this, 'save_attachment_actions' ) );
		wp_update_post( $changes );
		add_action( 'edit_attachment', array( $this, 'save_attachment_actions' ) );
	}

	public function save_translated_attachments( $post_id ) {
		if ( $this->is_uploading_plugin_or_theme() && get_post_type( $post_id ) == 'attachment' ) {
			return;
		}

		$language_details = $this->sitepress->get_element_language_details( $post_id, 'post_attachment' );
		if ( isset( $language_details->language_code ) ) {
			$this->translate_attachments( $post_id, $language_details->language_code );
		}
	}

	private function translate_attachments( $attachment_id, $source_language, $override_always_translate_media = false ) {
		if ( ! $source_language ) {
			return;
		}

		if ( $override_always_translate_media || Obj::prop( 'always_translate_media', Option::getNewContentSettings() ) ) {

			/** @var SitePress $sitepress */
			global $sitepress;

			$original_attachment_id = false;
			$trid                   = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
			if ( $trid ) {
				$translations                   = $sitepress->get_element_translations( $trid, 'post_attachment', true, true );
				$translated_languages           = [];
				$default_language               = $sitepress->get_default_language();
				$default_language_attachment_id = false;
				foreach ( $translations as $translation ) {
					// Get the default language attachment ID
					if ( $translation->original ) {
						$original_attachment_id = $translation->element_id;
					}
					if ( $translation->language_code == $default_language ) {
						$default_language_attachment_id = $translation->element_id;
					}
					// Store already translated versions
					$translated_languages[] = $translation->language_code;
				}
				// Original attachment is missing
				if ( ! $original_attachment_id ) {
					$attachment = get_post( $attachment_id );
					if ( ! $default_language_attachment_id ) {
						$this->create_duplicate_attachment( $attachment_id, $attachment->post_parent, $default_language );
					} else {
						$sitepress->set_element_language_details( $default_language_attachment_id, 'post_attachment', $trid, $default_language, null );
					}
					// Start over
					$this->translate_attachments( $attachment->ID, $source_language );
				} else {
					// Original attachment is present
					$original = get_post( $original_attachment_id );
					$codes    = array_keys( $sitepress->get_active_languages() );
					foreach ( $codes as $code ) {
						// If translation is not present, create it
						if ( ! in_array( $code, $translated_languages ) ) {
							$this->create_duplicate_attachment( $attachment_id, $original->post_parent, $code );
						}
					}
				}
			}
		}

	}

	private function is_uploading_plugin_or_theme() {
		global $action;

		return isset( $action ) && ( $action == 'upload-plugin' || $action == 'upload-theme' );
	}

	public function make_duplicate( $master_post_id, $target_lang, $post_array, $target_post_id ) {
		$translated_attachment_id = false;
		// Get Master Post attachments
		$master_post_attachment_ids_prepared = $this->wpdb->prepare(
			"SELECT ID FROM {$this->wpdb->posts} WHERE post_parent = %d AND post_type = %s",
			array(
				$master_post_id,
				'attachment',
			)
		);
		$master_post_attachment_ids          = $this->wpdb->get_col( $master_post_attachment_ids_prepared );

		if ( $master_post_attachment_ids ) {
			foreach ( $master_post_attachment_ids as $master_post_attachment_id ) {

				$attachment_trid = $this->sitepress->get_element_trid( $master_post_attachment_id, 'post_attachment' );

				if ( $attachment_trid ) {
					// Get attachment translation
					$attachment_translations = $this->sitepress->get_element_translations( $attachment_trid, 'post_attachment' );

					foreach ( $attachment_translations as $attachment_translation ) {
						if ( $attachment_translation->language_code == $target_lang ) {
							$translated_attachment_id = $attachment_translation->element_id;
							break;
						}
					}

					if ( ! $translated_attachment_id ) {
						$translated_attachment_id = $this->create_duplicate_attachment( $master_post_attachment_id, wp_get_post_parent_id( $master_post_id ), $target_lang );
					}

					if ( $translated_attachment_id ) {
						// Set the parent post, if not already set
						$translated_attachment = get_post( $translated_attachment_id );
						if ( $translated_attachment && ! $translated_attachment->post_parent ) {
							$prepared_query = $this->wpdb->prepare(
								"UPDATE {$this->wpdb->posts} SET post_parent=%d WHERE ID=%d",
								array(
									$target_post_id,
									$translated_attachment_id,
								)
							);
							$this->wpdb->query( $prepared_query );
						}
					}
				}
			}
		}

		// Duplicate the featured image.

		$thumbnail_id = get_post_meta( $master_post_id, '_thumbnail_id', true );

		if ( $thumbnail_id ) {

			$thumbnail_trid = $this->sitepress->get_element_trid( $thumbnail_id, 'post_attachment' );

			if ( $thumbnail_trid ) {
				// translation doesn't have a featured image
				$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $target_lang );
				if ( $t_thumbnail_id == null ) {
					$dup_att_id     = $this->create_duplicate_attachment( $thumbnail_id, $target_post_id, $target_lang );
					$t_thumbnail_id = $dup_att_id;
				}

				if ( $t_thumbnail_id != null ) {
					update_post_meta( $target_post_id, '_thumbnail_id', $t_thumbnail_id );
				}
			}
		}

		return $translated_attachment_id;
	}

	/**
	 * @param int            $attachment_id
	 * @param int|false|null $parent_id
	 * @param string         $target_language
	 *
	 * @return int|null
	 */
	public function create_duplicate_attachment( $attachment_id, $parent_id, $target_language ) {
		try {
			$attachment_post = get_post( $attachment_id );
			if ( ! $attachment_post ) {
				throw new WPML_Media_Exception( sprintf( 'Post with id %d does not exist', $attachment_id ) );
			}

			$trid = $this->sitepress->get_element_trid( $attachment_id, WPML_Model_Attachments::ATTACHMENT_TYPE );
			if ( ! $trid ) {
				throw new WPML_Media_Exception( sprintf( 'Attachment with id %s does not contain language information', $attachment_id ) );
			}

			$duplicated_attachment    = $this->attachments_model->find_duplicated_attachment( $trid, $target_language );
			$duplicated_attachment_id = null;
			if ( null !== $duplicated_attachment ) {
				$duplicated_attachment_id = $duplicated_attachment->ID;
			}
			$translated_parent_id = $this->attachments_model->fetch_translated_parent_id( $duplicated_attachment, $parent_id, $target_language );

			if ( null !== $duplicated_attachment ) {
				if ( (int) $duplicated_attachment->post_parent !== (int) $translated_parent_id ) {
					$this->attachments_model->update_parent_id_in_existing_attachment( $translated_parent_id, $duplicated_attachment );
				}
			} else {
				$duplicated_attachment_id = $this->attachments_model->duplicate_attachment( $attachment_id, $target_language, $translated_parent_id, $trid );
			}

			$this->attachments_model->duplicate_post_meta_data( $attachment_id, $duplicated_attachment_id );

			/**
			 * Fires when attachment is duplicated
			 *
			 * @since 4.1.0
			 *
			 * @param int $attachment_id            The ID of the source/original attachment.
			 * @param int $duplicated_attachment_id The ID of the duplicated attachment.
			 */
			do_action( 'wpml_after_duplicate_attachment', $attachment_id, $duplicated_attachment_id );

			return $duplicated_attachment_id;
		} catch ( WPML_Media_Exception $e ) {
			return null;
		}
	}

	public function sync_on_translation_complete( $new_post_id, $fields, $job ) {
		$new_post = get_post( $new_post_id );
		$this->save_post_actions( $new_post_id, $new_post );
	}

	public function record_original_thumbnail_ids_and_sync( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_thumbnail_id' === $meta_key ) {
			$original_thumbnail_id = get_post_meta( $object_id, $meta_key, true );
			if ( $original_thumbnail_id !== $meta_value ) {
				$this->original_thumbnail_ids[ $object_id ] = $original_thumbnail_id;
				$this->sync_post_thumbnail( $object_id, $meta_value ? $meta_value : false );
			}
		}
	}

	/**
	 * @param int     $pidd
	 * @param WP_Post $post
	 */
	function save_post_actions( $pidd, $post ) {
		if ( ! $post ) {
			return;
		}

		if ( $post->post_type !== 'attachment' && $post->post_status !== 'auto-draft' ) {
			$this->sync_attachments( $pidd, $post );
		}

		if ( $post->post_type === 'attachment' ) {
			$metadata      = wp_get_attachment_metadata( $post->ID );
			$attachment_id = $pidd;
			if ( $metadata ) {
				$this->synchronize_attachment_metadata( $metadata, $attachment_id );
			}
		}
	}

	/**
	 * @param int     $pidd
	 * @param WP_Post $post
	 */
	function sync_attachments( $pidd, $post ) {
		if ( $post->post_type == 'attachment' || $post->post_status == 'auto-draft' ) {
			return;
		}

		$posts_prepared                  = $this->wpdb->prepare( "SELECT post_type, post_status FROM {$this->wpdb->posts} WHERE ID = %d", array( $pidd ) );
		list( $post_type, $post_status ) = $this->wpdb->get_row( $posts_prepared, ARRAY_N );

		// checking - if translation and not saved before
		if ( isset( $_GET['trid'] ) && ! empty( $_GET['trid'] ) && $post_status == 'auto-draft' ) {

			// get source language
			if ( isset( $_GET['source_lang'] ) && ! empty( $_GET['source_lang'] ) ) {
				$src_lang = $_GET['source_lang'];
			} else {
				$src_lang = $this->sitepress->get_default_language();
			}

			// get source id
			$src_id_prepared = $this->wpdb->prepare( "SELECT element_id FROM {$this->wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s", array( $_GET['trid'], $src_lang ) );
			$src_id          = $this->wpdb->get_var( $src_id_prepared );

			// delete exist auto-draft post media
			$results_prepared = $this->wpdb->prepare( "SELECT p.id FROM {$this->wpdb->posts} AS p LEFT JOIN {$this->wpdb->posts} AS p1 ON p.post_parent = p1.id WHERE p1.post_status = %s", array( 'auto-draft' ) );
			$results          = $this->wpdb->get_results( $results_prepared, ARRAY_A );
			$attachments      = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$attachments[] = $result['id'];
				}
				if ( ! empty( $attachments ) ) {
					$in_attachments  = wpml_prepare_in( $attachments, '%d' );
					$delete_prepared = "DELETE FROM {$this->wpdb->posts} WHERE id IN (" . $in_attachments . ')';
					$this->wpdb->query( $delete_prepared );
					$delete_prepared = "DELETE FROM {$this->wpdb->postmeta} WHERE post_id IN (" . $in_attachments . ')';
					$this->wpdb->query( $delete_prepared );
				}
			}

			// checking - if set duplicate media
			if ( $src_id && Option::shouldDuplicateMedia( (int) $src_id ) ) {
				// duplicate media before first save
				$this->duplicate_post_attachments( $pidd, $_GET['trid'], $src_lang, $this->sitepress->get_language_for_element( $pidd, 'post_' . $post_type ) );
			}
		}

		// exceptions
		if (
			! $this->sitepress->is_translated_post_type( $post_type )
			|| isset( $_POST['autosave'] )
			|| ( isset( $_POST['post_ID'] ) && $_POST['post_ID'] != $pidd )
			|| ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'revision' )
			|| $post_type === 'revision'
			|| get_post_meta( $pidd, '_wp_trash_meta_status', true )
			|| ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' )
			|| $post_status === 'auto-draft'
		) {
			return;
		}

		if ( isset( $_POST['icl_trid'] ) ) {
			$icl_trid = $_POST['icl_trid'];
		} else {
			// get trid from database.
			$icl_trid_prepared = $this->wpdb->prepare( "SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type = %s", array( $pidd, 'post_' . $post_type ) );
			$icl_trid          = $this->wpdb->get_var( $icl_trid_prepared );
		}

		if ( $icl_trid ) {
			$language_details = $this->sitepress->get_element_language_details( $pidd, 'post_' . $post_type );

			// In some cases the sitepress cache doesn't get updated (e.g. when posts are created with wp_insert_post()
			// Only in this case, the sitepress cache will be cleared so we can read the element language details
			if ( ! $language_details ) {
				$this->sitepress->get_translations_cache()->clear();
				$language_details = $this->sitepress->get_element_language_details( $pidd, 'post_' . $post_type );
			}
			if ( $language_details ) {
				$this->duplicate_post_attachments( $pidd, $icl_trid, $language_details->source_language_code, $language_details->language_code );
			}
		}
	}

	/**
	 * @param int      $post_id
	 * @param int|null $request_post_thumbnail_id
	 */
	public function sync_post_thumbnail( $post_id, $request_post_thumbnail_id = null ) {

		if ( $post_id && Option::shouldDuplicateFeatured( $post_id ) ) {

			if ( null === $request_post_thumbnail_id ) {
				$request_post_thumbnail_id = filter_input(
					INPUT_POST,
					'thumbnail_id',
					FILTER_SANITIZE_NUMBER_INT,
					FILTER_NULL_ON_FAILURE
				);

				$thumbnail_id = $request_post_thumbnail_id ?
					$request_post_thumbnail_id :
					get_post_meta( $post_id, '_thumbnail_id', true );
			} else {
				$thumbnail_id = $request_post_thumbnail_id;
			}

			$trid         = $this->sitepress->get_element_trid( $post_id, 'post_' . get_post_type( $post_id ) );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . get_post_type( $post_id ) );

			// Check if it is original.
			$is_original = false;
			foreach ( $translations as $translation ) {
				if ( 1 === (int) $translation->original && (int) $translation->element_id === $post_id ) {
					$is_original = true;
				}
			}

			if ( $is_original ) {
				foreach ( $translations as $translation ) {
					if ( ! $translation->original && $translation->element_id ) {
						if ( $this->are_post_thumbnails_still_in_sync( $post_id, $thumbnail_id, $translation ) ) {
							if ( ! $thumbnail_id || - 1 === (int) $thumbnail_id ) {
								$this->withPostMetaFiltersDisabled(
									function () use ( $translation ) {
										delete_post_meta( $translation->element_id, '_thumbnail_id' );
									}
								);
							} else {
								$translated_thumbnail_id = wpml_object_id_filter(
									$thumbnail_id,
									'attachment',
									false,
									$translation->language_code
								);

								$id = get_post_meta( $translation->element_id, '_thumbnail_id', true );
								if ( (int) $id !== $translated_thumbnail_id ) {
									$this->withPostMetaFiltersDisabled(
										function () use ( $translation, $translated_thumbnail_id ) {
											update_post_meta( $translation->element_id, '_thumbnail_id', $translated_thumbnail_id );
										}
									);
								}
							}
						}
					}
				}
			}
		}
	}

	protected function are_post_thumbnails_still_in_sync( $source_id, $source_thumbnail_id, $translation ) {

		$translation_thumbnail_id = get_post_meta( $translation->element_id, '_thumbnail_id', true );

		if ( isset( $this->original_thumbnail_ids[ $source_id ] ) ) {
			if ( $this->original_thumbnail_ids[ $source_id ] === $translation_thumbnail_id ) {
				return true;
			}

			return $this->are_translations_of_each_other(
				$this->original_thumbnail_ids[ $source_id ],
				$translation_thumbnail_id
			);
		} else {
			return $this->are_translations_of_each_other(
				$source_thumbnail_id,
				$translation_thumbnail_id
			);
		}
	}

	private function are_translations_of_each_other( $post_id_1, $post_id_2 ) {
		return $this->sitepress->get_element_trid( $post_id_1, 'post_' . get_post_type( $post_id_1 ) ) ===
			   $this->sitepress->get_element_trid( $post_id_2, 'post_' . get_post_type( $post_id_2 ) );
	}

	function duplicate_post_attachments( $pidd, $icl_trid, $source_lang = null, $lang = null ) {
		$wpdb                           = $this->wpdb;
		$pidd                           = ( is_numeric( $pidd ) ) ? (int) $pidd : null;
		$request_post_icl_ajx_action    = filter_input( INPUT_POST, 'icl_ajx_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
		$request_post_icl_post_language = filter_input( INPUT_POST, 'icl_post_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
		$request_post_post_id           = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );

		if ( $icl_trid == '' ) {
			return;
		}

		if ( ! $source_lang ) {
			$source_lang_prepared = $this->wpdb->prepare( "SELECT source_language_code FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND trid=%d", array( $pidd, $icl_trid ) );
			$source_lang          = $this->wpdb->get_var( $source_lang_prepared );
		}

		// exception for making duplicates. language info not set when this runs and creating the duplicated posts 1/3
		if ( $request_post_icl_ajx_action == 'make_duplicates' && $request_post_icl_post_language ) {
			$source_lang_prepared = $this->wpdb->prepare(
				"SELECT language_code FROM {$this->wpdb->prefix}icl_translations
													 WHERE element_id = %d AND trid = %d",
				array( $request_post_post_id, $icl_trid )
			);
			$source_lang          = $this->wpdb->get_var( $source_lang_prepared );
			$lang                 = $request_post_icl_post_language;

		}

		if ( $source_lang == null || $source_lang == '' ) {
			// This is the original see if we should copy to translations

			if ( Option::shouldDuplicateMedia( $pidd ) || Option::shouldDuplicateFeatured( $pidd ) ) {
				$translations       = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT element_id FROM ' . $wpdb->prefix . 'icl_translations WHERE trid = %d',
						array( $icl_trid )
					)
				);
				$translations       = array_map( 'intval', $translations );
				$source_attachments = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_parent = %d AND post_type = %s',
						array( $pidd, 'attachment' )
					)
				);
				$source_attachments = array_map( 'intval', $source_attachments );

				$all_element_ids           = [];
				$attachments_by_element_id = [];
				foreach ( $translations as $element_id ) {
					if ( $element_id && $element_id !== $pidd ) {
						$all_element_ids[]                        = $element_id;
						$attachments_by_element_id[ $element_id ] = [];
					}
				}
				$all_attachments = [];
				if ( count( $all_element_ids ) > 0 ) {
					$all_attachments = $wpdb->get_results(
						$wpdb->prepare(
							// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
							'SELECT ID, post_parent AS element_id FROM ' . $wpdb->posts . ' WHERE post_parent IN (' . wpml_prepare_in( $all_element_ids ) . ') AND post_type = %s',
							array( 'attachment' )
						),
						ARRAY_A
					);
				}
				foreach ( $all_attachments as $attachment ) {
					$attachments_by_element_id[ (int) $attachment['element_id'] ][] = (int) $attachment['ID'];
				}

				foreach ( $translations as $element_id ) {
					if ( $element_id && $element_id !== $pidd ) {
						$lang_prepared = $this->wpdb->prepare( "SELECT language_code FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND trid = %d", array( $element_id, $icl_trid ) );
						$lang          = $this->wpdb->get_var( $lang_prepared );

						if ( Option::shouldDuplicateFeatured( $element_id ) ) {
							$attachments                           = $attachments_by_element_id[ $element_id ];
							$has_missing_translation_attachment_id = false;

							foreach ( $attachments as $attachment_id ) {
								if ( ! icl_object_id( $attachment_id, 'attachment', false, $lang ) ) {
									$has_missing_translation_attachment_id = true;
									break;
								}
							}

							$source_attachment_ids = $has_missing_translation_attachment_id ? $source_attachments : [];

							foreach ( $source_attachment_ids as $source_attachment_id ) {
								$this->create_duplicate_attachment_not_static( $source_attachment_id, $element_id, $lang );
							}
						}

						$translation_thumbnail_id = get_post_meta( $element_id, '_thumbnail_id', true );
						if ( Option::shouldDuplicateFeatured( $element_id ) && empty( $translation_thumbnail_id ) ) {
							$thumbnail_id = get_post_meta( $pidd, '_thumbnail_id', true );
							if ( $thumbnail_id ) {
								$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $lang );
								if ( $t_thumbnail_id == null ) {
									$dup_att_id     = $this->create_duplicate_attachment_not_static( $thumbnail_id, $element_id, $lang );
									$t_thumbnail_id = $dup_att_id;
								}

								if ( $t_thumbnail_id != null ) {
									update_post_meta( $element_id, '_thumbnail_id', $t_thumbnail_id );
								}
							}
						}
					}
				}
			}
		} else {
			// This is a translation.

			// exception for making duplicates. language info not set when this runs and creating the duplicated posts 2/3
			if ( $request_post_icl_ajx_action === 'make_duplicates' ) {
				$source_id = $request_post_post_id;
			} else {
				$source_id_prepared = $this->wpdb->prepare( "SELECT element_id FROM {$this->wpdb->prefix}icl_translations WHERE language_code = %s AND trid = %d", array( $source_lang, $icl_trid ) );
				$source_id          = $this->wpdb->get_var( $source_id_prepared );
			}

			if ( ! $lang ) {
				$lang_prepared = $this->wpdb->prepare( "SELECT language_code FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND trid = %d", array( $pidd, $icl_trid ) );
				$lang          = $this->wpdb->get_var( $lang_prepared );
			}

			// exception for making duplicates. language info not set when this runs and creating the duplicated posts 3/3
			if ( $request_post_icl_ajx_action === 'make_duplicates' ) {
				$duplicate = Option::shouldDuplicateMedia( $source_id );
			} else {
				$duplicate = Option::shouldDuplicateMedia( $pidd, false );
				if ( $duplicate === null ) {
					// check the original state
					$duplicate = Option::shouldDuplicateMedia( $source_id );
				}
			}

			if ( $duplicate ) {
				$source_attachments_prepared = $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_parent = %d AND post_type = %s", array( $source_id, 'attachment' ) );
				$source_attachments          = $this->wpdb->get_col( $source_attachments_prepared );

				foreach ( $source_attachments as $source_attachment_id ) {
					$translation_attachment_id = icl_object_id( $source_attachment_id, 'attachment', false, $lang );

					if ( ! $translation_attachment_id ) {
						self::create_duplicate_attachment( $source_attachment_id, $pidd, $lang );
					} else {
						$translated_attachment = get_post( $translation_attachment_id );
						if ( $translated_attachment && ! $translated_attachment->post_parent ) {
							$translated_attachment->post_parent = $pidd;
							/** @phpstan-ignore-next-line (WP doc issue) */
							wp_update_post( $translated_attachment );
						}
					}
				}
			}

			$featured = Option::shouldDuplicateFeatured( $pidd, false );
			if ( $featured === null ) {
				// check the original state
				$featured = Option::shouldDuplicateFeatured( $source_id );
			}

			$translation_thumbnail_id = get_post_meta( $pidd, '_thumbnail_id', true );
			if ( $featured && empty( $translation_thumbnail_id ) ) {
				$thumbnail_id = get_post_meta( $source_id, '_thumbnail_id', true );
				if ( $thumbnail_id ) {
					$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $lang );
					if ( $t_thumbnail_id == null ) {
						$dup_att_id     = self::create_duplicate_attachment( $thumbnail_id, $pidd, $lang );
						$t_thumbnail_id = $dup_att_id;
					}

					if ( $t_thumbnail_id != null ) {
						update_post_meta( $pidd, '_thumbnail_id', $t_thumbnail_id );
					}
				}
			}
		}

	}

	/**
	 * @param int    $source_attachment_id
	 * @param int    $pidd
	 * @param string $lang
	 *
	 * @return int|null|WP_Error
	 */
	public function create_duplicate_attachment_not_static( $source_attachment_id, $pidd, $lang ) {
		return self::create_duplicate_attachment( $source_attachment_id, $pidd, $lang );
	}

	private function duplicate_featured_images( $limit = 0, $offset = 0 ) {
		global $wpdb;

		list( $thumbnails, $processed ) = $this->get_post_thumbnail_map( $limit, $offset );

		if ( sizeof( $thumbnails ) ) {
			// Posts IDs with found featured images
			$post_ids       = wpml_prepare_in( array_keys( $thumbnails ), '%d' );
			$posts_prepared = "SELECT ID, post_type FROM {$wpdb->posts} WHERE ID IN ({$post_ids})";
			$posts          = $wpdb->get_results( $posts_prepared );
			foreach ( $posts as $post ) {
				$this->duplicate_featured_image_in_post( $post, $thumbnails );
			}
		}

		return $processed;
	}

	/**
	 * @param int $limit
	 * @param int $offset Offset to use for getting thumbnails. Default: 0.
	 *
	 * @return array
	 */
	public function get_post_thumbnail_map( $limit = 0, $offset = 0 ) {
		global $wpdb;

		$featured_images_sql = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' ORDER BY `meta_id`";

		if ( $limit > 0 ) {
			$featured_images_sql .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $limit );
		}

		$featured_images = $wpdb->get_results( $featured_images_sql );
		$processed       = count( $featured_images );

		$thumbnails = array();
		foreach ( $featured_images as $featured ) {
			$thumbnails[ $featured->post_id ] = $featured->meta_value;
		}

		return array( $thumbnails, $processed );
	}

	/**
	 * @param \stdClass $post       contains properties `ID` and `post_type`
	 * @param array     $thumbnails a map of post ID => thumbnail ID
	 */
	public function duplicate_featured_image_in_post( $post, $thumbnails = array() ) {
		global $wpdb, $sitepress;

		$row_prepared = $wpdb->prepare(
			"SELECT trid, source_language_code
												FROM {$wpdb->prefix}icl_translations
												WHERE element_id=%d
													AND element_type = %s",
			array( $post->ID, 'post_' . $post->post_type )
		);
		$row          = $wpdb->get_row( $row_prepared );
		if ( $row && $row->trid && ( $row->source_language_code == null || $row->source_language_code == '' ) ) {

			$translations = $sitepress->get_element_translations( $row->trid, 'post_' . $post->post_type );
			foreach ( $translations as $translation ) {

				if ( $translation->element_id != $post->ID ) {

					$translation_thumbnail_id = get_post_meta( $translation->element_id, '_thumbnail_id', true );
					if ( empty( $translation_thumbnail_id ) ) {
						if ( ! in_array( $translation->element_id, array_keys( $thumbnails ) ) ) {

							// translation doesn't have a featured image
							$t_thumbnail_id = icl_object_id( $thumbnails[ $post->ID ], 'attachment', false, $translation->language_code );
							if ( $t_thumbnail_id == null ) {
								$dup_att_id     = self::create_duplicate_attachment( $thumbnails[ $post->ID ], $translation->element_id, $translation->language_code );
								$t_thumbnail_id = $dup_att_id;
							}

							if ( $t_thumbnail_id != null ) {
								update_post_meta( $translation->element_id, '_thumbnail_id', $t_thumbnail_id );
							}
						} elseif ( $thumbnails[ $post->ID ] ) {
							update_post_meta( $translation->element_id, '_thumbnail_id', $thumbnails[ $post->ID ] );
						}
					}
				}
			}
		}
	}

	public function ajax_batch_duplicate_featured_images() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_duplicate_featured_images' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$featured_images_left = array_key_exists( 'featured_images_left', $_POST ) && is_numeric( $_POST['featured_images_left'] )
			? (int) $_POST['featured_images_left']
			: null;

		return $this->batch_duplicate_featured_images( true, $featured_images_left );
	}

	public function batch_duplicate_featured_images( $outputResult = true, $featured_images_left = null ) {
		// Use $featured_images_left if it's a number otherwise proceed with null.
		$featured_images_left = is_numeric( $featured_images_left ) ? (int) $featured_images_left : null;

		if ( null === $featured_images_left ) {
			$featured_images_left = $this->get_featured_images_total_number();
		}

		// Use 10 as limit or what's left if there are less than 10 images left to proceed.
		$limit = $featured_images_left < 10 ? $featured_images_left : 10;

		// Duplicate batch of feature images.
		$processed = $this->duplicate_featured_images( $limit, $featured_images_left - $limit );

		// Response result.
		$response = array( 'left' => max( $featured_images_left - $processed, 0 ) );
		if ( $response['left'] ) {
			$response['message'] = sprintf( __( 'Duplicating featured images. %d left', 'sitepress' ), $response['left'] );
		} else {
			$response['message'] = sprintf( __( 'Duplicating featured images: done!', 'sitepress' ), $response['left'] );
		}

		if ( $outputResult ) {
			wp_send_json( $response );
		}
		return $response['left'];
	}

	/**
	 * Returns the total number of Featured Images.
	 *
	 * @return int
	 */
	private function get_featured_images_total_number() {
		$wpdb = $this->wpdb; // Makes Codesniffer interpret the following correctly.

		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_thumbnail_id'"
		);
	}

	public function ajax_batch_duplicate_media() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_duplicate_media' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		return $this->batch_duplicate_media();
	}

	public function batch_duplicate_media( $outputResult = true ) {
		$limit = 10;

		$response = array();

		$attachments_prepared = $this->wpdb->prepare(
			"
            SELECT SQL_CALC_FOUND_ROWS p1.ID, p1.post_parent
            FROM {$this->wpdb->posts} p1
            WHERE post_type = %s
            AND ID NOT IN
            	(SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = %s)
            ORDER BY p1.ID ASC LIMIT %d",
			array( 'attachment', 'wpml_media_processed', $limit )
		);

		$attachments = $this->wpdb->get_results( $attachments_prepared );
		$found       = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$this->create_duplicated_media( $attachment );
			}
		}

		$response['left'] = max( $found - $limit, 0 );
		if ( $response['left'] ) {
			$response['message'] = sprintf( __( 'Duplicating media. %d left', 'sitepress' ), $response['left'] );
		} else {
			$response['message'] = sprintf( __( 'Duplicating media: done!', 'sitepress' ), $response['left'] );
		}

		if ( $outputResult ) {
			wp_send_json( $response );
		}
		return $response['left'];

	}

	private function get_batch_translate_limit( $activeLanguagesCount ) {
		global $sitepress;

		$limit = $sitepress->get_wp_api()->constant( 'WPML_MEDIA_BATCH_LIMIT' );
		$limit = $limit ?: ceil( 100 / max( $activeLanguagesCount - 1, 1 ) );

		return max( $limit, 1 );
	}

	public function ajax_batch_translate_media() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_translate_media' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		return $this->batch_translate_media();
	}

	public function batch_translate_media( $outputResult = true ) {
		$response = [];

		$activeLanguagesCount = count( $this->sitepress->get_active_languages() );
		$limit                = $this->get_batch_translate_limit( $activeLanguagesCount );

		$sql          = "
            SELECT SQL_CALC_FOUND_ROWS p1.ID, p1.post_parent
            FROM {$this->wpdb->prefix}icl_translations t
            INNER JOIN {$this->wpdb->posts} p1
            	ON t.element_id = p1.ID
            LEFT JOIN {$this->wpdb->prefix}icl_translations tt
            	ON t.trid = tt.trid
			WHERE t.element_type = 'post_attachment'
				AND t.source_language_code IS null
			GROUP BY p1.ID, p1.post_parent
			HAVING count(tt.language_code) < %d
            LIMIT %d
        ";

		$sql_prepared = $this->wpdb->prepare( $sql,
			[
				$activeLanguagesCount,
				$limit
			] );

		$attachments = $this->wpdb->get_results( $sql_prepared );

		$found = $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$lang = $this->sitepress->get_element_language_details( $attachment->ID, 'post_attachment' );
				$this->translate_attachments( $attachment->ID, ( is_object( $lang ) && property_exists( $lang, 'language_code' ) ) ? $lang->language_code : null, true );
			}
		}

		$response['left'] = max( $found - $limit, 0 );
		if ( $response['left'] ) {
			$response['message'] = sprintf( esc_html__( 'Translating media. %d left', 'sitepress' ), $response['left'] );
		} else {
			$response['message'] = __( 'Translating media: done!', 'sitepress' );
		}

		if ( $outputResult ) {
			wp_send_json( $response );
		}

		return $response['left'];
	}

	public function batch_set_initial_language() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_set_initial_language' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$default_language = $this->sitepress->get_default_language();
		$limit            = 10;

		$response             = array();
		$attachments_prepared = $this->wpdb->prepare(
			"
            SELECT SQL_CALC_FOUND_ROWS ID FROM {$this->wpdb->posts} WHERE post_type = %s AND ID NOT IN
            (SELECT element_id FROM {$this->wpdb->prefix}icl_translations WHERE element_type=%s) LIMIT %d",
			array(
				'attachment',
				'post_attachment',
				$limit,
			)
		);
		$attachments          = $this->wpdb->get_col( $attachments_prepared );

		$found = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		foreach ( $attachments as $attachment_id ) {
			$this->sitepress->set_element_language_details( $attachment_id, 'post_attachment', false, $default_language );
		}
		$response['left'] = max( $found - $limit, 0 );
		if ( $response['left'] ) {
			$response['message'] = sprintf( __( 'Setting language to media. %d left', 'sitepress' ), $response['left'] );
		} else {
			$response['message'] = sprintf( __( 'Setting language to media: done!', 'sitepress' ), $response['left'] );
		}

		echo wp_json_encode( $response );
		exit;
	}

	public function ajax_batch_scan_prepare() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_scan_prepare' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$this->batch_scan_prepare();
	}

	public function batch_scan_prepare( $outputResult = true ) {
		$response = array();
		$this->wpdb->delete( $this->wpdb->postmeta, array( 'meta_key' => 'wpml_media_processed' ) );

		$response['message'] = __( 'Started...', 'sitepress' );

		if ( $outputResult ) {
			wp_send_json( $response );
		}
	}

	public function ajax_batch_mark_processed() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_mark_processed' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$this->batch_mark_processed();
	}

	public function batch_mark_processed( $outputResult = true ) {

		$response                    = [];
		$wpmlMediaProcessedMetaValue = 1;
		$limit                       = 300;

		/**
		 * Query to get count of attachments from wp_posts table to decide how many rounds we should loop according to $limit
		 */
		$attachmentsCountQuery         = "SELECT COUNT(ID) from {$this->wpdb->posts} where post_type = %s";
		$attachmentsCountQueryPrepared = $this->wpdb->prepare( $attachmentsCountQuery, 'attachment' );

		/**
		 * Retrieving count of attachments
		 */
		$attachmentsCount = $this->wpdb->get_var( $attachmentsCountQueryPrepared );

		/**
		 * Query to get limited number of attachments with metadata up to $limit
		 *
		 * We join with the wp_postmeta table to also retrieve any related data of attachments in this table,
		 * we only need the related data when the wp_postmeta.metavalue is null or != 1 because if it equals 1 then it doesn't need to be processed again
		 */
		$limitedAttachmentsWithMetaDataQuery = "SELECT posts.ID, post_meta.post_id, post_meta.meta_key, post_meta.meta_value
		FROM {$this->wpdb->posts} AS posts
		LEFT JOIN {$this->wpdb->postmeta} AS post_meta
		ON posts.ID = post_meta.post_id AND post_meta.meta_key = %s
		WHERE posts.post_type = %s AND (post_meta.meta_value IS NULL OR post_meta.meta_value != %d)
		LIMIT %d";

		$limitedAttachmentsWithMetaDataQueryPrepared = $this->wpdb->prepare( $limitedAttachmentsWithMetaDataQuery,
			[
				self::WPML_MEDIA_PROCESSED_META_KEY,
				'attachment',
				1,
				$limit,
			] );


		/**
		 * Calculating loop rounds for processing attachments
		 */
		$attachmentsProcessingLoopRounds = $attachmentsCount ? ceil( $attachmentsCount / $limit ) : 0;

		/**
		 * Callback function used to decide if attachment already has metadata or not
		 *
		 * @param $attachmentWithMetaData
		 *
		 * @return bool
		 */
		$attachmentHasNoMetaData = function ( $attachmentWithMetaData ) {
			return Obj::prop( 'post_id', $attachmentWithMetaData ) === null &&
			       Obj::prop( 'meta_key', $attachmentWithMetaData ) === null &&
			       Obj::prop( 'meta_value', $attachmentWithMetaData ) === null;
		};

		/**
		 * Callback function that prepares values to be inserted in the wp_postmeta table
		 *
		 * @param $attachmentId
		 *
		 * @return array
		 */
		$prepareInsertAttachmentsMetaValues = function ( $attachmentId ) use ( $wpmlMediaProcessedMetaValue ) {
			// The order of returned items is important, it represents (meta_value, meta_key, post_id) when insert into wp_postmeta table is done
			return [ $wpmlMediaProcessedMetaValue, self::WPML_MEDIA_PROCESSED_META_KEY, $attachmentId ];
		};


		/**
		 * Looping through the retrieved limited number of attachments with metadata
		 */
		for ( $i = 0; $i < $attachmentsProcessingLoopRounds; $i ++ ) {

			/**
			 * Retrieving limited number of attachments with metadata
			 */
			$attachmentsWithMetaData = $this->wpdb->get_results( $limitedAttachmentsWithMetaDataQueryPrepared );

			if ( is_array( $attachmentsWithMetaData ) && count( $attachmentsWithMetaData ) ) {

				/**
				 * Filtering data to separate existing and non-existing attachments with metdata
				 */
				list( $notExistingMetaAttachmentIds, $existingAttachmentsWithMetaData ) = \WPML\FP\Lst::partition( $attachmentHasNoMetaData, $attachmentsWithMetaData );

				if ( is_array( $notExistingMetaAttachmentIds ) && count( $notExistingMetaAttachmentIds ) ) {

					/**
					 * If we have attachments with no related data in wp_postmeta table, we start inserting values for it in wp_postmeta
					 */

					// Getting only attachments Ids
					$notExistingAttachmentsIds = \WPML\FP\Lst::pluck( 'ID', $notExistingMetaAttachmentIds );

					// Preparing placeholders to be used in INSERT query
					/** @phpstan-ignore-next-line */
					$attachmentMetaValuesPlaceholders = implode( ',', \WPML\FP\Lst::repeat( '(%d, %s, %d)', count( $notExistingAttachmentsIds ) ) );

					// Preparing INSERT query
					$insertAttachmentsMetaQuery = "INSERT INTO {$this->wpdb->postmeta} (meta_value, meta_key, post_id) VALUES ";
					$insertAttachmentsMetaQuery .= $attachmentMetaValuesPlaceholders;

					// Preparing values to be inserted, at his point they're in separate arrays
					/** @phpstan-ignore-next-line */
					$insertAttachmentsMetaValues = array_map( $prepareInsertAttachmentsMetaValues, $notExistingAttachmentsIds );
					// Merging all values together in one array to be used wpdb->prepare function so each value is placed in a placeholder
					$insertAttachmentsMetaValues = array_merge( ...$insertAttachmentsMetaValues );

					// Start replacing placeholders with values and run query
					$insertAttachmentsMetaQuery = $this->wpdb->prepare( $insertAttachmentsMetaQuery, $insertAttachmentsMetaValues );
					$this->wpdb->query( $insertAttachmentsMetaQuery );
				}

				if ( count( $existingAttachmentsWithMetaData ) ) {

					/**
					 * If we have attachments with related data in wp_postmeta table, we start updating meta_value in wp_postmeta
					 */

					$existingAttachmentsIds = \WPML\FP\Lst::pluck( 'ID', $existingAttachmentsWithMetaData );

					$attachmentsIn = wpml_prepare_in( $existingAttachmentsIds, '%d' );

					$updateAttachmentsMetaQuery = $this->wpdb->prepare( "UPDATE {$this->wpdb->postmeta} SET meta_value = %d WHERE post_id IN ({$attachmentsIn})",
						[
							$wpmlMediaProcessedMetaValue,
						]
					);

					$this->wpdb->query( $updateAttachmentsMetaQuery );
				}
			} else {
				/**
				 * When there are no more attachments with metadata found we get out of the loop
				 */

				break;
			}

		}

		Option::setSetupFinished();

		$response['message'] = __( 'Done!', 'sitepress' );

		if ( $outputResult ) {
			wp_send_json( $response );
		}
	}

	public function create_duplicated_media( $attachment ) {
		static $parents_processed = array();

		if ( $attachment->post_parent && ! in_array( $attachment->post_parent, $parents_processed ) ) {

			// see if we have translations.
			$post_type_prepared = $this->wpdb->prepare( "SELECT post_type FROM {$this->wpdb->posts} WHERE ID = %d", array( $attachment->post_parent ) );
			$post_type          = $this->wpdb->get_var( $post_type_prepared );
			$trid_prepared      = $this->wpdb->prepare(
				"SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type = %s",
				array(
					$attachment->post_parent,
					'post_' . $post_type,
				)
			);
			$trid               = $this->wpdb->get_var( $trid_prepared );
			if ( $trid ) {

				$attachments_prepared = $this->wpdb->prepare(
					"SELECT ID FROM {$this->wpdb->posts} WHERE post_type = %s AND post_parent = %d",
					array(
						'attachment',
						$attachment->post_parent,
					)
				);
				$attachments          = $this->wpdb->get_col( $attachments_prepared );

				$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post_type );
				foreach ( $translations as $translation ) {
					if ( $translation->element_id && $translation->element_id != $attachment->post_parent ) {

						$attachments_in_translation_prepared = $this->wpdb->prepare(
							"SELECT ID FROM {$this->wpdb->posts} WHERE post_type = %s AND post_parent = %d",
							array(
								'attachment',
								$translation->element_id,
							)
						);
						$attachments_in_translation          = $this->wpdb->get_col( $attachments_in_translation_prepared );
						if ( sizeof( $attachments_in_translation ) == 0 ) {
							// only duplicate attachments if there a none already.
							foreach ( $attachments as $attachment_id ) {
								// duplicate the attachment
								self::create_duplicate_attachment( $attachment_id, $translation->element_id, $translation->language_code );
							}
						}
					}
				}
			}

			$parents_processed[] = $attachment->post_parent;

		} else {
			// no parent - set to default language

			$target_language = $this->sitepress->get_default_language();

			// Getting the trid and language, just in case image translation already exists
			$trid = $this->sitepress->get_element_trid( $attachment->ID, 'post_attachment' );
			if ( $trid ) {
				$target_language = $this->sitepress->get_language_for_element( $attachment->ID, 'post_attachment' );
			}

			$this->sitepress->set_element_language_details( $attachment->ID, 'post_attachment', $trid, $target_language );

		}

		// Duplicate the post meta of the source element the translation
		$source_element_id = SitePress::get_original_element_id_by_trid( $trid );
		if ( $source_element_id ) {
			$this->update_attachment_metadata( $source_element_id );
		}

		update_post_meta( $attachment->ID, 'wpml_media_processed', 1 );
	}

	function set_content_defaults_prepare() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_set_content_prepare' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$response = array( 'message' => __( 'Started...', 'sitepress' ) );
		echo wp_json_encode( $response );
		exit;
	}

	public function wpml_media_set_content_defaults() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpml_media_set_content_defaults' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!', 'sitepress' ) );
		}

		$this->set_content_defaults();

	}

	private function set_content_defaults() {

		$always_translate_media = $_POST['always_translate_media'];
		$duplicate_media        = $_POST['duplicate_media'];
		$duplicate_featured     = $_POST['duplicate_featured'];
		$translateMediaLibraryTexts     = \WPML\API\Sanitize::stringProp('translate_media_library_texts', $_POST);

		$content_defaults_option = [
			'always_translate_media' => $always_translate_media == 'true',
			'duplicate_media'        => $duplicate_media == 'true',
			'duplicate_featured'     => $duplicate_featured == 'true',
		];

		Option::setNewContentSettings( $content_defaults_option );

		$settings                         = get_option( '_wpml_media' );
		$settings['new_content_settings'] = $content_defaults_option;
		$settings['translate_media_library_texts'] = $translateMediaLibraryTexts === 'true';

		update_option( '_wpml_media', $settings );

		$response = [
			'result'  => true,
			'message' => __( 'Settings saved', 'sitepress' ),
		];
		wp_send_json_success( $response );
	}
}
