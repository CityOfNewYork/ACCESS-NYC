<?php

namespace OTGS\Installer\Templates\Repository;

class EndUsers {
	public static function render( $withProduct, $model ) {
		$withSiteName     = function ( $str ) use ( $model ) {
			$siteUrl = $model->productName === 'WPML' ? 'wpml.org' : 'toolset.com';

			return sprintf( $str, $siteUrl );
		};
		$withProductTwice = function ( $str ) use ( $model ) {
			return sprintf( $str, $model->productName, $model->productName );
		};

		// translators: %s replaced with wpml.org or toolset.com
		$accountQuestion        = $withSiteName( __( 'Do you have an account on %s?', 'installer' ) );
		$extendInfo             = __( 'Great! You just need to extend your subscription.', 'installer' );
		// translators: %s replaced with wpml.org or toolset.com
		$extendAlternative      = $withProduct( __( 'You have a %s account. You just need to extend your subscription.', 'installer' ) );
		$extendButton           = __( 'Extend Subscription', 'installer' );
		// translators: %s replaced with wpml.org or toolset.com
		$discountQuestion       = $withProduct( __( 'OK. You need to set up renewal for %s.', 'installer' ) );
		// translators: %1$s and %2$s replaced with wpml.org or toolset.com
		$discountAlternative    = $withProductTwice( __( 'You do not have a %1$s account yet. You need to set up a renewal for %2$s.', 'installer' ) );
		// translators: %s replaced with wpml.org or toolset.com
		$discountButton         = $withProduct( __( 'Set Up Renewal For %s', 'installer' ) );
		$findAccountInfo        = __( 'No worries. We can check that for you.', 'installer' );
		$findAccountButton      = __( 'Check', 'installer' );
		$findAccountPlaceholder = __( 'Your Email Address', 'installer' );
		
		if ( $model->endUserRenewalUrl ): ?>
            <div class="js-question">
                <p class="otgs-installer-notice-status-item"><?php echo esc_html( $accountQuestion ); ?></p>
                <a class="js-yes-button otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                   href="">
					<?php esc_html_e( 'Yes', 'installer' ); ?>
                </a>
                <a class="js-no-button otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                   href="">
					<?php esc_html_e( 'No', 'installer' ); ?>
                </a>
                <a class="js-dont-know otgs-installer-notice-status-item otgs-installer-notice-status-item-link"
                   href="">
					<?php esc_html_e( 'I do not remember', 'installer' ); ?>
                </a>
            </div>

            <div class="js-yes-section" style="display: none;">
                <p class="otgs-installer-notice-status-item"
                   data-alternative="<?php echo esc_html( $extendAlternative ); ?>"
                >
					<?php echo esc_html( $extendInfo ); ?>
                </p>
                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                   href="<?php echo esc_url( $model->productUrl . '/account' ); ?>"
                >
					<?php echo esc_html( $extendButton ); ?>
                </a>
            </div>

            <div class="js-no-section" style="display: none;">
                <p class="otgs-installer-notice-status-item"
                   data-alternative="<?php echo esc_html( $discountAlternative ); ?>"
                >
					<?php echo esc_html( $discountQuestion ); ?>
                </p>
                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                   href="<?php echo esc_url( $model->endUserRenewalUrl . '&token=' . $model->siteKey ); ?>">
					<?php echo esc_html( $discountButton ); ?>
                </a>
            </div>

            <div class="js-find-account-section" style="display: none;">
                <p class="otgs-installer-notice-status-item"><?php echo esc_html( $findAccountInfo ); ?></p>
                <div class="otgs-installer-notice-status-item-wrapper">
                    <input type="text"
                           placeholder="<?php echo esc_attr( $findAccountPlaceholder ); ?>"/>
                    <a class="js-find-account otgs-installer-notice-status-item otgs-installer-notice-status-item-btn btn-disabled"
                       href=""
                       data-repository="<?php echo $model->repoId ?>"
                       data-nonce="<?php echo $model->findAccountNonce ?>"
                    >
						<?php echo esc_html( $findAccountButton ); ?>
                    </a>
                </div>
            </div>
		<?php else: ?>
            <div class="js-yes-section">
                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                   href="<?php echo esc_url( $model->productUrl . '/account' ); ?>"
                >
					<?php echo esc_html( $extendButton ); ?>
                </a>
            </div>
		<?php
		endif;
	}
}