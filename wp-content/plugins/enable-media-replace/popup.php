<?php
/**
 * Uploadscreen for selecting and uploading new media file
 *
 * @author      Måns Jonasson  <http://www.mansjonasson.se>
 * @copyright   Måns Jonasson 13 sep 2010
 * @version     $Revision: 2303 $ | $Date: 2010-09-13 11:12:35 +0200 (ma, 13 sep 2010) $
 * @package     wordpress
 * @subpackage  enable-media-replace
 *
 */

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.', 'enable-media-replace'));

global $wpdb;

$table_name = $wpdb->prefix . "posts";

$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = " . (int) $_GET["attachment_id"];

list($current_filename, $current_filetype) = $wpdb->get_row($sql, ARRAY_N);

$current_filename = substr($current_filename, (strrpos($current_filename, "/") + 1));


?>
<style>
    .emr-plugin-button.emr-updating:before {
        font: 400 20px/1 dashicons;
        display: inline-block;
        content: "\f463";
        -webkit-animation: rotation 2s infinite linear;
        animation: rotation 2s infinite linear;
        margin: 3px 5px 0 -2px;
        vertical-align: top
    }
</style>
<div class="wrap">
	<h1><?php echo __("Replace Media Upload", "enable-media-replace"); ?></h1>

	<?php
	$url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&noheader=true&action=media_replace_upload&attachment_id=" . (int) $_GET["attachment_id"]);
	$action = "media_replace_upload";
    $formurl = wp_nonce_url( $url, $action );
	if (FORCE_SSL_ADMIN) {
			$formurl = str_replace("http:", "https:", $formurl);
		}
	?>

	<form enctype="multipart/form-data" method="post" action="<?php echo $formurl; ?>">
	<?php
		#wp_nonce_field('enable-media-replace');
    $plugins = get_plugins();
    $spInstalled = isset($plugins['shortpixel-image-optimiser/wp-shortpixel.php']);
    $spActive = is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');
	?>
		<input type="hidden" name="ID" value="<?php echo (int) $_GET["attachment_id"]; ?>" />
		<div id="message" class="updated notice notice-success is-dismissible"><p><?php printf( __('NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', "enable-media-replace"), $current_filename ); ?></p></div>

		<?php if(!$spInstalled) {?>
		<div style="background: #fff;width: 250px;min-height: 270px;border: 1px solid #ccc;float: right;padding: 15px;position: relative;margin: 0 0 10px 10px;">
			<h3 class="" style="margin-top: 0;text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<?php _e("Optimize your images with ShortPixel, get +50% credits!", "enable-media-replace"); ?>
				</a>
			</h3>
			<div class="" style="text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<img src="https://optimizingmattersblog.files.wordpress.com/2016/10/shortpixel.png">
				</a>
			</div>
			<div class="" style="margin-bottom: 10px;">
				<?php _e("Get more Google love by compressing your site's images! Check out how much ShortPixel can save your site and get +50% credits when signing up as an Enable Media Replace user! Forever!", "enable-media-replace"); ?>
			</div>
			<div class=""><div style="text-align: right;">
					<a href="#" data-action="install" data-slug="shortpixel-image-optimiser" data-message="Activated" data-add-link="options-general.php?page=wp-shortpixel" data-add-link-name="ShortPixel options" class="button-primary emr-plugin-button ">
                        <?php _e("Install &amp; Activate", "enable-media-replace"); ?>
                    </a>
					<a class="button button-secondary" id="shortpixel-image-optimiser-info" href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
						<?php _e("More info", "enable-media-replace"); ?></p>
					</a>
				</div>
			</div>
		</div>
		<?php } ?>

		<p><?php echo __("Choose a file to upload from your computer", "enable-media-replace"); ?></p>

		<input type="file" name="userfile" />

		<?php do_action( 'emr_before_replace_type_options' ); ?>

	<?php if ( apply_filters( 'emr_display_replace_type_options', true ) ) : ?>
		<p><?php echo __("Select media replacement type:", "enable-media-replace"); ?></p>

		<label for="replace_type_1"><input CHECKED id="replace_type_1" type="radio" name="replace_type" value="replace"> <?php echo __("Just replace the file", "enable-media-replace"); ?></label>
		<p class="howto"><?php printf( __("Note: This option requires you to upload a file of the same type (%s) as the one you are replacing. The name of the attachment will stay the same (%s) no matter what the file you upload is called.", "enable-media-replace"), $current_filetype, $current_filename ); ?></p>

		<?php if ( apply_filters( 'emr_enable_replace_and_search', true ) ) : ?>
		<label for="replace_type_2"><input id="replace_type_2" type="radio" name="replace_type" value="replace_and_search"> <?php echo __("Replace the file, use new file name and update all links", "enable-media-replace"); ?></label>
		<p class="howto"><?php printf( __("Note: If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file (%s) will be updated to point to the new file name.", "enable-media-replace"), $current_filename ); ?></p>
		<p class="howto"><?php echo __("Please note that if you upload a new image, only embeds/links of the original size image will be replaced in your posts.", "enable-media-replace"); ?></p>
		<?php endif; ?>
	<?php else : ?>
		<input type="hidden" name="replace_type" value="replace" />
	<?php endif; ?>
		<input type="submit" class="button button-primary" value="<?php echo __("Upload", "enable-media-replace"); ?>" />
        <a href="#" class="button" onclick="history.back();"><?php echo __("Cancel", "enable-media-replace"); ?></a>
	</form>
</div>
