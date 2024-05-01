<?php
namespace EnableMediaReplace;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

class InstallHelper
{

	 	public static function unInstallPlugin()
		{
			delete_option( 'enable_media_replace' );
			delete_option( 'emr_news' );
			delete_option( 'emr_url_cache');
		}

		public static function deactivatePlugin()
		{
			Notices::resetNotices();
		}
}
