<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Repository;

use WPML\StringTranslation\Application\StringCore\Repository\ComponentRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\LoadedTextdomainRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

class ComponentRepository implements ComponentRepositoryInterface {
	const PLUGIN_METADATA_TRANSLATION = 'plugin metadata';

	/** @var LoadedTextdomainRepositoryInterface */
	private $loadedTextdomainRepository;

	/** @var array */
	private $cache = [];

	/** @var string */
	private $activeThemeId;

	public function __construct(
		LoadedTextdomainRepositoryInterface $loadedTextdomainRepository
	) {
		$this->loadedTextdomainRepository = $loadedTextdomainRepository;
		$this->activeThemeId              = $this->getThemeId();
	}

	/**
	 * @return array {id: string, type: int}
	 */
	public function getComponentIdAndType( string $text, string $domain, string $context = null ): array {
		// Some themes like 'Divi' create special integrations for woocommerce plugin template parts in the theme source codes.
		// We should detect those strings as coming from woocommerce plugin too, as they are related to plugin and not theme itself.
		// Also, otherwise we will incorrectly set all next strings coming from woocommerce plugin as coming from theme(because of the cache).
		if ( $domain === 'woocommerce' ) {
			$id   = 'woocommerce';
			$type = StringItem::COMPONENT_TYPE_PLUGIN;

			return [
				$id,
				$type,
			];
		}

		if ( array_key_exists( $domain, $this->cache ) ) {
			return $this->cache[ $domain ];
		}

		// Will detect built-in themes like 'twentytwentyfour'.
		// 'default' value is set up in integration tests.
		if ( $domain === $this->activeThemeId && $this->activeThemeId !== 'default' ) {
			$id         = $this->activeThemeId;
			$type       = StringItem::COMPONENT_TYPE_THEME;
			$addToCache = true;
		} else {
			list( $id, $type, $addToCache ) = $this->getCmpIdAndTypeData( $text, $domain, $context );
		}

		if ( $addToCache ) {
			$this->cache[ $domain ] = [ $id, $type ];
		}

		return [
			$id,
			$type,
		];
	}

	private function getCmpIdAndTypeData( string $text, string $domain, string $context = null ): array {
		list( $id, $type ) = $this->getCmpIdAndType( $text, $domain, $context );
		$addToCache        = ! $this->isPluginMetadataTranslation( $id );

		return [
			$id,
			$type,
			$addToCache,
		];
	}

	private function getCmpIdAndType( string $text, string $domain, string $context = null ): array {
		$id   = 'WordPress';
		$type = StringItem::COMPONENT_TYPE_CORE;

		if ( $domain === 'default' ) {
			return [ $id, $type ];
		}

		if ( in_array( $domain, $this->loadedTextdomainRepository->getThemeDomains() ) ) {
			$id   = $this->getThemeId();
			$type = StringItem::COMPONENT_TYPE_THEME;

			return [ $id, $type ];
		}

		if ( in_array( $domain, $this->loadedTextdomainRepository->getPluginDomains() ) ) {
			$id   = $domain;
			$type = StringItem::COMPONENT_TYPE_PLUGIN;

			return [ $id, $type ];
		}

		$e                     = new \Exception();
		$trace                 = $e->getTrace();
		$index                 = $this->getGettextFnTraceIndex( $trace );
		list( $filepath, $fn ) = $this->getComponentTraceData( $trace, $index );

		if ( $this->isTheme( $filepath, $fn ) ) {
			$id   = $this->getThemeId();
			$type = StringItem::COMPONENT_TYPE_THEME;
		} else if ( $this->isPlugin( $filepath, $fn ) ) {
			$id   = $this->getPluginId( $filepath, $fn );
			$type = StringItem::COMPONENT_TYPE_PLUGIN;
		}

		return [ $id, $type ];
	}

