<?php

namespace ACFML\Upgrade\Commands;

use ACFML\Options;
use ACFML\FieldGroup\FieldNamePatterns;
use ACFML\Strings\Factory;
use ACFML\Strings\Package;
use ACFML\Strings\Translator;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Cache;

class MigrateToV2_2 implements Command {

	const KEY = 'migrate-to-v2_2';

	const STATUS_DONE = 'done';

	// Our integration runs at acf/init:1 and objects are registered at acf/init:5:
	// we need to include our callbacks in the middle.
	const INIT_PRIORITY = 2;

	public static function run() {
		Hooks::onAction( 'acf/init', self::INIT_PRIORITY )
			->then( function() {
				if ( self::isStActivated() && null === Options::get( self::KEY ) ) {
					$isLocalEnabled = acf_is_local_enabled();
					if ( $isLocalEnabled ) {
						acf_disable_local();
					}
					wpml_collect( acf_get_field_groups() )
						->filter( function( $fieldGroup ) {
							return ! Relation::propEq( 'ID', 0, $fieldGroup );
						} )
					->map( function( $fieldGroup ) {
						$fieldGroupId  = Obj::prop( 'ID', $fieldGroup );
						$fieldGroupKey = Obj::prop( 'key', $fieldGroup );
						$package       = Factory::createWpmlPackage( [
							'kind'      => Package::FIELD_GROUP_PACKAGE_KIND,
							'kind_slug' => Package::FIELD_GROUP_PACKAGE_KIND_SLUG,
							'name'      => $fieldGroupId,
						] );

						$packageId = $package->get_package_id();
						if ( ! $packageId ) {
							return;
						}

						// Update the package record.
						MigrateToV2_2::updatePackage( $package, $fieldGroupKey );
						// Update the package strings.
						MigrateToV2_2::updatePackageStrings( $packageId, $fieldGroupKey, $fieldGroupId );
						// Update name patterns.
						MigrateToV2_2::updateNamePatterns( $fieldGroup );
						// Refresh the package cache.
						$package->flush_cache();

						/** @see \WPML\ST\TranslationFile\Domains::MO_DOMAINS_CACHE_GROUP */
						Cache::flushGroup( 'WPML_ST_CACHE' );
					} );

					do_action( 'wpml_st_string_updated' );
					if ( $isLocalEnabled ) {
						acf_enable_local();
					}
					Options::set( self::KEY, self::STATUS_DONE );
				}
			} );
	}

	/**
	 * @return bool
	 */
	public static function isStActivated() {
		return defined( 'WPML_ST_VERSION' );
	}

	/**
	 * @param array<int,array>  $fields
	 * @param array<int,string> $idsToKeys
	 *
	 * @return array<int,string>
	 */
	public static function fieldIdsToKeys( $fields, $idsToKeys = [] ) {
		foreach ( $fields as $field ) {
			$idsToKeys[ $field['ID'] ] = $field['key'];

			if ( isset( $field['sub_fields'] ) ) {
				$idsToKeys = self::fieldIdsToKeys( $field['sub_fields'], $idsToKeys );
			}

			if ( isset( $field['layouts'] ) ) {
				foreach ( $field['layouts'] as &$layout ) {
					if ( isset( $layout['sub_fields'] ) ) {
						$idsToKeys = self::fieldIdsToKeys( $layout['sub_fields'], $idsToKeys );
					}
				}
			}
		}

		return $idsToKeys;
	}

	/**
	 * @param \WPML_Package $package
	 * @param string        $fieldGroupKey
	 */
	private static function updatePackage( $package, $fieldGroupKey ) {
		$package->name  = $fieldGroupKey;
		$package->title = sprintf( Package::FIELD_GROUP_PACKAGE_TITLE, $fieldGroupKey );
		$package->update_package_record();
	}

	/**
	 * @param int    $packageId
	 * @param string $fieldGroupKey
	 * @param int    $fieldGroupId
	 */
	private static function updatePackageStrings( $packageId, $fieldGroupKey, $fieldGroupId ) {
		global $wpdb;
		// phpcs:disable WordPress.VIP.DirectDatabaseQuery.DirectQuery,WordPress.VIP.DirectDatabaseQuery.NoCaching
		$packageStrings = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT id, name
				FROM {$wpdb->prefix}icl_strings
				WHERE string_package_id = %d
				",
				$packageId
			)
		);
		// phpcs:enable
		if ( empty( $packageStrings ) ) {
			return;
		}

		// Update the context on all strings in the package at once.
		$newContext = 'acf-field-group-' . $fieldGroupKey;
		// phpcs:disable WordPress.VIP.DirectDatabaseQuery.DirectQuery,WordPress.VIP.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$wpdb->prefix}icl_strings
				SET context = %s
				WHERE string_package_id = %d
				LIMIT %d
				",
				$newContext,
				$packageId,
				count( $packageStrings )
			)
		);
		// phpcs:enable

		// Update the name on the strings in the package.
		$fieldsInFieldGroup = acf_get_fields( $fieldGroupId );
		if ( empty( $fieldsInFieldGroup ) ) {
			return;
		}

		$fiedlIdsToKeys  = self::fieldIdsToKeys( $fieldsInFieldGroup );
		$pattern         = '/^(group-|field-)([0-9]+)(-.*?)$/';
		$entriesToUpdate = wpml_collect( $packageStrings )
			->map( function( $string ) use ( $pattern, $fieldGroupKey, $fiedlIdsToKeys ) {
				/* phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.Found */
				$hasMatch = preg_match( $pattern, $string->name, $matches );
				/* phpcs:enable */
				if ( ! (bool) $hasMatch || count( $matches ) < 4 ) {
					return null;
				}

				/* phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.Found */
				$itemKey = ( 'group-' === $matches[1] )
					? $fieldGroupKey
					: Obj::prop( $matches[2], $fiedlIdsToKeys );
				/* phpcs:enable */

				return (bool) $itemKey
					? [
						'id'   => $string->id,
						'name' => $matches[1] . $itemKey . $matches[3],
					]
					: null;
			} )
			->filter()
			->toArray();

		if ( empty( $entriesToUpdate ) ) {
			return;
		}

		$buildStringRow = function( $entryToUpdate ) use ( $wpdb ) {
			return $wpdb->prepare(
				'( %d, "", %s, "", "", "", 0, "", "", 0, 0 )',
				$entryToUpdate['id'],
				$entryToUpdate['name']
			);
		};

		$updateStringNameQuery = "
			INSERT IGNORE INTO {$wpdb->prefix}icl_strings "
			. '(`id`, `language`, `name`, `value`, `wrap_tag`, `type`, `status`, `gettext_context`, `translation_priority`, `string_type`, `component_type`) VALUES '
			. implode( ',', array_map( $buildStringRow, $entriesToUpdate ) )
			. ' ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)';

		$wpdb->suppress_errors = true;
		// phpcs:disable WordPress.VIP.DirectDatabaseQuery.DirectQuery,WordPress.VIP.DirectDatabaseQuery.NoCaching,WordPress.WP.PreparedSQL.NotPrepared
		$wpdb->query( $updateStringNameQuery );
		// phpcs:enable
		$wpdb->suppress_errors = false;
	}

	/**
	 * @param array<string,mixed> $fieldGroup
	 */
	private static function updateNamePatterns( $fieldGroup ) {
		$fieldNamePatterns = new FieldNamePatterns();
		$fieldGroupId      = Obj::prop( 'ID', $fieldGroup );
		$fieldNamePatterns->updateGroup( $fieldGroupId, [] );
		$fieldNamePatterns->updateFieldNamePatterns( $fieldGroup );
	}

}
