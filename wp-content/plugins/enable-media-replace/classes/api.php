<?php
/**
 * This page contains api class.
 */
namespace EnableMediaReplace;

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Controller\ReplaceController as ReplaceController;
use EnableMediaReplace\ApiKeyManager as ApiKeyManager;


use Exception;
use stdClass;
/**
 * This class contains api methods
 */
class Api {

	/**
	 * Request Counter
	 *
	 * @var int $counter
	 */
	private $counter = 0;

	/**
	 * ShortPixel api url (free tier — used when no API key is configured)
	 *
	 * @var string $url
	 */
	private $url = 'http://api.shortpixel.com/v2/free-reducer.php';

	/**
	 * ShortPixel authenticated api url — used when a valid API key is configured.
	 */
	private $authenticated_url = 'https://api.shortpixel.com/v2/reducer.php';

	private $cleanup_url = 'https://api.shortpixel.com/v2/cleanup.php';

	/**
	 * ShortPixel api request headers
	 *
	 * @var array $headers
	 */
	private $headers = array(
		'Content-Type: application/json',
		'Accept: application/json',
	);

	private $refresh = true; // only first request should be fresh



	public function __construct()
	{

	}
	/**
	 * Create ShortPixel api request
	 *
	 * @param  array $data
	 * @return stdClass $result
	 */
	public function request( array $posted_data ) {
		$bg_remove         = '1';
		$compression_level = 0; //  intval($posted_data['compression_level']); // off for now.

		$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : null;
		$attachment = get_post($attachment_id);


		if (is_null($attachment_id))
		{
			 $result = $this->getResponseObject();
			 $result->success = false;
			 $result->message = __('No attachment ID given', 'enable-media-replace');
			 return $result;
		}

  	if (! emr()->checkImagePermission($attachment)) {
			$result = $this->getResponseObject();
			$result->success = false;
			$result->message = __('No permission for user', 'enable-media-replace');
			return $result;
	  }

		$replaceController = new ReplaceController($attachment_id);
		$url = $replaceController->getSourceUrl();

		$settings = get_option('enable_media_replace', array()); // save settings and show last loaded.
		$settings['bg_type'] = isset($_POST['background']['type']) ? sanitize_text_field($_POST['background']['type']) : false;
		$settings['bg_color'] = isset($_POST['background']['color']) ? sanitize_text_field($_POST['background']['color']) : '#ffffff'; // default to white.
		$settings['bg_transparency'] = isset($_POST['background']['transparency']) ? sanitize_text_field($_POST['background']['transparency']) : false;

		update_option('enable_media_replace', $settings, false);


		if ( 'solid' === $posted_data['background']['type'] ) {
			$bg_remove = $posted_data['background']['color'];

			$transparency = isset($posted_data['background']['transparency']) ? intval($posted_data['background']['transparency']) : -1;
			if ($transparency >= 0 && $transparency <= 100)
			{
				if ($transparency == 100) {
					$alpha = 'FF';
				} else {
					$alpha = str_pad($transparency, 2, '0', STR_PAD_LEFT);
				}
				$bg_remove .= $alpha;
			}
		}



		$data = array(
			'plugin_version' => 'EMR4.2.0',
			'bg_remove'      => $bg_remove,
			'urllist'        => array( urlencode( esc_url($url) ) ),
			'lossy'          => $compression_level,
			'refresh'				 => $this->refresh,
		);

		$apiKeyManager = ApiKeyManager::getInstance();
		$apiKey   = $apiKeyManager->getApiKey();
		$endpoint = $this->url;
		if ( ! empty( $apiKey ) ) {
			if ( $this->counter === 0 && ! $apiKeyManager->verifyUnlimitedPlan() ) {
				$result = $this->getResponseObject();
				$result->success      = false;
				$result->plan_expired = true;
				$result->message      = __( 'Your Unlimited plan is no longer active. Please renew at shortpixel.com/pricing or remove your API Key to use the Free plan.', 'enable-media-replace' );
				return $result;
			}
			$data['key']     = $apiKey;
			$data['urllist'] = array( esc_url_raw( $url ) );
			$data['item_id'] = intval( $attachment_id );
			$endpoint        = $this->authenticated_url;

			if ( $this->counter === 0 && $this->refresh ) {
				$this->purgeRemoteCache( $apiKey, esc_url_raw( $url ), intval( $attachment_id ) );
			}
		}

		if ( ! empty( $apiKey ) ) {
			$request = array(
				'method'      => 'POST',
				'timeout'     => 15,
				'redirection' => 3,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => json_encode( $data, JSON_UNESCAPED_UNICODE ),
				'cookies'     => array(),
			);
		} else {
			$request = array(
				'method'  => 'POST',
				'timeout' => 60,
				'headers' => $this->headers,
				'body'    => json_encode( $data ),
			);
		}

		$settingsData = '';
		//unset($settingsData['url']);

		foreach($data as $key => $val)
		{
			 if ($key == 'urllist' || $key == 'refresh')
			 {
			 	continue;
			 }
			 $settingsData .= " $key:$val ";
		}


		if ($this->counter > 0) {
			sleep( ! empty( $apiKey ) ? 3 : ( $this->counter + 3 ) );
		}

		$this->counter++;

		$result = $this->getResponseObject();

		$maxAttempts = ! empty( $apiKey ) ? 30 : 10;

		if ( $this->counter < $maxAttempts ) {
			try {

				Log::addDebug('Sending request', $request);
				$response = wp_remote_post( $endpoint, $request );

				$this->refresh = false;

				if ( is_wp_error( $response ) ) {
					$result->message = $response->get_error_message();
				} else {

					$json = json_decode( $response['body'] );

					Log::addDebug('Response Json', $json);
					if ( is_array( $json ) && '2' === $json[0]->Status->Code ) {
						$result->success = true;

						if ( '1' === $compression_level || '2' === $compression_level ) {
							$result->image = $json[0]->LossyURL;
						} else {
							$result->image = $json[0]->LosslessURL;
						}

						$key = $this->handleSuccess($result);
						$result->key = $key;
						$result->url = $url;
						$result->image = add_query_arg('ts', time(), $result->image);

						$result->settings = $settingsData;

//						$this->handleSuccess($result);
					} elseif ( is_array( $json ) && '1' === $json[0]->Status->Code ) {
						return $this->request( $posted_data );
					} else {
						if (is_array($json))
						{
							$result->message = $json[0]->Status->Message;
						}
						elseif (is_object($json) && property_exists($json, 'Status'))
						{
							 $result->message = $json->Status->Message;
						}

						// Friendly override + flag when the free-reducer quota is exhausted.
						if ( ! empty( $result->message ) && stripos( $result->message, 'Quota exceeded' ) !== false ) {
							$result->quota_exceeded = true;
							$result->message       = __( 'Quota exceeded. Please purchase the Unlimited or Unlimited AI plan and add your API key to continue removing backgrounds.', 'enable-media-replace' );
						}
					}
				}
			} catch ( Exception $e ) {
				$result->message = $e->getMessage();
			}
		} else {
			$result->message = __( 'The background could not be removed in a reasonable amount of time. The file might be too big, or the API could be busy. Please try again later!', 'enable-media-replace' );
		}

		return $result;
	}

