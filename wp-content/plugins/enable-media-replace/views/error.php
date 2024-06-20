<?php
namespace EnableMediaReplace;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//use \EnableMediaReplace\UIHelper;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;


?>

<div class='enable-media-replace emr-screen error-screen'>
	<h3 class='title'><?php _e('Enable Media Replace', 'enable-media-replace'); ?></h3>

	<div class='content'>
		<h1><?php _e('An error occured', 'enable-media-replace'); ?></h1>
		<p class="error-message"> <?php echo $view->errorMessage; ?> </p>

		<?php if (property_exists($view, 'errorDescription'))
		{
				echo '<p class="description">' . $view->errorDescription . '</p>';
		} ?>

		<p><?php printf(esc_html__('You can return to %s previous page %s','enable-media-replace'),
		 '<a href="javascript:history.back()">', '</a>');	?></p>


		<p><?php printf(esc_html__('If you need help, please see the plugin %sdocumentation%s. It contains clear solutions to most of the problems you may encounter when using our plugin.', 'enable-media-replace'), '<a href="https://shortpixel.com/knowledge-base/category/308-enable-media-replace" target="_blank">', '</a>'); ?></p>
	</div>
</div> <!--- screen -->


<?php
require_once ABSPATH . 'wp-admin/admin-footer.php';
