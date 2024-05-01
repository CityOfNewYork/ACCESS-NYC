<?php

use WPML\FP\Str;

/**
 * WPML_Attachment_Action class file.
 *
 * @package WPML
 */

/**
 * Class WPML_Attachment_Action
 */
class WPML_Attachment_Action implements IWPML_Action {

	/**
	 * SitePress instance.
	 *
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * Wpdb instance.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WPML_Attachment_Action constructor.
	 *
	 * @param SitePress $sitepress SitePress instance.
	 * @param wpdb      $wpdb      wpdb instance.
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		if ( $this->is_admin_or_xmlrpc() && ! $this->is_uploading_plugin_or_theme() ) {

			$active_languages = $this->sitepress->get_active_languages();

			if ( count( $active_languages ) > 1 ) {
				add_filter( 'views_upload', array( $this, 'views_upload_actions' ) );
			}
		}

		add_filter( 'attachment_link', array( $this->sitepress, 'convert_url' ), 10, 1 );
		add_filter( 'wp_delete_file', array( $this, 'delete_file_filter' ) );
	}

	/**
	 * Check if we are in site console or xmlrpc request is active.
	 *
	 * @return bool
	 */
	private function is_admin_or_xmlrpc() {
		$is_admin  = is_admin();
		$is_xmlrpc = ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );

		return $is_admin || $is_xmlrpc;
	}

	/**
	 * Check if we are uploading plugin or theme.
	 *
	 * @return bool
	 */
	private function is_uploading_plugin_or_theme() {
		global $action;

		return ( isset( $action ) && ( 'upload-plugin' === $action || 'upload-theme' === $action ) );
	}

	/**
	 * Filter views.
	 *
	 * @param array $views Views.
	 *
	 * @return array
	 */
	public function views_upload_actions( $views ) {
		global $pagenow;

		if ( 'upload.php' === $pagenow ) {
			// Get current language.
			$lang = $this->sitepress->get_current_language();

			foreach ( $views as $key => $view ) {
				// Extract the base URL and query parameters.
				$href_count = preg_match( '/(href=["\'])([\s\S]+?)\?([\s\S]+?)(["\'])/', $view, $href_matches );
				if ( $href_count && isset( $href_args ) ) {
					$href_base = $href_matches[2];
					wp_parse_str( $href_matches[3], $href_args );
				} else {
					$href_base = 'upload.php';
					$href_args = array();
				}

				if ( 'all' !== $lang ) {
					$sql = $this->wpdb->prepare(
						"
						SELECT COUNT(p.id)
						FROM {$this->wpdb->posts} AS p
							INNER JOIN {$this->wpdb->prefix}icl_translations AS t
								ON p.id = t.element_id
						WHERE p.post_type = 'attachment'
						AND t.element_type='post_attachment'
						AND t.language_code = %s ",
						$lang
					);

					switch ( $key ) {
						case 'all':
							$and = " AND p.post_status != 'trash' ";
							break;
						case 'detached':
							$and = " AND p.post_status != 'trash' AND p.post_parent = 0 ";
							break;
						case 'trash':
							$and = " AND p.post_status = 'trash' ";
							break;
						default:
							if ( isset( $href_args['post_mime_type'] ) ) {
								$and = " AND p.post_status != 'trash' " . wp_post_mime_type_where( $href_args['post_mime_type'], 'p' );
							} else {
								$and = $this->wpdb->prepare( " AND p.post_status != 'trash' AND p.post_mime_type LIKE %s", $key . '%' );
							}
					}

					// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
					$and = apply_filters( 'wpml-media_view-upload-sql_and', $and, $key, $view, $lang );

					$sql_and = $sql . $and;
					$sql     = apply_filters( 'wpml-media_view-upload-sql', $sql_and, $key, $view, $lang );

					$res = apply_filters( 'wpml-media_view-upload-count', null, $key, $view, $lang );
					// phpcs:enable

					if ( null === $res ) {
						$res = $this->wpdb->get_col( $sql );
					}
					// Replace count.
					$view = preg_replace( '/\((\d+)\)/', '(' . $res[0] . ')', $view );
				}

				// Replace href link, adding the 'lang' argument and the revised count.
				$href_args['lang'] = $lang;
				$href_args         = array_map( 'urlencode', $href_args );
				$new_href          = add_query_arg( $href_args, $href_base );
				$views[ $key ]     = preg_replace( '/(href=["\'])([\s\S]+?)(["\'])/', '$1' . $new_href . '$3', $view );
			}
		}

		return $views;
	}

	/**
	 * Check if the image is not duplicated to another post before deleting it physically.
	 *
	 * @param string $file Full file name.
	 *
	 * @return string|null
	 */
	public function delete_file_filter( $file ) {
		if ( $file ) {
			$file_name           = $this->get_file_name( $file );
			$sql                 = "SELECT pm.meta_id, pm.post_id FROM {$this->wpdb->postmeta} AS pm
						WHERE pm.meta_value = %s AND pm.meta_key='_wp_attached_file'";
			$attachment_prepared = $this->wpdb->prepare( $sql, [ $file_name ] );
			$attachment          = $this->wpdb->get_row( $attachment_prepared );

			if ( ! empty( $attachment ) ) {
				$file = null;
			}
		}

		return $file;
	}

	private function get_file_name( $file ) {
		$file_name  = $this->get_file_name_without_size_from_full_name( $file );
		$upload_dir = wp_upload_dir();
		/** @phpstan-ignore-next-line */
		$path_parts = $file ? explode( "/", Str::replace( $upload_dir['basedir'], '', $file ) ) : [];

		if ( $path_parts ) {
			$path_parts[ count( $path_parts ) - 1 ] = $file_name;
		}

		return ltrim( implode( '/', $path_parts ), '/' );
	}

	/**
	 * Get file name without a size, i.e. 'a-600x400.png' -> 'a.png'.
	 *
	 * @param string $file Full file name.
	 *
	 * @return mixed|string|string[]|null
	 */
	private function get_file_name_without_size_from_full_name( $file ) {
		$file_name = preg_replace( '/^(.+)\-\d+x\d+(\.\w+)$/', '$1$2', $file );
		$file_name = preg_replace( '/^[\s\S]+(\/.+)$/', '$1', $file_name );
		$file_name = str_replace( '/', '', $file_name );

		return $file_name;
	}
}
