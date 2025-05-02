<?php

namespace WPML\PB\TranslationJob;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\Jobs\ExtraData;

use function WPML\FP\pipe;

class Groups {

	const PATH_SEPARATOR  = '/';
	const LABEL_SEPARATOR = ': ';
	const EXTRADATA_PATH  = [ 'attributes', 'extradata' ];

	const TOP_LEVEL_WIDGETS_ID          = 'Widgets-0';
	const TOP_LEVEL_WIDGETS_DESCRIPTION = 'Widgets';
	const WIDGET_GROUP_NAME             = 'Widget';

	/**
	 * @param array $elements
	 *
	 * @return array
	 */
	public static function flattenHierarchy( $elements ) {
		$groups = self::extractGroupIds( $elements );
		if ( ! $groups ) {
			return $elements;
		}

		$flattenGroup = function( $element ) use ( $groups ) {
			if ( ! isset( $element['attributes']['extradata'] ) ) {
				return $element;
			}

			$extradata = ExtraData::decode( $element['attributes']['extradata'] );

			if ( ! isset( $extradata['group_id'], $extradata['group'] ) ) {
				return $element;
			}

			if ( ! empty( $extradata['group_id'] ) && self::isPBGroup( $extradata['group_id'] ) ) {
				if ( self::isBlockElementInGroup( $extradata['group_id'] ) ) {
					list( $extradata['group_id'], $extradata['group'] ) = self::getBlockGroup( $extradata['group_id'] );
				} elseif ( self::isSingleElementInGroup( $extradata['group_id'], $groups ) ) {
					$extradata['group_id'] = self::removeLastCrumb( $extradata['group_id'] );
					$extradata['group']    = self::removeLastCrumb( $extradata['group'] );
				} else {
					$extradata['group_id'] = self::removeAllExceptLastCrumb( $extradata['group_id'] );
					$extradata['group']    = self::removeAllExceptLastCrumb( $extradata['group'] );
				}
				$element['attributes']['extradata'] = ExtraData::encode( $extradata );
			}

			return $element;
		};

		return wpml_collect( $elements )
			->map( $flattenGroup )
			->all();
	}

	/**
	 * @param string $groupId
	 *
	 * @return bool
	 */
	private static function isPBGroup( $groupId ) {
		return (bool) Str::startsWith( \WPML_TM_Page_Builders::TOP_LEVEL_GROUP_ID, $groupId );
	}

	/**
	 * @param array $elements
	 *
	 * @return array
	 */
	public static function extractGroupIds( $elements ) {
		return pipe(
			Lst::pluck( 'attributes' ),
			Lst::pluck( 'extradata' ),
			Fns::map( ExtraData::class . '::decode' ),
			Lst::pluck( 'group_id' )
		)( $elements );
	}

	/**
	 * @param string $needle
	 * @param array  $haystack
	 *
	 * @return bool
	 */
	public static function isSingleElementInGroup( $needle, $haystack ) {
		$count = wpml_collect( $haystack )
			->map( 'trailingslashit' )
			->filter( Str::startsWith( trailingslashit( $needle ) ) )
			->count();

		return 1 === $count;
	}

	/**
	 * @param string $groupId
	 *
	 * @return bool
	 */
	public static function isBlockElementInGroup( $groupId ) {
		return (bool) preg_match( '/\/block-\d+\//', $groupId );
	}

	/**
	 * @param string $group
	 *
	 * @return string
	 */
	public static function removeLastCrumb( $group ) {
		$crumbs = explode( self::PATH_SEPARATOR, $group );

		// Except if there is only 1 (ie: Main Content).
		if ( count( $crumbs ) > 1 ) {
			array_pop( $crumbs );
		}

		return self::removeAllExceptLastCrumb( implode( self::PATH_SEPARATOR, $crumbs ) );
	}

	/**
	 * @param string $group
	 *
	 * @return string
	 */
	public static function removeAllExceptLastCrumb( $group ) {
		$crumbs = explode( self::PATH_SEPARATOR, $group );

		return count( $crumbs ) > 2
			? reset( $crumbs ) . self::PATH_SEPARATOR . end( $crumbs )
			: $group;
	}

	/**
	 * @param string $groupIds
	 *
	 * @return string[]
	 */
	private static function getBlockGroup( string $groupIds ) : array {
		$blockId = null;

		if ( preg_match( '/block-\d+/', $groupIds, $matches ) ) {
			$blockId = $matches[0];
		}

		if ( null === $blockId ) {
			return [
				self::TOP_LEVEL_WIDGETS_ID . '/' . self::WIDGET_GROUP_NAME,
				self::TOP_LEVEL_WIDGETS_DESCRIPTION . '/' . self::WIDGET_GROUP_NAME,
			];
		}

		$sidebarsWidgets = get_option( 'sidebars_widgets' );
		$foundSidebarId  = self::WIDGET_GROUP_NAME;

		if ( is_array( $sidebarsWidgets ) ) {
			foreach ( $sidebarsWidgets as $sidebarId => $widgets ) {
				if ( is_array( $widgets ) && in_array( $blockId, $widgets, true ) ) {
					$foundSidebarId = $sidebarId;
					break;
				}
			}
		}

		return [
			self::TOP_LEVEL_WIDGETS_ID . '/' . $foundSidebarId,
			self::TOP_LEVEL_WIDGETS_DESCRIPTION . '/' . self::getSidebarName( $foundSidebarId ),
		];
	}

	/**
	 * @param string $sidebarId
	 *
	 * @return string
	 */
	private static function getSidebarName( $sidebarId ) {
		global $wp_registered_sidebars;

		return isset( $wp_registered_sidebars[ $sidebarId ] )
			? $wp_registered_sidebars[ $sidebarId ]['name']
			: Labels::convertToHuman( $sidebarId, true );
	}

	/**
	 * @param string $title
	 *
	 * @return bool
	 */
	public static function isGroupLabel( $title ) {
		return false !== strpos( $title, self::LABEL_SEPARATOR );
	}

	/**
	 * @param string[] $groups
	 * @param string   $title
	 * @param int|null $sequence
	 *
	 * @return string
	 */
	public static function buildGroupLabel( $groups, $title, $sequence = null ) {
		if ( ! $groups ) {
			return $title;
		}

		return implode( self::PATH_SEPARATOR, $groups )
			. ( null === $sequence ? '' : '-' . $sequence )
			. self::LABEL_SEPARATOR . $title;
	}

	/**
	 * @param string $string
	 *
	 * @return array{string[], string}
	 */
	public static function parseGroupLabel( $string ) {
		list( $groups, $title ) = explode( self::LABEL_SEPARATOR, $string, 2 );

		return [
			explode( self::PATH_SEPARATOR, $groups ),
			$title,
		];
	}

	/**
	 * @param string $groupLabel
	 * @param string $imageId
	 *
	 * @return string
	 */
	public static function appendImageIdToGroupLabel( $groupLabel, $imageId ) {
		list( $group, $title ) = explode( self::LABEL_SEPARATOR, $groupLabel, 2 );

		return $group . '-' . $imageId . self::LABEL_SEPARATOR . $title;
	}

}