	private function purgeRemoteCache( $apiKey, $imageUrl, $itemId = 0 )
	{
		$body = array(
			'plugin_version' => 'EMR4.2.0',
			'key'            => $apiKey,
			'urllist'        => array( $imageUrl ),
			'item_id'        => intval( $itemId ),
		);

		$request = array(
			'method'      => 'POST',
			'timeout'     => 15,
			'redirection' => 3,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => json_encode( $body, JSON_UNESCAPED_UNICODE ),
			'cookies'     => array(),
		);

		Log::addDebug( 'Purging remote cache', $request );
		$cleanup_response = wp_remote_post( $this->cleanup_url, $request );
		if ( is_wp_error( $cleanup_response ) ) {
			Log::addDebug( 'Cleanup error', $cleanup_response->get_error_message() );
		} else {
			Log::addDebug( 'Cleanup response', array(
				'code' => wp_remote_retrieve_response_code( $cleanup_response ),
				'body' => wp_remote_retrieve_body( $cleanup_response ),
			) );
		}
	}

	public function handleSuccess($result)
	{
		 // $fs = emr()->filesystem();
		//	$result = $fs->downloadFile($result->image, wp_tempnam($result->image));
		$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : wp_create_nonce();
		$key = wp_hash($nonce . $result->image, 'logged_in');

		set_transient('emr_' . $key, $result->image, 30 * MINUTE_IN_SECONDS);
		return $key;
	}

	public function handleDownload($key)
	{
		$url = get_transient('emr_' . $key);
		$result = $this->getResponseObject();

		if ($url === false)
		{
			 	$result->message = __('This file seems not available anymore. Please try again', 'enable-media-replace');
				return $result;
		}

		$fs = emr()->filesystem();
		$target = wp_tempnam($url);

		$bool = $fs->downloadFile($url, $target);

		if ($bool === false)
		 {
			  $result->message = __('Download failed', 'enable-media-replace');
		 }
		else {
			$result->success = true;
			$result->image = $target;
		}
		return $result;
	}

	protected function  getResponseObject()
	{
		$result          = new stdClass;
		$result->success = false;
		$result->image = null;
		$result->message = null;

		return $result;
	}

}