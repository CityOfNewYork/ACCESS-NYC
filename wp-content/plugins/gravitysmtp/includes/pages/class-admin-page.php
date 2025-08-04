<?php

namespace Gravity_Forms\Gravity_SMTP\Pages;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Users\Roles;

class Admin_Page {

	const ICON_DATA_URI = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDEwMDAgMTAwMCI+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTkwNS4yOCA2OTMuOTYgNjE5Ljg4IDEzNC40Yy03LjMtMTIuMzMtMjUuMjctMjIuMzktMzkuOC0yMi4zOUg0MTkuOWMtMTQuNTMgMC0zMi40MyAxMC4xMi0zOS44NyAyMi4zOUw5NC41NiA2OTMuOTZjLTcuMzcgMTIuMzMtMy4zIDI4LjY1LS4yOCA0NC45OGwyMy45NCAxMjUuNjNjMy4zIDE0LjYgMjQuOTkgMjIuNTkgMzkuNTkgMjIuNTlsMzQyLjU0LjgyIDM0MS43Ny0uODJjMTQuNiAwIDM2LjUtNy45OSAzOS41OS0yMi41OWwyMy45NC0xMjUuNjNjMy4zLTE0Ljg4IDcuMDktMzIuNjUtLjI4LTQ0Ljk4aC0uMDdabS0xNzguNSA1MS4zOC0xMzQuMzUtNDAuMjljLTE5LjE2LTYuMjctMzIuMjItMTAuNTQtMzguODItMjMuNDhsLTM4LjgyLTEwNy41OWMtMy4zLTYuNDEtNy41OC05LjQ0LTExLjkzLTkuNDRoLS4zNWMtLjA3IDAtLjI4LS4wNy0uNDIgMC00LjI4LjA3LTguNTcgMy4xLTExLjg2IDkuNWwtMzguODIgMTA3LjU5Yy02LjYgMTIuOTUtMTkuNTEgMTYuNjctMzguODIgMjMuNDhMMjc4LjE3IDc0NS40Yy0xNC43NCAwLTIwLjcxLTEwLjE5LTEzLjM0LTIyLjczbDIxOC4zLTQzNi43NWM4LjU2LTE3Ljg0IDI4LjQzLTE5LjQ5IDM4LjgyIDBsMjE4LjMgNDM2Ljc1YzcuMzcgMTIuNDcgMS40IDIyLjczLTEzLjM0IDIyLjczbC0uMTQtLjA3WiIgc3R5bGU9InN0cm9rZS13aWR0aDowIi8+PC9zdmc+';

	public function admin_pages() {
		add_menu_page( 'Gravity SMTP', __( 'SMTP', 'gravitysmtp' ), Roles::VIEW_EMAIL_LOG, 'gravitysmtp-dashboard', [ $this, 'app_page' ], self::ICON_DATA_URI, 81 );
		add_submenu_page( 'gravitysmtp-dashboard', __( 'Dashboard', 'gravitysmtp' ), __( 'Dashboard', 'gravitysmtp' ), Roles::VIEW_DASHBOARD, 'gravitysmtp-dashboard', [ $this, 'app_page' ] );
		add_submenu_page( 'gravitysmtp-dashboard', __( 'Email Log', 'gravitysmtp' ), __( 'Email Log', 'gravitysmtp' ), Roles::VIEW_EMAIL_LOG, 'gravitysmtp-activity-log', [ $this, 'app_page' ] );

		if ( Feature_Flag_Manager::is_enabled( 'email_suppression' ) ) {
			add_submenu_page( 'gravitysmtp-dashboard', __( 'Suppressions', 'gravitysmtp' ), __( 'Suppressions', 'gravitysmtp' ), Roles::VIEW_EMAIL_SUPPRESSION_SETTINGS, 'gravitysmtp-suppression', [ $this, 'app_page' ] );
		}

		add_submenu_page( 'gravitysmtp-dashboard', __( 'Settings', 'gravitysmtp' ), __( 'Settings', 'gravitysmtp' ), Roles::VIEW_GENERAL_SETTINGS, 'gravitysmtp-settings', [ $this, 'app_page' ] );
		add_submenu_page( 'gravitysmtp-dashboard', __( 'Tools', 'gravitysmtp' ), __( 'Tools', 'gravitysmtp' ), Roles::VIEW_TOOLS, 'gravitysmtp-tools', [ $this, 'app_page' ] );
	}

	public function app_page() {
		?>
		<div class="gravitysmtp-admin">
			<?php do_action( 'gravitysmtp_app_body' ); ?>
		</div>
		<?php
	}
}
