<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.

if (!current_user_can('upload_files'))
	wp_die( esc_html__('You do not have permission to upload files.', 'enable-media-replace') );

// Define DB table names
global $wpdb;
$table_name = $wpdb->prefix . "posts";
$postmeta_table_name = $wpdb->prefix . "postmeta";

require_once('thumbnail_updater.php');

/**
 * Delete a media file and its thumbnails.
 *
 * @param string     $current_file
 * @param array|null $metadta
 */
function emr_delete_current_files( $current_file, $metadta = null ) {
	// Delete old file

	// Find path of current file
	$current_path = substr($current_file, 0, (strrpos($current_file, "/")));

	// Check if old file exists first
	if (file_exists($current_file)) {
		// Now check for correct file permissions for old file
		clearstatcache();
		if (is_writable($current_file)) {
			// Everything OK; delete the file
			unlink($current_file);
		}
		else {
			// File exists, but has wrong permissions. Let the user know.
			printf( esc_html__('The file %1$s can not be deleted by the web server, most likely because the permissions on the file are wrong.', "enable-media-replace"), $current_file);
			exit;
		}
	}

	// Delete old resized versions if this was an image
	$suffix = substr($current_file, (strlen($current_file)-4));
	$prefix = substr($current_file, 0, (strlen($current_file)-4));

	if (strtolower($suffix) === ".pdf") {
		$prefix .= "-pdf";
		$suffix = ".jpg";
	}

	$imgAr = array(".png", ".gif", ".jpg");
	if (in_array($suffix, $imgAr)) {
		// It's a png/gif/jpg based on file name
		// Get thumbnail filenames from metadata
		if ( empty( $metadata ) ) {
			$metadata = wp_get_attachment_metadata( $_POST["ID"] );
		}

		if (is_array($metadata)) { // Added fix for error messages when there is no metadata (but WHY would there not be? I don't know…)
			foreach($metadata["sizes"] AS $thissize) {
				// Get all filenames and do an unlink() on each one;
				$thisfile = $thissize["file"];
				// Create array with all old sizes for replacing in posts later
				$oldfilesAr[] = $thisfile;
				// Look for files and delete them
				if (strlen($thisfile)) {
					$thisfile = $current_path . "/" . $thissize["file"];
					if (file_exists($thisfile)) {
						unlink($thisfile);
					}
				}
			}
		}
		// Old (brutal) method, left here for now
		//$mask = $prefix . "-*x*" . $suffix;
		//array_map( "unlink", glob( $mask ) );
	}
}

/**
 * Maybe remove query string from URL.
 *
 * @param string $url
 *
 * @return string
 */
function emr_maybe_remove_query_string( $url ) {
	$parts = explode( '?', $url );

	return reset( $parts );
}

/**
 * Remove scheme from URL.
 *
 * @param string $url
 *
 * @return string
 */
function emr_remove_scheme( $url ) {
	return preg_replace( '/^(?:http|https):/', '', $url );
}

/**
 * Remove size from filename (image[-100x100].jpeg).
 *
 * @param string $url
 * @param bool   $remove_extension
 *
 * @return string
 */
function emr_remove_size_from_filename( $url, $remove_extension = false ) {
	$url = preg_replace( '/^(\S+)-[0-9]{1,4}x[0-9]{1,4}(\.[a-zA-Z0-9\.]{2,})?/', '$1$2', $url );

	if ( $remove_extension ) {
		$ext = pathinfo( $url, PATHINFO_EXTENSION );
		$url = str_replace( ".$ext", '', $url );
	}

	return $url;
}

/**
 * Strip an image URL down to bare minimum for matching.
 *
 * @param string $url
 *
 * @return string
 */
function emr_get_match_url($url) {
	$url = emr_remove_scheme($url);
	$url = emr_maybe_remove_query_string($url);
	$url = emr_remove_size_from_filename($url, true);
	$url = emr_remove_domain_from_filename($url);

	return $url;
}


function emr_remove_domain_from_filename($url) {
	// Holding place for possible future function
	$url = str_replace(emr_remove_scheme(get_bloginfo('url')), '', $url);
	return $url;
	}

/**
 * Build an array of search or replace URLs for given attachment GUID and its metadata.
 *
 * @param string $guid
 * @param array  $metadata
 *
 * @return array
 */
function emr_get_file_urls( $guid, $metadata ) {
	$urls = array();

	$guid = emr_remove_scheme( $guid );
	$guid= emr_remove_domain_from_filename($guid);

	$urls['guid'] = $guid;

	if ( empty( $metadata ) ) {
		return $urls;
	}

	$base_url = dirname( $guid );

	if ( ! empty( $metadata['file'] ) ) {
		$urls['file'] = trailingslashit( $base_url ) . wp_basename( $metadata['file'] );
	}

	if ( ! empty( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $key => $value ) {
			$urls[ $key ] = trailingslashit( $base_url ) . wp_basename( $value['file'] );
		}
	}

	return $urls;
}

