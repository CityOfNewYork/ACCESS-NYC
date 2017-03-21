<?php
/*
Plugin Name: Google Authenticator
Plugin URI: http://henrik.schack.dk/google-authenticator-for-wordpress
Description: Two-Factor Authentication for WordPress using the Android/iPhone/Blackberry app as One Time Password generator.
Author: Henrik Schack
Version: 0.48
Author URI: http://henrik.schack.dk/
Compatibility: WordPress 4.5
Text Domain: google-authenticator
Domain Path: /lang

----------------------------------------------------------------------------

	Thanks to Bryan Ruiz for his Base32 encode/decode class, found at php.net.
	Thanks to Tobias Bäthge for his major code rewrite and German translation.
	Thanks to Pascal de Bruijn for his relaxed mode idea.
	Thanks to Daniel Werl for his usability tips.
	Thanks to Dion Hulse for his bugfixes.
	Thanks to Aldo Latino for his Italian translation.
	Thanks to Kaijia Feng for his Simplified Chinese translation.
	Thanks to Ian Dunn for fixing some depricated function calls.
	Thanks to Kimmo Suominen for fixing the iPhone description issue.
	Thanks to Alex Concha for some security tips.
	Thanks to Sébastien Prunier for his Spanish and French translations.

----------------------------------------------------------------------------

    Copyright 2013  Henrik Schack  (email : henrik@schack.dk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class GoogleAuthenticator {

static $instance; // to store a reference to the plugin, allows other plugins to remove actions

/**
 * Constructor, entry point of the plugin
 */
function __construct() {
    self::$instance = $this;
    add_action( 'init', array( $this, 'init' ) );
}

/**
 * Initialization, Hooks, and localization
 */
function init() {
    require_once( 'base32.php' );
    
    add_action( 'login_form', array( $this, 'loginform' ) );
    add_action( 'login_footer', array( $this, 'loginfooter' ) );
    add_filter( 'authenticate', array( $this, 'check_otp' ), 50, 3 );

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        add_action( 'wp_ajax_GoogleAuthenticator_action', array( $this, 'ajax_callback' ) );
    }

    add_action( 'personal_options_update', array( $this, 'personal_options_update' ) );
    add_action( 'profile_personal_options', array( $this, 'profile_personal_options' ) );
    add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ) );
    add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ) );

	add_action('admin_enqueue_scripts', array($this, 'add_qrcode_script'));

    load_plugin_textdomain( 'google-authenticator', false, basename( dirname( __FILE__ ) ) . '/lang' );
}


/**
 * Check the verification code entered by the user.
 */
function verify( $secretkey, $thistry, $relaxedmode, $lasttimeslot ) {

	// Did the user enter 6 digits ?
	if ( strlen( $thistry ) != 6) {
		return false;
	} else {
		$thistry = intval ( $thistry );
	}

	// If user is running in relaxed mode, we allow more time drifting
	// ±4 min, as opposed to ± 30 seconds in normal mode.
	if ( $relaxedmode == 'enabled' ) {
		$firstcount = -8;
		$lastcount  =  8; 
	} else {
		$firstcount = -1;
		$lastcount  =  1; 	
	}
	
	$tm = floor( time() / 30 );
	
	$secretkey=Base32::decode($secretkey);
	// Keys from 30 seconds before and after are valid aswell.
	for ($i=$firstcount; $i<=$lastcount; $i++) {
		// Pack time into binary string
		$time=chr(0).chr(0).chr(0).chr(0).pack('N*',$tm+$i);
		// Hash it with users secret key
		$hm = hash_hmac( 'SHA1', $time, $secretkey, true );
		// Use last nipple of result as index/offset
		$offset = ord(substr($hm,-1)) & 0x0F;
		// grab 4 bytes of the result
		$hashpart=substr($hm,$offset,4);
		// Unpak binary value
		$value=unpack("N",$hashpart);
		$value=$value[1];
		// Only 32 bits
		$value = $value & 0x7FFFFFFF;
		$value = $value % 1000000;
		if ( $value === $thistry ) {
			// Check for replay (Man-in-the-middle) attack.
			// Since this is not Star Trek, time can only move forward,
			// meaning current login attempt has to be in the future compared to
			// last successful login.
			if ( $lasttimeslot >= ($tm+$i) ) {
				error_log("Google Authenticator plugin: Man-in-the-middle attack detected (Could also be 2 legit login attempts within the same 30 second period)");
				return false;
			}
			// Return timeslot in which login happened.
			return $tm+$i;
		}
	}
	return false;
}

