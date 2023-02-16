<?php

namespace OTGS\Installer\Templates\Repository;

class Expired {

    public static function render( $model ) {
        $withProduct      = function ( $str ) use ( $model ) {
            return sprintf( $str, $model->productName );
        };

        $title                  = $withProduct( __( 'You are using an expired account of %s.', 'installer' ) );
        $into                   = __( 'It means you will not receive updates. This can lead to stability and even security issues.', 'installer' );
        $renew                  = __( 'Have you already renewed your license?', 'installer' );
        $refresh                = __( 'Refresh account status', 'installer' );
        ?>
        <div class="otgs-installer-registered otgs-installer-expired clearfix">
            <div class="notice inline otgs-installer-notice otgs-installer-notice-expired otgs-installer-notice-expired-gd">
                <div class="otgs-installer-notice-content">
                    <div class="otgs-installer-notice-content__one">
                        <h2><?php echo esc_html( $title ); ?></h2>
                        <p><?php echo esc_html( $into ); ?></p>
                        <div class="otgs-installer-notice-status">

                            <?php
                            EndUsers::render( $withProduct, $model );
                            RegisteredButtons::render( $model );
                            ?>
                        </div>
                    </div>
                    <div class="otgs-installer-notice-content__two">
                        <h3><?php echo esc_html( $renew ); ?></h3>
                        <a class="update_site_key_js"
                           href="#"
                           data-repository="<?php echo $model->repoId ?>"
                           data-nonce="<?php echo $model->updateSiteKeyNonce ?>"
                        ><?php echo esc_html( $refresh ); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