/**
 * Ensure new search URLs cover known sizes for old attachment.
 * Falls back to full URL if size not covered (srcset or width/height attributes should compensate).
 *
 * @param array $old
 * @param array $new
 *
 * @return array
 */
function emr_normalize_file_urls( $old, $new ) {
	$result = array();

	if ( empty( $new['guid'] ) ) {
		return $result;
	}

	$guid = $new['guid'];

	foreach ( $old as $key => $value ) {
		$result[ $key ] = empty( $new[ $key ] ) ? $guid : $new[ $key ];
	}

	return $result;
}

// Get old guid and filetype from DB
$sql = "SELECT post_mime_type FROM $table_name WHERE ID = '" . (int) $_POST["ID"] . "'";
list($current_filetype) = $wpdb->get_row($sql, ARRAY_N);

// Massage a bunch of vars
$current_guid =wp_get_attachment_url($_POST['ID']);

$ID = (int) $_POST["ID"];

$current_file = get_attached_file($ID, apply_filters( 'emr_unfiltered_get_attached_file', true ));
$current_path = substr($current_file, 0, (strrpos($current_file, "/")));
$current_file = preg_replace("|(?<!:)/{2,}|", "/", $current_file);
$current_filename = wp_basename($current_file);
$current_metadata = wp_get_attachment_metadata( $_POST["ID"] );

$replace_type = $_POST["replace_type"];
// We have two types: replace / replace_and_search

