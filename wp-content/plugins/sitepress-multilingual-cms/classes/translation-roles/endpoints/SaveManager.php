<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\partial;

class SaveManager extends SaveUser {

	const TRANSLATION_MANAGER_INSTRUCTIONS_TEMPLATE = 'notification/translation-manager-instructions.twig';

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {

		// $setRole :: WP_User -> WP_User
		$setRole = Fns::tap( invoke( 'add_cap' )->with( User::CAP_MANAGE_TRANSLATIONS ) );

		return self::getUser( $data )
		           ->map( $setRole )
		           ->map( [ self::class, 'sendInstructions' ] )
		           ->map( function( $user ) {
					   do_action( 'wpml_tm_ate_synchronize_managers' );
					   return true;
				   } );
	}

	public static function sendInstructions( \WP_User $manager ) {
		$siteName            = get_option( 'blogname' );
		$translationSetupUrl = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' );
		$adminUser           = User::getCurrent();

		$model = [
			'setup_url'       => esc_url( $translationSetupUrl ),
			'username'        => $manager->display_name,
			'intro_message_1' => sprintf( __( 'You are the Translation Manager for %s. This role lets you manage everything related to translation for this site.', 'sitepress' ), $siteName ),
			'intro_message_2' => __( 'Before you can start sending content to translation, you need to complete a short setup.', 'sitepress' ),
			'setup'           => __( 'Set-up the translation', 'sitepress' ),
			'reminder'        => sprintf( __( '* Remember, your login name for %1$s is %2$s. If you need help with your password, use the password reset in the login page.', 'sitepress' ), $siteName, $manager->user_login ),
			'at_your_service' => __( 'At your service', 'sitepress' ),
			'admin_name'      => $adminUser->display_name,
			'admin_for_site'  => sprintf( __( 'Administrator for %s', 'sitepress' ), $siteName ),
		];

		$to      = $manager->display_name . ' <' . $manager->user_email . '>';
		$message = make( \WPML_TM_Email_Notification_View::class )->render_model(
			$model,
			self::TRANSLATION_MANAGER_INSTRUCTIONS_TEMPLATE
		);
		$subject = sprintf( __( 'You are now the Translation Manager for %s - action needed', 'sitepress' ), $siteName );

		$headers = array(
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=UTF-8',
			'Reply-To: ' . $adminUser->display_name . ' <' . $adminUser->user_email . '>',
		);

		$forceDisplayName = Fns::always( $adminUser->display_name );

		$sendMail = partial( 'wp_mail', $to, $subject, $message, $headers );

		Hooks::callWithFilter( $sendMail, 'wp_mail_from_name', $forceDisplayName );

		return true;
	}
}
