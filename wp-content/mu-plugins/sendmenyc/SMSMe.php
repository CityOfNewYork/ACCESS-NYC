<?php
namespace SMNYC;

require_once plugin_dir_path( __FILE__ ) . 'ContactMe.php';
require plugin_dir_path( __FILE__ ) . '/third-party/twilio-php/Twilio/autoload.php';

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException as TwilioErr;

class SMSMe extends ContactMe {
	protected $action = 'SMS'; //nonce and ajax key for what this class provides
	protected $service = 'Twilio'; //name used in settings/options keys

	protected $account_label = 'SID';
	protected $secret_label = 'Token';
	protected $from_label = 'Sender Phone Number';

	protected $account_hint = 'AC43fceec9fb0836fff217f4b4fEXAMPLE';
	protected $secret_hint ='2674d4ec2a325a63cbcc63d25EXAMPLE';
	protected $from_hint ='+15551230789';

	protected function content( $url, $page , $orig_url) {
		// extract the language code - TO EDIT: add these as fields in the CMS so they can be easily modified
		$exp = explode('/',$orig_url);
		$language = $exp[3];
		// results page
		if ( $page == self::RESULTS_PAGE ) {
			if ( $language == "es" ) {
				return "RECORDATORIO: usted podría ser elegido para los siguientes programas de NYC: ".$url;
			}elseif ( $language == "ru" ) {
				return "НАПОМИНАНИЕ. Вы можете иметь право на участие в этих городских программах Нью-Йорка: ".$url;
			}elseif ( $language == "ko" ) {
				return "알림: 귀하는 다음 NYC 프로그램의 자격 대상일 수 있습니다: ".$url;
			}elseif ( $language == "ar" ) {
				return "تذكير: من الممكن أن تكون مؤهل لبرامج مدينة نيويورك التالية: ".$url;
			}elseif ( $language == "ht" ) {
				return "RAPÈL: Ou kapab kalifye pou pwogram NYC sa yo: ".$url;
			}elseif ( $language == "zh-hant" ) {
				return "提醒：您可能符合以下 NYC 計劃資格: ".$url;
			}elseif ( $language == "fr" ) {
				return "RAPPEL : Vous pourriez bénéficier des programmes NYC suivants : ".$url;
			}elseif ( $language == "pl" ) {
				return "PRZYPOMNIENIE: Możesz być uprawniony(-a) do następujących programów w Nowym Jorku: ".$url;
			}elseif ( $language == "ur" ) {
				return " یاددہانی: آپ ان NYC پروگرامز کے لیے اہل ہو سکتے ہیں: ".$url;
			}elseif ( $language == "bn" ) {
				return "অনুস্মারক: আপনি এই NYC কার্যক্রমগুলির জন্য যোগ্য হতে পারেন: ".$url;
			}else {
				return 'REMINDER: you may be eligible for these NYC Programs: '.$url;
			}
		// programs page
		} else {
			if ( $language == "es" ) {
				return "Puede enviar una solicitud para los programas de NYC aquí: ".$url;
			}elseif ( $language == "ru" ) {
				return "Оформить заявление на участие в городских программах Нью-Йорка можно здесь: ".$url;
			}elseif ( $language == "ko" ) {
				return "귀하는 다음에서 NYC 프로그램을 신청할 수 있습니다: ".$url;
			}elseif ( $language == "ar" ) {
				return "يمكنك التقدم بطلب للحصول على برامج مدينة نيويورك هنا: ".$url;
			}elseif ( $language == "ht" ) {
				return "Ou ka aplike pou pwogram Vil New York yo la a: ".$url;
			}elseif ( $language == "zh-hant" ) {
				return "您可以在此處申請 NYC 計劃： ".$url;
			}elseif ( $language == "fr" ) {
				return "Vous pouvez déposer votre demande de programmes NYC ici: ".$url;
			}elseif ( $language == "pl" ) {
				return "Możesz złożyć wniosek o zapisanie się do programów NYC tutaj: ".$url;
			}elseif ( $language == "ur" ) {
				return "آپ NYC کے پروگرامز کے لیے یہاں درخواست دے سکتے/سکتی ہیں: ".$url;
			}elseif ( $language == "bn" ) {
				return "আপনি এখানে NYC কর্মসূচিগুলির জন্য আবেদন করতে পারবেন: ".$url;
			}else {
				return "You can apply for NYC programs here: ".$url;
			}
		}
	}

	protected function send( $to, $msg ) {
		try {
			$user = get_option('smnyc_twilio_user');
			$secret = get_option('smnyc_twilio_secret');
			$from = get_option('smnyc_twilio_from');

			$user = (!empty($user)) ? $user : $_ENV['SMNYC_TWILIO_USER'];
			$secret = (!empty($secret)) ? $secret : $_ENV['SMNYC_TWILIO_SECRET'];
			$from = (!empty($from)) ? $from : $_ENV['SMNYC_TWILIO_FROM'];

			$client = new Client( $user, $secret);
			$sms = $client->messages->create($to, ['from' => $from, 'body'=> $msg]);
		} catch ( TwilioErr $e ) {
			return $this->parse_error( $e->getCode() );
		}
		if ( $sms->status == 'failed' || $sms->status == 'undelivered' || $sms->errorCode || $sms->errorMessage  ) {
			return $this->parse_error( $sms->errorCode, $sms->errorMessage );
		}
		/* $sms properties:
			sid,dateCreated,dateUpdated,dateSent,accountSid,from,to,body,
			numMedia,numSegments,status,errorCode,errorMessage,direction,price,
			priceUnit,apiVersion,uri,subresourceUris
		*/
	}


	protected function valid_recipient( $to ) {
		$to = preg_replace( '/\D/', '', $to ); //strip all non-numbers

		// grab user's number
		if (empty( $to )) {
			$this->failure( 1, 'Phone number must be supplied' );
		} elseif ( strlen( $to ) < 10 ) {
			$this->failure( 2, 'Invalid phone number. Please provide 10-digit number with area code' );
		} elseif ( strlen( $to ) === 10 ) {
			$to = '1' . $to; // US country code left off
		}
		//assume longer numbers have country code specified
		return '+'.$to;
	}

	protected function parse_error( $code, $message = 'An error occurred while sending' ) {
		$retry = false;

		switch ( $code ) {
			case 14101: case 21211: case 21612:
			case 21408: case 21610: case 21614:
			case 30003: case 30004: case 30005:
			case 30006: // ^ Something wrong/invalid with 'To' number
				$message = 'Unable to send to number provided. Please use another number';
				break;
			case 21611: //our outbox queue is full
				$retry = true;
				$message = 'Please try again later';
				break;
			case 14103: case 21602: case 21617:
			case 21618: case 21619: case 30007:
				$message = 'Invalid message body';
				break;
			case 30001:
			case 30009://ephemeral errors that a retry might solve https://www.twilio.com/docs/api/rest/message#error-values
				$retry = true;
				break;
			default:
				$message = 'An error occurred during delivery';
				break;
		}
		$this->failure( $code, $message, $retry );
	}

}
