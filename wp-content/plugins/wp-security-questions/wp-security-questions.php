<?php
/**
 * WP Security Question class file.
 * @package Forms
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.5
 */

/*
Plugin Name: WP Security Question
Plugin URI: http://www.flippercode.com/
Description:  Protect your wordpress account with security question. Ask security questions on login, forgot password and registration page.
Author: flippercode
Author URI: http://www.flippercode.com/
Version: 1.0.5
Text Domain: wp_security_question
Domain Path: /lang/
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
if( !class_exists( 'FC_Plugin_Base_Lite' ) ) {
   $pluginClass =  plugin_dir_path( __FILE__ ). '/core/class.plugin-lite.php';
   if( file_exists( $pluginClass ) )
   include( $pluginClass );
}
if ( ! class_exists( 'WP_Security_Question' ) and class_exists( 'FC_Plugin_Base_Lite' ) ) {

	/**
	 * Main plugin class
	 * @author Flipper Code <hello@flippercode.com>
	 * @package Posts
	 */
	class WP_Security_Question extends FC_Plugin_Base_Lite
	{
		/**
		 * List of Modules.
		 * @var array
		 */
		private $modules = array();

		/**
		 * Intialize variables, files and call actions.
		 * @var array
		 */
		public function __construct() {
			 error_reporting( E_ERROR | E_PARSE );
			parent::__construct( $this->_plugin_definition() );
			$this->register_hooks();
		}

		function _plugin_definition() {

			$this->pluginPrefix = 'wsq';
			$pluginClasses = array('wsq-form.php','wsq-controller.php','wsq-model.php' );
			$pluginModules = array( 'overview','settings');
			$pluginCssFilesFrontEnd = array( 'wsq-frontend.css' );
			$pluginCssFilesBackendEnd = array('select2.css','wsq-backend.css');
			$pluginJsFilesFrontEnd = array('wsq-frontend.js');
			$pluginJsFilesBackEnd = array('wsq-backend.js','select2.js');
			$pluginData = array('childFileRefrence' => __FILE__,
								'childClassRefrence' => __CLASS__,
								'pluginPrefix' => $this->pluginPrefix,
								'pluginDirectory' => plugin_dir_path( __FILE__ ),
								'pluginTextDomain' => 'wp_security_question',
								'pluginURL' =>  plugin_dir_url( __FILE__ ),
								'dboptions' => 'wthp_option_settings',
								'controller' => 'WSQ_Controller',
								'model' => 'WSQ_Model',
								'pluginLabel' => 'Was This Helpful',
								'pluginClasses' =>  $pluginClasses,
								'pluginmodules' => $pluginModules,
								'pluginmodulesprefix' => 'WSQ_Model_',
								'pluginCssFilesFrontEnd' => $pluginCssFilesFrontEnd,
								'pluginCssFilesBackEnd' => $pluginCssFilesBackendEnd,
								'pluginJsFilesFrontEnd' => $pluginJsFilesFrontEnd,
								'pluginJsFilesBackEnd' => $pluginJsFilesBackEnd,
								'loadCustomizer' => false);

							return $pluginData;
		}

		function register_hooks(){

				add_action( 'init', array( $this, '_init' ) );
				add_action( 'wp_ajax_wsq_ajax_call',array( $this, 'wsq_ajax_call' ) );
				add_action( 'wp_ajax_nopriv_wsq_ajax_call', array( $this, 'wsq_ajax_call' ) );
				add_action( 'show_user_profile', array( $this, 'show_extra_profile_fields' ) );
				add_action( 'edit_user_profile', array( $this, 'show_extra_profile_fields' ) );
				add_action( 'personal_options_update', array( $this, 'save_extra_profile_fields' ) );
				add_action( 'edit_user_profile_update', array( $this, 'save_extra_profile_fields' ) );
				add_action( 'user_register', array( $this, 'save_extra_profile_fields' ) );
				add_action( 'register_form',array( $this, 'show_register_fields' ) );
				add_action( 'login_form', array( $this, 'show_login_fields' ) );
				add_action( 'lostpassword_form', array( $this, 'show_forgot_fields' ) );
				add_filter( 'registration_errors', array( $this, 'check_register_answers' ), 10, 3 );
				add_filter( 'wp_authenticate_user', array( $this, 'check_login_answers' ), 10, 2 );
				add_filter( 'allow_password_reset', array( $this, 'check_forgot_answers' ), 10, 2 );
				add_action( 'wp_login_failed',array( $this, 'track_attempts' ) );

		}
		/**
		 * Ajax Call
		 */
		function wsq_ajax_call() {

			check_ajax_referer( 'wsq-call-nonce', 'nonce' );
			$operation = sanitize_text_field( wp_unslash( $_POST['operation'] ) );
			$value = wp_unslash( $_POST );
			if ( isset( $operation ) ) {
				$this->$operation($value);
			}
			exit;
		}
		function show_login_fields() {
			$wpr_sec_ques_setting = get_option( 'wpr_security_ques_setting' );
			$show = true;
			if ( $show == true && get_option( 'wpr_register_security_ques' ) !== false && ! empty( $wpr_sec_ques_setting['allow_sec_ques_login'] ) ) {
				$this->ask_questions();
			}
		}
		function show_forgot_fields() {
			$wpr_sec_ques_setting = get_option( 'wpr_security_ques_setting' );
			if ( get_option( 'wpr_register_security_ques' ) !== false && ! empty( $wpr_sec_ques_setting['allow_sec_ques_forgot'] ) ) {
				$this->ask_questions();
			}
		}
		function show_register_fields() {
			$wpr_sec_ques_setting = get_option( 'wpr_security_ques_setting' );
			if ( get_option( 'wpr_register_security_ques' ) !== false && ! empty( $wpr_sec_ques_setting['allow_sec_ques_register'] ) ) {
				$this->ask_questions();
			}
		}
		function ask_questions() {

			if ( get_option( 'wpr_register_security_ques' ) !== false ) {
				$security_questions = get_option( 'wpr_register_security_ques' );
				$plugin_settings = get_option( 'wpr_security_ques_setting' );
				$html .= '';
				$html .= '<p><label>'.__( 'Security Question', WSQ_TEXT_DOMAIN )."</label></p><p><select name='seq_ques[]' class='input' style='font-size:14px; height:35px;'>";
				foreach ( $security_questions as $id => $quest ) :
					$html .= "<option value='".$id."'>".$quest.'</option>';
					$questions++;
					endforeach;
					$html .= '</select>';
					$html .= '<label>'.__( 'Your Answer',WSQ_TEXT_DOMAIN );
					$html .= '<br /><input type="text" name="seq_ans[]" id="seq_ans[]" value="" class="input" /></p>';
				echo $html;
			}
		}
		function check_user_input($user_id) {
			if ( defined( 'IGNORE_SECURITY_QUESTIONS' ) ) {
				return true;
			}
			$security_questions = get_option( 'wpr_register_security_ques' );
			$all_userans = get_user_meta( $user_id, 'security_check', true );
			if ( $all_userans ) {
				$check_answer = false;
				$empty_answer = false;
				$incorrect_answer = false;
				$is_error_question = false;
				$is_duplicate_question = false;
				$plugin_settings = get_option( 'wpr_security_ques_setting' );
				$error_message = __( 'Your selected questions or answers are wrong.',WSQ_TEXT_DOMAIN );
				$admin_question_changed = true;
				if ( is_array( $security_questions ) ) {
					foreach ( $all_userans as $question => $answer ) {

						if ( ! array_key_exists( $question, $security_questions ) ) {
							$admin_question_changed = false;
						}
					}
				}
				if ( $admin_question_changed == false ) {
					return true;
				}
				if ( is_array( $_POST['seq_ques'] ) ) {
					foreach ( $_POST['seq_ques'] as $key_ques => $question ) {
						if ( ! array_key_exists( $question,$all_userans ) ) {
							$is_error_question = true;
						} else {
							$user_ques[$question] = $question ;
						}
					}
				}
				if ( $is_error_question == true ) {
					return $user_id = new WP_Error( 'security_question', '<strong>'.__( 'ERROR:',WSQ_TEXT_DOMAIN ).'</strong> '.$error_message );
				}
				if ( $is_error_question == false ) {
					if ( $_POST['seq_ans'] ) :
						foreach ( $_POST['seq_ans'] as $question => $answer ) :
							if ( empty( $answer ) ) {
								$empty_answer = true;
							}
					endforeach;
				endif;
				}

				if ( $empty_answer == true ) {
					return $user = new WP_Error( 'security_question', '<strong>'.__( 'ERROR:',WSQ_TEXT_DOMAIN ).'</strong> '.$error_message );
				}

				$correctanswer = 0;

				if ( $empty_answer == false ) {

					if ( $_POST['seq_ans'] ) :
						foreach ( $_POST['seq_ans'] as $question => $answer ) {

							if ( htmlspecialchars( stripcslashes( $answer ),ENT_QUOTES ) != $all_userans[$_POST['seq_ques'][$question]] ) {
								$incorrect_answer = true;
							}
						}
					endif;

					if ( $incorrect_answer == true ) {
						return $user = new WP_Error( 'security_question', '<strong>'.__( 'ERROR:',WSQ_TEXT_DOMAIN ).'</strong> '.$error_message );
					}
				}
			}
			return true;
		}

		function check_forgot_answers($bool, $user_id) {
			$check_user_input = $this->check_user_input( $user_id );
			return $check_user_input;
		}
		function track_attempts() {
			$cookieValue = unserialize( $_COOKIE['wsq_login_attempts'] );
				$ip = $_SERVER['REMOTE_ADDR'];
			if ( isset( $cookieValue[$ip] ) ) {
				$cookieValue[$ip] = (int) $cookieValue[$ip] + 1;
			} else {
				$cookieValue[$ip] = 1;
			}
				setcookie( 'wsq_login_attempts', serialize( $cookieValue ), strtotime( '+1 day' ) );
		}
		function check_login_answers($user) {
			$check_user_input = $this->check_user_input( $user->ID );
			if ( $check_user_input !== true ) {
				return $check_user_input;
			}
			return $user;
		}
		function check_register_answers($errors, $sanitized_user_login, $user_email) {

			$is_error_question = false;
			$is_error_answer = false;

			$plugin_settings = get_option( 'wpr_security_ques_setting' );
			if ( is_array( $_POST['seq_ans'] ) ) {
				$all_user_answers = array();
				foreach ( $_POST['seq_ans'] as $key_ans => $answer ) {
					$all_user_answers[] = $answer;
				}
			}
			$all_user_answers = array_unique( $all_user_answers );
			if ( in_array( '', $all_user_answers ) ) {
				$is_error_answer = true;
			}
			if ( is_array( $_POST['seq_ques'] ) ) {
				foreach ( $_POST['seq_ques'] as $key_ques => $question ) {

					if ( empty( $question ) & $question != '0' ) {
						$is_error_question = true;
					}
				}
			}
			if ( $is_error_answer == true or $is_error_question == true ) {
				$errors->add( 'security_answer', __( '<strong>ERROR:</strong> Security answer is requried.',WSQ_TEXT_DOMAIN ) ); }
			if ( count( $_POST['seq_ques'] ) === count( array_unique( $_POST['seq_ques'] ) ) ) {

			} else {
				if ( $is_error_question == false ) {
					$errors->add( 'security_answer', __( '<strong>ERROR:</strong> Security answer is requried.',WSQ_TEXT_DOMAIN ) ); }
			}
			return $errors;
		}
		function show_extra_profile_fields($user) {
			$user_id = $user->data->ID;
			$plugin_settings = get_option( 'wpr_security_ques_setting' );

			if ( get_option( 'wpr_register_security_ques' ) !== false ) {
				$security_questions = get_option( 'wpr_register_security_ques' );
				$user_answers = get_user_meta( $user_id,'security_check',true );

				if ( ! is_array( $user_answers ) ) {
					$user_answers = array();
				}

				$html = '<h3>'.__( 'My Security Questions',WSQ_TEXT_DOMAIN ).'</h3>';
				$html .= "<table class='form-table'>";
					$html .= '<th><label>'.__( 'Choose Question', WSQ_TEXT_DOMAIN )."</label></th><td><select name='seq_ques[]'>";
					$answer = '';
				foreach ( $security_questions as $id => $quest ) :
					if ( array_key_exists( $id, $user_answers ) ) {
						$s = 'selected="selected"';
						$answer = $user_answers[$id];
					} else {
						$s = '';
					}
					$html .= '<option '.$s." value='".$id."'>".$quest.'</option>';
					endforeach;
					$html .= '</select>';
					$html .= '<p><label>'.__( 'Your Answer',WSQ_TEXT_DOMAIN ).'<br /><input type="text" name="seq_ans[]" id="seq_ans[]" value="'.$answer.'" class="input" /></p></td></tr>';
					$limit_question = $limit_question + $display_question;
					$html .= '</table>';
				echo $html;
			}
		}
		function save_extra_profile_fields($user_id) {
			
			$plugin_settings = get_option( 'wpr_security_ques_setting' );
			delete_user_meta( $user_id,'security_check' );
			$all_ques = get_option( 'wpr_register_security_ques' );
			if ( isset( $_POST['seq_ques'] ) ) {
				$sec_ans = array();
				foreach ( $_POST['seq_ques'] as $key => $question ) {
					if ( ! empty( $_POST['seq_ans'][ $key ] ) ) {
						$sec_ans[$question] = esc_attr( sanitize_text_field( $_POST['seq_ans'][ $key ] ) );
					}
				}
				update_user_meta( $user_id, 'security_check', $sec_ans, true );
			}
		}

		/**
		 * Call WordPress hooks.
		 */
		function _init() {
		add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this,'plugin_action_links') );
		add_filter( 'plugin_row_meta', array($this,'plugin_row_meta'), 10,2 );

		}
		/**
		 * Settings link.
		 * @param  array $links Array of Links.
		 * @return array        Array of Links.
		 */
		function plugin_row_meta( $links, $file ) {

			if( basename(dirname($file)) == 'wp-security-questions' ) {
				$links[] = '<a href="http://www.flippercode.com/product/wp-security-questions/" target="_blank">Upgrade to Pro</a>';
		   		$links[] = '<a href="http://www.flippercode.com/forums" target="_blank">Support Forums</a>';
			}

		   return $links;
		}
		/**
		 * Settings link.
		 * @param  array $links Array of Links.
		 * @return array        Array of Links.
		 */
		function plugin_action_links( $links ) {
		   $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wsq_manage_settings') ) .'">Settings</a>';
		   return $links;
		}

		/**
		 * Create backend navigation.
		 */
		function define_admin_menu() {

			$pagehook1 = add_menu_page(
				__( 'WP Security Questions', WSQ_TEXT_DOMAIN ),
				__( 'WP Security Questions', WSQ_TEXT_DOMAIN ),
				'wsq_admin_overview',
				WSQ_SLUG,
				array( $this,'processor' ),
				WSQ_IMAGES.'fc-small-logo.png'
			);
			return $pagehook1;
		}
		/**
		 * Eneque scripts in the backend.
		 */
		function backend_script_localisation() {

			$wsq_js_lang = array();
			$wsq_js_lang['ajax_url'] = admin_url( 'admin-ajax.php' );
			$wsq_js_lang['nonce'] = wp_create_nonce( 'wsq-call-nonce' );
			$wsq_js_lang['confirm'] = __( 'Are you sure to delete item?',WSQ_TEXT_DOMAIN );
			wp_localize_script( 'wsq-backend', 'wsq_js_lang', $wsq_js_lang );
		}


		/**
		 * Perform tasks on plugin deactivation.
		 */
		function plugin_activation_work() {

			if ( get_option( 'wpr_register_security_ques' ) == false ) {
				$all_question = array(
				'What was your childhood nickname?',
				'In what city did you meet your spouse/significant other?',
				'What is the name of your favorite childhood friend?',
				'What was the name of your first stuffed animal?',
				'Who was your childhood hero?',
				'Where did you vacation last year?',
				'In what city and country do you want to retire?',
				'What year did you graduate from High School?',
				'What is the name of the first school you attended?',
				'What are the last 5 digits of your driver\'s license number?',
				'What was the name of your elementary / primary school?',
				'What was your favorite place to visit as a child?',
				'What was your dream job as a child?',
				'What is the last name of your favorite high school teacher?',
				'What is the name of the company of your first job?',
				);

				foreach ( $all_question as $index => $question ) {
					$all_new_question[sanitize_title( $question )] = $question;
				}
				update_option( 'wpr_register_security_ques', $all_new_question );
			}
		}

		/**
		 * Define all constants.
		 */
		 function _define_constants() {

			global $wpdb;

			if ( ! defined( 'WSQ_SLUG' ) ) {
				define( 'WSQ_SLUG', 'wsq_view_overview' );
			}

			if ( ! defined( 'WSQ_VERSION' ) ) {
				define( 'WSQ_VERSION', '1.0.4' );
			}

			if ( ! defined( 'WSQ_TEXT_DOMAIN' ) ) {
				define( 'WSQ_TEXT_DOMAIN', 'wp_security_question' );
			}

			if ( ! defined( 'WSQ_FOLDER' ) ) {
				define( 'WSQ_FOLDER', basename( dirname( __FILE__ ) ) );
			}

			if ( ! defined( 'WSQ_DIR' ) ) {
				define( 'WSQ_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'WSQ_CORE_CLASSES' ) ) {
				define( 'WSQ_CORE_CLASSES', WSQ_DIR.'core/' );
			}

			if ( ! defined( 'WSQ_PLUGIN_CLASSES' ) ) {
				define( 'WSQ_PLUGIN_CLASSES', WSQ_DIR.'classes/' );
			}

			if ( ! defined( 'WSQ_CONTROLLER' ) ) {
				define( 'WSQ_CONTROLLER', WSQ_CORE_CLASSES );
			}

			if ( ! defined( 'WSQ_CORE_CONTROLLER_CLASS' ) ) {
				define( 'WSQ_CORE_CONTROLLER_CLASS', WSQ_CORE_CLASSES.'class.controller.php' );
			}

			if ( ! defined( 'WSQ_Model' ) ) {
				define( 'WSQ_Model', WSQ_DIR.'modules/' );
			}

			if ( ! defined( 'WSQ_URL' ) ) {
				define( 'WSQ_URL', plugin_dir_url( WSQ_FOLDER ).WSQ_FOLDER.'/' );
			}

			if ( ! defined( 'FC_CORE_URL' ) ) {
				define( 'FC_CORE_URL', plugin_dir_url( WSQ_FOLDER ).WSQ_FOLDER.'/core/' );
			}

			if ( ! defined( 'WSQ_INC_URL' ) ) {
				define( 'WSQ_INC_URL', WSQ_URL.'includes/' );
			}

			if ( ! defined( 'WSQ_VIEWS_PATH' ) ) {
				define( 'WSQ_VIEWS_PATH', wsq_CLASSES.'view' );
			}

			if ( ! defined( 'WSQ_CSS' ) ) {
				define( 'WSQ_CSS', WSQ_URL.'/assets/css/' );
			}

			if ( ! defined( 'WSQ_JS' ) ) {
				define( 'WSQ_JS', WSQ_URL.'/assets/js/' );
			}

			if ( ! defined( 'WSQ_IMAGES' ) ) {
				define( 'WSQ_IMAGES', WSQ_URL.'/assets/images/' );
			}

			if ( ! defined( 'WSQ_FONTS' ) ) {
				define( 'WSQ_FONTS', WSQ_URL.'fonts/' );
			}

		}
		
	}
}

new WP_Security_Question();
