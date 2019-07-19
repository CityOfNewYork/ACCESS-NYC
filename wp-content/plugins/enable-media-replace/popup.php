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

 if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.

if (!current_user_can('upload_files'))
	wp_die( esc_html__('You do not have permission to upload files.', 'enable-media-replace') );

global $wpdb;

$table_name = $wpdb->prefix . "posts";

$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = " . (int) $_GET["attachment_id"];

list($current_filename, $current_filetype) = $wpdb->get_row($sql, ARRAY_N);

$current_fullpath = $current_filename;
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
	<h1><?php echo esc_html__("Replace Media Upload", "enable-media-replace"); ?></h1>

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
		<div id="message" class="updated notice notice-success is-dismissible"><p><?php printf( esc_html__('NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', "enable-media-replace"), $current_filename ); ?></p></div>

		<?php if(!$spInstalled) {?>
		<div style="background: #fff;width: 250px;min-height: 270px;border: 1px solid #ccc;float: <?php echo (is_rtl()) ? 'left' : 'right' ?>;padding: 15px;margin: 0 0 10px;">
			<h3 class="" style="margin-top: 0;text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<?php echo esc_html__("Optimize your images with ShortPixel, get +50% credits!", "enable-media-replace"); ?>
				</a>
			</h3>
			<div class="" style="text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<img src="https://optimizingmattersblog.files.wordpress.com/2016/10/shortpixel.png">
				</a>
			</div>
			<div class="" style="margin-bottom: 10px;">
				<?php echo esc_html__("Get more Google love by compressing your site's images! Check out how much ShortPixel can save your site and get +50% credits when signing up as an Enable Media Replace user! Forever!", "enable-media-replace"); ?>
			</div>
			<div class=""><div style="text-align: <?php echo (is_rtl()) ? 'left' : 'right' ?>;">
					<a class="button button-primary" id="shortpixel-image-optimiser-info" href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
						<?php echo esc_html__("More info", "enable-media-replace"); ?></p>
					</a>
				</div>
			</div>
		</div>
		<?php } ?>

		<p><?php echo esc_html__("Choose a file to upload from your computer", "enable-media-replace"); ?></p>

		<input type="file" name="userfile" id="userfile" onchange="imageHandle(event);" />
        <div>
            <img src="<?php echo $current_fullpath ?>" width="150px" height="150px" style="object-fit: cover"/>
            <img id="previewImage" src="https://via.placeholder.com/150x150" width="150px" height="150px"/>
        </div>

		<?php do_action( 'emr_before_replace_type_options' ); ?>

    <?php $s3pluginExist =  class_exists('S3_Uploads'); ?>
	<?php if ( apply_filters( 'emr_display_replace_type_options', true ) ) : ?>
        <?php if ( ! $s3pluginExist) : ?>
		<p><?php echo esc_html__("Select media replacement type:", "enable-media-replace"); ?></p>

		<label for="replace_type_1"><input CHECKED id="replace_type_1" type="radio" name="replace_type" value="replace"> <?php echo esc_html__("Just replace the file", "enable-media-replace"); ?></label>
		<p class="howto"><?php printf( esc_html__("Note: This option requires you to upload a file of the same type (%s) as the one you are replacing. The name of the attachment will stay the same (%s) no matter what the file you upload is called.", "enable-media-replace"), $current_filetype, $current_filename ); ?></p>

        <?php endif; ?>
		<?php if ( apply_filters( 'emr_enable_replace_and_search', true ) ) : ?>
		<label for="replace_type_2"><input <?php echo $s3pluginExist ? 'CHECKED' : '' ?> id="replace_type_2" type="radio" name="replace_type" value="replace_and_search"> <?php echo __("Replace the file, use new file name and update all links", "enable-media-replace"); ?></label>
		<p class="howto"><?php printf( esc_html__("Note: If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file (%s) will be updated to point to the new file name.", "enable-media-replace"), $current_filename ); ?></p>
		<p class="howto"><?php echo esc_html__("Please note that if you upload a new image, only embeds/links of the original size image will be replaced in your posts.", "enable-media-replace"); ?></p>
		<?php endif; ?>
	<?php else : ?>
        <?php if ( ! $s3pluginExist) : ?>
            <input type="hidden" name="replace_type" value="replace" />
        <?php else : ?>
            <input type="hidden" name="replace_type" value="replace_and_search" />
        <?php endif; ?>
	<?php endif; ?>
		<input id="submit" type="submit" class="button button-primary" disabled="disabled" value="<?php echo esc_attr__("Upload", "enable-media-replace"); ?>" />
        <a href="#" class="button" onclick="history.back();"><?php echo esc_html__("Cancel", "enable-media-replace"); ?></a>
	</form>
</div>
<script>
    function imageHandle(event) {
        var file = document.getElementById("userfile");
        var submit = document.getElementById("submit");
        var preview = document.getElementById("previewImage");

        appendPreview(file, preview, event);
        enableSubmitButton(file, submit);
    }

    function appendPreview(fileSource, preview, event) {
        if (fileSource.value) {
            var file = fileSource.files[0];
            if (file.type.match("image/*")) {
                preview.setAttribute("src", window.URL.createObjectURL(file));
                preview.setAttribute("style", "object-fit: cover");
            } else {
                preview.setAttribute("src", "https://dummyimage.com/150x150/ccc/969696.gif&text=File");
                preview.removeAttribute("style");
            }
        } else {
            preview.setAttribute("src", "https://via.placeholder.com/150x150");
        }
    }
    function enableSubmitButton(file, submit)
    {
        if (file.value) {
            submit.removeAttribute("disabled");
        } else {
            submit.setAttribute("disabled", true);
        }
    }
</script>