if (is_uploaded_file($_FILES["userfile"]["tmp_name"])) {

	// New method for validating that the uploaded file is allowed, using WP:s internal wp_check_filetype_and_ext() function.
	$filedata = wp_check_filetype_and_ext($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"]);

	if ($filedata["ext"] == "") {
		echo esc_html__("File type does not meet security guidelines. Try another.", 'enable-media-replace');
		exit;
	}

	$thumbUpdater = new ThumbnailUpdater($ID);
	$thumbUpdater->setOldMetadata($current_metadata);

	$new_filename = $_FILES["userfile"]["name"];
	$new_filesize = $_FILES["userfile"]["size"];
	$new_filetype = $filedata["type"];

	// save original file permissions
	$original_file_perms = fileperms($current_file) & 0777;

	if ($replace_type == "replace") {
		// Drop-in replace and we don't even care if you uploaded something that is the wrong file-type.
		// That's your own fault, because we warned you!

        //call replace action to give a chance to plugins like ShortPixel to delete the metadata, backups and cleanup the cache on server
        do_action('wp_handle_replace', array('post_id' => $ID));

        emr_delete_current_files( $current_file, $current_metadata );

        $new_filename = wp_unique_filename( $current_path, $current_filename );
        $new_filename = apply_filters( 'emr_unique_filename', $current_filename, $current_path, $ID );

        // Move new file to old location, new name
        $new_file = $current_path . "/" . $current_filename;
        move_uploaded_file($_FILES["userfile"]["tmp_name"], $new_file);

		// Chmod new file to original file permissions
		@chmod($current_file, $original_file_perms);

		apply_filters( 'enable_media_replace_title', basename($new_file) ); // Thanks Jonas Lundman (http://wordpress.org/support/topic/add-filter-hook-suggestion-to)

        // Update database file timestamp
        $post_date = gmdate( 'Y-m-d H:i:s' );
        $sql = $wpdb->prepare(
            "UPDATE $table_name SET post_date = '$post_date', post_date_gmt = '$post_date' WHERE ID = %d;",
            $ID
        );
        $wpdb->query($sql);

        //call upload action to give a chance to plugins like Resize Image After Upload to handle the new image
        do_action('wp_handle_upload', array('file' => $current_file, 'url' => wp_get_attachment_url($ID), 'type' => $new_filetype));

        // Make thumb and/or update metadata
			$metadata = wp_generate_attachment_metadata( $ID, $current_file );
			wp_update_attachment_metadata( $ID, $metadata );

			$thumbUpdater->setNewMetadata($metadata);
			$thumbUpdater->updateThumbnails();


		// Trigger possible updates on CDN and other plugins
		update_attached_file( $ID, $current_file);


	} elseif ( 'replace_and_search' == $replace_type && apply_filters( 'emr_enable_replace_and_search', true ) ) {
		// Replace file, replace file name, update meta data, replace links pointing to old file name

		//call replace action to give a chance to plugins like ShortPixel to delete the metadata, backups and cleanup the cache on server
		do_action('wp_handle_replace', array('post_id' => $ID));

		emr_delete_current_files( $current_file, $current_metadata );

		// Massage new filename to adhere to WordPress standards
		$new_filename = wp_unique_filename( $current_path, $new_filename );
		$new_filename = apply_filters( 'emr_unique_filename', $new_filename, $current_path, $ID );

		// Move new file to old location, new name
		$new_file = $current_path . "/" . $new_filename;
		move_uploaded_file($_FILES["userfile"]["tmp_name"], $new_file);

		// Chmod new file to original file permissions
		@chmod($current_file, $original_file_perms);

		$new_filetitle = preg_replace('/\.[^.]+$/', '', basename($new_file));
		$new_filetitle = apply_filters( 'enable_media_replace_title', $new_filetitle ); // Thanks Jonas Lundman (http://wordpress.org/support/topic/add-filter-hook-suggestion-to)
		$new_guid = str_replace($current_filename, $new_filename, $current_guid);

		//call upload action to give a chance to plugins like ShortPixel to handle the new image
		//do_action('wp_handle_upload', array('file' => $new_file, 'url' => $new_guid, 'type' => $new_filetype));

        $post_date = gmdate( 'Y-m-d H:i:s' );

        // Update database file name
		$sql = $wpdb->prepare(
			"UPDATE $table_name SET post_title = '$new_filetitle', post_name = '$new_filetitle', guid = '$new_guid', post_mime_type = '$new_filetype', post_date = '$post_date', post_date_gmt = '$post_date' WHERE ID = %d;",
			$ID
		);
		$wpdb->query($sql);

		// Update the postmeta file name

		// Get old postmeta _wp_attached_file
		$sql = $wpdb->prepare(
			"SELECT meta_value FROM $postmeta_table_name WHERE meta_key = '_wp_attached_file' AND post_id = %d;",
			$ID
		);

		$old_meta_name = $wpdb->get_row($sql, ARRAY_A);
		$old_meta_name = $old_meta_name["meta_value"];

		// Make new postmeta _wp_attached_file
		$new_meta_name = str_replace($current_filename, $new_filename, $old_meta_name);
		$sql = $wpdb->prepare(
			"UPDATE $postmeta_table_name SET meta_value = '$new_meta_name' WHERE meta_key = '_wp_attached_file' AND post_id = %d;",
			$ID
		);
		$wpdb->query($sql);

		// Make thumb and/or update metadata
		$new_metadata = wp_generate_attachment_metadata( $ID, $new_file );
		wp_update_attachment_metadata( $ID, $new_metadata );

		// Search-and-replace filename in post database
		$current_base_url = emr_get_match_url( $current_guid );

		$sql = $wpdb->prepare(
			"SELECT ID, post_content FROM $table_name WHERE post_status = 'publish' AND post_content LIKE %s;",
			'%' . $current_base_url . '%'
		);


		$rs = $wpdb->get_results( $sql, ARRAY_A );

		$number_of_updates = 0;


		if ( ! empty( $rs ) ) {
			$search_urls  = emr_get_file_urls( $current_guid, $current_metadata );
			$replace_urls = emr_get_file_urls( $new_guid, $new_metadata );
			$replace_urls = emr_normalize_file_urls( $search_urls, $replace_urls );


			foreach ( $rs AS $rows ) {

				$number_of_updates = $number_of_updates + 1;

				// replace old URLs with new URLs.
				$post_content = $rows["post_content"];
				$post_content = str_replace( $search_urls, $replace_urls, $post_content );

				$sql = $wpdb->prepare(
					"UPDATE $table_name SET post_content = %s WHERE ID = %d;",
					array($post_content, $rows["ID"])
				);

				$wpdb->query( $sql );
			}
		}

		$thumbUpdater->setNewMetadata($new_metadata);
		$thumbUpdater->updateThumbnails();

		// Trigger possible updates on CDN and other plugins
		update_attached_file( $ID, $new_file );
	}

	#echo "Updated: " . $number_of_updates;

	$returnurl = admin_url("/post.php?post={$_POST["ID"]}&action=edit&message=1");

	// Execute hook actions - thanks rubious for the suggestion!
	if (isset($new_guid)) { do_action("enable-media-replace-upload-done", $new_guid, $current_guid); }

} else {
	//TODO Better error handling when no file is selected.
	//For now just go back to media management
	$returnurl = admin_url("upload.php");
}

if (FORCE_SSL_ADMIN) {
	$returnurl = str_replace("http:", "https:", $returnurl);
}

// Allow developers to override $returnurl
$returnurl = apply_filters('emr_returnurl', $returnurl);

//save redirection
wp_redirect($returnurl);
?>
