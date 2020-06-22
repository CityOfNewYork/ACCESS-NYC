<?php

$wppath = str_replace("wp-content/plugins/enable-media-replace/upload.php", "", __FILE__);

require_once($wppath . "wp-load.php");
require_once($wppath . "wp-admin/admin.php");

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));
	
global $wpdb;

// Define DB table names
$table_name = $wpdb->prefix . "posts";
$postmeta_table_name = $wpdb->prefix . "postmeta";

// Get old guid and filetype from DB
$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = {$_POST["ID"]}";
list($current_filename, $current_filetype) = mysql_fetch_array(mysql_query($sql));

// Massage a bunch of vars
$current_guid = $current_filename;
$current_filename = substr($current_filename, (strrpos($current_filename, "/") + 1));

$current_file = get_attached_file($_POST["ID"], true);
$current_path = substr($current_file, 0, (strrpos($current_file, "/")));
$current_file = str_replace("//", "/", $current_file);
$current_filename = basename($current_file);


$replace_type = $_POST["replace_type"];
// We have two types: replace / replace_and_search

if (is_uploaded_file($_FILES["userfile"]["tmp_name"])) {
	$new_filename = $_FILES["userfile"]["name"];
	$new_filetype = $_FILES["userfile"]["type"];
	$new_filesize = $_FILES["userfile"]["size"];

	if ($replace_type == "replace") {
		// Drop-in replace and we don't even care if you uploaded something that is the wrong file-type.
		// That's your own fault, because we warned you!

		// Delete old file
		unlink($current_file);
		
		// Move new file to old location/name
		move_uploaded_file($_FILES["userfile"]["tmp_name"], $current_file);
		
		// Chmod new file to 644
		chmod($current_file, 0644);
		
		// Make thumb and/or update metadata
		wp_update_attachment_metadata( $_POST["ID"], wp_generate_attachment_metadata( $_POST["ID"], $current_file ) );
		
	}
	
	else {
		// Replace file, replace file name, update meta data, replace links pointing to old file name
		
		// Delete old file
		unlink($current_file);
		
		// Massage new filename to adhere to WordPress standards
		$new_filename= wp_unique_filename( $current_path, $new_filename );
		
		// Move new file to old location, new name
		$new_file = $current_path . "/" . $new_filename;
		move_uploaded_file($_FILES["userfile"]["tmp_name"], $new_file);

		// Chmod new file to 644
		chmod($new_file, 0644);	
		
		$new_filetitle = preg_replace('/\.[^.]+$/', '', basename($new_file));
		$new_guid = str_replace($current_filename, $new_filename, $current_guid);
		
		// Update database file name
		mysql_query("UPDATE $table_name SET post_title = '$new_filetitle', post_name = '$new_filetitle', guid = '$new_guid', post_mime_type = '$new_filetype' WHERE ID = {$_POST["ID"]}");
		
		// Update the postmeta file name

		// Get old postmeta _wp_attached_file
		$sql = "SELECT meta_value FROM $postmeta_table_name WHERE meta_key = '_wp_attached_file' AND post_id = '{$_POST["ID"]}'";
		$old_meta_name = mysql_result(mysql_query($sql),0);
		
		// Make new postmeta _wp_attached_file
		$new_meta_name = str_replace($current_filename, $new_filename, $old_meta_name);
		mysql_query("UPDATE $postmeta_table_name SET meta_value = '$new_meta_name' WHERE meta_key = '_wp_attached_file' AND post_id = '{$_POST["ID"]}'");
		
		// Make thumb and/or update metadata
		wp_update_attachment_metadata( $_POST["ID"], wp_generate_attachment_metadata( $_POST["ID"], $new_file) );
		
		// Search-and-replace filename in post database
		$sql = "SELECT ID, post_content FROM $table_name WHERE post_content LIKE '%$current_guid%'";
		$rs = mysql_query($sql);
		
		while($rows = mysql_fetch_assoc($rs)) {
			
			// replace old guid with new guid
			$post_content = $rows["post_content"];
			$post_content = addslashes(str_replace($current_guid, $new_guid, $post_content));
			
			mysql_query("UPDATE $table_name SET post_content = '$post_content' WHERE ID = {$rows["ID"]}");
		}
			
	}
	
}
	
?><html><head></head><body><script type="text/javascript">window.close();window.opener.location.reload();</script></body></html>