/**
 * Create a new random secret for the Google Authenticator app.
 * 16 characters, randomly chosen from the allowed Base32 characters
 * equals 10 bytes = 80 bits, as 256^10 = 32^16 = 2^80
 */ 
function create_secret() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // allowed characters in Base32
    $secret = '';
    for ( $i = 0; $i < 16; $i++ ) {
        $secret .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
    }
    return $secret;
}

/**
 * Add the script to generate QR codes.
 */
function add_qrcode_script() {
    wp_enqueue_script('jquery');
    wp_register_script('qrcode_script', plugins_url('jquery.qrcode.min.js', __FILE__),array("jquery"));
    wp_enqueue_script('qrcode_script');
}

/**
 * Add verification code field to login form.
 */
function loginform() {
    echo "\t<p>\n";
    echo "\t\t<label title=\"".__('If you don\'t have Google Authenticator enabled for your WordPress account, leave this field empty.','google-authenticator')."\">".__('Google Authenticator code','google-authenticator')."<span id=\"google-auth-info\"></span><br />\n";
    echo "\t\t<input type=\"text\" name=\"googleotp\" id=\"user_email\" class=\"input\" value=\"\" size=\"20\" style=\"ime-mode: inactive;\" /></label>\n";
    echo "\t</p>\n";
}

/**
 * Disable autocomplete on Google Authenticator code input field.
 */
function loginfooter() {
    echo "\n<script type=\"text/javascript\">\n";
    echo "\ttry{\n";
    echo "\t\tdocument.getElementById('user_email').setAttribute('autocomplete','off');\n";
    echo "\t} catch(e){}\n";
    echo "</script>\n";
}

/**
 * Login form handling.
 * Check Google Authenticator verification code, if user has been setup to do so.
 * @param wordpressuser
 * @return user/loginstatus
 */
function check_otp( $user, $username = '', $password = '' ) {
	// Store result of loginprocess, so far.
	$userstate = $user;

	// Get information on user, we need this in case an app password has been enabled,
	// since the $user var only contain an error at this point in the login flow.
	if ( get_user_by( 'email', $username ) === false ) {
		$user = get_user_by( 'login', $username );
	} else {
		$user = get_user_by( 'email', $username );
	}

	// Does the user have the Google Authenticator enabled ?
	if ( isset( $user->ID ) && trim(get_user_option( 'googleauthenticator_enabled', $user->ID ) ) == 'enabled' ) {

		// Get the users secret
		$GA_secret = trim( get_user_option( 'googleauthenticator_secret', $user->ID ) );
		
		// Figure out if user is using relaxed mode ?
		$GA_relaxedmode = trim( get_user_option( 'googleauthenticator_relaxedmode', $user->ID ) );
		
		// Get the verification code entered by the user trying to login
		if ( !empty( $_POST['googleotp'] )) { // Prevent PHP notices when using app password login
			$otp = trim( $_POST[ 'googleotp' ] );
		} else {
			$otp = '';
		}
		// When was the last successful login performed ?
		$lasttimeslot = trim( get_user_option( 'googleauthenticator_lasttimeslot', $user->ID ) );
		// Valid code ?
		if ( $timeslot = $this->verify( $GA_secret, $otp, $GA_relaxedmode, $lasttimeslot ) ) {
			// Store the timeslot in which login was successful.
			update_user_option( $user->ID, 'googleauthenticator_lasttimeslot', $timeslot, true );
			return $userstate;
		} else {
			// No, lets see if an app password is enabled, and this is an XMLRPC / APP login ?
			if ( trim( get_user_option( 'googleauthenticator_pwdenabled', $user->ID ) ) == 'enabled' && ( defined('XMLRPC_REQUEST') || defined('APP_REQUEST') ) ) {
				$GA_passwords 	= json_decode(  get_user_option( 'googleauthenticator_passwords', $user->ID ) );
				$passwordhash	= trim($GA_passwords->{'password'} );
				$usersha1		= sha1( strtoupper( str_replace( ' ', '', $password ) ) );
				if ( $passwordhash == $usersha1 ) { // ToDo: Remove after some time when users have migrated to new format
					return new WP_User( $user->ID );
				  // Try the new version based on thee wp_hash_password	function
				} elseif (wp_check_password( strtoupper( str_replace( ' ', '', $password ) ), $passwordhash)) {
					return new WP_User( $user->ID );
				} else {
					// Wrong XMLRPC/APP password !
					return new WP_Error( 'invalid_google_authenticator_password', __( '<strong>ERROR</strong>: The Google Authenticator password is incorrect.', 'google-authenticator' ) );
				} 		 
			} else {
				return new WP_Error( 'invalid_google_authenticator_token', __( '<strong>ERROR</strong>: The Google Authenticator code is incorrect or has expired.', 'google-authenticator' ) );
			}	
		}
	}
	// Google Authenticator isn't enabled for this account,
	// just resume normal authentication.
	return $userstate;
}


