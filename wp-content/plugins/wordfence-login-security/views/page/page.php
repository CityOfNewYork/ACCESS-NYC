<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

/**
 * @var array $sections The content tabs, each element is an array of the syntax array('tab' => Model_Tab instance, 'title' => Title instance, 'content' => HTML content). Required.
 */
?>
<?php do_action('wfls_activation_page_header'); ?>
<div class="wrap wordfence-ls">
	<?php
	if (\WordfenceLS\Controller_Permissions::shared()->can_manage_settings() && !\WordfenceLS\Controller_Settings::shared()->get_bool(\WordfenceLS\Controller_Settings::OPTION_DISMISSED_FRESH_INSTALL_MODAL) && !WORDFENCE_LS_FROM_CORE) {
		echo \WordfenceLS\Model_View::create('onboarding/standalone-header')->render();
	}
	?>
	<div class="wfls-container-fluid">
		<?php
		$tabs = array_map(function($t) { return $t['tab']; }, $sections);
		echo \WordfenceLS\Model_View::create('page/tabbar', array(
			'tabs' => $tabs,
		))->render();
		?>
		<div class="wfls-row">
			<div class="wfls-col-xs-12">
				<?php foreach ($sections as $s): ?>
				<div id="<?php echo esc_attr($s['tab']->id); ?>" class="wfls-tab-content" data-title="<?php echo esc_attr($s['tab']->pageTitle); ?>">
					<?php
					echo \WordfenceLS\Model_View::create('page/section-title', array(
							'title' => $s['title'],
					))->render();
					echo $s['content'];
					?>
				</div> <!-- end <?php echo \WordfenceLS\Text\Model_HTML::esc_html($s['tab']->id); ?> block -->
				<?php endforeach; ?>
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
