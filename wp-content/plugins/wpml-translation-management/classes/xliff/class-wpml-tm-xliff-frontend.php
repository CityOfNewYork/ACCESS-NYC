<?php
/**
 * WPML_TM_Xliff_Frontend class file
 *
 * @package wpml-translation-management
 */

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once WPML_TM_PATH . '/inc/wpml_zip.php';

/**
 * Class WPML_TM_Xliff_Frontend
 */
class WPML_TM_Xliff_Frontend extends WPML_TM_Xliff_Shared {

	/**
	 * Success admin notices
	 *
	 * @var array
	 */
	private $success;

	/**
	 * Attachments
	 *
	 * @var array
	 */
	private $attachments = array();

	/**
	 * SitePress instance
	 *
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * Name of archive
	 *
	 * @var string
	 */
	private $export_archive_name;

	/**
	 * Priority of late initialisation
	 *
	 * @var int
	 */
	private $late_init_priority = 9999;

	/**
	 * Is simple xml turned on
	 *
	 * @var bool
	 */
	private $simplexml_on;

	/**
	 * WPML_TM_Xliff_Frontend constructor
	 *
	 * @param WPML_Translation_Job_Factory $job_factory  Job factory.
	 * @param SitePress                    $sitepress    SitePress instance.
	 * @param boolean                      $simplexml_on Is simple xml turned on.
	 */
	public function __construct( WPML_Translation_Job_Factory $job_factory, SitePress $sitepress, $simplexml_on ) {
		parent::__construct( $job_factory );
		$this->sitepress    = $sitepress;
		$this->simplexml_on = $simplexml_on;
	}

	/**
	 * Get available xliff versions
	 *
	 * @return array
	 */
	public function get_available_xliff_versions() {

		return array(
			'10' => '1.0',
			'11' => '1.1',
			'12' => '1.2',
		);
	}

	/**
	 * Get init priority
	 *
	 * @return int
	 */
	public function get_init_priority() {
		return isset( $_POST['xliff_upload'] ) ||
		       ( isset( $_GET['wpml_xliff_action'] ) && $_GET['wpml_xliff_action'] === 'download' ) ||
		       isset( $_POST['wpml_xliff_export_all_filtered'] ) ?
			$this->get_late_init_priority() : 10;
	}

	/**
	 * Get late init priority
	 *
	 * @return int
	 */
	public function get_late_init_priority() {
		return $this->late_init_priority;
	}

	/**
	 * Init class
	 *
	 * @return bool
	 * @throws Exception Throws an exception in case of errors.
	 */
	public function init() {
		$this->attachments = array();
		$this->error       = null;
		if ( $this->sitepress->get_wp_api()->is_admin() ) {
			add_action( 'admin_head', array( $this, 'js_scripts' ) );
			add_action(
				'wp_ajax_set_xliff_options',
				array(
					$this,
					'ajax_set_xliff_options',
				),
				10,
				2
			);
			if ( ! $this->sitepress->get_setting( 'xliff_newlines' ) ) {
				$this->sitepress->set_setting( 'xliff_newlines', WPML_XLIFF_TM_NEWLINES_ORIGINAL, true );
			}
			if ( ! $this->sitepress->get_setting( 'tm_xliff_version' ) ) {
				$this->sitepress->set_setting( 'tm_xliff_version', '12', true );
			}

			if ( 1 < count( $this->sitepress->get_languages( false, true ) ) ) {
				add_filter(
					'wpml_translation_queue_actions',
					array(
						$this,
						'translation_queue_add_actions',
					)
				);
				add_action(
					'wpml_xliff_select_actions',
					array(
						$this,
						'translation_queue_xliff_select_actions',
					),
					10,
					3
				);
				add_action(
					'wpml_translation_queue_do_actions_export_xliff',
					array(
						$this,
						'translation_queue_do_actions_export_xliff',
					),
					10,
					2
				);
				add_action(
					'wpml_translation_queue_after_display',
					array(
						$this,
						'translation_queue_after_display',
					),
					10,
					2
				);
				add_action(
					'wpml_translator_notification',
					array(
						$this,
						'translator_notification',
					),
					10,
					0
				);
				add_filter(
					'wpml_new_job_notification',
					array(
						$this,
						'new_job_notification',
					),
					10,
					2
				);
				add_filter(
					'wpml_new_job_notification_attachments',
					array(
						$this,
						'new_job_notification_attachments',
					)
				);
			}
			if (
				isset( $_GET['wpml_xliff_action'] ) &&
				$_GET['wpml_xliff_action'] === 'download' &&
				wp_verify_nonce( $_GET['nonce'], 'xliff-export' )
			) {
				$archive = $this->get_xliff_archive(
					isset( $_GET['xliff_version'] ) ? $_GET['xliff_version'] : ''
				);
				$this->stream_xliff_archive( $archive );
			}
			if (
				isset( $_POST['wpml_xliff_export_all_filtered'] ) &&
				wp_verify_nonce( $_POST['nonce'], 'xliff-export-all-filtered' )
			) {
				$job_ids = $this->get_all_filtered_job_ids();
				$archive = $this->get_xliff_archive( $_POST['xliff_version'], $job_ids );
				$this->stream_xliff_archive( $archive );
			}
			if ( isset( $_POST['xliff_upload'] ) ) {
				$this->import_xliff(
					isset( $_FILES['import'] ) ? $_FILES['import'] : array()
				);
				if ( is_wp_error( $this->error ) ) {
					add_action( 'admin_notices', array( $this, 'admin_notices_error' ) );
				}
			}
		}

		return true;
	}

