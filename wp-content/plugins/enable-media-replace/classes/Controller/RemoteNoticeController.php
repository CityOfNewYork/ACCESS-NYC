<?php
namespace EnableMediaReplace\Controller;
use EnableMediaReplace\Notices\NoticeController as Notices;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;


if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class RemoteNoticeController
{
		protected static $instance;
		private $remote_message_endpoint = 'https://api.shortpixel.com/v2/notices.php';


		public function __construct()
		{
			 $this->doRemoteNotices();
		}

		public static function getInstance()
		{
				if ( is_null(self::$instance))
				{
					  self::$instance = new RemoteNoticeController();
				}

				return self::$instance;
		}

		protected function doRemoteNotices()
    {
        $notices = $this->get_remote_notices();

        if (! is_array($notices))
            return;

        foreach($notices as $remoteNotice)
        {
            if (! isset($remoteNotice->id) && ! isset($remoteNotice->message))
                return;

            if (! isset($remoteNotice->type))
                $remoteNotice->type = 'notice';

            $message = esc_html($remoteNotice->message);
            $id = sanitize_text_field($remoteNotice->id);

            $noticeController = Notices::getInstance();
            $noticeObj = $noticeController->getNoticeByID($id);

            // not added to system yet
            if ($noticeObj === false)
            {
                switch ($remoteNotice->type)
                {
                    case 'warning':
                        $new_notice = Notices::addWarning($message);
                        break;
                    case 'error':
                        $new_notice = Notices::addError($message);
                        break;
                    case 'notice':
                    default:
                        $new_notice = Notices::addNormal($message);
                        break;
                }

                Notices::makePersistent($new_notice, $id, MONTH_IN_SECONDS);
            }

        }
    }

		private function get_remote_notices()
		{
				$transient_name = 'emr_remote_notice';
				$transient_duration = DAY_IN_SECONDS;


		//		$keyControl = new apiKeyController();
				//$keyControl->loadKey();

				$notices = get_transient($transient_name);
				$url = $this->remote_message_endpoint;
				$url = add_query_arg(array(  // has url
						'version' => EMR_VERSION,
						'plugin' => 'enable-media-replace',
						'target' => 4,

				), $url);


				if ( $notices === false || $notices == 'none'  ) {
						$notices_response = wp_safe_remote_request( $url );

						$content = false;
						if (! is_wp_error( $notices_response ) )
						{
								$notices = json_decode($notices_response['body']);

								if (! is_array($notices))
										$notices = 'none';

								// Save transient anywhere to prevent over-asking when nothing good is there.
								set_transient( $transient_name, 'true', $transient_duration );
						}
						else
						{
								set_transient( $transient_name, false, $transient_duration );
						}
				}

				return $notices;
		}

} // class