	private function getGettextFnTraceIndex( array $trace ): int {
		$gettextIndex = PHP_INT_MAX;

		for ( $i = 0; $i < count( $trace ); $i++ ) {
			$item = $trace[ $i ];

			if ( ! $item || ! isset( $item['function'] ) ) {
				continue;
			}

			$fn = $item['function'];

 			if (
				'translate' === $fn || 
				'translate_plural' === $fn || 
				'translate_with_gettext_context' === $fn 
			) {
				$gettextIndex = $i;
				break;
			}
		}

		return $gettextIndex;
	}

	private function getComponentTraceData( array $trace, int $index ): array {
		$file = null;
		$fn   = null;

		for ( $i = $index + 1; $i < count( $trace ); $i++ ) {
			$item = $trace[ $i ];

			if ( ! $item || ! isset( $item['file'] ) ) {
				continue;
			}

			if ( $this->isLoadingAndTranslatingPluginMetadataNotFromPluginItself( $item['function'] ) ) {
				$file = self::PLUGIN_METADATA_TRANSLATION;
				$fn   = $item['function'];
				break;
			}

			if ( isset( $item['function'] ) ) {
				$fn = $item['function'];
			}

			if ( $this->isPlugin( $item['file'], $fn ) ) {
				$file = $item['file'];
				break;
			}

			if ( $this->isTheme( $item['file'], $fn ) ) {
				$file = $item['file'];
				break;
			}
		}

		return [ $file, $fn ];
	}

	// This method checks if we have detected that we are currently checking the case when plugin metadata is translated.
	// We set $filepath to special self::PLUGIN_METADATA_TRANSLATION key in that case.
	// That happens after initial check in isLoadingAndTranslatingPluginMetadataNotFromPluginItself function.
	// Please check comment to that function where all edge case is explained.
	private function isPluginMetadataTranslation( string $filepath ): bool {
		return $filepath === self::PLUGIN_METADATA_TRANSLATION;
	}

	private function isPlugin( string $filepath = null, string $fn = null ): bool {
		if ( is_null( $filepath ) ) {
			return false;
		}

		if ( $this->isPluginMetadataTranslation( $filepath ) ) {
			return true;
		}

		$isPluginFnCallFromTests = $fn === 'callTranslateFromPlugin';

		return ( $isPluginFnCallFromTests || strpos( $filepath, 'wp-content/plugins' ) !== false );
	}

	private function isTheme( string $filepath = null, string $fn = null ): bool {
		if ( is_null( $filepath ) ) {
			return false;
		}

		return (
			strpos( $filepath, 'wp-content/themes' ) !== false ||
			$fn === '_register_theme_block_patterns' ||
			$fn === 'register_block_core_template_part'
		);
	}

	// Call with plugin textdomain can happen when we are loading plugin metadata from other plugin.
	// In such case we cannot determine real plugin name by reading the trace, because other plugin path will be used instead.
	// Like for 'ntechdev-devtools' textdomain and plugin we expect find in trace '.../wp-content/plugins/ntechdev-devtools/...' but it will not exist.
	// Instead we will have just a call for WP Core -> get_plugin_data -> _get_plugin_data_markup_translate -> translate calls.
	// In ST we will have calls from sitepress/.../wpml-lib-dependencies/.../class-wpml-dependencies.php -> add_installed_plugin -> get_plugin_data(...).
	private function isLoadingAndTranslatingPluginMetadataNotFromPluginItself( string $function = null ): bool {
		return ( is_null( $function ) ) ? false : ( $function === '_get_plugin_data_markup_translate' );
	}

	private function getPluginId( string $filepath, string $fn = null ): string {
		if ( $this->isPluginMetadataTranslation( $filepath ) ) {
			return $filepath;
		}

		$isPluginFnCallFromTests = $fn === 'callTranslateFromPlugin';
		if ( $isPluginFnCallFromTests ) {
			return 'wpml-string-translation';
		}

		$parts = explode( 'wp-content/plugins/', $filepath );
		$parts = explode( '/', $parts[1] );

		return $parts[0];
	}

	private function getThemeId(): string {
		return $this->getThemeDirectory();
	}

	private function getThemeDirectory(): string {
		$theme    = get_template_directory();
		$parts    = explode( '/', $theme );
		$themeDir = $parts[count( $parts ) - 1];

		return $themeDir;
	}
}