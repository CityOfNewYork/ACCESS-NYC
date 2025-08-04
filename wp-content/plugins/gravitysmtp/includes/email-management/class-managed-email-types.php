<?php

namespace Gravity_Forms\Gravity_SMTP;

class Managed_Email_Types {

	public function types() {
		$types = array_merge(
			$this->email_type_change_of_admin_email(),
			$this->email_type_automatic_updates(),
			$this->email_type_comments()
		);

		if (
			( current_user_can( 'manage_sites' )
			  || current_user_can( 'create_sites' )
			  || current_user_can( 'manage_network' )
			  || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS ) )
		) {
			$types = array_merge( $types, $this->email_type_new_site() );
		}

		$types = array_merge( $types,
			$this->email_type_new_user(),
			$this->email_type_personal_data_requests(),
			$this->email_type_change_of_user_email_password()
		);

		return $types;
	}

	private function email_type_change_of_admin_email() {
		$items = array(
			array(
				'key'              => 'change_of_site_admin_email_address_is_attempted',
				'label'            => __( 'Site Admin Email Change Attempt', 'gravitysmtp' ),
				'category'         => __( 'Admin', 'gravitysmtp' ),
				'description'      => __( 'Sent to the proposed new Site Admin email address when the Administration Email Address is changed in WordPress General Settings.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_action( 'admin_init', function() {
						remove_action( 'add_option_new_admin_email', 'update_option_new_admin_email' );
						remove_action( 'update_option_new_admin_email', 'update_option_new_admin_email' );
					}, 10 );
				},
			),
			array(
				'key'              => 'site_admin_email_address_is_changed',
				'label'            => __( 'Site Admin Email Changed', 'gravitysmtp' ),
				'category'         => __( 'Admin', 'gravitysmtp' ),
				'description'      => __( 'Sent to the old Site Admin email address when the Administration Email Address change has been confirmed.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_site_admin_email_change_email', '__return_false' );
				},
			)
		);

		if ( current_user_can( 'manage_network_users' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS ) ) {
			$items[] = array(
				'key'              => 'change_of_network_admin_email_address_is_attempted',
				'label'            => __( 'Network Admin Email Change Attempt', 'gravitysmtp' ),
				'category'         => __( 'Admin', 'gravitysmtp' ),
				'description'      => __( 'Sent to the proposed new Network Admin email address when the Network Admin Email is changed in Network Settings.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'add_site_option_new_admin_email', 'update_network_option_new_admin_email' );
					remove_action( 'update_site_option_new_admin_email', 'update_network_option_new_admin_email' );
				},
			);

			$items[] = array(
				'key'              => 'network_admin_email_address_is_changed',
				'label'            => __( 'Network Admin Email Changed', 'gravitysmtp' ),
				'category'         => __( 'Admin', 'gravitysmtp' ),
				'description'      => __( 'Sent to the old Network Admin email address when the Network Admin Email change has been confirmed.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_network_admin_email_change_email', '__return_false' );
				},
			);
		}

		return $items;
	}

	private function email_type_change_of_user_email_password() {
		return array(
			array(
				'key'              => 'user_or_administrator_requests_a_password_reset',
				'label'            => __( 'User Password Reset Request', 'gravitysmtp' ),
				'category'         => __( 'User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the User email address when a password reset is requested by the User or an administrator.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_retrieve_password_email', '__return_false' );
				},
			),
			array(
				'key'              => 'user_resets_their_password',
				'label'            => __( 'User Password Reset', 'gravitysmtp' ),
				'category'         => __( 'User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Site Admin email address when a User resets their password.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'after_password_reset', 'wp_password_change_notification' );
					add_filter( 'woocommerce_disable_password_change_notification', '__return_true' );
				},
			),
			array(
				'key'              => 'user_changes_their_password',
				'label'            => __( 'User Password Changed', 'gravitysmtp' ),
				'category'         => __( 'User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the User when their password is changed.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_password_change_email', '__return_false' );
				},
			),
			array(
				'key'              => 'user_attempts_to_change_their_email_address',
				'label'            => __( 'User Email Change Attempt', 'gravitysmtp' ),
				'category'         => __( 'User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the proposed new User email address when a User attempts to change their email address.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
				},
			),
			array(
				'key'              => 'user_changes_their_email_address',
				'label'            => __( 'User Email Changed', 'gravitysmtp' ),
				'category'         => __( 'User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the User email address when they confirm their email address change.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_email_change_email', '__return_false' );
				},
			),
		);
	}

	private function email_type_personal_data_requests() {
		$admin_type = is_multisite() ? __( 'Network Admin', 'gravitysmtp' ) : __( 'Site Admin', 'gravitysmtp' );
		return array(
			array(
				'key'              => 'user_confirms_personal_data_export_or_erasure_request',
				'label'            => __( 'Personal Data Request Confirmation', 'gravitysmtp' ),
				'category'         => __( 'Personal Data', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Site Admin email address when a User confirms a Personal Data Export or Erasure Request.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'user_request_action_confirmed', '_wp_privacy_send_request_confirmation_notification' );
				},
			),
			array(
				'key'              => 'site_admin_sends_link_to_a_personal_data_export',
				'label'            => __( 'Personal Data Export Sent', 'gravitysmtp' ),
				'category'         => __( 'Personal Data', 'gravitysmtp' ),
				/* translators: %s: Site Admin or Network Admin */
				'description'      => sprintf( __( 'Sent to the Requester email address when an administrator responds to a Personal Data Export Request.', 'gravitysmtp' ), $admin_type ),
				'disable_callback' => function () {
					remove_action( 'wp_privacy_personal_data_export_page', 'wp_privacy_send_personal_data_export_email' );
				},
			),
			array(
				'key'              => 'site_admin_erases_personal_data_to_fulfill_a_data_erasure_request',
				'label'            => __( 'Personal Data Erased', 'gravitysmtp' ),
				'category'         => __( 'Personal Data', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Requester email address when an administrator has processed a Personal Data Erasure Request.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'wp_privacy_personal_data_erased', '_wp_privacy_send_erasure_fulfillment_notification' );
				},
			),
		);
	}

	private function email_type_automatic_updates() {
		$admin_type = is_multisite() ? __( 'Network Admin', 'gravitysmtp' ) : __( 'Site Admin', 'gravitysmtp' );
		return array(
			array(
				'key'              => 'automatic_plugin_updates',
				'label'            => __( 'Automatic Plugin Update', 'gravitysmtp' ),
				'category'         => __( 'Automatic Updates', 'gravitysmtp' ),
				/* translators: %s: Site Admin or Network Admin */
				'description'      => sprintf( __( 'Sent to the %s when a background automatic update to a plugin completes or fails.', 'gravitysmtp' ), $admin_type ),
				'disable_callback' => function () {
					add_filter( 'auto_plugin_update_send_email', '__return_false' );
				},
			),
			array(
				'key'              => 'automatic_theme_updates',
				'label'            => __( 'Automatic Theme Update', 'gravitysmtp' ),
				'category'         => __( 'Automatic Updates', 'gravitysmtp' ),
				/* translators: %s: Site Admin or Network Admin */
				'description'      => sprintf( __( 'Sent to the %s when a background automatic update to a theme completes or fails.', 'gravitysmtp' ), $admin_type ),
				'disable_callback' => function () {
					add_filter( 'auto_theme_update_send_email', '__return_false' );
				},
			),
			array(
				'key'              => 'automatic_core_update',
				'label'            => __( 'Automatic Core Update', 'gravitysmtp' ),
				'category'         => __( 'Automatic Updates', 'gravitysmtp' ),
				/* translators: %s: Site Admin or Network Admin */
				'description'      => sprintf( __( 'Sent to the %s when a background automatic update to WordPress core completes or fails.', 'gravitysmtp' ), $admin_type ),
				'disable_callback' => function () {
					add_filter( 'auto_core_update_send_email', '__return_false' );
					add_filter( 'send_core_update_notification_email', '__return_false' );
				},
			),
			array(
				'key'              => 'full_log_of_background_update_results',
				'label'            => __( 'Full Background Update Result Log', 'gravitysmtp' ),
				'category'         => __( 'Automatic Updates', 'gravitysmtp' ),
				/* translators: %s: Site Admin or Network Admin */
				'description'      => sprintf( __( 'Sent to the %s. Only sent when using a development version of WordPress.', 'gravitysmtp' ), $admin_type ),
				'disable_callback' => function () {
					add_filter( 'automatic_updates_send_debug_email', '__return_false' );
				},
			),
		);
	}

	private function email_type_new_user() {
		$items = array();

		if ( current_user_can( 'manage_network_users' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS )  ) {
			$items[] = array(
				'key'              => 'a_new_user_is_invited_to_join_a_site',
				'label'            => __( 'New User Invited to Site', 'gravitysmtp' ),
				'category'         => __( 'New User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the User email address when they are invited to join a site.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'wpmu_signup_user_notification', '__return_false' );
				},
			);

			$items[] = array(
				'key'              => 'a_new_user_account_is_created',
				'label'            => __( 'New User Created', 'gravitysmtp' ),
				'category'         => __( 'New User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Network Admin email address when a new user is created via wpmu_create_user().', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'wpmu_new_user', 'newuser_notify_siteadmin' );
				},
			);

			$items[] = array(
				'key'              => 'a_user_is_added,_or_their_account_activation_is_successful',
				'label'            => __( 'User Added or Activated', 'gravitysmtp' ),
				'category'         => __( 'New User', 'gravitysmtp' ),
				'description'      => __( 'Sent to the User email address when they are added or their account is activated.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'wpmu_welcome_user_notification', '__return_false' );
				},
			);
		}

		$items[] = array(
			'key'              => 'a_new_user_is_created_admin',
			'label'            => __( 'New User Admin Notification', 'gravitysmtp' ),
			'category'         => __( 'New User', 'gravitysmtp' ),
			'description'      => __( 'Sent to the Site Admin email address when a new user is created.', 'gravitysmtp' ),
			'disable_callback' => function () {
				add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
			},
		);

		$items[] = array(
			'key'              => 'a_new_user_is_created_user',
			'label'            => __( 'New User Notification', 'gravitysmtp' ),
			'category'         => __( 'New User', 'gravitysmtp' ),
			'description'      => __( 'Sent to the User email address when their account is created.', 'gravitysmtp' ),
			'disable_callback' => function () {
				add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
			},
		);

		return $items;
	}

	private function email_type_comments() {
		return array(
			array(
				'key'              => 'comment_is_awaiting_moderation',
				'label'            => __( 'Comment Awaiting Moderation', 'gravitysmtp' ),
				'category'         => __( 'Comments', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Site Admin and Post Author (if they have the ability to edit comments) email address when a user or visitor submits a comment and it is awaiting moderation.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'notify_moderator', '__return_false' );
				},
			),
			array(
				'key'              => 'comment_is_published',
				'label'            => __( 'Comment is Published', 'gravitysmtp' ),
				'category'         => __( 'Comments', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Post Author email address when a comment is published.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'notify_post_author', '__return_false' );
				},
			),
		);
	}

	private function email_type_new_site() {
		$items = array();

		if ( current_user_can( 'manage_sites' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS )  ) {
			$items[] = array(
				'key'              => 'a_new_site_is_created',
				'label'            => __( 'New Site Created', 'gravitysmtp' ),
				'category'         => __( 'New Site', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Network Admin email address when a New Site is created from Network Admin.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'send_new_site_email', '__return_false' );
				},
			);
		}

		if ( current_user_can( 'create_sites' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS )  ) {
			$items[] = array(
				'key'              => 'user_registers_for_a_new_site',
				'label'            => __( 'User Registers New Site', 'gravitysmtp' ),
				'category'         => __( 'New Site', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Site Admin email address when a visitor registers a new user account and site when site registration is enabled.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'wpmu_signup_blog_notification', '__return_false' );
				},
			);
		}

		if ( current_user_can( 'manage_network' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS )  ) {
			$items[] = array(
				'key'              => 'user_activates_their_new_site_or_site_added_from_network_admin',
				'label'            => __( 'New Site Network Admin Notification', 'gravitysmtp' ),
				'category'         => __( 'New Site', 'gravitysmtp' ),
				'description'      => __( 'Sent to the Network Admin email address when a user activates a new site, or a site is added from the Network Admin.', 'gravitysmtp' ),
				'disable_callback' => function () {
					remove_action( 'wpmu_new_blog', 'newblog_notify_siteadmin' );
					remove_action( 'wp_initialize_site', 'newblog_notify_siteadmin' );
				},
			);
		}

		if ( current_user_can( 'manage_sites' ) || ( defined( 'GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS' ) && GRAVITYSMTP_DISPLAY_ALL_EMAIL_SETTINGS )  ) {
			$items[] = array(
				'key'              => 'user_activates_their_new_site_or_site_added_from_network_admin_site_admin',
				'label'            => __( 'New Site Admin Notification', 'gravitysmtp' ),
				'category'         => __( 'New Site', 'gravitysmtp' ),
				'description'      => __( 'Sent to new Site Admin email address when user activates a new site, or a site is added from the Network Admin.', 'gravitysmtp' ),
				'disable_callback' => function () {
					add_filter( 'wpmu_welcome_notification', '__return_false' );
				},
			);
		}

		return $items;
	}
}