/**
 * Extend personal profile page with Google Authenticator settings.
 */
function profile_personal_options() {
	global $user_id, $is_profile_page;

	// If editing of Google Authenticator settings has been disabled, just return
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	if ( $GA_hidefromuser == 'enabled') return;
	
	$GA_secret			= trim( get_user_option( 'googleauthenticator_secret', $user_id ) );
	$GA_enabled			= trim( get_user_option( 'googleauthenticator_enabled', $user_id ) );
	$GA_relaxedmode		= trim( get_user_option( 'googleauthenticator_relaxedmode', $user_id ) );
	$GA_description		= trim( get_user_option( 'googleauthenticator_description', $user_id ) );
	$GA_pwdenabled		= trim( get_user_option( 'googleauthenticator_pwdenabled', $user_id ) );
	$GA_password		= trim( get_user_option( 'googleauthenticator_passwords', $user_id ) );
	
	// We dont store the generated app password in cleartext so there is no point in trying
	// to show the user anything except from the fact that a password exists.
	if ( $GA_password != '' ) {
		$GA_password = "XXXX XXXX XXXX XXXX";
	}

	// In case the user has no secret ready (new install), we create one.
	if ( '' == $GA_secret ) {
		$GA_secret = $this->create_secret();
	}
	
	// Use "WordPress Blog" as default description
	if ( '' == $GA_description ) {
		$GA_description = __( 'WordPressBlog', 'google-authenticator' );
	}
	
	echo "<h3>".__( 'Google Authenticator Settings', 'google-authenticator' )."</h3>\n";

	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";
	echo "<tr>\n";
	echo "<th scope=\"row\">".__( 'Active', 'google-authenticator' )."</th>\n";
	echo "<td>\n";
	echo "<input name=\"GA_enabled\" id=\"GA_enabled\" class=\"tog\" type=\"checkbox\"" . checked( $GA_enabled, 'enabled', false ) . "/>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ( $is_profile_page || IS_PROFILE_PAGE ) {
		echo "<tr>\n";
		echo "<th scope=\"row\">".__( 'Relaxed mode', 'google-authenticator' )."</th>\n";
		echo "<td>\n";
		echo "<input name=\"GA_relaxedmode\" id=\"GA_relaxedmode\" class=\"tog\" type=\"checkbox\"" . checked( $GA_relaxedmode, 'enabled', false ) . "/><span class=\"description\">".__(' Relaxed mode allows for more time drifting on your phone clock (&#177;4 min).','google-authenticator')."</span>\n";
		echo "</td>\n";
		echo "</tr>\n";
		
		echo "<tr>\n";
		echo "<th><label for=\"GA_description\">".__('Description','google-authenticator')."</label></th>\n";
		echo "<td><input name=\"GA_description\" id=\"GA_description\" value=\"{$GA_description}\"  type=\"text\" size=\"25\" /><span class=\"description\">".__(' Description that you\'ll see in the Google Authenticator app on your phone.','google-authenticator')."</span><br /></td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<th><label for=\"GA_secret\">".__('Secret','google-authenticator')."</label></th>\n";
		echo "<td>\n";
		echo "<input name=\"GA_secret\" id=\"GA_secret\" value=\"{$GA_secret}\" readonly=\"readonly\"  type=\"text\" size=\"25\" />";
		echo "<input name=\"GA_newsecret\" id=\"GA_newsecret\" value=\"".__("Create new secret",'google-authenticator')."\"   type=\"button\" class=\"button\" />";
		echo "<input name=\"show_qr\" id=\"show_qr\" value=\"".__("Show/Hide QR code",'google-authenticator')."\"   type=\"button\" class=\"button\" onclick=\"ShowOrHideQRCode();\" />";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<td><div id=\"GA_QR_INFO\" style=\"display: none\" >";
		echo "<div id=\"GA_QRCODE\"/></div>";

		echo '<span class="description"><br/> ' . __( 'Scan this with the Google Authenticator app.', 'google-authenticator' ) . '</span>';
		echo "</div></td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<th scope=\"row\">".__( 'Enable App password', 'google-authenticator' )."</th>\n";
		echo "<td>\n";
		echo "<input name=\"GA_pwdenabled\" id=\"GA_pwdenabled\" class=\"tog\" type=\"checkbox\"" . checked( $GA_pwdenabled, 'enabled', false ) . "/><span class=\"description\">".__(' Enabling an App password will decrease your overall login security.','google-authenticator')."</span>\n";
		echo "</td>\n";
		echo "</tr>\n";
		
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<td>\n";
		echo "<input name=\"GA_password\" id=\"GA_password\" readonly=\"readonly\" value=\"".$GA_password."\" type=\"text\" size=\"25\" />";
		echo "<input name=\"GA_createpassword\" id=\"GA_createpassword\" value=\"".__("Create new password",'google-authenticator')."\"   type=\"button\" class=\"button\" />";
		echo "<span class=\"description\" id=\"GA_passworddesc\"> ".__(' Password is not stored in cleartext, this is your only chance to see it.','google-authenticator')."</span>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "</tbody></table>\n";
	echo "<script type=\"text/javascript\">\n";
	echo "var GAnonce='".wp_create_nonce('GoogleAuthenticatoraction')."';\n";

  	echo <<<ENDOFJS
  	//Create new secret and display it
	jQuery('#GA_newsecret').bind('click', function() {
		// Remove existing QRCode
		jQuery('#GA_QRCODE').html("");
		var data=new Object();
		data['action']	= 'GoogleAuthenticator_action';
		data['nonce']	= GAnonce;
		jQuery.post(ajaxurl, data, function(response) {
  			jQuery('#GA_secret').val(response['new-secret']);
  			var qrcode="otpauth://totp/WordPress:"+escape(jQuery('#GA_description').val())+"?secret="+jQuery('#GA_secret').val()+"&issuer=WordPress";
			jQuery('#GA_QRCODE').qrcode(qrcode);
 			jQuery('#GA_QR_INFO').show('slow');
  		});  	
	});

	// If the user starts modifying the description, hide the qrcode
	jQuery('#GA_description').bind('focus blur change keyup', function() {
		// Only remove QR Code if it's visible
		if (jQuery('#GA_QR_INFO').is(':visible')) {
			jQuery('#GA_QR_INFO').hide('slow');
			jQuery('#GA_QRCODE').html("");
  		}
	});

	// Create new app password
	jQuery('#GA_createpassword').bind('click',function() {
		var data=new Object();
		data['action']	= 'GoogleAuthenticator_action';
		data['nonce']	= GAnonce;
		data['save']	= 1;
		jQuery.post(ajaxurl, data, function(response) {
  			jQuery('#GA_password').val(response['new-secret'].match(new RegExp(".{0,4}","g")).join(' '));
  			jQuery('#GA_passworddesc').show();
  		});  	
	});
	
	jQuery('#GA_enabled').bind('change',function() {
		GoogleAuthenticator_apppasswordcontrol();
	});

	jQuery(document).ready(function() {
		jQuery('#GA_passworddesc').hide();
		GoogleAuthenticator_apppasswordcontrol();
	});
	
	function GoogleAuthenticator_apppasswordcontrol() {
		if (jQuery('#GA_enabled').is(':checked')) {
			jQuery('#GA_pwdenabled').removeAttr('disabled');
			jQuery('#GA_createpassword').removeAttr('disabled');
		} else {
			jQuery('#GA_pwdenabled').removeAttr('checked')
			jQuery('#GA_pwdenabled').attr('disabled', true);
			jQuery('#GA_createpassword').attr('disabled', true);
		}
	}

	function ShowOrHideQRCode() {
		if (jQuery('#GA_QR_INFO').is(':hidden')) {
			var qrcode="otpauth://totp/WordPress:"+escape(jQuery('#GA_description').val())+"?secret="+jQuery('#GA_secret').val()+"&issuer=WordPress";
			jQuery('#GA_QRCODE').qrcode(qrcode);
	        jQuery('#GA_QR_INFO').show('slow');
		} else {
			jQuery('#GA_QR_INFO').hide('slow');
			jQuery('#GA_QRCODE').html("");
		}
	}
</script>
ENDOFJS;
}

