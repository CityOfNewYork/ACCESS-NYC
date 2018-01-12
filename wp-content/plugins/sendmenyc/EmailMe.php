<?php

namespace SMNYC;


require_once plugin_dir_path( __FILE__ ) . 'ContactMe.php';
require plugin_dir_path( __FILE__ ) . 'third-party/AWS/aws-autoloader.php';

use Aws\Ses\SesClient;

class EmailMe extends ContactMe {
	protected $action = 'Email';
	protected $service = 'AWS';

	protected $account_label = 'Key';
	protected $secret_label = 'Secret';
	protected $from_label = 'From Email Address';

	protected $account_hint = 'AKIAIOSFODNN7EXAMPLE';
	protected $secret_hint = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';
	protected $from_hint = 'noreply@example.com';


	protected function content( $url, $page, $orig_url ){
		// extract the language code - TO EDIT: add these as fields in the CMS so they can be easily modified
		$exp = explode('/',$orig_url); 
		$language = $exp[3]; 

		// reads in the email template
		$html = file_get_contents( __DIR__ .'/emails/index.html' );

		// results page
		if ( $page == self::RESULTS_PAGE ) {
			if ( $language == "es" ) {
				$subject = 'Es tiempo de solicitar los programas de NYC';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Sus resultados";
			}elseif ( $language == "ru" ) {
				$subject = 'Сейчас можно оформить заявление на участие в городских программах Нью-Йорка';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Ваши результаты";
			}elseif ( $language == "ko" ) {
				$subject = 'NYC 프로그램에 신청하실 때입니다';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="결과";
			}elseif ( $language == "ar" ) {
				$subject = 'حان وقت تقديم الطلب لبرامج مدينة نيويورك';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="نتائجك";
			}elseif ( $language == "ht" ) {
				$subject = 'Lè a rive pou ou aplike pou pwogram NYC ou yo';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Rezilta ou yo";
			}elseif ( $language == "zh-hant" ) {
				$subject = '是您申請 NYC 計劃的時候了';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="您的結果";
			}elseif ( $language == "fr" ) {
				$subject = 'C’est le moment de faire une demande pour bénéficier des programmes NYC';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Vos résultats";
			}elseif ( $language == "pl" ) {
				$subject = 'Czas złożyć wniosek o programy NYC';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Twoje wyniki";
			}elseif ( $language == "ur" ) {
				$subject = 'اپنے NYC پروگرامز کے لیے درخواست دینے کا وقت';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button=" آپ کے نتائج";
			}elseif ( $language == "bn" ) {
				$subject = 'আপনার NYC কার্যক্রমগুলির জন্য আবেদন করার সময় এসে গেছে';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="আপনার ফলাফলসমূহ";
			}else {
				$subject = 'Time to apply for your NYC programs';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Your Results";
			}
		// programs page
		} else {
			if ( $language == "es" ) {
				$subject='ES How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "Cómo solicitar el beneficio";
			}elseif ( $language == "ru" ) {
				$subject='RU How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "Порядок подачи заявления";
			}elseif ( $language == "ko" ) {
				$subject='KO How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "신청방법";
			}elseif ( $language == "ar" ) {
				$subject='AR How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "طريقة التقديم";
			}elseif ( $language == "ht" ) {
				$subject='HT How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "Kouman pou Aplike";
			}elseif ( $language == "zh-hant" ) {
				$subject='ZH How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "如何申請";
			}elseif ( $language == "fr" ) {
				$subject='FR How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "Comment présenter une demande";
			}elseif ( $language == "pl" ) {
				$subject='PL How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "Jak złożyć wniosek";
			}elseif ( $language == "ur" ) {
				$subject='UR How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "درخواست کیسے دیں";
			}elseif ( $language == "bn" ) {
				$subject='BN How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "কীভাবে আবেদন করতে হয়";
			}else {
				$subject='How to apply for your NYC program';
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "How To Apply";
			}
		}

        $html_body = str_replace("\r\n","<br>",$body);
        $html_body = str_replace(" (https://access.nyc.gov)","",$html_body);
        $html_body = str_replace("ACCESS NYC","<a href=\"https://access.nyc.gov\" style=\"color:#184e9e;\">ACCESS NYC</a>",$html_body);
		$html = str_replace('%(body)', $html_body, $html);
		$html = str_replace('%(link)', $url, $html);
		$html = str_replace('%(button)', $button, $html);

		return [
			"subject"=>$subject,
			"body"=>$body."\r\n\r\n".$url."\r\n\r\nHave questions? Contact us: http://on.nyc.gov/accessnyc-contact-us",
			"html"=>$html,
		];

	}

	protected function send( $to, $info ) {
		try {
			$client = new SesClient([
				'version'=>'latest',
				'region'=>'us-east-1',
				'credentials' => [
					'key' => get_option( 'smnyc_aws_user' ),
					'secret' => get_option( 'smnyc_aws_secret' ),
				],
			]);
			$config = [
				'Source'=> get_option( 'smnyc_aws_from' ),
				'Destination'=>[ 'ToAddresses'=>[$to] ],
				'Message'=>[
					'Subject'=>[ 'Data'=>$info['subject'] ],
					'Body'=>[
						'Text'=>[ 'Data'=>$info['body'] ],
						'Html'=>[ 'Data'=>$info['html'] ]
					]
				]
			];
			if ( !empty(get_option('smnyc_aws_reply')) ) {
				$config['ReplyToAddresses'] = [get_option('smnyc_aws_reply')];
				$config['ReturnPath'] = get_option('smnyc_aws_reply');
			}
			$result = $client->sendEmail($config);
		} catch ( \Aws\Ses\Exception\SesException $e ) {
			$this->failure(3, $e->getMessage());
		}
	}

	protected function valid_recipient( $addr ) {
		if ( empty($addr) ){
			$this->failure( 1, 'Email address must be supplied' );
		} elseif ( !filter_var( $addr, FILTER_VALIDATE_EMAIL ) ){
			$this->failure(2, 'Invalid email address. Please provide a valid email' );
		}
		return $addr;
	}

	public function create_settings_section() {
		parent::create_settings_section();
		$field = 'smnyc_'.strtolower($this->service).'_reply';
		add_settings_field(
			$field,
			"Reply-To <em>(optional)</em>",
			[$this,'settings_field_html'],
			'smnyc_config',
			'smnyc_'.strtolower($this->action).'_section',
			[ $field, "no-reply@domain.org" ]
		);
		register_setting( 'smnyc_settings', $field);
	}

}
