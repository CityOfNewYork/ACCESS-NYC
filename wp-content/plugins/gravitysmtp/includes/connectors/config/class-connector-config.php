<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Config;

class Connector_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	protected $fields;
	protected $logo;
	protected $full_logo;
	protected $title;
	protected $description;
	protected $short_name;
	protected $data;
	protected $i18n;

	public function set_data( $data ) {
		$this->fields      = $data['fields'];
		$this->short_name  = $data['name'];
		$this->logo        = $data['logo'];
		$this->full_logo   = $data['full_logo'];
		$this->title       = $data['title'];
		$this->description = $data['description'];
		$this->data        = $data['data'];
		$this->i18n        = $data['i18n'];
	}

	public function should_enqueue() {
		return is_admin();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$connector_data = array(
			'title'       => $this->title,
			'description' => $this->description,
			'id'          => $this->short_name,
			'logo'        => $this->logo,
			'full_logo'   => $this->full_logo,
			'settings'    => $this->fields,
			'data'        => $this->data,
			'i18n'        => $this->i18n,
		);

		$components = array(
			'settings' => array(
				'data' => array(
					'integrations' => array(
						$connector_data,
					),
				),
			),
			'tools'    => array(
				'data' => array(
					'integrations' => array(
						$connector_data,
					),
				),
			),
		);

		if ( $this->should_enqueue_setup_wizard() ) {
			$components['setup_wizard'] = array(
				'data' => array(
					'integrations' => array(
						$connector_data,
					),
				),
			);
		}

		return array(
			'components' => $components,
		);
	}

	private function should_enqueue_setup_wizard() {
		$should_enqueue = Gravity_SMTP::container()->get( App_Service_Provider::SHOULD_ENQUEUE_SETUP_WIZARD );
		return is_callable( $should_enqueue ) ? $should_enqueue() : $should_enqueue;
	}

}
