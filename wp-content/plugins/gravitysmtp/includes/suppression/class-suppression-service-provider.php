<?php

namespace Gravity_Forms\Gravity_SMTP\Suppression;

use Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model;
use Gravity_Forms\Gravity_SMTP\Suppression\Config\Suppression_Settings_Config;
use Gravity_Forms\Gravity_SMTP\Suppression\Endpoints\Add_Suppressed_Emails_Endpoint;
use Gravity_Forms\Gravity_SMTP\Suppression\Endpoints\Get_Paginated_Items;
use Gravity_Forms\Gravity_SMTP\Suppression\Endpoints\Reactivate_Suppressed_Emails_Endpoint;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Suppression_Service_Provider extends Config_Service_Provider {

	const SUPPRESSED_EMAILS_MODEL     = 'suppressed_emails_model';
	const SUPPRESSION_SETTINGS_CONFIG = 'suppression_settings_config';

	const GET_PAGINATED_ITEMS_ENDPOINT          = 'get_paginated_suppression_items_endpoint';
	const ADD_SUPPRESSED_EMAILS_ENDPOINT        = 'add_suppressed_emails_endpoint';
	const REACTIVATE_SUPPRESSED_EMAILS_ENDPOINT = 'reactivate_suppressed_emails_endpoint';

	protected $configs = array(
		self::SUPPRESSION_SETTINGS_CONFIG => Suppression_Settings_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( self::SUPPRESSED_EMAILS_MODEL, function () {
			return new Suppressed_Emails_Model();
		} );

		$this->container->add( self::GET_PAGINATED_ITEMS_ENDPOINT, function () use ( $container ) {
			return new Get_Paginated_Items( $container->get( self::SUPPRESSED_EMAILS_MODEL ) );
		} );

		$this->container->add( self::ADD_SUPPRESSED_EMAILS_ENDPOINT, function () use ( $container ) {
			return new Add_Suppressed_Emails_Endpoint( $container->get( self::SUPPRESSED_EMAILS_MODEL ) );
		} );

		$this->container->add( self::REACTIVATE_SUPPRESSED_EMAILS_ENDPOINT, function () use ( $container ) {
			return new Reactivate_Suppressed_Emails_Endpoint( $container->get( self::SUPPRESSED_EMAILS_MODEL ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . Get_Paginated_Items::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_PAGINATED_ITEMS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Add_Suppressed_Emails_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ADD_SUPPRESSED_EMAILS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Reactivate_Suppressed_Emails_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::REACTIVATE_SUPPRESSED_EMAILS_ENDPOINT )->handle();
		} );
	}

}