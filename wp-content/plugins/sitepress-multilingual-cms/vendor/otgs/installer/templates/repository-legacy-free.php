<?php

namespace OTGS\Installer\Templates\Repository;

class LegacyFree {

	public static function render( $model ) {
		$url         = $model->productUrl . '/buy/';
		$linkMessage = __( 'Upgrade your account', 'installer' );
		$link        = '<a href="' . esc_url( $url ) . '">' . esc_html( $linkMessage ) . '</a>';
		$message     = sprintf( __( 'You have an old Types-free subscription, which doesn\'t provide automatic updates. %s', 'installer' ), $link );
		?>
        <div class="otgs-installer-registered wp-clearfix">
            <div class="notice inline otgs-installer-notice otgs-installer-notice-registered otgs-installer-notice-<?php echo $model->repoId; ?>">
                <div class="otgs-installer-notice-content">
					<?php echo $message ?>
					<?php \OTGS\Installer\Templates\Repository\RegisteredButtons::render( $model ); ?>
                </div>
            </div>
        </div>
		<?php

	}
}
