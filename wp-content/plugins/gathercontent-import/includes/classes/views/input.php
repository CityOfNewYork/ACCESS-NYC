<?php
namespace GatherContent\Importer\Views;

class Input extends Form_Element {

	protected $default_attributes = array(
		'type'  => 'text',
		'class' => 'regular-text',
		'id'    => '',
		'name'  => '',
		'value' => '',
		'desc'  => '',
	);

	protected function element() {
		$content = '<input';
		foreach ( $this->attributes() as $attr => $attr_value ) {
			$content .= ' ' . $attr . '="'. $attr_value .'"';
		}
		$content .= ' />';

		return $content;
	}

}
