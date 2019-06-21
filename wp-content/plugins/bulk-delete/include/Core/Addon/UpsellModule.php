<?php

namespace BulkWP\BulkDelete\Core\Addon;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * A Module that upsells an add-on.
 *
 * Upseller Module is displayed for add-ons with a description and a link to buy them.
 * If an add-on is installed, then the Upseller Module is automatically deactivated.
 *
 * Methods that are not needed are left empty.
 *
 * @since 6.0.0
 */
class UpsellModule extends BaseModule {
	/**
	 * Details about the add-on.
	 *
	 * @var \BulkWP\BulkDelete\Core\Addon\AddonUpsellInfo
	 */
	protected $addon_upsell_info;

	/**
	 * Create the UpsellModule using add-on info.
	 *
	 * @param \BulkWP\BulkDelete\Core\Addon\AddonUpsellInfo $addon_upsell_info Addon Upsell Info.
	 */
	public function __construct( $addon_upsell_info ) {
		$this->addon_upsell_info = $addon_upsell_info;

		$this->meta_box_slug = $this->addon_upsell_info->get_slug();
		$this->messages      = array(
			'box_label' => $addon_upsell_info->get_upsell_title(),
		);
	}

	/**
	 * Upsell modules will use the name of the Add-on as their name.
	 *
	 * @return string Upsell Module name.
	 */
	public function get_name() {
		return str_replace( ' ', '', $this->addon_upsell_info->get_name() );
	}

	public function render() {
		?>

		<p>
			<?php echo $this->addon_upsell_info->get_upsell_message(); ?>
			<a href="<?php echo esc_url( $this->addon_upsell_info->get_buy_url() ); ?>" target="_blank"><?php _e( 'Buy Now', 'bulk-delete' ); ?></a>
		</p>

		<?php
	}

	protected function initialize() {
		// Empty by design.
	}

	protected function parse_common_filters( $request ) {
		// Empty by design.
	}

	protected function convert_user_input_to_options( $request, $options ) {
		// Empty by design.
	}

	protected function get_success_message( $items_deleted ) {
		// Empty by design.
	}

	protected function do_delete( $options ) {
		// Empty by design.
	}
}
