<?php

namespace SMNYC;

require_once plugin_dir_path( __FILE__ ) . 'ContactMe.php';

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
				$body = "Recientemente completó un cuestionario en ACCESS NYC (https://access.nyc.gov), la página web para encontrar ayuda relacionada con alimentos, dinero, alojamiento, empleo y más. \r\n\r\nEstos son los programas para los cuales podría ser elegible:";
				$button="Sus resultados";
				$questions="¿Tiene alguna pregunta? Póngase en contacto con nosotros.";
			}elseif ( $language == "ru" ) {
				$subject = 'Сейчас можно оформить заявление на участие в городских программах Нью-Йорка';
				$body = "Вы недавно заполнили анкету на портале ACCESS NYC (https://access.nyc.gov), на котором вы можете найти помощь в отношении питания, финансов, жилья, работы и многого другого. \r\n\r\nЭти программы могут быть доступны для вас:";
				$button="Ваши результаты";
				$questions="Есть вопросы? Как связаться с нами.";
			}elseif ( $language == "ko" ) {
				$subject = 'NYC 프로그램에 신청하실 때입니다';
				$body = "귀하는 최근 식품, 현금, 거주지 및 직장 등에 관한 지원을 받을 수 있는 이 웹사이트 링크 (https://access.nyc.gov)에서 ACCESS NYC에 관한 설문을 완료하였습니다. \r\n\r\n귀하는 다음 프로그램에 관한 자격 대상일 수 있습니다:";
				$button="결과";
				$questions="문의사항이 있습니까? 연락처.";
			}elseif ( $language == "ar" ) {
				$subject = 'حان وقت تقديم الطلب لبرامج مدينة نيويورك';
				$body = "لقد أكملت مؤخرًا استبيانًا بشأن ACCESS NYC (https://access.nyc.gov)، الموقع الإلكتروني للحصول على مساعدة في الغذاء والمال والسكن والعمل، وأكثر من ذلك. \r\n\r\nهذه هي البرامج التي قد تكون مؤهلاً للحصول عليها:";
				$button="نتائجك";
				$questions="هل لديك أسئلة؟ الإتصال بنا";
			}elseif ( $language == "ht" ) {
				$subject = 'Lè a rive pou ou aplike pou pwogram NYC ou yo';
				$body = "Pa twò lontan ou te ranpli yon kesyonè ak  ACCESS NYC (https://access.nyc.gov), sitwèb pou jwenn èd ak manje, lajan, lojman, travay ak plis toujou. \r\n\r\nSa yo se pwogram ou ka kalifye pou yo:";
				$button="Rezilta ou yo";
				$questions="Èske ou gen kesyon? Kontakte Nou.";
			}elseif ( $language == "zh-hant" ) {
				$subject = '是您申請 NYC 計劃的時候了';
				$body = "您最近在 ACCESS NYC (https://access.nyc.gov) 上完成了一份問卷，該網站上可以尋找食物、金錢、住房、工作以及更多方面的幫助。\r\n\r\n這些是您可能符合資格的計劃：";
				$button="您的結果";
				$questions="有問題嗎? 與我們聯絡.";
			}elseif ( $language == "fr" ) {
				$subject = 'C’est le moment de faire une demande pour bénéficier des programmes NYC';
				$body = "Vous venez de répondre à un questionnaire sur ACCESS NYC (https://access.nyc.gov), le site Web pour trouver une aide alimentaire, financière, de logement, et plus encore. \r\n\r\nVoici les programmes pour lesquels vous pourriez être éligible :";
				$button="Vos résultats";
				$questions="Avez-vous des questions? Contactez-nous.";
			}elseif ( $language == "pl" ) {
				$subject = 'Czas złożyć wniosek o programy NYC';
				$body = "Niedawno wypełniłeś(-as) ankietę dotyczącą ACCESS NYC (https://access.nyc.gov), na stronie internetowej zawierającej informacje dotyczące pomocy w zakresie żywności, pieniędzy, zakwaterowania, pracy i innych kwestii. \r\n\r\nMożesz zakwalifikować do udziału w tych programach:";
				$button="Twoje wyniki";
				$questions="Masz pytania? Skontaktuj się z nami.";
			}elseif ( $language == "ur" ) {
				$subject = 'اپنے NYC پروگرامز کے لیے درخواست دینے کا وقت';
				$body = "آپ نے حال ہی میں ACCESS NYC (https://access.nyc.gov) پر ایک سوالنامہ مکمل کیا تھا، جو کہ وہ ویب سائیٹ ہے جہاں سے آپ غذائی اشیاء، پیسوں، رہائش، کام اور دیگر کے ضمن میں مدد حاصل کر سکتے/سکتی ہیں۔ \r\n\r\nیہ وہ پروگرامز ہیں، جن کے لیے آپ اہل ہو سکتے/سکتی ہیں:";
				$button=" آپ کے نتائج";
				$questions="سوالات ہیں؟ ہم سے رابطہ کریں";
			}elseif ( $language == "bn" ) {
				$subject = 'আপনার NYC কার্যক্রমগুলির জন্য আবেদন করার সময় এসে গেছে';
				$body = "সম্প্রতি আপনি ACCESS NYC (https://access.nyc.gov), -তে একটি প্রশ্নাবলী সম্পূর্ণ করেছেন, যেটি খাদ্য, অর্থ, আবাসন, কাজ ও আরও অনেক কিছুর জন্য সাহায্যে খোঁজ করার ওযেবসাইট। \r\n\r\nএই কর্মসূচিগুলির জন্য আপনি যোগ্য হতে পারেন:";
				$button="আপনার ফলাফলসমূহ";
				$questions="কোন প্রশ্ন আছে? আমাদের সঙ্গে যোগাযোগ করুন.";
			}else {
				$subject = 'Time to apply for your NYC programs';
				$body = "You recently completed a questionnaire on ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\nThese are the programs that you may be eligible for:";
				$button="Your Results";
				$questions="Have questions? Contact us.";
				$language="";
			}
		// programs page
		} else {
			if ( $language == "es" ) {
				$subject='Cómo enviar una solicitud para su programa de la Ciudad de Nueva York';
				$body = "A continuación, encontrará un enlace con los detalles necesarios para aplicar a un programa de ACCESS NYC (https://access.nyc.gov), la página web para encontrar ayuda relacionada con alimentos, dinero, alojamiento, empleo y más.\r\n\r\n";
				$button = "Cómo solicitar el beneficio";
				$questions="¿Tiene alguna pregunta? Póngase en contacto con nosotros.";
			}elseif ( $language == "ru" ) {
				$subject='Как оформить заявление на участие в городских программах Нью-Йорка';
				$body = "По этой ссылке вы можете получить информацию об оформлении заявления на участие в одной из программ, представленных на портале ACCESS NYC (https://access.nyc.gov). На этом портале вы можете найти помощь в отношении питания, финансов, жилья, работы и многого другого.\r\n\r\n";
				$button = "Порядок подачи заявления";
				$questions="Есть вопросы? Как связаться с нами.";
			}elseif ( $language == "ko" ) {
				$subject='NYC 프로그램 신청 방법';
				$body = "식품, 현금, 거주지 및 직장 등에 관한 지원을 받을 수 있는 이 웹사이트 링크 (https://access.nyc.gov)에서 ACCESS NYC 프로그램에 관한 신청 세부 정보를 확인하십시오.\r\n\r\n";
				$button = "신청방법";
				$questions="문의사항이 있습니까? 연락처.";
			}elseif ( $language == "ar" ) {
				$subject='كيفية التقدم بطلب لبرنامج مدينة نيويورك الخاص بك';
				$body = "إليك رابطًا لتفاصيل تقديم الطلب للبرنامج من ACCESS NYC (https://access.nyc.gov)، الموقع الإلكتروني للحصول على مساعدة في الغذاء والمال والسكن والعمل، وأكثر من ذلك.\r\n\r\n";
				$button = "طريقة التقديم";
				$questions="هل لديك أسئلة؟ الإتصال بنا";
			}elseif ( $language == "ht" ) {
				$subject='Fason pou aplike pou pwogram Vil New York ou a';
				$body = "Men yon lyen pou ale sou detay aplikasyon ou yon pwogram nan ACCESS NYC (https://access.nyc.gov), sitwèb pou jwenn èd ak manje, lajan, lojman, travay ak plis toujou.\r\n\r\n";
				$button = "Kouman pou Aplike";
				$questions="Èske ou gen kesyon? Kontakte Nou.";
			}elseif ( $language == "zh-hant" ) {
				$subject='如何申請您的 NYC 計劃';
				$body = "這是關於 ACCESS NYC 的一項計劃的申請詳情的鏈接 (https://access.nyc.gov)，該網站上可以尋找食物、金錢、住房、工作以及更多方面的幫助。\r\n\r\n";
				$button = "如何申請";
				$questions="有問題嗎? 與我們聯絡.";
			}elseif ( $language == "fr" ) {
				$subject='Comment présenter une demande pour bénéficier de votre programme NYC';
				$body = "Voici un lien vers les informations relatives au processus de demande pour bénéficier d'un programme ACCESS NYC (https://access.nyc.gov), le site Web pour trouver une aide alimentaire, financière, de logement, et plus encore.\r\n\r\n";
				$button = "Comment présenter une demande";
				$questions="Avez-vous des questions? Contactez-nous.";
			}elseif ( $language == "pl" ) {
				$subject='Jak złożyć wniosek do wybranego przez siebie programu NYC';
				$body = "Oto link do szczegółów dotyczących składania wniosku do programu ACCESS NYC (https://access.nyc.gov), strony internetowej zawierającej informacje dotyczące pomocy w zakresie żywności, pieniędzy, zakwaterowania, pracy i innych kwestii.\r\n\r\n";
				$button = "Jak złożyć wniosek";
				$questions="Masz pytania? Skontaktuj się z nami.";
			}elseif ( $language == "ur" ) {
				$subject='اپنے NYC پروگرامز کے لیے درخواست کیسے دیں';
				$body = "ACCESS NYC (https://access.nyc.gov) کے کسی بھی پروگرام کے لیے درخواست دینے کی تفصیلات کا لنک یہ ہے، جو کہ وہ ویب سائیٹ ہے جہاں سے آپ غذائی اشیاء، پیسوں، رہائش، کام اور دیگر کے ضمن میں مدد حاصل کر سکتے/سکتی ہیں۔\r\n\r\n";
				$button = "درخواست کیسے دیں";
				$questions="سوالات ہیں؟ ہم سے رابطہ کریں";
			}elseif ( $language == "bn" ) {
				$subject='আপনার NYC কর্মসূচির জন্য কীভাবে আবেদন করবেন';
				$body = "ACCESS NYC থেকে কোনো কর্মসূচির আবেদনের বিবরণের জন্য এখানে একটি লিঙ্ক (https://access.nyc.gov) রয়েছে, ওয়েবসাইটটিতে খাদ্য, অর্থ, আবাসন, কাজ ও আরও অনেক কিছুর জন্য সাহায্যের খোঁজ করুন।\r\n\r\n";
				$button = "কীভাবে আবেদন করতে হয়";
				$questions="কোন প্রশ্ন আছে? আমাদের সঙ্গে যোগাযোগ করুন.";
			}else {
				$subject='How to apply for your NYC program';
				$questions="Have questions? Contact us.";
				$body = "Here's a link to application details for a program from ACCESS NYC (https://access.nyc.gov), the website for finding help with food, money, housing, work, and more.\r\n\r\n";
				$button = "How To Apply";
				$questions="Have questions? Contact us.";
				$language="";
			}
		}

        $html_body = str_replace("\r\n","<br>",$body);
        $html_body = str_replace(" (https://access.nyc.gov)","",$html_body);
        $html_body = str_replace("ACCESS NYC","<a href=\"https://access.nyc.gov\" style=\"color:#184e9e;\">ACCESS NYC</a>",$html_body);

        if (strpos($questions, '?') !== false) {
		  $contactUs = substr($questions, strpos($questions, "?")+2);
		}else if (strpos($questions, json_decode('"\u061F"')) !== false){
		  $contactUs = substr($questions, strpos($questions, json_decode('"\u061F"')) + 2);
		}

      	if($language !=""){
	        $html_body2 = str_replace($contactUs,"<a href=\"http://on.nyc.gov/accessnyc-contact-us-".$language."\" style=\"color:#184e9e;\">".$contactUs."</a>",$questions);
      	}else{
      		$html_body2 = str_replace($contactUs,"<a href=\"http://on.nyc.gov/accessnyc-contact-us\" style=\"color:#184e9e;\">".$contactUs."</a>",$questions);
      	}

		$html = str_replace('%(contactUs)', $html_body2, $html);
		$html = str_replace('%(header)', $subject, $html);
		$html = str_replace('%(body)', $html_body, $html);
		$html = str_replace('%(link)', $url, $html);
		$html = str_replace('%(button)', $button, $html);

		return [
			"subject"=>$subject,
			"body"=>$body."\r\n\r\n".$url."\r\n\r\n".$questions,
			"html"=>$html,
		];

	}

  protected function send($to, $info) {
    try {
      $user = get_option('smnyc_aws_user');
      $secret = get_option('smnyc_aws_secret');
			$from = get_option('smnyc_aws_from');
			$display = get_option('smnyc_aws_display_name');
      $reply = get_option('smnyc_aws_reply');

      $user = (!empty($user)) ? $user : $_ENV['SMNYC_AWS_USER'];
      $secret = (!empty($secret)) ? $secret : $_ENV['SMNYC_AWS_SECRET'];
      $from = (!empty($from)) ? $from : $_ENV['SMNYC_AWS_FROM'];
			$display = (!empty($display))
				? $display : $_ENV['SMNYC_AWS_DISPLAY_NAME'];
			$reply = (!empty($reply)) ? $reply : $_ENV['SMNYC_AWS_REPLY'];

			// Build display name
			$from = (!empty($display)) ? "$display<$from>" : $from;

      $client = new SesClient([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => [
          'key' => $user,
          'secret' => $secret
        ]
			]);

      $config = [
        'Source' => $from,
        'Destination' => ['ToAddresses' => [$to]],
        'Message' => [
          'Subject' => ['Data' => $info['subject']],
          'Body' => [
            'Text' => ['Data' => $info['body']],
            'Html' => ['Data' => $info['html']]
          ]
        ]
      ];

      if (!empty($reply)) {
        $config['ReplyToAddresses'] = [$reply];
        $config['ReturnPath'] = $reply;
      }

      $result = $client->sendEmail($config);
    } catch (\Aws\Ses\Exception\SesException $e) {
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

		$field_display = 'smnyc_' . strtolower($this->service) . '_display_name';
		$field_reply = 'smnyc_' . strtolower($this->service) . '_reply';

		add_settings_field(
			$field_display,
			'Email Display Name <em>(optional)</em>',
			[$this, 'settings_field_html'],
			'smnyc_config',
			'smnyc_' . strtolower($this->action) . '_section',
			[$field_display, '']
		);

		add_settings_field(
			$field_reply,
			'Reply-To <em>(optional)</em>',
			[$this, 'settings_field_html'],
			'smnyc_config',
			'smnyc_' . strtolower($this->action) . '_section',
			[$field_reply, 'no-reply@domain.org']
		);

		register_setting('smnyc_settings', $field_display);
		register_setting('smnyc_settings', $field_reply);
	}
}