	/**
	 * Set xliff options
	 */
	public function ajax_set_xliff_options() {
		check_ajax_referer( 'icl_xliff_options_form_nonce', 'security' );
		$newlines = isset( $_POST['icl_xliff_newlines'] ) ? (int) $_POST['icl_xliff_newlines'] : 0;
		$this->sitepress->set_setting( 'xliff_newlines', $newlines, true );
		$version = isset( $_POST['icl_xliff_version'] ) ? (int) $_POST['icl_xliff_version'] : 0;
		$this->sitepress->set_setting( 'tm_xliff_version', $version, true );

		wp_send_json_success(
			array(
				'message'        => 'OK',
				'newlines_saved' => $newlines,
				'version_saved'  => $version,
			)
		);
	}

	/**
	 * New job notification
	 *
	 * @param array $mail   Email content.
	 * @param int   $job_id Job id.
	 *
	 * @return array
	 */
	public function new_job_notification( $mail, $job_id ) {
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );

		if ( isset( $tm_settings['notification']['include_xliff'] ) && $tm_settings['notification']['include_xliff'] ) {
			$xliff_version = $this->get_user_xliff_version();
			$xliff_file    = $this->get_xliff_file( $job_id, $xliff_version );
			$temp_dir      = get_temp_dir();
			$file_name     = $temp_dir . get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff';
			$fh            = fopen( $file_name, 'w' );
			if ( $fh ) {
				fwrite( $fh, $xliff_file );
				fclose( $fh );
				$mail['attachment']           = $file_name;
				$this->attachments[ $job_id ] = $file_name;

				$mail['body'] .= __( ' - A xliff file is attached.', 'wpml-translation-management' );
			}
		}

