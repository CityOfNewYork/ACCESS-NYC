<?php

namespace OTGS\Installer\Api\Endpoint;

use OTGS\Installer\Api\Exception\InvalidProductsResponseException;
use OTGS\Installer\Api\SiteUrl;

class ProductBucketUrl {
	/**
	 * @var SiteUrl
	 */
	private $siteUrl;

	/**
	 * @var string
	 */
	private $repositoryId;

	/**
	 * @param string $repositoryId
	 * @param SiteUrl $siteUrl
	 */
	public function __construct( $repositoryId, SiteUrl $siteUrl ) {
		$this->siteUrl = $siteUrl;
		$this->repositoryId = $repositoryId;
	}

	/**
	 * @param string $siteKey
	 *
	 * @return array
	 */
	public function prepareRequest( $siteKey ) {
		return [
			'action'   => 'product_bucket_url',
			'site_key' => $siteKey,
			'site_url' => $this->siteUrl->get( $this->repositoryId ),
		];
	}

	/**
	 * @param array $response
	 * @throws InvalidProductsResponseException
	 */
	public function parseResponse( $response ) {
		if (isset( $response['response']['code'] ) &&
			$response['response']['code'] == 200
		) {
			$body = wp_remote_retrieve_body( $response );
			if ( $body ) {
				$response_data = json_decode( $body );

				if ( isset( $response_data->success ) && $response_data->success === true ) {
					return $response_data->bucket->url;
				}
			}
		}

		throw new InvalidProductsResponseException();
	}
}