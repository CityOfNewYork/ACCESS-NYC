<?php

namespace WPML\XMLConfig\RemoteNotices;

use WPML\API\Sanitize;
use WPML\LIB\WP\Hooks as WPHooks;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const NOTICE_GROUP = 'remote-notices';
	const NOTICE_CLASS = 'wpml-remote-notice';

	/** @var \WPML_Notices $adminNotices */
	private $adminNotices;

	/** @var Conditions $conditions */
	private $conditions;

	public function __construct( \WPML_Notices $adminNotices, Conditions $conditions ) {
		$this->adminNotices = $adminNotices;
		$this->conditions   = $conditions;
	}

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_config_array' )->then( spreadArgs( [ $this, 'updateRemoteNotices' ] ) );
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function updateRemoteNotices( $config ) {
		$this->adminNotices->remove_notice_group( self::NOTICE_GROUP );

		$remoteNotices = $config['wpml-config']['notices']['notice'] ?? [];

		if ( $remoteNotices ) {
			foreach ( $this->getNormalizedNotices( $remoteNotices ) as $noticeArgs ) {
				if ( $this->conditions->meetConditions( $noticeArgs['conditions'] ) ) {
					$newNotice = $this->adminNotices->get_new_notice( $noticeArgs['id'], $noticeArgs['content'], self::NOTICE_GROUP );
					$newNotice->set_dismissible( $noticeArgs['dismissible'] );
					$newNotice->set_css_class_types( $noticeArgs['type'] );
					$newNotice->set_css_classes( self::NOTICE_CLASS );
					$newNotice->set_restrict_to_screen_ids( $noticeArgs['screenIds'] );
					$this->adminNotices->add_notice( $newNotice );
				}
			}
		}

		return $config;
	}

	/**
	 * @param array $notices
	 *
	 * @return array<string, array{
	 *     id: string,
	 *     type: string,
	 *     dismissible: bool,
	 *     conditions: array{
	 *         relation: string,
	 *         plugin: string[],
	 *         theme: string[],
	 *         conditions: array[],
	 *     },
	 *     screenIds: string[],
	 *     content: string,
	 * }>
	 */
	private function getNormalizedNotices( $notices ) {
		$normalizedNotices = [];

		foreach ( $notices as $notice ) {
			$normalizedNotice = [
				'id'          => sanitize_key( $notice['attr']['id'] ?? '' ),
				'type'        => sanitize_key( $notice['attr']['type'] ?? 'info' ),
				'dismissible' => (bool) $notice['attr']['dismissible'] ?? true,
				'conditions'  => self::getConditions( $notice['conditions'] ?? [] ),
				'screenIds'   => self::getLocations( $notice['locations']['screenId'] ?? [] ),
				'content'     => wp_kses_post( $notice['content']['value'] ?? null ),
			];

			$normalizedNotice['id'] = $normalizedNotice['id'] ?: md5( serialize( $normalizedNotice ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$normalizedNotices[]    = $normalizedNotice;
		}

		return $normalizedNotices;
	}

	/**
	 * @param array $conditions
	 *
	 * @return array
	 */
	private static function getConditions( $conditions ) {
		$normalizedConditions = [];

		foreach ( [ 'plugin', 'theme' ] as $type ) {
			if ( isset( $conditions[ $type ] ) ) {
				$normalizedConditions[ $type ] = self::getSanitizedValues( self::getNormalizedItem( $conditions[ $type ] ) );
			} else {
				$normalizedConditions[ $type ] = [];
			}
		}

		$relation                         = $conditions['attr']['relation'] ?? null;
		$normalizedConditions['relation'] = in_array( $relation, [ 'AND', 'OR' ], true ) ? $relation : 'AND';

		if ( isset( $conditions['conditions'] ) ) {
			$normalizedConditions['conditions'] = [];
			$subConditions                      = self::getNormalizedItem( $conditions['conditions'] );

			foreach ( $subConditions as $subCondition ) {
				$normalizedConditions['conditions'][] = self::getConditions( $subCondition );
			}
		}

		return $normalizedConditions;
	}

	/**
	 * @param array $locations
	 *
	 * @return array
	 */
	private static function getLocations( $locations ) {
		return self::getSanitizedValues( self::getNormalizedItem( $locations ) );
	}

	/**
	 * @param array $items
	 *
	 * @return string[]
	 */
	private static function getSanitizedValues( $items ) {
		return array_map(
			function( $item ) {
				return Sanitize::stringProp( 'value', $item );
			},
			$items
		);
	}

	/**
	 * @param array $item
	 *
	 * @return array
	 */
	private static function getNormalizedItem( $item ) {
		return isset( $item['value'] ) ? [ $item ] : $item;
	}
}
