<?php

namespace WPML\PB\Elementor\Config;

use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use WPML\PB\ConvertIds\Helper;

use function WPML\FP\spreadArgs;

class IdsInWidgets implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const OPTION_IDS_IN_WIDGETS = 'wpml-elementor-config-ids-in-widgets';

	public function add_hooks() {
		Hooks::onFilter( 'wpml_config_array' )
			->then( spreadArgs( Fns::tap( [ $this, 'updateFromConfig' ] ) ) );
		Hooks::onFilter( 'wpmlpb_elementor_fields_with_ids' )
			->then( spreadArgs( [ $this, 'addFieldsWithIds' ] ) );
	}

	/**
	 * @param array $config
	 */
	public function updateFromConfig( $config ) {
		$ids = [];

		if ( isset( $config['wpml-config']['elementor-widgets']['widget'] ) ) {
			foreach ( $config['wpml-config']['elementor-widgets']['widget'] as $widget ) {
				if ( isset( $widget['fields'] ) ) {
					$ids = $this->extractIdsFromFields( $ids, $widget['attr']['name'], $widget['fields'] );
				}
				if ( isset( $widget['fields-in-item'], $widget['fields-in-item']['attr']['items_of'] ) ) {
					$ids = $this->extractIdsFromFields( $ids, $widget['attr']['name'], $widget['fields-in-item'], $widget['fields-in-item']['attr']['items_of'] );
				}
			}
		}

		update_option( self::OPTION_IDS_IN_WIDGETS, $ids );
	}

	/**
	 * @param array       $ids
	 * @param string      $widgetName
	 * @param array       $fields
	 * @param string|null $itemKey
	 *
	 * @return array
	 */
	private function extractIdsFromFields( $ids, $widgetName, $fields, $itemKey = null ) {
		if ( isset( $fields['field'] ) ) {
			// Wrap single field in an array (caused by how XML is parsed).
			if ( isset( $fields['field']['value'] ) ) {
				$fields['field'] = [ $fields['field'] ];
			}

			foreach ( $fields['field'] as $field ) {
				if ( isset( $field['attr']['type'] ) && in_array( $field['attr']['type'], [ Helper::TYPE_POST_IDS, Helper::TYPE_TAXONOMY_IDS ], true ) ) {
					$idEntry = [
						'widget' => $widgetName,
						'field'  => $field['value'],
					];

					if ( isset( $field['attr']['sub-type'] ) ) {
						$idEntry['type'] = $field['attr']['sub-type'];
					}

					if ( $itemKey ) {
						$idEntry['item'] = $itemKey;
					}

					if ( Helper::TYPE_TAXONOMY_IDS === $field['attr']['type'] ) {
						$idEntry['id_type'] = 'term';
					}

					$ids[] = $idEntry;
				}
			}
		}

		return $ids;
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function addFieldsWithIds( $fields ) {
		$ids = get_option( self::OPTION_IDS_IN_WIDGETS );
		if ( $ids ) {
			foreach ( $ids as $id ) {
				$fieldEntry = [
					'field_key' => $id['field'],
				];

				if ( isset( $id['type'] ) ) {
					$fieldEntry['type'] = $id['type'];
				}

				if ( isset( $id['item'] ) ) {
					$fieldEntry['repeater_key'] = $id['item'];
				}

				if ( isset( $id['id_type'] ) ) {
					$fieldEntry['id_type'] = $id['id_type'];
				}

				$widgetKey = $id['widget'];
				if ( ! isset( $fields[ $widgetKey ] ) ) {
					$fields[ $widgetKey ] = [ 'fields' => [] ];
				}

				$fields[ $widgetKey ]['fields'][] = $fieldEntry;
			}
		}

		return $fields;
	}
}