		return $mail;
	}

	/**
	 * Get zip name from jobs
	 *
	 * @param array $job_ids Job ids.
	 *
	 * @return string
	 */
	private function get_zip_name_from_jobs( $job_ids ) {
		$min_job = min( $job_ids );
		$max_job = max( $job_ids );
		if ( $max_job === $min_job ) {
			return get_bloginfo( 'name' ) . '-translation-job-' . $max_job . '.zip';
		} else {
			return get_bloginfo( 'name' ) . '-translation-job-' . $min_job . '-' . $max_job . '.zip';
		}
	}

	/**
	 * New job notification attachments
	 *
	 * @param array $attachments Job notification attachments.
	 *
	 * @return array
	 */
	public function new_job_notification_attachments( $attachments ) {
		$found   = false;
		$archive = new wpml_zip();

		foreach ( $attachments as $index => $attachment ) {
			if ( in_array( $attachment, $this->attachments, true ) ) {
				$fh         = fopen( $attachment, 'r' );
				$xliff_file = fread( $fh, filesize( $attachment ) );
				fclose( $fh );
				$archive->addFile( $xliff_file, basename( $attachment ) );

				unset( $attachments[ $index ] );
				$found = true;
			}
		}

		if ( $found ) {
			// Add the zip file to the attachments.
			$archive_data = $archive->getZipData();
			$temp_dir     = get_temp_dir();
			$file_name    = $temp_dir . $this->get_zip_name_from_jobs( array_keys( $this->attachments ) );
			$fh           = fopen( $file_name, 'w' );
			fwrite( $fh, $archive_data );
			fclose( $fh );
			$attachments[] = $file_name;
		}

		return $attachments;
	}

	/**
	 * Get xliff file
	 *
	 * @param int    $job_id        Job id.
	 * @param string $xliff_version Xliff version.
	 *
	 * @return string
	 */
	private function get_xliff_file( $job_id, $xliff_version = WPML_XLIFF_DEFAULT_VERSION ) {
		return wpml_tm_xliff_factory()
			->create_writer( $xliff_version )
			->generate_job_xliff( $job_id );
	}

	/**
	 * Get xliff archive
	 *
	 * @param string     $xliff_version Xliff version.
	 * @param array|null $job_ids       Job ids.
	 *
	 * @return wpml_zip
	 *
	 * @throws Exception Throws an exception in case of errors.
	 */
	public function get_xliff_archive( $xliff_version, $job_ids = array() ) {
		global $wpdb, $current_user;

		if ( empty( $job_ids ) && isset( $_GET['xliff_export_data'] ) ) {
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$data = json_decode( base64_decode( $_GET['xliff_export_data'] ) );
			// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$job_ids = isset( $data->job ) ? array_keys( (array) $data->job ) : array();
		}

		$archive = new wpml_zip();
		foreach ( $job_ids as $job_id ) {
			$xliff_file = $this->get_xliff_file( $job_id, $xliff_version );

			// Assign the job to this translator.
			$rid        = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT rid
					 FROM {$wpdb->prefix}icl_translate_job
					 WHERE job_id=%d ",
					$job_id
				)
			);
			$data_value = array( 'translator_id' => $current_user->ID );
			$data_where = array( 'job_id' => $job_id );
			$wpdb->update( $wpdb->prefix . 'icl_translate_job', $data_value, $data_where );
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data_value, $data_where );
			$archive->addFile( $xliff_file, get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff' );
		}

		$this->export_archive_name = $this->get_zip_name_from_jobs( $job_ids );
		$archive->finalize();

		return $archive;
	}

	/**
	 * Stream xliff archive
	 *
	 * @param wpml_zip $archive Zip archive.
	 *
	 * @throws Exception Throws an exception in case of errors.
	 */
	private function stream_xliff_archive( $archive ) {
		if ( is_a( $archive, 'wpml_zip' ) ) {
			if ( defined( 'WPML_SAVE_XLIFF_PATH' ) && trim( WPML_SAVE_XLIFF_PATH ) ) {
				$this->save_zip_file( WPML_SAVE_XLIFF_PATH, $archive );
			}
			$archive->sendZip( $this->export_archive_name );
		}
		exit;
	}

	/**
	 * Save zip file
	 *
	 * @param string   $path    Where to save the archive.
	 * @param wpml_zip $archive Zip archive.
	 */
	private function save_zip_file( $path, $archive ) {
		$path = trailingslashit( $path );
		if ( ! is_dir( $path ) ) {
			$result = mkdir( $path );
			if ( ! $result ) {
				return;
			}
		}
		$archive->setZipFile( $path . $this->export_archive_name );
	}

	/**
	 * Get all filtered job ids
	 *
	 * @return array
	 */
	public function get_all_filtered_job_ids() {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		/**
		 * Translation management instance
		 * Translation job factory
		 *
		 * @var TranslationManagement        $iclTranslationManagement
		 * @var WPML_Translation_Job_Factory $wpml_translation_job_factory
		 */
		global $iclTranslationManagement, $wpml_translation_job_factory;

		$job_ids            = array();
		$current_translator = $iclTranslationManagement->get_current_translator();
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$can_translate = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;

		if ( $can_translate ) {
			$icl_translation_filter = WPML_Translations_Queue::get_cookie_filters();

			$icl_translation_filter['translator_id']      = $current_translator->ID;
			$icl_translation_filter['include_unassigned'] = true;

			$translation_jobs = $wpml_translation_job_factory->get_translation_jobs( (array) $icl_translation_filter, true );
			$job_ids          = wp_list_pluck( $translation_jobs, 'job_id' );
		}

		return $job_ids;
	}

	/**
	 * Stops any redirects from happening when we call the
	 * translation manager to save the translations.
	 *
	 * @return null
	 */
	public function stop_redirect() {
		return null;
	}

	/**
	 * Import xliff file
	 *
	 * @param array $file Xliff file data.
	 *
	 * @return bool|WP_Error
	 */
	private function import_xliff( $file ) {
		global $current_user;

		// We don't want any redirects happening when we save the translation.
		add_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );

		$this->success = array();
		$contents      = array();

		if ( 0 === (int) $file['size'] ) {
			$this->error = new WP_Error( 'empty_file', __( 'You are trying to import an empty file.', 'wpml-translation-management' ) );

			return false;
		} elseif ( isset( $file['tmp_name'] ) && $file['tmp_name'] ) {
			$fh   = fopen( $file['tmp_name'], 'r' );
			$data = fread( $fh, 4 );
			fclose( $fh );
			if ( $data[0] == 'P' && $data[1] == 'K' && $data[2] == chr( 03 ) && $data[3] == chr( 04 ) ) {
				if ( class_exists( 'ZipArchive' ) ) {
					$z     = new ZipArchive();
					$zopen = $z->open( $file['tmp_name'], 4 );
					if ( true !== $zopen ) {
						$this->error = new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.', 'wpml-translation-management' ) );

						return false;
					}
					$empty_files = array();
					for ( $i = 0; $i < $z->numFiles; $i ++ ) {
						if ( ! $info = $z->statIndex( $i ) ) {
							$this->error = new WP_Error( 'stat_failed', __( 'Could not retrieve file from archive.', 'wpml-translation-management' ) );

							return false;
						}
						$content = $z->getFromIndex( $i );
						if ( false === (bool) $content ) {
							$empty_files[] = $info['name'];
						}
						$contents[ $info['name'] ] = $content;
					}
					if ( $empty_files ) {
						$this->error = new WP_Error( 'extract_failed', __( 'The archive contains one or more empty files.', 'wpml-translation-management' ), $empty_files );

						return false;
					}
				} else {
					require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
					$archive = new PclZip( $file['tmp_name'] );
					// Is the archive valid?
					$archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );
					if ( false == $archive_files ) {
						$this->error = new WP_Error( 'incompatible_archive', __( 'You are trying to import an incompatible Archive.', 'wpml-translation-management' ), $archive->errorInfo( true ) );

						return false;
					}
					if ( 0 === count( $archive_files ) ) {
						$this->error = new WP_Error( 'empty_archive', __( 'You are trying to import an empty archive.', 'wpml-translation-management' ) );

						return false;
					}
					$empty_files = array();
					foreach ( $archive_files as $content ) {
						if ( false === (bool) $content['content'] ) {
							$empty_files[] = $content['filename'];
						}
						$contents[ $content['filename'] ] = $content['content'];
					}
					if ( $empty_files ) {
						$this->error = new WP_Error( 'extract_failed', __( 'The archive contains one or more empty files.', 'wpml-translation-management' ), $empty_files );

						return false;
					}
				}
			} else {
				$fh   = fopen( $file['tmp_name'], 'r' );
				$data = fread( $fh, $file['size'] );
				fclose( $fh );
				$contents[ $file['name'] ] = $data;
			}

			foreach ( $contents as $name => $content ) {
				if ( $this->validate_file_name( $name ) ) {
					list( $job, $job_data ) = $this->validate_file( $name, $content, $current_user );
					if ( null !== $this->error ) {
						return $job_data;
					}
					kses_remove_filters();
					wpml_tm_save_data( $job_data );
					kses_init();
					// translators: %s: job id.
					$this->success[] = sprintf( __( 'Translation of job %s has been uploaded and completed.', 'wpml-translation-management' ), $job->job_id );
				}
			}

			if ( count( $this->success ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notices_success' ) );

				return true;
			}
		}

		return false;
	}

	/**
	 * Translation queue actions
	 *
	 * @param array  $actions          Actions.
	 * @param string $action_name      Action name.
	 * @param array  $translation_jobs Translation jobs.
	 */
	public function translation_queue_xliff_select_actions( $actions, $action_name, $translation_jobs ) {
		if ( $this->has_translation_jobs( $translation_jobs ) && sizeof( $actions ) > 0 ) :
			$user_version = $this->get_user_xliff_version();
			?>
			<div class="alignleft actions">
				<select name="<?php echo esc_html( $action_name ); ?>">
					<option
							value="-1" <?php echo $user_version == false ? "selected='selected'" : ""; ?>><?php _e( 'Bulk Actions' ); ?></option>
					<?php foreach ( $actions as $key => $action ) : ?>
						<option <?php disabled( ! $this->simplexml_on ); ?>
								value="<?php echo $key; ?>" <?php echo $user_version == $key && $this->simplexml_on ? "selected='selected'" : ""; ?>><?php echo $action; ?></option>
					<?php endforeach; ?>
				</select>
				<input
						type="submit" value="<?php esc_attr_e( 'Apply' ); ?>"
						name="do<?php echo esc_html( $action_name ); ?>"
						class="button-secondary action"/>
			</div>
		<?php
		endif;
	}

	/**
	 * Has translation jobs
	 *
	 * @param array $translation_jobs Translation jobs.
	 *
	 * @return bool
	 */
	private function has_translation_jobs( $translation_jobs ) {
		return $translation_jobs && array_key_exists( 'jobs', $translation_jobs ) && $translation_jobs['jobs'];
	}

	/**
	 * Get xliff version select options
	 *
	 * @return string
	 */
	private function get_xliff_version_select_options() {
		$output       = '';
		$user_version = (int) $this->get_user_xliff_version();
		foreach ( $this->get_available_xliff_versions() as $value => $label ) {
			$user_version = false === $user_version ? $value : $user_version;

			$output .= '<option value="' . $value . '"';
			$output .= $user_version === $value ? 'selected="selected"' : '';
			$output .= '>XLIFF ' . $label . '</option>';
		}

		return $output;
	}

	/**
	 * Adds the various possible XLIFF versions to translations queue page's export actions on display
	 *
	 * @param array $actions Actions.
	 *
	 * @return array
	 */
	public function translation_queue_add_actions( $actions ) {
		foreach ( $this->get_available_xliff_versions() as $key => $value ) {
			// translators: %s: XLIFF version.
			$actions[ $key ] = sprintf( __( 'Export XLIFF %s', 'wpml-translation-management' ), $value );
		}

		return $actions;
	}

	/**
	 * Export xliff
	 *
	 * @param array  $data          Xliff data.
	 * @param string $xliff_version Xliff version.
	 */
	public function translation_queue_do_actions_export_xliff( $data, $xliff_version ) {
		?>
		<script type="text/javascript">
			<?php
			if ( isset( $data['job'] ) ) {
			?>
			var xliff_export_data  = "<?php echo base64_encode( json_encode( $data ) ); ?>";
			var xliff_export_nonce = "<?php echo wp_create_nonce( 'xliff-export' ); ?>";
			var xliff_version      = "<?php echo $xliff_version; ?>";
			addLoadEvent( function() {
				window.location = "<?php echo htmlentities( $_SERVER['REQUEST_URI'] ); ?>&wpml_xliff_action=download&xliff_export_data=" + xliff_export_data + '&nonce=' + xliff_export_nonce + '&xliff_version=' + xliff_version;
			} );
			<?php
			} else {
			?>
			var error_message = "<?php echo esc_html__( 'No translation jobs were selected for export.', 'wpml-translation-management' ); ?>";
			alert( error_message );
			<?php
			}
			?>
		</script>
		<?php
	}

	/**
	 * Show error messages in admin notices
	 */
	public function admin_notices_error() {
		if ( is_wp_error( $this->error ) ) {
			?>
			<div class="message error">
				<p><?php echo $this->error->get_error_message(); ?></p>
				<?php
				if ( $this->error->get_error_data() ) {
					?>
					<ol>
						<li><?php echo implode( '</li><li>', $this->error->get_error_data() ); ?></li>
					</ol>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Show success messages in admin notices
	 */
	public function admin_notices_success() {
		?>
		<div class="message updated"><p>
			<ul>
				<?php
				foreach ( $this->success as $message ) {
					echo '<li>' . $message . '</li>';
				}
				?>
			</ul>
			</p></div>
		<?php
	}

	/**
	 * Check translation queue after display
	 *
	 * @param array $translation_jobs Translation jobs.
	 */
	public function translation_queue_after_display( $translation_jobs = array() ) {
		if ( ! $this->has_translation_jobs( $translation_jobs ) ) {
			return;
		}

		$export_label = esc_html__( 'Export all jobs:', 'wpml-translation-management' );

		$cookie_filters = WPML_Translations_Queue::get_cookie_filters();

		if ( $cookie_filters ) {
			$type = __( 'All types', 'wpml-translation-management' );

			if ( ! empty( $cookie_filters['type'] ) ) {
				$post_slug  = preg_replace( '/^post_|^package_/', '', $cookie_filters['type'], 1 );
				$post_types = $this->sitepress->get_translatable_documents( true );
				$post_types = apply_filters( 'wpml_get_translatable_types', $post_types );

				if ( array_key_exists( $post_slug, $post_types ) ) {
					$type = $post_types[ $post_slug ]->label;
				}
			}

			$from   = ! empty( $cookie_filters['from'] )
				? $this->sitepress->get_display_language_name( $cookie_filters['from'] )
				: __( 'Any language', 'wpml-translation-management' );
			$to     = ! empty( $cookie_filters['to'] )
				? $this->sitepress->get_display_language_name( $cookie_filters['to'] )
				: __( 'Any language', 'wpml-translation-management' );

			$status = ! empty( $cookie_filters['status'] ) && (int) $cookie_filters['status'] !== ICL_TM_WAITING_FOR_TRANSLATOR
				? TranslationManagement::status2text( $cookie_filters['status'] )
				: ( ! empty( $cookie_filters['status'] ) ? __('Available to translate', 'wpml-translation-management') : 'All statuses' );

			$export_label = sprintf(
			// translators: %1: post type, %2: from language, %3: to language, %4: status.
				esc_html__( 'Export all filtered jobs of %1$s from %2$s to %3$s in %4$s:', 'wpml-translation-management' ),
				'<b>' . $type . '</b>',
				'<b>' . $from . '</b>',
				'<b>' . $to . '</b>',
				'<b>' . $status . '</b>'
			);
		}
		?>

		<br/>
		<table class="widefat">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Import / Export XLIFF', 'wpml-translation-management' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>
					<?php if ( ! $this->simplexml_on ) : ?>
						<div class="otgs-notice error">
							<p>
								<strong><?php esc_html_e( 'SimpleXML missing!', 'wpml-translation-management' ); ?></strong>
							</p>
							<p>
								<?php esc_html_e( 'SimpleXML extension is required for using XLIFF files in WPML Translation Management.', 'wpml-translation-management' ); ?>
								<a href="https://wpml.org/?page_id=716"><?php esc_html_e( 'WPML Minimum Requirements', 'wpml-translation-management' ); ?></a>
							</p>
						</div>
					<?php endif; ?>
					<form method="post" id="translation-xliff-export-all-filtered" action="">
						<label for="wpml_xliff_export_all_filtered"><?php echo $export_label; ?></label>
						<select name="xliff_version" class="select" <?php disabled( ! $this->simplexml_on ); ?>><?php
							echo $this->get_xliff_version_select_options(); ?></select>
						<input
								type="submit"
								value="<?php esc_attr_e( 'Export', 'wpml-translation-management' ); ?>" <?php
						disabled( ! $this->simplexml_on ); ?> name="wpml_xliff_export_all_filtered" id="xliff_download"
								class="button-secondary action"/>
						<input
								type="hidden" value="<?php echo wp_create_nonce( 'xliff-export-all-filtered' ); ?>"
								name="nonce">
					</form>
					<hr>
					<form enctype="multipart/form-data" method="post" id="translation-xliff-upload" action="">
						<label for="upload-xliff-file"><?php _e( 'Select the xliff file or zip file to upload from your computer:&nbsp;', 'wpml-translation-management' ); ?></label>
						<input
								type="file" id="upload-xliff-file"
								name="import" <?php disabled( ! $this->simplexml_on ); ?> />
						<input
								type="submit" value="<?php _e( 'Upload', 'wpml-translation-management' ); ?>"
								name="xliff_upload" id="xliff_upload" class="button-secondary action" <?php
						disabled( ! $this->simplexml_on ); ?> />
					</form>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Print online js script
	 */
	public function js_scripts() {
		?>
		<script type="text/javascript">
			var wpml_xliff_ajax_nonce = '<?php echo wp_create_nonce( 'icl_xliff_options_form_nonce' ); ?>';
		</script>
		<?php
	}

	/**
	 * Provide translator notification
	 */
	public function translator_notification() {
		$checked = $this->sitepress->get_setting( 'include_xliff_in_notification' ) ? 'checked="checked"' : '';
		?>
		<input
				type="checkbox" name="include_xliff" id="icl_include_xliff"
				value="1" <?php echo $checked; ?>/>
		<label
				for="icl_include_xliff"><?php _e( 'Include XLIFF files in notification emails', 'wpml-translation-management' ); ?></label>
		<?php
	}

	/**
	 * Get user xliff version
	 *
	 * @return bool|string
	 */
	private function get_user_xliff_version() {

		return $this->sitepress->get_setting( 'tm_xliff_version', false );
	}
}