/**
 * Form handling of Google Authenticator options added to personal profile page (user editing his own profile)
 */
function personal_options_update() {
	global $user_id;

	// If editing of Google Authenticator settings has been disabled, just return
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	if ( $GA_hidefromuser == 'enabled') return;


	$GA_enabled	= ! empty( $_POST['GA_enabled'] );
	$GA_description	= trim( sanitize_text_field($_POST['GA_description'] ) );
	$GA_relaxedmode	= ! empty( $_POST['GA_relaxedmode'] );
	$GA_secret	= trim( $_POST['GA_secret'] );
	$GA_pwdenabled	= ! empty( $_POST['GA_pwdenabled'] );
	$GA_password	= str_replace(' ', '', trim( $_POST['GA_password'] ) );
	
	if ( ! $GA_enabled ) {
		$GA_enabled = 'disabled';
	} else {
		$GA_enabled = 'enabled';
	}

	if ( ! $GA_relaxedmode ) {
		$GA_relaxedmode = 'disabled';
	} else {
		$GA_relaxedmode = 'enabled';
	}


	if ( ! $GA_pwdenabled ) {
		$GA_pwdenabled = 'disabled';
	} else {
		$GA_pwdenabled = 'enabled';
	}
	
	// Only store password if a new one has been generated.
	if (strtoupper($GA_password) != 'XXXXXXXXXXXXXXXX' ) {
		// Store the password in a format that can be expanded easily later on if needed.
		$GA_password = array( 'appname' => 'Default', 'password' => wp_hash_password( $GA_password ) );
		update_user_option( $user_id, 'googleauthenticator_passwords', json_encode( $GA_password ), true );
	}
	
	update_user_option( $user_id, 'googleauthenticator_enabled', $GA_enabled, true );
	update_user_option( $user_id, 'googleauthenticator_description', $GA_description, true );
	update_user_option( $user_id, 'googleauthenticator_relaxedmode', $GA_relaxedmode, true );
	update_user_option( $user_id, 'googleauthenticator_secret', $GA_secret, true );
	update_user_option( $user_id, 'googleauthenticator_pwdenabled', $GA_pwdenabled, true );

}

/**
 * Extend profile page with ability to enable/disable Google Authenticator authentication requirement.
 * Used by an administrator when editing other users.
 */
function edit_user_profile() {
	global $user_id;
	$GA_enabled      = trim( get_user_option( 'googleauthenticator_enabled', $user_id ) );
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	echo "<h3>".__('Google Authenticator Settings','google-authenticator')."</h3>\n";
	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";

	echo "<tr>\n";
	echo "<th scope=\"row\">".__('Hide settings from user','google-authenticator')."</th>\n";
	echo "<td>\n";
	echo "<div><input name=\"GA_hidefromuser\" id=\"GA_hidefromuser\"  class=\"tog\" type=\"checkbox\"" . checked( $GA_hidefromuser, 'enabled', false ) . "/>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<th scope=\"row\">".__('Active','google-authenticator')."</th>\n";
	echo "<td>\n";
	echo "<div><input name=\"GA_enabled\" id=\"GA_enabled\"  class=\"tog\" type=\"checkbox\"" . checked( $GA_enabled, 'enabled', false ) . "/>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</tbody>\n";
	echo "</table>\n";
}

/**
 * Form handling of Google Authenticator options on edit profile page (admin user editing other user)
 */
function edit_user_profile_update() {
	global $user_id;
	
	$GA_enabled	     = ! empty( $_POST['GA_enabled'] );
	$GA_hidefromuser = ! empty( $_POST['GA_hidefromuser'] );

	if ( ! $GA_enabled ) {
		$GA_enabled = 'disabled';
	} else {
		$GA_enabled = 'enabled';
	}

	if ( ! $GA_hidefromuser ) {
		$GA_hidefromuser = 'disabled';
	} else {
		$GA_hidefromuser = 'enabled';
	}

	update_user_option( $user_id, 'googleauthenticator_enabled', $GA_enabled, true );
	update_user_option( $user_id, 'googleauthenticator_hidefromuser', $GA_hidefromuser, true );

}


/**
* AJAX callback function used to generate new secret
*/
function ajax_callback() {
	global $user_id;

	// Some AJAX security.
	check_ajax_referer( 'GoogleAuthenticatoraction', 'nonce' );
	
	// Create new secret.
	$secret = $this->create_secret();

	$result = array( 'new-secret' => $secret );
	header( 'Content-Type: application/json' );
	echo json_encode( $result );

	// die() is required to return a proper result
	die(); 
}

} // end class

$google_authenticator = new GoogleAuthenticator;
?>
